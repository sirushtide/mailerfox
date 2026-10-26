#!/usr/bin/env bash
set -euo pipefail
APP=/var/www/mailpurse
usermod -aG www-data ubuntu
if [ ! -f /swapfile ]; then
 fallocate -l 2G /swapfile
 chmod 600 /swapfile
 mkswap /swapfile
 swapon /swapfile
 echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi
install -d -o ubuntu -g www-data -m 2750 "$APP"
rsync -a /home/ubuntu/mailpurse-upload/ "$APP/"
chown -R ubuntu:www-data "$APP"
find "$APP" -type d -exec chmod 750 {} +
find "$APP" -type f -exec chmod 640 {} +
install -d -o www-data -g www-data -m 770 "$APP/storage/framework/cache/data" "$APP/storage/framework/sessions" "$APP/storage/framework/views" "$APP/storage/logs" "$APP/storage/app/private" "$APP/storage/app/public" "$APP/bootstrap/cache"
chown -R www-data:www-data "$APP/storage" "$APP/bootstrap/cache"
chmod -R ug+rwX "$APP/storage" "$APP/bootstrap/cache"
# This distribution ships a shared-hosting entrypoint; use Laravel's standard public entrypoint.
cat > "$APP/public/index.php" <<'PHP'
<?php
use Illuminate\Http\Request;
define('LARAVEL_START', microtime(true));
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}
require __DIR__.'/../vendor/autoload.php';
(require_once __DIR__.'/../bootstrap/app.php')->handleRequest(Request::capture());
PHP
chown ubuntu:www-data "$APP/public/index.php"
chmod 640 "$APP/public/index.php"
python3 - <<'PY'
from pathlib import Path
import secrets,subprocess,base64
p=Path('/var/www/mailpurse')
if (p/'.env').exists(): raise SystemExit('Existing .env found; refusing to replace secrets')
pw=secrets.token_hex(24)
sql=f"CREATE DATABASE mailpurse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER 'mailpurse'@'127.0.0.1' IDENTIFIED BY '{pw}'; GRANT ALL PRIVILEGES ON mailpurse.* TO 'mailpurse'@'127.0.0.1';"
subprocess.run(['mysql'],input=sql,text=True,check=True)
v=dict(APP_NAME='MailPurse',APP_ENV='production',APP_DEBUG='false',APP_URL='https://app.gorealo.org',APP_KEY='base64:'+base64.b64encode(secrets.token_bytes(32)).decode(),SKIP_INSTALL_WIZARD='true',DB_CONNECTION='mysql',DB_HOST='127.0.0.1',DB_PORT='3306',DB_DATABASE='mailpurse',DB_USERNAME='mailpurse',DB_PASSWORD=pw,CACHE_STORE='file',SESSION_DRIVER='file',SESSION_SECURE_COOKIE='true',QUEUE_CONNECTION='database',DB_QUEUE_RETRY_AFTER='360',LOG_LEVEL='warning',MAIL_MAILER='log',MAIL_VERIFY_PEER='true',MAIL_VERIFY_PEER_NAME='true',MAIL_ALLOW_SELF_SIGNED='false',MAIL_FROM_ADDRESS='admin@gorealo.org',UNIBOX_ENC_KEY=base64.b64encode(secrets.token_bytes(32)).decode())
lines=(p/'.env.example').read_text().splitlines()
seen=set(); out=[]
for l in lines:
 k=l.split('=',1)[0]
 if k in v: out.append(k+'='+v[k]); seen.add(k)
 else: out.append(l)
out.extend(k+'='+x for k,x in v.items() if k not in seen)
(p/'.env').write_text('\n'.join(out)+'\n'); (p/'.env').chmod(0o640)
# The checked-in routes/console.php already leaves persistent queue processing to Supervisor.
cat > /etc/mysql/mysql.conf.d/mailpurse.cnf <<'CONF'
[mysqld]
innodb_buffer_pool_size=256M
max_connections=40
CONF
cat > /etc/php/8.3/fpm/conf.d/99-mailpurse.ini <<'CONF'
expose_php=Off
memory_limit=192M
upload_max_filesize=32M
post_max_size=36M
max_execution_time=120
opcache.enable=1
opcache.memory_consumption=96
CONF
sed -i 's/^pm.max_children =.*/pm.max_children = 4/; s/^pm.start_servers =.*/pm.start_servers = 1/; s/^pm.min_spare_servers =.*/pm.min_spare_servers = 1/; s/^pm.max_spare_servers =.*/pm.max_spare_servers = 2/' /etc/php/8.3/fpm/pool.d/www.conf
systemctl restart mysql php8.3-fpm
cd "$APP"
sudo -u ubuntu composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts
sudo -u www-data php artisan package:discover --ansi
sudo -u www-data php artisan migrate --force
ln -s "$APP/storage/app/public" "$APP/public/storage"
