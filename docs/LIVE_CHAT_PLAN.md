# Live Chat (Customer ↔ Admin) — Implementation Plan

Real-time support chat between a signed-in storefront customer and the admin team,
pushed over **Laravel Reverb** (websockets). This document is the build plan; it is
written *before* code so every piece lands in the house pattern
(`docs/ADMIN-CRUD-GUIDELINE.md`, `CLAUDE.md`).

---

## 1. Goals & scope

| In scope | Out of scope (future) |
|---|---|
| One support **conversation per customer** (reused; closed threads reopen on a new message) | Multiple parallel threads per customer |
| Customer sends/receives messages live from a **floating chat widget** (every storefront page) **and** a full **Account → Messages** page | File / image attachments |
| Admin **inbox** (`admin/chats`): list of conversations + thread pane, reply live, close / reopen, assign to a staff member, unread counts | Canned replies, bots, SLA timers |
| **Reverb** broadcasting of new messages, read receipts, status changes; **typing indicator** via client whispers | Guest (anonymous) chat — the launcher asks guests to sign in |
| Live unread badges: storefront header / bottom-nav / account sidebar, admin header + sidebar | Push/email notification on new message |
| Graceful fallback: if the websocket is unavailable the UI **polls** every 8 s | |

Already in the repo and reused as-is: `laravel/reverb` (installed, `.env` configured,
`BROADCAST_CONNECTION=reverb`), `routes/channels.php`, storefront Echo bootstrap (CDN
`laravel-echo` + `pusher-js` in `frontend.blade.php`), `docs/realtime-notifications.md`.

---

## 2. Data model

### `conversations`
| column | type | notes |
|---|---|---|
| id | bigint PK | |
| user_id | FK users, cascadeOnDelete | the customer (unique → one conversation per customer) |
| assigned_to | FK users nullable, nullOnDelete | staff member handling it |
| subject | string nullable | optional first-message topic |
| status | string (`open`/`closed`), default `open`, indexed | `ConversationStatus` enum |
| last_message_at | timestamp nullable, indexed | inbox ordering |
| last_message_preview | string(160) nullable | inbox list without a join |
| customer_unread | unsigned int default 0 | messages from staff the customer hasn't read |
| admin_unread | unsigned int default 0 | messages from the customer staff haven't read |
| closed_at | timestamp nullable | |
| timestamps | | |

### `chat_messages`
| column | type | notes |
|---|---|---|
| id | bigint PK | |
| conversation_id | FK conversations, cascadeOnDelete | index `(conversation_id, id)` |
| sender_id | FK users, cascadeOnDelete | |
| sender_role | string (`customer`/`staff`) | `ChatSenderRole` enum; avoids role lookups when rendering |
| body | text | max 2000 chars (validated) |
| read_at | timestamp nullable | set when the *other* side opens the thread |
| timestamps | | |

### Permissions
New subject **`chats`** → `view chats`, `create chats`, `edit chats`, `delete chats`
(admin role gets all). Added to `RolePermissionSeeder::$subjects` **and** a
migration `add_chat_permissions` (same pattern as
`2026_08_16_000001_add_report_page_permissions.php`) so existing installs pick it
up on `migrate` without reseeding.

---

## 3. Backend pieces (files to create)

```
app/Enums/ConversationStatus.php            open | closed  (label(), options(), badge tone)
app/Enums/ChatSenderRole.php                customer | staff
app/Models/Conversation.php                 fillable, casts, customer()/assignee()/messages()/latestMessage(),
                                            scopeSearch(), scopeStatus(), scopeOpen(), helpers isOpen()
app/Models/ChatMessage.php                  fillable, casts, conversation()/sender(), isFromCustomer()
database/factories/ConversationFactory.php
database/factories/ChatMessageFactory.php
database/migrations/2026_08_23_000001_create_conversations_table.php
database/migrations/2026_08_23_000002_create_chat_messages_table.php
database/migrations/2026_08_23_000003_add_chat_permissions.php
app/Policies/ConversationPolicy.php         extends AdminRolePolicy, $subject = 'chats'
app/Services/Admin/ChatService.php          SHARED core (storefront imports it from Admin, like SettingService):
                                            conversationFor(User), sendMessage(Conversation, User, body),
                                            markRead(Conversation, viewer), close()/reopen()/assign(),
                                            paginateInbox(filters, perPage), messagesPage(conv, beforeId),
                                            unreadForCustomer(User), unreadForAdmin(), serializeMessage()
app/Events/ChatMessageSent.php              ShouldBroadcastNow → chat.conversation.{id} + admin.chat
app/Events/ConversationUpdated.php          ShouldBroadcastNow (status/assignee/read) → same channels
app/Http/Requests/Chat/StoreChatMessageRequest.php   body: required|string|max:2000 (trimmed)
app/Http/Controllers/Frontend/ChatController.php     index (account page), messages (JSON), store (JSON), read (JSON), state (JSON unread)
app/Http/Controllers/Backend/ChatController.php      index (inbox), show (JSON thread), messages (JSON older), store, read, updateStatus, assign
routes/web.php                               frontend: account/messages…  admin: chats…   (throttle on store)
routes/channels.php                          chat.conversation.{conversation}, admin.chat
```

