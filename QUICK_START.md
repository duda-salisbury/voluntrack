# Voluntrack Quick Start Guide

## Development Server

Start the development server:
```bash
php artisan serve
```

The app will be available at: http://localhost:8000

## Public Interface

### Log Volunteer Hours
**URL**: http://localhost:8000

Anyone can log hours without authentication:
1. Select a location from the dropdown
2. Choose the date you volunteered
3. Enter hours (0.25 increments)
4. Optionally add your name
5. Submit

The form uses HTMX for inline success messages - no page reload needed.

## Admin Interface

### Login
**URL**: http://localhost:8000/admin/login

**Credentials**:
- Email: `admin@voluntrack.test`
- Password: `password`

### Dashboard
**URL**: http://localhost:8000/admin/dashboard

Features:
- **Stats Overview**: Quick metrics at the top
- **Filters**: Location, date range, matched status
- **Entries Table**: All logged hours with inline actions

### Inline Actions (HTMX)

**Toggle Match Status**:
- Click the "Matched" (green) or "Unmatched" (gray) badge
- Status toggles instantly without page reload
- Badge color changes to reflect new state

**Edit Notes**:
- Click in the notes textarea
- Type your notes
- Tab out or click elsewhere
- Notes auto-save without page reload

### Full Edit/Delete

**Edit Entry**:
- Click "Edit" button on any row
- Modify any field
- Click "Update Entry" to save

**Delete Entry**:
- Click "Delete" button on any row
- Confirm the deletion
- Entry is removed permanently

**Add New Entry**:
- Click "Add New Entry" button at top of dashboard
- Fill in all required fields
- Submit to create

## Database

The app uses SQLite for simplicity. Database file:
```
database/database.sqlite
```

### Reset Database
```bash
php artisan migrate:fresh --seed
```

This will:
- Drop all tables
- Re-run all migrations
- Seed locations and admin user

### Update Admin User
If you need to manually set admin flag:
```bash
php artisan tinker

# In tinker:
$user = User::where('email', 'admin@voluntrack.test')->first();
$user->is_admin = true;
$user->save();
```

## Sample Locations

The seeder creates these locations:
- Riverside Park
- Forest Trail Network
- Community Garden
- Historic Mill Pathway
- Lakeside Conservation Area

You can add more in the admin interface or by editing:
```
database/seeders/LocationSeeder.php
```

## Building Assets

The app uses Vite to compile custom CSS:
```bash
npm run build  # For production
npm run dev    # For development with hot reload
```

Bootstrap 5 and HTMX are loaded via CDN, not through Vite.

## Troubleshooting

### Can't login as admin
Make sure the is_admin flag is set:
```bash
php artisan tinker --execute="\$user = User::where('email', 'admin@voluntrack.test')->first(); \$user->is_admin = true; \$user->save();"
```

### HTMX not working
1. Check browser console for errors
2. Ensure HTMX CDN is loading: view source and check the script tag
3. Clear browser cache

### CSS not updating
```bash
npm run build
```
Then hard refresh browser (Ctrl+Shift+R)

### Database errors
Reset the database:
```bash
php artisan migrate:fresh --seed
```

## File Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── EntryController.php
│   │   │   └── LoginController.php
│   │   └── VolunteerHourController.php
│   └── Middleware/
│       └── AdminMiddleware.php
├── Models/
│   ├── Location.php
│   ├── User.php
│   └── VolunteerEntry.php

resources/
├── css/
│   └── app.css              # Design system
├── views/
│   ├── admin/
│   │   ├── dashboard.blade.php
│   │   ├── login.blade.php
│   │   ├── entries/
│   │   │   ├── create.blade.php
│   │   │   └── edit.blade.php
│   │   └── partials/
│   │       └── entry-row.blade.php
│   ├── hours/
│   │   ├── log.blade.php
│   │   └── partials/
│   │       ├── form.blade.php
│   │       └── success-inline.blade.php
│   └── layouts/
│       └── app.blade.php

routes/
└── web.php                  # All routes

database/
├── migrations/
│   ├── 2026_04_17_004028_create_locations_table.php
│   ├── 2026_04_17_004034_create_volunteer_entries_table.php
│   ├── 2026_04_17_010047_add_notes_to_volunteer_entries_table.php
│   └── 2026_04_17_010113_add_is_admin_to_users_table.php
└── seeders/
    ├── DatabaseSeeder.php
    └── LocationSeeder.php
```

## Design Tokens

Available CSS variables in `resources/css/app.css`:

**Colors**:
- `--color-primary`: Forest green (#16a34a)
- `--color-bg`: Deep slate (#0f172a)
- `--color-bg-secondary`: Medium slate (#1e293b)
- `--color-bg-tertiary`: Light slate (#334155)

**Spacing**: `--space-1` through `--space-10` (0.25rem to 10rem)

**Typography**: 
- `--font-sans`: Instrument Sans
- `--text-xs` through `--text-4xl`

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile Safari (iOS 13+)
- Chrome Mobile (Android 8+)

HTMX requires JavaScript enabled.

---

**Need Help?** Check ADMIN_IMPLEMENTATION.md for technical details.
