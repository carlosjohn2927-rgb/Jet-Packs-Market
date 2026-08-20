# Troubleshooting — admin login & cPanel email

Two tools do the diagnosis for you. Both are CLI-only (they refuse to run over
HTTP) and both read `app/.env` themselves, so there is nothing to configure.

Open **cPanel → Terminal** (or SSH in), `cd` to the folder that contains
`app/` and `install/`, then:

```bash
php install/fix-admin.php --check     # diagnose, change nothing
php install/fix-admin.php             # diagnose and repair
php install/test-mail.php             # SMTP connect + auth test
php install/test-mail.php --to=you@gmail.com   # ...and send a real message
```

If cPanel gives you PHP 7.x by default, call the 8.x binary explicitly:
`/usr/local/bin/ea-php82 install/fix-admin.php`.

---

## 1. Admin login

Sign in at **https://halykpetroleum-kz.com/admin/login**

| | |
|---|---|
| Email | the value of `VP_ADMIN_EMAIL` in `app/.env` |
| Password | the value of `VP_ADMIN_PASSWORD` in `app/.env` |

(Those live only in `app/.env`, which is gitignored — deliberately never
committed. `cat app/.env | grep VP_ADMIN` on the server if you need to look
them up.)

`php install/fix-admin.php` checks and repairs all six things that make this
page reject you. Each one produces the same vague symptom — "nothing happens"
or "invalid credentials" — so guessing between them is a waste of time.

### 1.1 The account does not exist

You get "Invalid credentials" no matter what you type. This happens when the
schema was imported through phpMyAdmin and `install/install.php` was never
run, so `users` is empty.

*Fix:* `php install/fix-admin.php` creates the account as `SUPER_ADMIN`.
Alternatively `php install/install.php --users-only` creates it from the same
`VP_ADMIN_*` values without touching your content.

### 1.2 The password hash does not match

The row exists but was written by a different installer run, or the password
was changed in the admin UI and forgotten.

*Fix:* `php install/fix-admin.php` re-hashes `VP_ADMIN_PASSWORD` with bcrypt
and writes it back.

### 1.3 `isActive = 0`

This one is nastier than it looks. `Vp_auth::attempt()` refuses the login, and
`Vp_auth::user()` **force-logs-out** any existing session belonging to an
inactive user on the very next request. So even if you somehow got a session,
you are ejected immediately.

*Fix:* the tool sets `isActive = 1`.

### 1.4 `mustChangePassword = 1`

Login actually **succeeds**, but `Admin_Controller` then redirects every single
admin page to `admin/users/edit/<your-id>`. From the outside it looks like the
login silently failed or the dashboard is broken.

*Fix:* the tool clears the flag.

### 1.5 The role is not a staff role

`Auth::admin_login()` accepts the password, checks `is_staff()`, and logs you
straight back out with "insufficient permissions" if the role is `CUSTOMER`.
Staff roles are `SUPER_ADMIN`, `ADMIN`, `SALES`, `ENGINEER`, `EDITOR`.

*Fix:* the tool promotes the account to `SUPER_ADMIN`.

### 1.6 You are locked out by the rate limiter

**This is the one that catches people who are typing the correct password.**
After 5 failed attempts in 15 minutes, `Vp_auth::attempt()` returns `null`
*before it ever calls `password_verify()`*. Once you finally remember the right
password, it still fails, which convinces you the password is wrong and makes
you try more — extending the lockout.

The lockout is a JSON file under `app/assets/logs/ratelimit/`, keyed
`login:<your-ip>:<sha256 of the email>` with every character outside
`[A-Za-z0-9._-]` replaced by `_`.

*Fix:* the tool deletes them. By hand:

```bash
rm -f app/assets/logs/ratelimit/login_*
```

Waiting 15 minutes also clears it.

### 1.7 If it still loops back to the login page

Login succeeds but you bounce back to `/admin/login`. That is the **session**
being dropped, not authentication.

> New in this build: the app now **auto-creates `ci_sessions`** the first time
> anyone opens `/admin/login` (same schema as `install.sql`), and failed
> admin logins now say *why* — wrong credentials vs. temporarily locked vs.
> deactivated account — instead of one generic message. If the session table
> is missing and cannot be created automatically, the login page tells you so.

The tool checks all three causes:

- **`ci_sessions` is missing.** The app uses the `database` session driver, so
  no table means no session survives the redirect. The tool creates it with
  exactly the schema in `install/install.sql` (including the `primary_key`
  column CI3 needs when `sess_match_ip` is on).
- **`VP_COOKIE_DOMAIN` does not match `VP_BASE_URL`.** The browser silently
  discards the cookie. Ours is `.halykpetroleum-kz.com` against
  `https://halykpetroleum-kz.com/` — the leading dot covers both the apex and
  `www`, so that pair is correct. If you ever move the site to a subdomain,
  update both together.