**Why `ShouldBroadcastNow`** — chat must feel instant and must not depend on a queue
worker being alive; the HTTP request that stores the message pushes it to Reverb
synchronously. A Reverb outage is caught (`try/catch` in the service) so the message
still saves and the UI still works via polling.

**Authorization**
- Storefront: routes under `account` (`auth`); every action resolves the conversation
  via `ChatService::conversationFor($request->user())` — a customer can only ever touch
  their own thread (no id in the URL).
- Admin: `$this->authorize('viewAny'|'update', Conversation::class)` as the first line
  of each action (Policy → `view chats` / `edit chats`).
- Channels: `chat.conversation.{id}` → owner **or** `can('view chats')`;
  `admin.chat` → `can('view chats')`.

**Routes**
```
GET   /account/messages                  frontend.account.messages          (page)
GET   /account/messages/feed             frontend.account.messages.feed     (JSON: messages, before_id cursor)
POST  /account/messages                  frontend.account.messages.store    (JSON, throttle:30,1)
POST  /account/messages/read             frontend.account.messages.read     (JSON)
GET   /account/messages/state            frontend.account.messages.state    (JSON: unread, status) — polling fallback

GET   /admin/chats                       admin.chats.index                  (inbox page; ?conversation=ID opens a thread)
GET   /admin/chats/{conversation}        admin.chats.show                   (JSON: conversation + latest 30 messages)
GET   /admin/chats/{conversation}/messages admin.chats.messages             (JSON: older page, before_id)
POST  /admin/chats/{conversation}/messages admin.chats.store                (JSON, throttle:60,1)
POST  /admin/chats/{conversation}/read   admin.chats.read
PATCH /admin/chats/{conversation}/status admin.chats.status                 (open|closed)
PATCH /admin/chats/{conversation}/assign admin.chats.assign                 (assigned_to nullable)
GET   /admin/chats/unread                admin.chats.unread                 (JSON count — header badge fallback)
```

**Broadcast payloads**
```jsonc
// ChatMessageSent  (event name ".chat.message")
{ "conversation_id": 7, "message": { "id": 91, "body": "…", "sender_role": "customer",
  "sender": { "id": 3, "name": "Sok", "avatar": "…|null" }, "created_at": "2026-08-23T10:00:00Z" },
  "conversation": { "id": 7, "status": "open", "last_message_preview": "…", "admin_unread": 2, "customer_unread": 0, "customer": {…}, "assignee": {…|null} } }

// ConversationUpdated (event name ".chat.conversation")
{ "conversation": { …same summary… }, "reason": "status|assigned|read" }
```
Typing: `channel.whisper('typing', { role, name })` — client event, no server code.

---

## 4. Storefront UI (customer)

Design direction — same **editorial / warm-bone** system as the rest of the storefront
(`--bg #F4F1EA`, ink `#100E0B`, clay accent `#7A6446`, Kantumruy Pro, sharp `--r-btn`
radii, hairline borders). The chat reads like a **concierge desk**: a bone-paper
panel with an ink header kicker ("Concierge · usually replies in minutes"), ink
bubbles for the customer (right), paper bubbles with a small staff monogram for the
team (left), day dividers in small caps, a thin clay "unread" rule, a composer with
a single ink send button. Panel rises with the site's `--ease-out` entrance; dots
pulse for typing; reduced-motion respected.

