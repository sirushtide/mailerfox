# MailerFox application

This repository contains the Laravel application deployed at [app.gorealo.org](https://app.gorealo.org), its setup and operations notes, and GoRealo email templates in `resources/email-templates/gorealo/`. The application is branded MailerFox; internal Composer/package identifiers still use the upstream MailPurse names.

## Developer setup

Requirements: PHP 8.3 with Composer and the PHP extensions required by `composer.json`, Node.js 20 or newer with npm, and SQLite for a local development database. MySQL and Redis are used by the production server.

```bash
git clone https://github.com/sirushtide/mailerfox.git
cd mailerfox
cp .env.example .env
mkdir -p database
touch database/database.sqlite
composer install
php artisan key:generate
php artisan migrate
npm ci
npm run build
php artisan serve
```

Open the local URL printed by Artisan and complete the application’s setup flow. The example environment uses SQLite and the log mailer, so it does not send email. Keep real `.env` values, API credentials, database files, and keys outside Git. Never copy production `.env` or database data into a developer checkout.

To regenerate the GoRealo template HTML and native builder JSON after changing campaign content:

```bash
python3 resources/email-templates/gorealo/build_templates.py
```

See the template folder README for importing and editing its 15 campaigns.

## Production environment

The production instance is on Ubuntu 24.04 at `app.gorealo.org` and uses Nginx, PHP 8.3 FPM, MySQL 8, Redis, Supervisor, and Laravel’s scheduled tasks. Nginx serves `public/`; the app listens through PHP-FPM. Public HTTP and HTTPS use ports 80 and 443. The host firewall is inactive; Lightsail networking controls inbound access. MySQL and Redis bind to loopback. The production environment uses a private `.env` and persistent database/storage directories, none of which are in this repository.

Production operations, first-time provisioning notes, SSL, backups, queue workers, and mail/DNS status are documented in [`deployment/README.md`](deployment/README.md). Provisioning scripts are for an initial install and must not be rerun as an update on the existing server. Deploy reviewed source changes with a backup and the release procedure documented there; do not overwrite `.env`, the database, or `storage/`.

## Repository layout

- `app/`, `routes/`, `resources/`, `database/`, `config/` — application source.
- `resources/email-templates/gorealo/` — 15 GoRealo campaigns, builder data, HTML, images, generator, and import command.
- `deployment/` — production configuration and operational documentation.
- `composer.lock`, `package-lock.json` — pinned PHP and JavaScript dependencies.

The repository intentionally excludes `vendor/`, `node_modules/`, compiled frontend output, runtime state, credentials, and production data. Run Composer and npm install/build commands locally after cloning.