- **`http://` base URL with secure cookies.** `VP_FORCE_HTTPS=1` marks cookies
  `Secure`; a browser will not send those over plain HTTP. Keep `VP_BASE_URL`
  on `https://`.

### 1.8 If your password is shorter than 8 characters

The current `VP_ADMIN_PASSWORD` is 7 characters. That is fine for signing in —
the login form only enforces `min_length[4]` — but the **admin user-editing
screen enforces `min_length[8]`**, so you cannot re-save a password that short
from inside the admin UI. Passwords under 8 characters can only be set by
`install/fix-admin.php`, by `install/install.php`, or by direct SQL.

Please change it to something long and unique once you are in
(**Admin → Users → your account**). Set the new value in `app/.env` as
`VP_ADMIN_PASSWORD` too, so the recovery tool stays in sync.

---

## 2. cPanel email (SMTP)

### 2.1 Why mail silently does nothing

`Mailer::dispatch()` picks a transport in this order:

1. **SMTP** — only when **both** `smtp_host` **and** `smtp_pass` are non-empty
2. **Resend** — only when `RESEND_API_KEY` is set
3. **`mail()`** — the fallback, with no error surfaced to the user

So a blank `VP_SMTP_PASS` does not raise an error. It quietly demotes you to
PHP `mail()`, which shared hosts routinely drop or spam-folder. This is the
single most common cause of "SMTP doesn't work", and it is the current state of
`app/.env`: everything is filled in **except the password**.

> New in this build: partial SMTP config is now **loud**:
> - it is written to the app log every time an email is skipped to `mail()`;
> - every transport-level failure creates an **in-app notification** for all
>   SUPER_ADMIN/ADMIN users (so the failure appears in the admin bell);
> - the **admin Dashboard** shows a red "Outgoing email needs attention"
>   banner with the transport in use, the failure count and the last error;
> - the `mail()` fallback now sets the envelope sender (`-f from@domain`), so
>   cPanel deliverability/SPF checks no longer silently discard those mails.

### 2.2 Finishing the setup

1. cPanel → **Email Accounts** → find or **Create** `no-reply@halykpetroleum-kz.com`.
2. **Manage** → set a password → copy it exactly.
3. Put it in `app/.env`:

   ```
   VP_SMTP_PASS=the-password-you-just-set
   ```

   No quotes needed. Avoid leading/trailing spaces — the loader trims the line
   but not inside quotes.
4. Verify:

   ```bash
   php install/test-mail.php --to=your-personal@gmail.com
   ```

The script resolves DNS, opens the socket, prints the SMTP banner, runs `EHLO`,
negotiates TLS if configured, performs a real `AUTH LOGIN`, and then sends the
message — printing every server reply, so a failure names the exact step.

### 2.3 Current values

```
VP_SMTP_HOST=mail.halykpetroleum-kz.com
VP_SMTP_PORT=465
VP_SMTP_USER=no-reply@halykpetroleum-kz.com
VP_SMTP_PASS=            <-- fill this in
VP_SMTP_CRYPTO=ssl
```

### 2.4 If port 465 is blocked

Some hosts firewall outbound 465/587. Because the site runs *on* the same
server as the mail server, you can hand mail to the local MTA instead, with no
authentication:

```
VP_SMTP_HOST=localhost
VP_SMTP_PORT=25
VP_SMTP_USER=no-reply@halykpetroleum-kz.com
VP_SMTP_PASS=anything-non-empty
VP_SMTP_CRYPTO=
```

`VP_SMTP_PASS` must stay non-empty — it is the flag that tells `Mailer` to take
the SMTP path at all, even though localhost:25 will not ask for it.

Port 587 with `VP_SMTP_CRYPTO=tls` is the other thing worth trying.

### 2.5 Mail arrives but lands in spam

cPanel → **Email Deliverability** → `halykpetroleum-kz.com` → install the
suggested **SPF** and **DKIM** records. Sending as `no-reply@` at your own
domain without those two is what triggers spam filters.

### 2.6 Where to look afterwards

Every send is recorded in the `email_logs` table (status, provider id, and the
error text on failure), and failures are also written to
`app/assets/logs/`. Admin → Email Log shows the same data in the UI.

---

## 3. Quick reference

| Symptom | Most likely cause | Command |
|---|---|---|
| "Invalid credentials" with the right password | rate-limit lockout (§1.6) | `php install/fix-admin.php` |
| Login works, every page redirects to a profile form | `mustChangePassword` (§1.4) | `php install/fix-admin.php` |
| Login works, bounced back to `/admin/login` | missing `ci_sessions` (§1.7) | `php install/fix-admin.php` |
| "Insufficient permissions" | non-staff role (§1.5) | `php install/fix-admin.php` |
| Forms submit, no email arrives | blank `VP_SMTP_PASS` (§2.1) | `php install/test-mail.php` |
| Email arrives in spam | missing SPF/DKIM (§2.5) | cPanel → Email Deliverability |
