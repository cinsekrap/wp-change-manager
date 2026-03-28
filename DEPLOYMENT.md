# ACME Change — Production Deployment Guide

## Pre-deployment Checklist

### 1. Environment File (`.env`)

Create `.env` on the server with these critical settings:

```env
APP_NAME="ACME Change"
APP_ENV=production
APP_KEY=                        # Generate with: php artisan key:generate --show
APP_DEBUG=false                 # CRITICAL: must be false in production
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=acme_change
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Sessions
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true      # Requires HTTPS

# Mail (can also be configured via admin UI at /admin/settings/mail)
MAIL_MAILER=smtp
MAIL_FROM_ADDRESS="noreply@hcrgmarketing.co.uk"
MAIL_FROM_NAME="${APP_NAME}"

# Deploy
DEPLOY_TOKEN=                   # Generate a long random string (64+ chars)
```

### 2. Critical Settings

| Setting | Value | Why |
|---------|-------|-----|
| `APP_DEBUG` | `false` | Prevents stack traces/secrets leaking to users |
| `APP_ENV` | `production` | Prevents seeders running, enables production optimisations |
| `SESSION_SECURE_COOKIE` | `true` | Cookies only sent over HTTPS |
| `DEPLOY_TOKEN` | 64+ random chars | Protects the deploy endpoint |

### 3. Generate APP_KEY

If not set, generate locally and paste into the server's `.env`:
```bash
php artisan key:generate --show
```

## First-time Server Setup

1. **Upload the codebase** — clone from GitHub or upload via hosting panel
2. **Point document root** at the `/public` directory
3. **Create `.env`** with the settings above
4. **Run migrations** — hit the deploy endpoint or use the hosting panel's PHP runner:
   ```
   POST https://your-domain.com/deploy/YOUR_DEPLOY_TOKEN
   ```
5. **Create first admin user** — use one of:
   - The seeder (only in non-production): `php artisan db:seed --class=AdminSeeder`
   - The artisan command: `php artisan admin:create`
   - Or enable Entra SSO and auto-provision, then promote the first user to admin via the database

## Ongoing Deployments

1. Push changes to GitHub
2. Trigger deploy:
   ```bash
   curl -X POST https://your-domain.com/deploy/YOUR_DEPLOY_TOKEN
   ```
   Or set up a GitHub webhook to call this URL on push.

The deploy endpoint runs `git pull origin main` and `php artisan migrate --force`.

## Post-deployment Configuration

All of these are configurable via the admin panel (no SSH needed):

1. **Mail Settings** (`/admin/settings/mail`) — configure SMTP credentials
2. **SSO Settings** (`/admin/settings/entra`) — configure Microsoft Entra SSO
3. **Sites** (`/admin/sites`) — add WordPress sites
4. **Content Types** (`/admin/cpts`) — configure form fields, block types
5. **Check Questions** (`/admin/questions`) — set up pre-submission checks
6. **Users** (`/admin/users`) — create admin accounts

## Maintenance

### Temp File Cleanup

Orphaned upload files (from abandoned wizard sessions) should be cleaned periodically:
```bash
php artisan uploads:clean --hours=24
```

On shared hosting without cron, set up an external cron service (e.g., cron-job.org) to hit a URL that triggers this, or clean up manually via the hosting panel.

### Sitemap Refresh

Sitemaps auto-refresh when stale (>24hrs), but can be manually refreshed:
- Via admin: Sites > Refresh Sitemap button
- Via CLI: `php artisan sitemap:refresh`

### MFA Reset

If a user loses their authenticator:
- Admin goes to Users > Edit user > "Reset two-factor authentication"
- User will be prompted to set up MFA again on next login

## Security Notes

- **Deploy token**: treat as a secret. Rotate periodically.
- **APP_KEY**: if compromised, rotate and re-encrypt any encrypted data.
- **Entra client secret**: expires (max 2 years in Azure). Set a reminder to rotate.
- **Admin password**: MFA is mandatory for password logins. Ensure all admins set it up.
- **File uploads**: stored outside webroot in `storage/app/private/uploads/`. Only accessible via authenticated admin download route.

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Blank page / 500 error | Check `APP_DEBUG` is `false`, check `storage/logs/laravel.log` |
| Can't log in | Run migrations (deploy endpoint). Check `.env` DB credentials. |
| Emails not sending | Configure SMTP via admin panel. Check `storage/logs/laravel.log`. |
| SSO not working | Check Entra settings in admin. Verify redirect URI matches Azure app registration. |
| Deploy fails | Check `storage/logs/deploy.log`. Verify `DEPLOY_TOKEN` matches. |
| MFA setup fails | Ensure server time is accurate (TOTP is time-sensitive). |
