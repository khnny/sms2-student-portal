# BCP Student Portal (separate deployment)

The student-facing portal for SMS 2. It runs as its **own app and deployment**. It never connects to the SMS 2 or CRAD database. Everything comes from the SMS 2 REST API:

- **Login:** the student signs in with their SMS 2 account (JWT, via `login/sso-authorize.php` on SMS 2).
- **Data:** REST API v1 (`me`, research, members, milestones, submissions, feedback, defenses, clearance, announcements, notifications).
- **Live updates:** SMS 2 sends signed webhooks to `webhook.php`, for example "title approved", "defense rescheduled", "chapter reviewed" or "payment approved". Each one becomes a notification here.
- **Actions not moved yet** (submitting chapters, progress updates, panel application, final manuscript, payment upload, clearance form) open the existing SMS 2 pages with the same login, through `open.php`.

The full contract is in the SMS 2 project: `docs/INTEGRATION-API.md`.

## Files

| Path | What it is |
|---|---|
| `index.php` | Dashboard |
| `research.php`, `defenses.php`, `clearance.php`, `announcements.php`, `notifications.php`, `profile.php` | Pages |
| `login.php`, `callback.php`, `logout.php` | Sign-in through SMS 2 |
| `open.php` | Opens an SMS 2 student page with the same login |
| `webhook.php` | Receives SMS 2 webhooks |
| `src/bootstrap.php` | Session, API client, token refresh, portal database |
| `src/layout.php` | Page layout |
| `config/config.php`, `config/local.example.php` | Settings |
| `database/portal_db.sql` | Portal tables (created automatically) |

## Run on XAMPP

1. Put this folder at `htdocs\sms2-student-portal`, next to `htdocs\sms2-capstone-v3`.
2. In the SMS 2 folder, register the portal (once):
   ```
   C:\xampp\php\php.exe tools\register-api-client.php student-portal --name="Student Portal" --roles=student --redirect=http://localhost/sms2-student-portal/callback.php --webhook=http://localhost/sms2-student-portal/webhook.php
   ```
   Use your own XAMPP `php.exe` path if it is different, for example `A:\xampp_new\php\php.exe`.
3. Copy `config\local.example.php` to `config\local.php`, and paste the CLIENT SECRET and WEBHOOK SECRET it printed.
4. Open `http://localhost/sms2-student-portal/` and sign in with a student account. The `sms2_portal` database is created on first use, or you can import `database/portal_db.sql` in phpMyAdmin.
5. For live notifications, run the webhook job in the SMS 2 folder. Run it once by hand:
   ```
   C:\xampp\php\php.exe tools\send-webhooks.php
   ```
   Or leave it running during a demo:
   ```
   C:\xampp\php\php.exe tools\send-webhooks.php --loop
   ```

## Deploy on HostForge (no terminal needed)

HostForge gives one database per app, so the portal is its **own app** with its own GitHub repo and database.

1. **Make two secrets** on your computer, and run this twice: `php -r "echo bin2hex(random_bytes(32));"`. One is the *client secret* and one is the *webhook secret*. Keep them private, and never put them in Git or chat.
2. **Push this folder** to a new GitHub repo, and create a new HostForge app from it. It builds from the `Dockerfile`. Add a MySQL database to the app.
3. **Portal app Environment Variables:**
   - `PORTAL_SMS2_URL=https://<sms2 app address>`
   - `PORTAL_PUBLIC_URL=https://<portal app address>`
   - `PORTAL_CLIENT_SECRET=<client secret>`
   - `PORTAL_WEBHOOK_SECRET=<webhook secret>`
   - Database: nothing to add. HostForge injects `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` for the app database, and the portal reads them. `PORTAL_DB_*` still overrides them.
4. **SMS 2 app Environment Variables:**
   - `SMS2_JWT_SECRET=<a third random value>`
   - `SMS2_PORTAL_CLIENT_SECRET=<client secret>`
   - `SMS2_PORTAL_REDIRECT_URI=https://<portal app address>/callback.php`
   - `SMS2_PORTAL_WEBHOOK_URL=https://<portal app address>/webhook.php`
   - `SMS2_PORTAL_WEBHOOK_SECRET=<webhook secret>`
   - `SMS2_CRON_TOKEN=<a fourth random value>`
5. **Push the SMS 2 changes** and redeploy both apps.
6. **Webhook timer** (HostForge has no cron): at cron-job.org, create a job every 1 minute.
   - URL: `https://<sms2 app address>/api/v1/index.php/internal/webhooks/run`
   - Method: POST
   - Header: `X-Cron-Token: <SMS2_CRON_TOKEN value>`
7. Open `https://<portal app address>/` and sign in with a student account.

## Security notes

- Access tokens (15 minutes) and refresh tokens (8 hours, rotated) live only in the PHP session on this server.
- The sign-in `state` is checked to stop login CSRF. Logout is a POST with a CSRF token, and it revokes the refresh token at SMS 2.
- Webhooks must carry a valid HMAC signature and a fresh timestamp. Duplicate event ids are ignored.
- `config/`, `src/`, `database/` and `storage/` are blocked from the web by `.htaccess`. Never commit `config/local.php`.
