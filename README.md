# BCP Student Portal (separate deployment)

The student-facing portal for SMS 2. It runs as its **own app and deployment**. It never connects to the SMS 2 or CRAD database. Everything comes from the SMS 2 REST API:

- **Login:** the portal has its own login page. The student uses their SMS 2 account; the portal sends the password server-to-server to the SMS 2 API (`grant_type=password`), so SMS 2 still checks it with its lockout and Authenticator rules and the portal never stores passwords. Students with an Authenticator enter their 6-digit code (or an emailed code) on the portal too.
- **Data:** REST API v1 (`me`, research, members, milestones, submissions, feedback, defenses, clearance, announcements, notifications).
- **Live updates:** SMS 2 sends signed webhooks to `webhook.php`, for example "title approved", "defense rescheduled", "chapter reviewed" or "payment approved". Each one becomes a notification here.
- **Look:** same as the CRAD (SMS 2) student UI: top bar with clock, dark mode, notifications and user menu; grouped sidebar; Research Hub banner, progress ring and KPI tiles.
- **Actions in the portal:** every CRAD student step is done on `crad/step.php` through the SMS 2 API, so the student never has to open SMS 2: research group, title approval form, Chapters 1-3, progress updates, defense application, final manuscript, college payment and the signed research clearance (`me/research/*`; needs the matching SMS 2 update). CRAD officers, advisers, coordinators, panels and Admin still review everything in SMS 2.

The full contract is in the SMS 2 project: `docs/INTEGRATION-API.md`.

## Modules (one contract per module)

The portal is the student's single front door for every SMS 2 module. Each module has **one folder for its code** and **one folder for its pages**, so modules never mix:

```
index.php, login.php, logout.php, notifications.php,
announcements.php, profile.php, webhook.php, callback.php, open.php   shared portal pages

crad/                        CRAD (Research) pages the student opens
  index.php                    Research Workspace
  step.php                     every CRAD step (step.php?do=group, proposal, chapters, ...)
  defenses.php, clearance.php
<module>/                    pages of the next module (Registrar, Payments, ...)

src/core/                    shared code: bootstrap (session, API client, cache), layout, module loader
src/modules/crad/            module.php (contract), steps/<step>.php (one form per step), research-hub.php
src/modules/<module>/        module.php of each other module
assets/css/crad/, assets/js/crad/   CSS and JS used only by CRAD
```

`src/modules/<key>/module.php` returns the module's label, sidebar entries, student actions, the API prefix it uses, the webhook events it owns and how those events read as notifications. The full contract is written at the top of `src/core/modules.php`.

| Module | Code | Pages | Status |
|---|---|---|---|
| CRAD (Research) | `src/modules/crad/` | `crad/` | Live |
| Registrar | `src/modules/registrar/` | (none yet) | Stub, `enabled => false` |
| Payments | `src/modules/payment/` | (none yet) | Stub, `enabled => false` |
| Accreditation | `src/modules/accreditation/` | (none yet) | Stub, `enabled => false` |

To add a module: add its endpoints under its prefix in SMS 2 API v1 (and its webhook events), fill in `src/modules/<key>/module.php`, add its pages in `<key>/` (start each with `require_once __DIR__ . '/../src/core/bootstrap.php';` and the layout), then set `enabled` to `true`. The sidebar and webhook notifications pick it up automatically. Links in pages are relative to the portal root (the layout sets `<base href>`), so write `crad/step.php?do=group`, not `step.php?do=group`.

The old root links `research.php`, `step.php`, `defenses.php` and `clearance.php` still work: they redirect to `crad/`.

## When SMS 2 is down

Every successful read from the API is saved per student in `portal_api_cache`. If SMS 2 cannot be reached (no connection or a 5xx error), the portal shows that saved data with a banner giving the time it was last updated. Uploads and forms are not queued: the student is told nothing was sent and to try again later.

## Files

| Path | What it is |
|---|---|
| `index.php` | Dashboard |
| `announcements.php`, `notifications.php`, `profile.php` | Shared pages |
| `login.php`, `logout.php` | Portal login page (password checked by SMS 2) and sign-out |
| `callback.php` | Older SMS 2 single sign-on return page, kept for links that still use it |
| `crad/` | CRAD pages: `index.php` (Research Workspace), `step.php` (every step), `defenses.php`, `clearance.php` |
| `research.php`, `step.php`, `defenses.php`, `clearance.php` | Old links, redirect to `crad/` |
| `open.php` | Opens an SMS 2 page with the same login, for a module action that is not in the portal yet (none for CRAD now) |
| `webhook.php` | Receives SMS 2 webhooks |
| `src/core/bootstrap.php` | Session, API client, token refresh, last-known-status cache, portal database |
| `src/core/modules.php` | Module registry (the contract every module follows) |
| `src/core/layout.php` | Page layout |
| `src/modules/<key>/` | One folder per module: `module.php`, and for CRAD `steps/*.php` and `research-hub.php` |
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
- The login form has a CSRF token, and failed attempts are counted at SMS 2 per student IP (sent as `client_ip`) and per account. The SSO `state` is still checked on `callback.php`. Logout is a POST with a CSRF token, and it revokes the refresh token at SMS 2.
- Webhooks must carry a valid HMAC signature and a fresh timestamp. Duplicate event ids are ignored.
- `config/`, `src/`, `database/` and `storage/` are blocked from the web by `.htaccess`. Never commit `config/local.php`.
