# Deployment (GitHub Actions → Linux VPS)

| Branch | Workflow | Result |
|---|---|---|
| any other branch, any PR | `tests.yml` | Pint + Pest only |
| `dev` | `deploy.yml` | tests → deploy to **staging** |
| `main` | `deploy.yml` | tests → deploy to **production** |

Flow: work on a feature branch → PR into `dev` → check staging → PR `dev` into `main` → live.
A deploy never starts when tests fail. Re-run a deploy by hand from **Actions → Deploy → Run workflow**.

## What a deploy does

`deploy.yml` SSHes into the server, `cd`s to `DEPLOY_PATH`, and runs `scripts/deploy.sh <sha>`
from the commit being deployed:

1. `php artisan down` (site shows maintenance page; always brought back `up`, even on failure)
2. `git reset --hard <sha>` — the server checkout is deploy-only; `.env`, `public/uploads`, `storage` are untracked and kept
3. `composer install --no-dev --optimize-autoloader`
4. `npm ci && npm run build`
5. `php artisan migrate --force`
6. `php artisan optimize:clear && php artisan optimize`
7. `php artisan queue:restart` and `php artisan reverb:restart`

## One-time server setup (do it for staging and production)

Ubuntu VPS with PHP 8.4 (+ `mbstring intl gd zip bcmath exif mysql`), Composer 2, Node 22, MySQL, Nginx, Supervisor.

```bash
# 1. Deploy user's SSH key for GitHub Actions (on your PC, NOT on the server)
ssh-keygen -t ed25519 -C "github-actions-deploy" -f deploy_key -N ""
#    put deploy_key.pub into ~/.ssh/authorized_keys of the deploy user on the server
#    deploy_key (private) goes into the SSH_PRIVATE_KEY secret below

# 2. Clone the app (on the server)
git clone https://github.com/PhoeunChantha/shop_management.git /var/www/shop-staging
cd /var/www/shop-staging
git checkout dev            # production: main

# 3. Environment — never committed
cp .env.example .env        # fill DB_*, MAIL_*, CUTLUY_*, APP_URL
#    APP_ENV=staging|production, APP_DEBUG=false
php artisan key:generate
php artisan storage:link

# 4. First deploy by hand
bash scripts/deploy.sh origin/dev

# 5. Permissions
sudo chown -R deploy:www-data storage bootstrap/cache public/uploads
sudo chmod -R ug+rwx storage bootstrap/cache public/uploads
```

Nginx root → `<app>/public`. Supervisor programs (one set per environment):

```ini
[program:shop-staging-queue]
command=php /var/www/shop-staging/artisan queue:work --sleep=3 --tries=3 --max-time=3600
user=deploy
autostart=true
autorestart=true

[program:shop-staging-reverb]
command=php /var/www/shop-staging/artisan reverb:start
user=deploy
autostart=true
autorestart=true
```

Cron for the scheduler: `* * * * * cd /var/www/shop-staging && php artisan schedule:run >> /dev/null 2>&1`

## GitHub settings

**Settings → Environments** has `staging` (deploys from `dev` only) and `production` (deploys from `main` only).
Add these **secrets** to each environment:

| Secret | Example |
|---|---|
| `SSH_HOST` | `203.0.113.10` |
| `SSH_USER` | `deploy` |
| `SSH_PRIVATE_KEY` | contents of `deploy_key` |
| `SSH_PORT` | `22` (optional) |
| `DEPLOY_PATH` | `/var/www/shop-staging` |

Optional environment **variable** `APP_URL` shows a link on the deploy run.

Until `SSH_HOST` is set, the deploy job is skipped with a warning (tests still run).
App secrets (`.env`: DB password, CutLuy keys, mail password) stay on the server — never in GitHub or the repo.
