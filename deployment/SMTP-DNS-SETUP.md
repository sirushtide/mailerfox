# GoRealo mail delivery status

Production uses `MAIL_MAILER=log`, so Laravel is not currently sending campaign messages through a live SMTP provider. The server has Postfix and OpenDKIM configured with SMTP bound only to `127.0.0.1:25`; this host configuration does not enable inbound mailboxes or make the app use SMTP. Public SMTP ports 25, 465, and 587 are not exposed.

Before connecting a delivery provider or switching the app to local Postfix:

1. Choose the intended delivery route and confirm the provider supports the expected sending volume and bounce/complaint handling.
2. Confirm the current authoritative DNS records with the domain owner. Merge SPF includes into one SPF record; do not add a second SPF TXT record.
3. Publish the provider's required DKIM records and a monitored DMARC policy. Do not replace existing mail records without checking what currently sends mail for the domain.
4. If sending directly from the Lightsail server, first verify AWS outbound TCP 25 approval and request reverse DNS/PTR for the sending IP. Confirm the `mail.gorealo.org` A record points to the server.
5. Configure MailerFox credentials outside Git, set the production `MAIL_*` values securely, clear/rebuild Laravel config cache, and test delivery, SPF/DKIM/DMARC alignment, bounces, and unsubscribe handling before enabling campaigns.

`setup-smtp.sh` is a host configuration reference that installs/reconfigures Postfix and OpenDKIM and creates a server-private DKIM key when absent. It is not a routine deployment step. Review it before running, preserve the existing server key, and obtain any DNS value directly from the host after setup. Never commit the private key.
