# DR inventory template

Keep this document OUTSIDE git.

This is not a backup artifact. It is an operational recovery inventory.

## Provider inventory

- VPS provider:
- VPS account owner:
- VPS account login URL:
- billing email:
- support contact:

## Domain and DNS

- registrar:
- registrar login URL:
- domain owner:
- primary domains:
- subdomains:
- DNS provider:
- DNS login URL:
- TTL policy:

## Server topology

- production hostname:
- production IP:
- staging hostname:
- staging IP:
- WordPress root path:
- backup local path:
- off-site backup destination:

## Service inventory

- web server: nginx / apache / both
- PHP version:
- database engine/version:
- cache layer:
- cron owner:
- SSL provider:

## Credentials and keys

- root or sudo access holders:
- SSH key location:
- DB credential location:
- WordPress admin emergency user:
- SMTP provider:
- payment gateway:
- CDN/WAF:

## Restore contacts

- technical owner:
- business owner:
- hosting escalation:
- DNS escalation:
- payment escalation:

## Monthly drill record

- last restore drill date:
- drill environment:
- bundle used:
- result:
- issues found: