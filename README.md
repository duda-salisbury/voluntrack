# Voluntrack

Organization-scoped volunteer-hours tracking with Blade, Bootstrap 5, and HTMX.
The repository currently targets **Laravel 13 and PHP 8.3+** (see
[composer.json](composer.json)); the UX improvements do not change framework
versions or introduce a frontend framework.

## Run the updated application

With the existing dependencies installed:

```bash
php artisan migrate
php artisan storage:link
npm run build
```

The new migration adds `special_events` and a nullable `special_event_id` on
volunteer entries. Existing hours, locations, accounts, and organization settings
are retained. Back up your database before production migrations as usual.
Do not use `migrate:fresh` against existing data.

Set `APP_URL` to the public HTTPS origin. Configure a working Laravel mail
transport and `MAIL_FROM_ADDRESS` before sending invitations or password-reset
emails. A `log` or `array` mailer does **not** deliver email to recipients.
Invitation mail is sent synchronously; no queue worker is needed for it.
If environment configuration is cached, rebuild it after changing mail settings.
Log in at `/login` with an existing platform administrator account.

### Create a platform administrator

After running migrations:

```bash
php artisan createAdmin
```

Enter the administrator's name, email, and a password of at least eight
characters, then confirm the password. Password input is hidden and is hashed
before storage. The command creates a platform administrator with no organization
assignment; it never overwrites or promotes an existing account. Sign in at
`/login`, then invite organization administrators from the organization overview.

For Laravel Cloud's non-interactive remote command runner:

```bash
php artisan createAdmin --name="Platform Admin" --email="admin@example.com" --no-interaction
```

Both options are required with `--no-interaction`; missing or invalid values
produce an error and a nonzero exit code, without prompting. This mode generates
an unknown random password and sends a secure, expiring, single-use
password-setup link using Laravel's existing password broker and configured
mailer (Resend in production). No password is passed on the command line.
Locally, `--name` and `--email` can also prefill those values while retaining
the hidden password and confirmation prompts.

Configure mail delivery and the public HTTPS `APP_URL` before using remote
creation. Existing accounts, including archived accounts, are rejected without
changing their password or privileges. If email delivery fails after creation,
the command fails explicitly but preserves the created account; correct the mail
configuration and request a fresh link at `/account/password/forgot`, rather
than rerunning account creation. A log/array mailer does not deliver real email.

### Resend email delivery

The Resend PHP SDK is installed and Laravel's native `resend` transport is used
for administrator invitations and password resets. No SMTP credentials or custom
mail transport are needed.

Configure these values in your deployment environment or untracked `.env`:

```dotenv
APP_NAME=Voluntrack
APP_URL=https://voluntrack.js7y.io
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=app@voluntrack.js7y.io
MAIL_FROM_NAME=Voluntrack
RESEND_API_KEY=your-resend-api-key
```

Keep the real API key out of source control. Verify `voluntrack.js7y.io` in the
Resend dashboard and publish the DNS records Resend supplies. If this is a
domain-restricted sending key, grant access to that verified domain. The sender
must match a domain you have verified; changing `MAIL_FROM_ADDRESS` alone does
not verify it. `APP_URL` is also used for links in console-generated mail; use
the correct HTTPS URL, not localhost.

After adding the key or changing environment settings:

```bash
php artisan config:clear
# On production deployments that cache configuration:
php artisan config:cache
```

Send an administrator invitation or request a password reset, then check its
delivery status in Resend. Email is sent synchronously and API errors are not
silently redirected to a log mailer. A sent response means Resend accepted the
message, not that the recipient's mail server delivered it; inspect Resend's
delivery/bounce events when troubleshooting. Local development can explicitly
set `MAIL_MAILER=log` to avoid sending real messages.

## Complete QC NEMBA walkthrough

1. **Create the organization.** From the platform overview, choose **Create
   organization**. Enter `QC NEMBA`, customize the slug to `qcnemba`, and optionally
   set a logo and accent color. Saving opens its onboarding overview.
