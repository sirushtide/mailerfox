#!/bin/bash
set -euo pipefail
postconf -e 'myhostname = mail.gorealo.org' 'mydomain = gorealo.org' 'myorigin = $mydomain' 'inet_interfaces = loopback-only' 'inet_protocols = ipv4' 'mynetworks = 127.0.0.0/8' 'mydestination = localhost' 'smtpd_relay_restrictions = permit_mynetworks,reject_unauth_destination' 'smtp_tls_security_level = may' 'smtp_tls_loglevel = 1' 'smtp_helo_name = $myhostname' 'disable_vrfy_command = yes'
install -d -o opendkim -g opendkim -m 700 /etc/opendkim/keys/gorealo.org
if [ ! -f /etc/opendkim/keys/gorealo.org/mailerfox.private ]; then
 opendkim-genkey -b 2048 -d gorealo.org -s mailerfox -D /etc/opendkim/keys/gorealo.org
fi
chown -R opendkim:opendkim /etc/opendkim/keys
chmod 600 /etc/opendkim/keys/gorealo.org/mailerfox.private
cat > /etc/opendkim.conf <<'CONF'
Syslog yes
UMask 007
Mode sv
Canonicalization relaxed/simple
OversignHeaders From
UserID opendkim
Socket inet:8891@127.0.0.1
PidFile /run/opendkim/opendkim.pid
KeyTable /etc/opendkim/KeyTable
SigningTable refile:/etc/opendkim/SigningTable
InternalHosts /etc/opendkim/TrustedHosts
CONF
printf 'mailerfox._domainkey.gorealo.org gorealo.org:mailerfox:/etc/opendkim/keys/gorealo.org/mailerfox.private\n' > /etc/opendkim/KeyTable
printf '*@gorealo.org mailerfox._domainkey.gorealo.org\n' > /etc/opendkim/SigningTable
printf '127.0.0.1\nlocalhost\n' > /etc/opendkim/TrustedHosts
postconf -e 'smtpd_milters = inet:127.0.0.1:8891' 'non_smtpd_milters = inet:127.0.0.1:8891' 'milter_protocol = 6' 'milter_default_action = tempfail'
postfix check
systemctl restart opendkim postfix
systemctl enable opendkim postfix
systemctl is-active opendkim postfix
cat /etc/opendkim/keys/gorealo.org/mailerfox.txt
