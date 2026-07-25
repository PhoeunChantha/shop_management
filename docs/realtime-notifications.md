# Real-time notifications (Reverb + Supervisor)

Customer notifications are Laravel **database notifications** that also **broadcast**
over websockets via **Laravel Reverb**, so the storefront updates live.

## How it works

- `App\Notifications\OrderStatusUpdated` sends on the `database` + `broadcast`
  channels. It is dispatched from `OrderService::updateFulfilment` whenever an
  order's status actually changes (`$order->user?->notify(...)`).
- `database` → row in the `notifications` table (shown at **Account → Notifications**,
  read via `AccountService::notifications()` / `unreadNotifications()`).
- `broadcast` → private channel `App.Models.User.{id}` (authorised in
  `routes/channels.php`). The storefront layout loads Laravel Echo (CDN) and, for
  logged-in customers, subscribes and shows a toast + bumps any `[data-notif-count]`
  badge. Echo failures are swallowed — the site works without websockets.

## Configuration

`.env` (already scaffolded by `php artisan reverb:install`):

```
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
```

## Running it

**Local dev (Windows / any):**

```bash
php artisan reverb:start        # websocket server on REVERB_PORT (8080)
php artisan queue:work          # required: OrderStatusUpdated is ShouldQueue
php artisan serve               # app (use --port=8000 to match Google OAuth)
```

Then log in as a customer and change one of their orders' status in the admin —
a toast should appear live.

**Production (Linux, Supervisor)** — see `docs/supervisor/` configs. Point
Supervisor at `reverb:start` (and `queue:work` if you queue broadcasts), then:

```bash
sudo cp docs/supervisor/*.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start all
```

Put a TLS-terminating proxy (nginx) in front of Reverb for `wss://` and set
`REVERB_SCHEME=https`, `REVERB_PORT=443` accordingly.

## Notes

- `OrderStatusUpdated` is `ShouldQueue`, so both the DB write and the broadcast run
  in a **queue worker** (dispatched after the order transaction commits). A Reverb
  outage only fails/retries a background job — it never breaks the admin's order
  update. Supervisor therefore keeps **both** `reverb:start` and `queue:work` alive.
- Tests set `BROADCAST_CONNECTION=null` and `QUEUE_CONNECTION=sync` (see
  `phpunit.xml`), so notifications run inline and no server is needed for the suite.