Files:
```
resources/views/components/frontend/chat-widget.blade.php   floating launcher + panel (auth only; guests → sign-in CTA) — included in frontend layout
resources/views/frontend/account/messages.blade.php          Account → Messages full page (same thread DOM contract)
resources/views/components/frontend/account-sidebar.blade.php  + "Messages" item with unread badge
resources/views/components/frontend/header.blade.php         + chat icon w/ [data-chat-count] (desktop)
resources/views/components/frontend/mobile-bottom-nav.blade.php  "Help" → keeps FAQ; badge added to Account icon? (no) — widget launcher covers mobile
resources/views/frontend/partials/icon.blade.php             + 'chat' icon path
resources/views/frontend/layouts/frontend.blade.php          expose window.UT_CHAT {urls, conversationId, unread}, load chat.js after Echo
public/assets/frontend/js/chat.js                            vanilla JS: UTChat.mount(rootEl) — render, send, read, infinite "load earlier", Echo subscribe or poll fallback, typing whisper, badge sync
public/assets/frontend/css/style.css                         .ut-chat-* styles (appended section)
```
JS contract (no build step — storefront assets are plain files under `public/assets`):
- `window.UT_CHAT = { id, status, unread, urls: {feed, store, read, state}, me: {id, name} }`
- `chat.js` subscribes to `Echo.private('chat.conversation.'+id)` → `.listen('.chat.message')`,
  `.listen('.chat.conversation')`, `.listenForWhisper('typing')`; if `window.Echo` is
  absent it polls `state` and `feed?after_id=` every 8 s.
- Widget and account page share one thread component; the page simply mounts it inline.

---

## 5. Admin UI

Matches the admin design system (`admin-page`, `premium-button`, `page-section-header`,
dark mode via `.dark`). New sidebar entry **Sales → Live Chat** (`fa-comments`) and a
header chat icon with a live unread dot next to the bell.

```
resources/views/admin/chats/index.blade.php    two-pane inbox: left list (search, status filter, assigned-to-me, unread chips, per-conversation preview/unread/time),
                                               right thread (customer card, status + assign controls, messages, composer, typing line). Alpine component `adminChatInbox`.
resources/views/admin/layouts/sidebar.blade.php  + Live Chat link (permission 'view chats'), Sales group active routes
resources/views/admin/layouts/header.blade.php   + chat icon with [data-admin-chat-count]
resources/js/echo.js                           Echo bootstrap for the admin bundle (npm laravel-echo + pusher-js), config from <meta name="reverb-*">
resources/js/app.js                            import './echo'; global admin listener on admin.chat → bump badge + toastr
resources/css/app.css                          .admin-chat-* styles (requires `npm run build`)
```
Admin Echo is bundled (Vite) because the admin already ships one bundle; the
storefront keeps its CDN loader. If `npm install` is impossible, fallback is the same
CDN pair in `admin/layouts/app.blade.php`.

---

## 6. Translations
Add every new UI string to `resources/lang/en.json` and `resources/lang/km.json`
(Khmer translations provided). Strings go through `__()` in Blade and are passed to
JS via `data-*` / a small `i18n` object in `window.UT_CHAT` / the Alpine component.

---

## 7. Tests (Pest, `tests/Feature/`)
```
ChatCustomerTest.php   customer page loads; store creates conversation+message, bumps admin_unread, fires ChatMessageSent (Event::fake);
                       body validation; guest redirected; feed only returns own messages; read resets customer_unread
ChatAdminTest.php      admin with 'view chats' sees inbox + JSON thread; without permission → 403; reply stores staff message & bumps customer_unread & reopens closed thread;
                       close/reopen/assign; unread endpoint
ChatChannelAuthTest.php POST /broadcasting/auth: owner OK, other customer 403, admin OK; admin.chat only for 'view chats'
```
`phpunit.xml` already forces `BROADCAST_CONNECTION=null`, `QUEUE_CONNECTION=sync`.

---

