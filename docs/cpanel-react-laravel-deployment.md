# cPanel Deployment: React + Laravel

This project is split into two deployable apps:

- React frontend: built from `client/` and served as static files.
- Laravel backend: served from Laravel's `public/` directory and exposes `/api`, `/admin`, and storage assets.

## Recommended Domains

Use one public frontend domain and one backend/API domain.

- Frontend: `https://bookmystyle.limach.lk`
- Backend: `https://api.bookmystyle.limach.lk`

If cPanel only gives one domain, serve React at the main domain and Laravel in a subdomain or separate add-on domain. Avoid placing the React build inside Laravel's `public/` folder unless you intentionally want one combined deployment.

## Laravel `.env`

For a separated production setup:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.bookmystyle.limach.lk
FRONTEND_URL=https://bookmystyle.limach.lk
CORS_ALLOWED_ORIGINS=https://bookmystyle.limach.lk
SESSION_DOMAIN=.bookmystyle.limach.lk
SANCTUM_STATEFUL_DOMAINS=bookmystyle.limach.lk,api.bookmystyle.limach.lk
```

The current React auth flow uses Sanctum personal access tokens, so API requests authenticate with `Authorization: Bearer <token>`. `supports_credentials` can remain `false` in `config/cors.php` for this mode.

## React `.env`

In `client/.env.production`:

```dotenv
VITE_API_BASE_URL=https://api.bookmystyle.limach.lk/api
VITE_SITE_URL=https://bookmystyle.limach.lk
VITE_GOOGLE_CLIENT_ID=
VITE_GOOGLE_MAPS_API_KEY=
```

Then build:

```bash
cd client
npm ci
npm run build
```

Upload the contents of `client/dist/` to the frontend domain document root, usually `public_html/` for the main domain or the subdomain document root. The `client/public/.htaccess` file is copied into `dist/` and makes React Router deep links work.

## Laravel Upload

Upload Laravel outside `public_html` when possible, then point the backend domain document root to Laravel's `public/` directory.

Run on the server:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Make sure Apache forwards the `Authorization` header. The included `public/.htaccess` already contains the required rewrite rule for Sanctum bearer tokens.

## Filament Roles

- `admin` can access `/admin` and admin-only resources.
- `owner` can access `/admin` only after approval; unapproved owners are redirected to the pending approval page.
- Owner API routes are protected by `auth:sanctum`, `role:owner`, and `approved`.
- React admin/owner/customer pages should still use role routes for UX, but Laravel remains the source of truth.
