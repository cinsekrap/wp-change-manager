# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.0.x   | Yes       |

Only the latest release receives security updates.

## Reporting a Vulnerability

If you discover a security vulnerability, please report it responsibly. **Do not open a public issue.**

Email: **wpchange.issues@nicparkes.com**

Include:
- A description of the vulnerability
- Steps to reproduce
- The potential impact
- Any suggested fix (optional)

You should receive a response within 48 hours. We will work with you to understand the issue and coordinate a fix before any public disclosure.

## Security Architecture

### Authentication & Access Control

- **Admin panel** is protected by three middleware layers: `auth` (session), `admin` (role check), `mfa` (TOTP verification)
- **TOTP MFA** is mandatory for all password-login admin users. Setup is forced on first login. Uses industry-standard TOTP (RFC 6238), compatible with Google Authenticator, Microsoft Authenticator, and similar apps
- **Microsoft Entra SSO** is supported as an alternative login method. SSO users bypass TOTP (Microsoft handles their MFA). SSO is configured via the admin panel, not hardcoded
- **Role separation**: `role` column controls admin panel access (`super_admin`, `editor`, or `null`). SSO-provisioned users default to no admin access and must be promoted by a super admin
- **Password policy**: minimum 10 characters, mixed case, numbers required
- **Login rate limiting**: 5 attempts per minute
- **MFA challenge rate limiting**: 5 attempts per minute
- **Password reset**: signed links with 60-minute expiry, branded emails, rate limited

### Input Validation & Injection Prevention

- **Server-side validation** on all inputs via Laravel Form Request classes
- **CSRF protection** on all state-changing routes via Laravel's built-in middleware
- **Prepared statements** via Eloquent ORM — no raw SQL concatenation
- **XSS prevention**: all dynamic content in JavaScript uses an `esc()` HTML entity encoding helper. Error messages use `textContent` instead of `innerHTML`
- **Content Security Policy** header applied via middleware

### File Upload Security

- **MIME type validation** server-side using PHP's `guessExtension()` (detects actual file type, not client-supplied extension)
- **File size limit**: 10MB per file, validated server-side
- **UUID filenames**: uploaded files are renamed to UUIDs, preventing path traversal or filename-based attacks
- **Session-bound uploads**: only the session that uploaded a file can delete it
- **Filename format validation**: UUID format enforced via regex on submission
- **Metadata verified from disk**: file size and MIME type are read from the actual stored file during submission, not trusted from client JSON
- **Storage isolation**: files stored in `storage/app/private/uploads/` (outside the web root). Served only via an authenticated controller route
- **Orphaned file cleanup**: `php artisan uploads:clean` removes abandoned temporary uploads

### API & Endpoint Security

- **Rate limiting** on all public endpoints: submission (10/hour), API (60/min), login (5/min), approval POST (5/min), deploy (3/min), password reset (3/min)
- **Deploy endpoint**: POST only, timing-safe token comparison via `hash_equals()`, no internal output returned to caller, all attempts logged
- **Sitemap API**: validates site is active before returning data. Generic error messages returned (no internal details leaked)
- **Approval tokens**: cryptographically random 64-character hex strings. Valid until used, then cleared. Two-step flow (GET shows page, POST records decision) prevents email security scanners from triggering actions
- **Tracking URLs**: signed using Laravel's URL signing (HMAC). Prevents enumeration of requests by guessing reference numbers

### Data Protection

- **Passwords**: hashed via bcrypt (Laravel's `hashed` cast). Never stored or logged in plaintext
- **MFA secrets**: encrypted at rest via Laravel's `encrypted` cast (AES-256-CBC using APP_KEY). Excluded from JSON serialisation via `$hidden`
- **SMTP password**: encrypted at rest in the `settings` table via `Crypt::encryptString()`. Decrypted transparently on read. Masked in the admin UI
- **Entra client secret**: encrypted at rest in the `settings` table. Same encryption pattern
- **Backward compatible**: if secrets were stored before encryption was enabled, they are returned as-is without breaking the application. Re-saving them will encrypt them
- **Session cookies**: `SESSION_SECURE_COOKIE=true` recommended for production (HTTPS-only cookies)
- **APP_DEBUG**: must be `false` in production to prevent stack traces and environment variables leaking to users

### Dependency Management

- **Vendor directory is committed** to the repository because the target hosting environment cannot run Composer. This means dependency updates require a local `composer update`, commit, and deploy
- Run `composer audit` periodically to check for known vulnerabilities in dependencies
- The self-update system pulls from GitHub releases — ensure the repository is not compromised

## Production Hardening Checklist

Before deploying to production, verify:

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `DEPLOY_TOKEN` is a cryptographically random string (64+ characters)
- [ ] `APP_KEY` has been generated and is unique to this installation
- [ ] Default admin password has been changed
- [ ] SMTP credentials configured (for email notifications)
- [ ] MFA set up by all admin users
- [ ] File permissions: `storage/` and `bootstrap/cache/` are writable by the web server
- [ ] `storage/app/private/uploads/` is not directly accessible via the web
