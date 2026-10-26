#!/usr/bin/env bash
set -euo pipefail
umask 077
backup_dir=/var/backups/mailpurse
install -d -m 700 "$backup_dir"
stamp=$(date -u +%Y%m%dT%H%M%SZ)
mysqldump --single-transaction --no-tablespaces mailpurse | gzip > "$backup_dir/database-$stamp.sql.gz"
tar -czf "$backup_dir/files-$stamp.tar.gz" -C /var/www/mailpurse .env storage/app
find "$backup_dir" -type f -mtime +7 -delete
