# BookMyStyle — cPanel Deployment Guide

The layout below describes the original React deployment. For the staged Blade migration, follow [step-five deployment notes](docs/migration/step-5-public-blade.md), [step-six customer cutover notes](docs/migration/step-6-customer-blade.md), and [step-seven Filament management notes](docs/migration/step-7-filament-management.md): Laravel now owns public/auth/customer routes and core admin/owner bookmark redirects. Root Vite assets are required; the old React rewrite and static SEO files must not overwrite the new arrangement. Remaining dashboard analytics, administrative feature parity and legacy-route retirement still need validation before release.

This project ships **two things** that live behind one domain (`bookmystyle.limach.lk`):

| Part | Source folder | What gets uploaded |
| --- | --- | --- |
| Laravel 12 API + Filament admin | repo root | the whole project (minus `node_modules` / `client`) |
| React SPA (Vite) | `client/` | only the built `client/dist/` contents |

Both are served from the **same document root** (`public_html/`) — the `.htaccess` in [client/public/.htaccess](client/public/.htaccess) routes `/api/*` to Laravel and everything else to the React SPA.

---

## 1. Pre-deploy: verify locally

```powershell
# 1a. Laravel — install prod deps, run checks
composer install --no-dev --optimize-autoloader
php artisan route:list                # should list 234 routes (no `test`/`test-email`)
php artisan config:show app           # confirm env values

# 1b. React — build a fresh production bundle
cd client
npm ci
npm run build                         # outputs to client/dist/
```

Confirm `client/dist/index.html` references the new hashed `assets/*.js` and `assets/*.css`.

---

## 2. cPanel — one-time setup

1. **Create a MySQL database + user** in cPanel → MySQL Databases. Grant ALL privileges. Note the prefixed name (e.g. `cpuser_bookmystyle`).
2. **Set PHP version to 8.2+** (8.3 recommended) in cPanel → MultiPHP Manager. Required extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd` (Intervention/Image), `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`.
3. **Point the domain** `bookmystyle.limach.lk` to `public_html/` (or a subdir if it's an addon domain).

---

## 3. Upload layout

Recommended layout (keeps Laravel internals **outside** the web root):

```
/home/<cpanel-user>/
├── bookmystyle/              ← Laravel app (NOT under public_html)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/
│   ├── vendor/               ← composer install --no-dev --optimize-autoloader
│   └── .env                  ← copy from .env.production.example, fill in secrets
└── public_html/              ← document root
    ├── index.php             ← from bookmystyle/public/index.php, edit paths (see below)
    ├── .htaccess             ← from client/public/.htaccess (SPA + /api rewrite)
    ├── assets/               ← from client/dist/assets/
    ├── logo2.png, favicon.ico, robots.txt, sitemap.xml, etc.
    └── storage/              ← symlink → ../bookmystyle/storage/app/public