## 8. Build order (commit-sized steps)
1. Enums, migrations, models, factories, permissions migration + seeder subject, policy.
2. `ChatService` + events + channels.
3. Frontend controller + routes + JSON endpoints.
4. Admin controller + routes.
5. Storefront UI: icon, layout bootstrap, `chat.js`, CSS, widget, account page, badges.
6. Admin UI: echo.js, inbox page, sidebar/header, CSS, `npm run build`.
7. Translations (en/km).
8. Tests → `php artisan test --filter=Chat`, `./vendor/bin/pint`.
9. Docs: append a "Live chat" section to `docs/realtime-notifications.md`; update `CLAUDE.md` service list.

## 9. Running it locally
```bash
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder && php artisan permission:cache-reset
php artisan reverb:start          # websockets (port 8080)
composer dev                      # serve + queue + vite
```
Open the storefront as a customer → chat bubble bottom-right; open `/admin/chats` as
admin in another browser → messages appear live in both directions.

---

## 10. Status (2026-08-23) - implemented

All build-order steps 1-9 are done and green (`php artisan test`: 223 passed, including
`ChatCustomerTest`, `ChatAdminTest`, `ChatChannelAuthTest`).

Notable implementation details that differ from / refine the plan above:

- The customer's `conversations` row is created **lazily** (first widget open or first
  message), so curious visitors don't flood the admin inbox. `chat.js` subscribes to the
  Reverb channel as soon as it learns the id (`adoptConversation`).
- Admin Echo is bundled (`resources/js/echo.js`, npm `laravel-echo@2` + `pusher-js@8`);
  the admin layout emits `<meta name="reverb-*">` plus `<meta name="admin-chat">` so the
  global header badge + toast work on every admin page (`window.adminChatFeed`).
- The admin inbox list is server-rendered inside `[data-ajax-page]` so search / chips /
  pagination swap only the list; the Alpine thread pane (`adminChatInbox`) stays alive
  and patches list rows in place from live events.
- The storefront layout must use the **single-line** `@php(...)` directive for the chat
  variables (a multi-line php block there pairs with the inline favicon directive and
  breaks compilation) - see the comment in `frontend.blade.php`.
- Throttles: customer `30/min`, staff `60/min` on message POSTs.

## 11. Addendum (2026-08-23) - "Ask about this product"

- `chat_messages.product_id` (nullable FK, `nullOnDelete`); `ChatMessage::product()`;
  `StoreChatMessageRequest` accepts `product_id` (`exists:products,id`).
- `ChatService::sendMessage(..., ?int $productId)`; `serializeMessage()` adds a
  `product` snapshot (`serializeProduct()`: id, name, image, price, price_label, url,
  admin_url) to REST + broadcast payloads.
- Storefront: every product card has a chat icon (`.ut-pcard-ask`) and the PDP has a
  wide "Questions about this piece?" row; both carry `data-chat-product="{json}"`.
  `chat.js` (`askAbout`) attaches the product as a composer chip, opens the widget,
  prefills a prompt, sends `product_id`, and renders a product card inside the bubble.
  Guests are sent to sign in (fallback script in `chat-widget.blade.php`).
- Admin inbox renders the product card with "View in store" / "Edit product" links.
- Back-to-top button (`<x-frontend.back-to-top>`) added to the storefront layout.

## 12. Addendum (2026-08-23) - Settings tab + alert sounds

- New `SettingGroup::Chat` ("Live Chat" tab, `fa-comments`) with fields: `chat_enabled`,
  `chat_guest_launcher`, `chat_ask_product_enabled`, `chat_header_title`, `chat_reply_note`,
  `chat_welcome_title`, `chat_welcome_text`, `chat_product_prefill`, `chat_sound_admin`,
  `chat_sound_customer` (chime|pop|ding|off), `chat_sound_volume` (25/50/75/100).
  Read via `SettingService::chat()` / `askProductEnabled()`. Settings page supports
  `?tab=chat` deep links.
- Storefront: widget/launcher/product buttons honour the settings; `UT_CHAT.sound`
  drives a Web Audio synth in `chat.js` (plays when staff reply).
- Admin: `<meta name="admin-chat-sound|volume">`; `resources/js/echo.js` plays the alert
  on every incoming customer message (sockets or polling), with a per-browser mute
  (`localStorage` `admin-chat-muted`) toggled from the inbox header (bell icon), plus a
  shortcut to the settings tab.
- Sounds are synthesised (no audio files). Browsers require one user gesture before
  audio can play; the first click/keypress on the page unlocks it.
