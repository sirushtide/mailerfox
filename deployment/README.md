# Production deployment and operations

The production app is `https://app.gorealo.org`, hosted on Ubuntu 24.04 at `54.210.17.130` under `/var/www/mailpurse`. It runs Nginx, PHP 8.3 FPM, MySQL 8, Redis, Supervisor, and Laravel scheduler tasks. Port 80 redirects to HTTPS and port 443 serves the site. MySQL and Redis listen on loopback; inbound access is controlled by Lightsail networking. UFW is inactive.

## Source and deploy workflow

The live app is represented in this repository. For changes, work on a branch and review the diff before deployment. Back up the database and app storage first, then deploy the selected commit to `/var/www/mailpurse` while preserving the existing `.env`, MySQL data, `storage/`, permissions, and Supervisor configuration. Install PHP dependencies from `composer.lock`, rebuild frontend assets with `npm ci && npm run build`, run migrations with `php artisan migrate --force`, clear/rebuild Laravel caches as appropriate, and restart queue workers with `php artisan queue:restart`. Check the site, Nginx/PHP-FPM, scheduled tasks, and Supervisor after release.

Do not run `provision.sh` on the existing server: it is a one-time initial provisioning script. `activate.sh` creates an initial administrator when absent and applies server setup; review it before using on a new host. Keep backups off the application checkout. The backup helper writes local files to `/var/backups/mailpurse`; configure off-server backups or snapshots separately.

## Current service configuration

- Nginx serves only the Laravel `public/` directory and forwards PHP to PHP 8.3 FPM.
- TLS certificates cover `app.gorealo.org` and `gorealo.org`; the latter redirects to the app hostname.
- Supervisor manages the persistent queue worker. Cron runs Laravel’s scheduler each minute.
- The app’s `MAIL_MAILER` is `log`; a delivery provider is not connected in the production app configuration. Postfix/OpenDKIM are configured on the host for local-only SMTP on `127.0.0.1:25`; that does not mean the app is using SMTP or that inbound mailboxes exist. Public ports 25, 465, and 587 are not open/listening.
- Never put production `.env`, keys, database dumps, admin credentials, or private DKIM keys in Git. Production credentials and initial admin details are held separately on the server.

## Useful checks

```bash
sudo supervisorctl status
sudo systemctl status nginx php8.3-fpm mysql redis-server
cd /var/www/mailpurse
sudo -u www-data php artisan migrate:status
sudo -u www-data php artisan queue:restart
sudo -u www-data php artisan schedule:list
sudo certbot certificates
sudo certbot renew --dry-run
```

The Nginx site configuration is in [`nginx-mailerfox.conf`](nginx-mailerfox.conf). Initial host provisioning, activation, backup, and SMTP setup scripts are included for reference; read and adapt them before use on a new server.
