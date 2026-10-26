#!/usr/bin/env bash
set -euo pipefail
cd /var/www/mailpurse
python3 - <<'PY'
import secrets,json,pathlib
p=pathlib.Path('/var/www/mailpurse/storage/app/private/initial-admin.json')
if not p.exists():
 p.write_text(json.dumps({'email':'admin@gorealo.org','password':secrets.token_urlsafe(24)}))
 p.chmod(0o600)
PY
chown www-data:www-data storage/app/private/initial-admin.json
cat > /var/www/mailpurse/create-initial-admin.php <<'PHP'
<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c=json_decode(file_get_contents(storage_path('app/private/initial-admin.json')),true);
app(App\Services\RoleService::class)->ensureDefaultRoles();
$role=App\Models\Role::where('slug','superadmin')->firstOrFail();
$user=App\Models\User::firstOrCreate(['email'=>$c['email']],['first_name'=>'Gorealo','last_name'=>'Admin','password'=>Illuminate\Support\Facades\Hash::make($c['password']),'status'=>'active','email_verified_at'=>now()]);
$user->roles()->syncWithoutDetaching([$role->id]);
file_put_contents(storage_path('app/private/installed.json'),json_encode(['installed_at'=>now()->toIso8601String(),'admin_email'=>$c['email']]));
echo "Superadmin created and installation locked.\n";
PHP
sudo -u www-data php create-initial-admin.php
rm create-initial-admin.php
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:cache
cat > /etc/nginx/sites-available/mailpurse <<'CONF'
server {
    listen 80;
    server_name app.gorealo.org localhost 127.0.0.1;
    root /var/www/mailpurse/public;
    index index.php;
    server_tokens off;
    client_max_body_size 36m;
    add_header X-Content-Type-Options nosniff always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_read_timeout 120;
    }
    location ~ \.php(?:/|$) { return 404; }
    location ~ /\.(?!well-known).* { deny all; }
    location ~* ^/(?:vendor|config|database|bootstrap|resources|routes|tests|dev)(?:/|$) { return 404; }
    access_log /var/log/nginx/mailpurse-access.log;
    error_log /var/log/nginx/mailpurse-error.log;
}
CONF
ln -sfn /etc/nginx/sites-available/mailpurse /etc/nginx/sites-enabled/mailpurse
nginx -t
systemctl reload nginx
cat > /etc/supervisor/conf.d/mailpurse.conf <<'CONF'
[program:mailpurse-worker]
directory=/var/www/mailpurse
command=/usr/bin/php artisan queue:work --queue=campaigns,email-validation,default --sleep=3 --tries=3 --timeout=300 --memory=192 --max-time=3600
user=www-data
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
stopwaitsecs=360
redirect_stderr=true
stdout_logfile=/var/www/mailpurse/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
CONF
supervisorctl reread
supervisorctl update
cat > /etc/cron.d/mailpurse <<'CONF'
* * * * * www-data cd /var/www/mailpurse && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
CONF
chmod 644 /etc/cron.d/mailpurse
systemctl enable nginx mysql php8.3-fpm supervisor redis-server certbot.timer
supervisorctl status
curl -s -o /dev/null -w 'Local login HTTP status: %{http_code}\n' -H 'Host: app.gorealo.org' http://127.0.0.1/login