2. **Invite its administrator.** In the organization's **Administrators** section,
   enter the person's name and email and choose **Send secure invitation**. They
   receive an expiring, single-use password-setup link and then sign in at
   `/login`. Repeating the form can resend a link (subject to throttling).
   Existing accounts in this organization can be promoted. Accounts from another
   organization and platform administrator accounts are never reassigned here.
3. **Customize the form.** As the organization administrator, open **Settings &
   public form**. Change the heading, introduction, labels, submission text,
   confirmation message, logo, color, and supported behavior toggles. Save, then
   use **Preview Form**. A required name still does not require an account.
4. **Create locations.** Open **Locations**, add `Pachaug State Forest`, and leave
   it active. Import/source references are available under the advanced section.
5. **Create an optional event.** Open **Special events**, create `Fall Trail
   Cleanup 2026`, enter its date or date range, and leave it available on the
   public form. Its overview includes a preselected-event logging link.
6. **Share the form.** Copy the public link from **Overview** or **Settings &
   public form**: `/qcnemba/log-hours`. Existing `/qcnemba` bookmarks continue to
   work. Event links use `/qcnemba/log-hours?event=EVENT_ID`.
7. **Submit without an account.** Open the link in a private browser, choose the
   forest, enter four hours and a non-future work date, and optionally select the
   event. Leave the name blank for anonymous logging. The confirmation shows the
   recorded details and offers **Log Another Entry**.
8. **Submit ordinary hours.** Log another two hours with **Everyday volunteering /
   no event** selected. Events are never required. Creating an account remains
   optional; registered volunteers can view their own history.
9. **Review and report.** Sign in as the organization administrator. **Volunteer
   hours** supports reviewing, editing, administrative matching classification,
   and notes. **Reports** provides totals, location and event breakdowns, hours
   over time, recent submissions, and an estimated unique-volunteer count.
   Expand **Refine this view** to filter dates, locations, events, volunteer
   accounts or submitted names, sources, and matching classification.
10. **Check the arithmetic and isolation.** The two entries above total six hours:
    four event hours and two ordinary hours, all six at the location. Marking the
    four-hour entry as matching makes matching hours four; it does not increase
    the total. Every card and breakdown follows the same filters. Another
    organization's administrator cannot view or edit these contributions.

## Product behavior and limits

- Platform administrators manage organizations and access; organization
  administrators manage only their own active organization's data and settings.
- Events retain their historical entries when deactivated. Event dates describe
  the event; they do not prevent later entry of historical hours. Public entry
  dates cannot be in the future.
- Organization archiving preserves data and disables its public form and admin
  workspace. A location with recorded hours cannot be moved to another
  organization or deleted; deactivate it instead.
- Anonymous continuity uses a browser-stored UUID, not fingerprinting. Blocking
  storage does not prevent submission. Estimated volunteers use account IDs,
  browser identifiers, or normalized names, in that order. Shared names/devices
  can undercount; changed devices or later sign-in can overcount. Submissions
  with no usable identifier are reported separately.
- The public form supports home-screen shortcuts with an organization-specific
  web manifest. **An internet connection is required**; this is not an offline
  submission queue.
- The current account schema still has one `organization_id`. Multi-organization
  memberships, organization switching, and cross-organization account joining
  require a future membership model. This change does not silently move people
  between organizations or introduce an incomplete membership system.
- Copyable links and previews are provided. QR generation is intentionally
  deferred rather than sending organization URLs to an external QR service or
  adding a dependency for an optional feature.
- Bootstrap and HTMX retain their existing CDN loading. New application styles,
  copy controls, and continuity behavior are built locally by Vite.

## Validation

```bash
php artisan test
php artisan view:cache
npm run build
```

[Workflow feature tests](tests/Feature/VolunteerWorkflowTest.php) cover
onboarding, invitation activation/expiry, branding, anonymous and account-linked
submissions, event attribution, filtered report arithmetic, and organization
permissions. Browser checks use a separate temporary database, never production
or local application records.

---

## Laravel

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
