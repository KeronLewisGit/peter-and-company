# Peter & Company Distribution – Hostinger installation guide

Requirements: **PHP 8.3** (8.4 also works), a **MySQL database**, and SSH access if possible. Without SSH, use the zip and its one-time `setup.php` installer (Option A).

## Option A – upload the zip into public_html (fastest, no Git or SSH)

Run `scripts/package-hostinger.sh` locally. It builds `dist/peterco-hostinger.zip`, which includes the production `vendor/` and built assets.

```
public_html/
├── index.php  .htaccess  robots.txt  favicon.svg  setup.php
├── build/                 compiled CSS, JS and fonts
├── img/                   site photography (WebP, three sizes each)
├── uploads/products/      catalogue pack shots added in the back office (scripts never run here)
└── peterco-app/            the Laravel application (blocked from the web by its own .htaccess)
```

Before you start, do steps 1–4 of **Prepare hPanel** in Option B below (domain + SSL, PHP 8.3, MySQL database, mailbox).

1. In File Manager, empty `public_html/` (delete Hostinger's `default.php`), upload the zip to `public_html` and extract it there.
2. Copy `peterco-app/.env.example` to `peterco-app/.env`. Fill in the database, mail and `PETERCO_*` values, and set `SETUP_TOKEN` to a long random string.
3. Open `https://acadostlucia.com/setup.php?token=YOUR_TOKEN`. It generates the app key, migrates, creates the first staff account and deletes itself.
4. Remove `SETUP_TOKEN` from `.env`.

To update later, build a new zip, and upload and extract it over the old files. `peterco-app/.env`, `peterco-app/storage/` (customer uploads, logs) and `uploads/` are not in the zip, so they are kept. Then visit `/setup.php?token=…` again if the release has new migrations (add a `SETUP_TOKEN` first; once a staff account exists the installer only migrates and never creates or resets accounts), or run `php artisan migrate --force` over SSH from `peterco-app/`.

## Option B – Hostinger Git deployment (for ongoing development)

The whole repository is cloned straight into `public_html/`:

```
public_html/
├── .htaccess        forces HTTPS, rewrites every request into public/
├── .env             created by you on the server (never committed)
├── app/ bootstrap/ config/ database/ lang/ resources/ routes/ storage/ vendor/
└── public/          the only folder the web can reach (index.php, build/, robots.txt)
```

- The root `.htaccess` sends every request into `public/`, so `.env`, `storage/`, `vendor/` and `.git/` are never served.
- `composer.lock` is resolved for PHP 8.3 through `config.platform.php` in `composer.json`.
- `public/build/` holds the compiled CSS, JS and fonts. It is committed because the server has no Node, so run `npm run build` and commit the result after any CSS or JS change.
- Nothing needs a cron job or a queue worker: there are no scheduled tasks and mail is sent inline (`QUEUE_CONNECTION=sync`).

### 1. Prepare hPanel (one time)

1. **Domain and SSL.** Point `acadostlucia.com` (and `www`) at the hosting plan; change only the web (A) records and leave the MX and SPF records alone, because the company's email runs on Microsoft 365. Then go to hPanel → Security → SSL and install the free certificate. The `.htaccess` redirects every request to HTTPS, so the site will not load until the certificate is active.
2. **PHP version.** Go to hPanel → Advanced → PHP Configuration and choose **PHP 8.3** (8.4 also works). Leave the default extensions enabled. `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer` and `gd` (catalogue photo resizing) are required.
3. **Database.** Go to hPanel → Databases → MySQL Databases and create a database and a user. Write down the full names, which include the `u123456789_` prefix, and the password.
4. **Sending mailbox.** The site emails staff about new requests and sends customers their reference number and, on approval, their trade account number. acadostlucia.com mail is on Microsoft 365 and its SPF record only allows Microsoft's servers, so mail sent through Hostinger's SMTP as an @acadostlucia.com address would fail SPF. Ask the company's IT for a Microsoft 365 mailbox with SMTP AUTH enabled (e.g. `website@acadostlucia.com`) and set `MAIL_HOST=smtp.office365.com`, `MAIL_PORT=587`, `MAIL_SCHEME=null` (STARTTLS), or have them add Hostinger to the SPF record before using a Hostinger mailbox.
5. **SSH.** Go to hPanel → Advanced → SSH Access, enable it, and note the IP, port (usually `65002`) and username.

### 2. Connect the private GitHub repository

The repository (`YOUR-GITHUB-USER/peter-and-company`) is private, so Hostinger needs a read-only deploy key.

1. Go to hPanel → Advanced → **GIT** and click **Generate SSH key**. Copy the public key.
2. On GitHub, go to the repository → Settings → Deploy keys → **Add deploy key**. Paste the key, name it "Hostinger", and leave *Allow write access* unchecked.
3. Hostinger only deploys into an **empty** directory. Open File Manager and delete everything in `public_html/`, including Hostinger's `default.php`.
4. Back in hPanel → GIT → Create a new repository, fill in:
   - **Repository:** `git@github.com:YOUR-GITHUB-USER/peter-and-company.git` (the SSH address, not `https://`)
   - **Branch:** `master`
   - **Directory:** leave empty. An empty value deploys into `public_html/`.
5. Click **Create**, then **Deploy**. When `composer.json` is present, Hostinger runs `composer install` after it clones the repository.

### 3. First-time setup over SSH

```bash
ssh -p 65002 u123456789@YOUR_SERVER_IP
cd ~/domains/acadostlucia.com/public_html

php -v                                   # must be 8.3+. If not, replace `php` below with /opt/alt/php83/usr/bin/php
ls vendor/autoload.php                   # if this is missing, Composer did not run: see the next line
composer install --no-dev --optimize-autoloader --no-interaction

cp deploy/env.production.example .env
nano .env                                # DB_*, MAIL_PASSWORD, PETERCO_* values. Leave APP_KEY empty
chmod -R 775 storage bootstrap/cache
php artisan key:generate --force
php artisan migrate --force
php artisan app:create-admin             # first administrator (prompts for name, email, password); add other staff in Dashboard → Staff
php artisan optimize
```

`SETUP_TOKEN` and the `ADMIN_*` lines in `.env` are only used by the zip installer (Option A). Leave them empty here.

Open `https://acadostlucia.com` and sign in at `/login` to check the install.

### 4. Turn on automatic deploys (optional)

Go to hPanel → GIT → your repository → **Auto Deployment** and copy the webhook URL. On GitHub, go to Settings → Webhooks → **Add webhook**, paste the URL as the *Payload URL*, and keep the *push* event. Every push to `master` then deploys on its own.

### 5. Every later deploy

1. Locally: run `npm run build` if any CSS or JS changed, run the checks (`php artisan test`, `vendor/bin/phpstan analyse`, `vendor/bin/pint`), then commit and push to `master`.
2. Hostinger pulls the code, either through the webhook or when you click **Deploy** in hPanel → GIT. `.env`, `storage/` contents and `vendor/` are not tracked in Git, so a pull leaves them alone.
3. Over SSH:

   ```bash
   cd ~/domains/acadostlucia.com/public_html
   composer install --no-dev --optimize-autoloader --no-interaction
   php artisan migrate --force
   php artisan optimize
   ```

   Do not skip `optimize`. The routes, config and views are cached, so new pages return 404 and new settings are ignored until you run it.

`optimize` also caches the configuration. After any later `.env` edit, run `php artisan optimize` again or the change is ignored.

### Troubleshooting

| Symptom | Fix |
|---|---|
| hPanel says the directory is not empty | Delete everything in `public_html/`, including hidden files, then deploy again. |
| "Permission denied (publickey)" during deploy | The deploy key is missing on GitHub, or the repository URL is the `https://` form. |
| 500 error with no message | Read `storage/logs/laravel-*.log`. Usual causes: no `.env`, empty `APP_KEY`, wrong `DB_*` values, or `storage/` not writable. |
| "Your Composer dependencies require a PHP version >= 8.3" | The website or SSH PHP is older than 8.3. Fix it in PHP Configuration, or run `/opt/alt/php83/usr/bin/php $(which composer) install --no-dev`. |
| Redirect loop or "too many redirects" | SSL is not active yet, or Cloudflare is in *Flexible* mode. Use *Full*. |
| Page loads without styling | `public/build/` was not committed. Run `npm run build` locally, commit, push and deploy again. |
| Emails never arrive | Check `MAIL_*` and `PETERCO_NOTIFY_EMAIL` in `.env`, run `php artisan optimize`, and check the mailbox's spam folder. |

## Pitch preview (demo data on a Hostinger preview domain)

Previews deploy from Git (Option B), not from the zip, which leaves `DemoSeeder` out. `DemoSeeder` refuses to run when `APP_ENV=production` unless the preview flag is set, because every demo staff account starts with the password `password`.

1. In `.env`, set `PETERCO_PREVIEW=true`. This shows the preview banner, sends `noindex`, and lets the demo seeder run.
2. Run `php artisan migrate --force --seeder=DemoSeeder`.
3. Straight away, reset every staff password to the preview's own demo password with tinker. Never leave `password` live.
4. When the site becomes the client's real site, set `PETERCO_PREVIEW=false` and `php artisan migrate:fresh --force` without the seeder. Then create the first administrator with `php artisan app:create-admin` (or `ADMIN_*` and `db:seed`).

## Check after install

- `https://acadostlucia.com/.env`, `/.git/config` and `/composer.json` must return 403 or 404. For Option A, check `/peterco-app/` as well.
- `http://` must redirect to `https://`.
- From a phone, add catalogue lines to an order list and send it, apply for a trade account with small PDFs, send a principal enquiry and a job application with a CV. Each should show a reference number, email the customer, and email the matching `PETERCO_NOTIFY_*` inbox.
- Sign in as a **Credit** user and open the trade licence and ID from a trade account application. Then sign in as an administrator and confirm the same files show a lock ("Credit control only"). Sign in as **HR** and open a CV; a sales user must not see applications at all. Confirm `/peterco-app/storage/app/private/leads/` returns 403 (Option A) or that `storage/` is unreachable (Option B).
- Add a catalogue line with a pack shot in Back office → Catalogue and check it on `/catalogue`. Photos are stored in `uploads/products/`, which never runs PHP.
- Approve a test trade account application on the credit board and check the customer receives the PC- account number by email, then quote it on an order request.
- Submit `/sitemap.xml` in Google Search Console.
- When the site replaces the live acadostlucia.com, set `PETERCO_PREVIEW=false`. On a temporary preview domain, set it to `true` so a banner shows and search engines skip it.