```

### 3a. Patch `public_html/index.php`

Open it and adjust the require paths so they look outside `public_html`:

```php
require __DIR__.'/../bookmystyle/vendor/autoload.php';
$app = require_once __DIR__.'/../bookmystyle/bootstrap/app.php';
```

(Optional: also override `Application` paths if you want assets/storage to resolve from the new location — see Laravel docs "Shared Hosting".)

### 3b. Create the storage symlink

In cPanel → Terminal (or SSH):

```bash
cd ~/public_html
ln -s ../bookmystyle/storage/app/public storage
```

If symlinks are disabled, instead upload `bookmystyle/storage/app/public/*` directly into `public_html/storage/` and re-upload after each new salon image (or move uploads to S3).

---

## 4. Environment file

Copy [.env.production.example](.env.production.example) → `bookmystyle/.env` and fill in:

| Key | Value |
| --- | --- |
| `APP_KEY` | run `php artisan key:generate --show` locally, paste output |
| `APP_DEBUG` | **must be `false`** |
| `APP_URL` | `https://bookmystyle.limach.lk` |
| `DB_*` | cPanel MySQL credentials |
| `MAIL_*` | cPanel SMTP (or Mailtrap / SES) |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | from Google Cloud Console — **add `https://bookmystyle.limach.lk` as Authorized JS origin & redirect URI** |
| `SESSION_DOMAIN` | `.bookmystyle.limach.lk` |
| `SESSION_SECURE_COOKIE` | `true` (HTTPS only) |

Also set `client/.env.production` before running `npm run build`:

```
VITE_API_BASE_URL=/api
VITE_SITE_URL=https://bookmystyle.limach.lk
VITE_GOOGLE_CLIENT_ID=<same as backend>
VITE_GOOGLE_MAPS_API_KEY=<restricted key>
```

---

## 5. Post-deploy commands (cPanel Terminal / SSH)

```bash
cd ~/bookmystyle

php artisan migrate --force
php artisan db:seed --force          # only if seeding production data
php artisan shield:install --fresh   # if using Filament Shield permissions
php artisan storage:link             # if you put public/ at a non-default location

# Cache for performance — re-run after any .env or code change
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# Verify
curl -I https://bookmystyle.limach.lk/up      # health endpoint → 200
curl https://bookmystyle.limach.lk/api/salons # should return JSON
```

If you can't run a queue worker, keep `QUEUE_CONNECTION=sync` (already the case for most outbound mail in this app uses `Mail::to(...)->send(...)` directly).

If you **can** run a worker (cPanel → Cron Jobs), add:

```cron
* * * * * cd /home/<user>/bookmystyle && php artisan schedule:run >> /dev/null 2>&1
```

---

## 6. Permissions

```bash
cd ~/bookmystyle
chmod -R 775 storage bootstrap/cache
# cPanel usually runs PHP as your user, so 755 is also fine; never 777.
```

---

## 7. Smoke test checklist

- [ ] `https://bookmystyle.limach.lk/` loads the React app (no 404 on `/assets/...`)
- [ ] `https://bookmystyle.limach.lk/salons` (SPA route) refreshes correctly — `.htaccess` is doing its job
- [ ] `https://bookmystyle.limach.lk/api/salons` returns JSON
- [ ] `https://bookmystyle.limach.lk/admin` (Filament) loads its login
- [ ] `https://bookmystyle.limach.lk/sitemap.xml` and `/robots.txt` resolve
- [ ] Google login works (Authorized origins/redirects updated)
- [ ] Booking confirmation email arrives
- [ ] `storage/logs/laravel.log` shows no errors

---

## 8. What was fixed in the audit

| Item | Before | After |
| --- | --- | --- |
| Debug `Route::get('/test-email/{id}', ...)` in `routes/web.php` | exposed in prod | **removed** |
| Debug `Route::get('/test', ...)` in `routes/api.php` | exposed in prod | **removed** |
| `client/src/App.jsx` | imported `./pages/*` files that don't exist (dead code) | **deleted** |
| `.env.production.example` | missing | **added** with safe prod defaults |
| `client/.env.example` | only 2 vars, no comments | **expanded** with all required vars + comments |
| `sitemap.xml` | no `<lastmod>` | **added** `<lastmod>` to every entry |
| `robots.txt` | missed `/api/`, `/subscription/` | **added** disallows |
| `index.html` | no canonical, no JSON-LD, no theme-color, no preconnect | **added** canonical, WebSite + Organization JSON-LD, theme-color, preconnect for fonts, apple-touch-icon |
| `dist/index.html` and `dist/{sitemap.xml,robots.txt}` | out of date | **regenerated** to match source |
| Google Search Console verification | no placeholder | added `<meta name="google-site-verification">` slot in `index.html` (replace token before deploy) |

Everything in `app/` PHP-lints clean (`php -l`), 234 routes resolve, Laravel boots without errors.

---

## 9. Google Search Console setup

After the site is live and reachable at `https://bookmystyle.limach.lk`:

### 9a. Verify ownership (do **one** of the four)

1. Go to https://search.google.com/search-console → **Add Property**.
2. Choose a property type:

   | Type | When | What you do |
   | --- | --- | --- |
   | **Domain** (preferred) | covers `*.limach.lk` + http/https/www | add a TXT record at your DNS host (`google-site-verification=...`) |
   | **URL prefix** | covers only `https://bookmystyle.limach.lk/` | use one of the methods below |

#### Method A — HTML meta tag (already wired up)

[client/index.html](client/index.html) and [client/dist/index.html](client/dist/index.html) already contain a placeholder:

```html
<meta name="google-site-verification" content="REPLACE_WITH_GSC_TOKEN" />
```

1. In Search Console, pick **HTML tag** verification → copy the value of `content="..."`.
2. Replace `REPLACE_WITH_GSC_TOKEN` in **both** files (or just `client/index.html` and re-run `npm run build`).
3. Re-deploy `client/dist/` → click **Verify** in Search Console.

#### Method B — HTML file upload (no rebuild required)

1. In Search Console, pick **HTML file** verification → download `googleXXXXXXXXX.html`.
2. Upload it to `public_html/` on cPanel (same level as `index.html` / `index.php`).
3. Confirm it loads at `https://bookmystyle.limach.lk/googleXXXXXXXXX.html`.
4. Click **Verify**.

#### Method C — DNS TXT record (best for "Domain" property)

1. In Search Console choose **Domain** property → it gives you a `google-site-verification=...` token.
2. In your DNS provider (where `limach.lk` is hosted), add:

   ```
   Type:  TXT
   Host:  bookmystyle      (or @ for the apex)
   Value: google-site-verification=THE_TOKEN
   TTL:   3600
   ```

3. Wait 5–30 min for propagation (`nslookup -type=TXT bookmystyle.limach.lk`), then click **Verify**.

### 9b. Submit the sitemap

Once verified:

1. Search Console → left sidebar → **Sitemaps**.
2. Enter `sitemap.xml` → **Submit**. Status should become **Success** within a day with 9 discovered URLs.

### 9c. Request indexing of key pages

For each high-value URL (`/`, `/salons`, `/services`, `/deals`, `/about`):

1. Paste the full URL into the top search bar.
2. Click **Request indexing**. (Limited to ~10/day per property.)

### 9d. Connect Bing Webmaster (1-minute bonus)

Bing Webmaster Tools at https://www.bing.com/webmasters lets you **import** a verified Google Search Console property — no extra DNS / file needed. Submit the same sitemap there too.

### 9e. After deploy: things to watch in Search Console

| Report | What to look for |
| --- | --- |
| **Coverage / Pages** | Should show 9 indexed within ~1 week. Errors here usually mean robots.txt is blocking. |
| **Mobile Usability** | The Tailwind layout should pass; flag any "Clickable elements too close" issues. |
| **Core Web Vitals** | LCP/CLS/INP scores. Fonts loaded via cdnjs + Google Fonts can hurt LCP — already mitigated with `preconnect` in `index.html`. |
| **Sitemap → Discovered URLs** | If salons are dynamic, see the follow-up below to make sitemap include them. |

> **SPA caveat:** Googlebot now renders JavaScript, but per-route titles/descriptions from `react-helmet-async` are injected *after* render. The site's structure (canonical URLs in `<head>` via Helmet + a baseline `<title>` in `index.html`) handles this correctly, but expect crawl + index for SPA routes to take 1–2 weeks longer than for static HTML.

---

## 10. Known follow-ups (not blockers)

- Sitemap is static. If you want it to include each salon detail page (`/salon/{id}`), add a small Artisan command that queries published salons and rewrites `public/sitemap.xml` nightly via the cron above.
- `APP_DEBUG=true` is set in the **local** `.env` — that's fine for dev, just make sure the **server** `.env` has `APP_DEBUG=false`.
- `MAIL_PASSWORD` and `GOOGLE_CLIENT_SECRET` are currently committed in `.env`. After deploy, **rotate** both and add `.env` to `.gitignore` if not already.
- Filament panel is at `/admin` and is disallowed in robots — confirmed correct.
