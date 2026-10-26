# GoRealo email templates

This folder contains 15 distinct customer, homebuyer, homeowner, mortgage education, and agent emails, plus the five pre-existing GoRealo library templates. The 15 campaigns include native MailerFox block-builder data and responsive email-safe HTML. Screenshots for all 20 templates are in `public/email-template-previews/gorealo/` and are used as picker thumbnails.

## Files

- `templates.json` — all 15 import records.
- `templates/<slug>/template.json` — one complete record per campaign.
- `templates/<slug>/builder.json` — native editable blocks (`builder: mailpurse`).
- `templates/<slug>/email.html` — standalone rendered HTML.
- `assets/` — GoRealo logo, campaign imagery, and app/product images.
- `legacy/` — HTML source for the five pre-existing GoRealo library templates.
- `templates-overview.png` — contact sheet of all 20 rendered template screenshots.
- `build_templates.py` — rebuilds the campaign HTML and JSON.
- `install.php` — imports or updates these records in the app’s public template library.

## Import into a MailerFox installation

From the application root, run the importer as the application user after the database migrations are current:

```bash
php resources/email-templates/gorealo/install.php
```

The importer upserts the 15 campaign records in the `GoRealo Business` public-template category and adds screenshot thumbnails to the five existing GoRealo records when present. After import, preview the templates in the public template gallery and confirm personalization, recipient group, reply-to address, footer, and unsubscribe handling before sending.

## Rebuild and images

```bash
python3 resources/email-templates/gorealo/build_templates.py
```

The SVG logo is also provided as `assets/gorealo-logo.png` for email-client compatibility. Campaign visuals use GoRealo’s blue (`#003FDA`) and green (`#0BFF56`) brand colors. The public image files under `public/email-template-previews/gorealo/` provide durable URLs for email assets and picker screenshots.

## Campaigns

1. Customer welcome
2. Homebuyer budget
3. Mortgage rate check-in
4. Refinance review
5. Loan options
6. Preapproval next step
7. First-time buyer
8. VA home loan
9. FHA options
10. Self-employed buyer
11. Homeowner check-in
12. Rate education
13. Lead re-engagement
14. Real estate agent partner introduction
15. Agent technology

Rate copy points readers to the live rate page rather than hard-coding figures that can go stale. Each campaign has its own subject, preheader, conversation prompt, CTA, and content flow. Rate and eligibility language depends on borrower and property details and may change. Review claims, current legal disclosures, consent, and unsubscribe requirements before use.

Content and visual direction draw on [GoRealo](https://www.gorealo.com/), its [current mortgage rates](https://www.gorealo.com/mortgage-rates/), and [About GoRealo](https://www.gorealo.com/about/gorealo/).
