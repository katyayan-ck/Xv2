# VDMS - Vehicle Dealership Management System

VDMS is a comprehensive Laravel-based system for managing vehicle dealership operations. It includes features for authentication, notifications, entity history tracking, document management, RBAC (Role-Based Access Control), data scoping, approvals, and more. Built with Backpack for admin CRUD, L5-Swagger for API documentation, Spatie packages for permissions/media, and custom services for business logic.

## Features
- **Authentication**: OTP-based login with rate limiting, account locking, and device binding.
- **Notifications**: FCM push, alerts, messages with threading.
- **Entity History**: Track status/communications for entities (bookings, quotes) or standalone (chats) with threaded comments, attachments, and notifications.
- **Document Management**: Upload/share/track documents with versioning, expiry notifications, AI tagging (Google Vision, toggleable), groups/caches, dynamic access (combos: role/dept/branch/scope), search (Scout), analytics.
- **RBAC & Scoping**: Role/permission management with data scopes (branch/location/dept/vertical/brand).
- **Approvals**: Hierarchical workflows with graph-based traversal.
- **API Documentation**: Multi-page Swagger (e.g., separate for docs/notifications).
- **Integrations**: Firebase, Google Cloud Vision, Excel exports, audits.

## Requirements
- PHP 8.2+
- Laravel 12+
- MySQL/MariaDB
- Composer
- Node.js/NPM (for assets if using frontend)
- Google Cloud account (for Vision AI—optional)
- Firebase project (for notifications)

## Installation Instructions

Follow these steps to set up VDMS locally. This assumes a fresh install; adjust for existing setups.

### Step 1: Clone the Repository
```bash
git clone https://your-repo-url/vdms.git
cd vdms
```

### Step 2: Install Dependencies
Run Composer to install PHP packages:
```bash
composer install
```

If using frontend (e.g., Backpack/Tabler theme):
```bash
npm install && npm run dev
```

### Step 3: Set Up Environment
Copy the example env file:
```bash
cp .env.example .env
```

Edit `.env`:
- Database: `DB_DATABASE=vdms`, `DB_USERNAME=root`, `DB_PASSWORD=`.
- App Key: Run `php artisan key:generate`.
- Sanctum: For API auth.
- Firebase: Add credentials (JSON path) for notifications.
- Google Vision: Add `GOOGLE_APPLICATION_CREDENTIALS=storage/app/google/key.json` (upload key.json first).
- Other: Mail, queue driver (e.g., database for expiries).

### Step 4: Database Setup
Create the database (e.g., via phpMyAdmin or CLI: `mysql -u root -p -e "CREATE DATABASE vdms;"`).

Import SQL dumps (from provided files, e.g., vdms.sql):
```bash
mysql -u root -p vdms < database/vdms.sql
```

Run seeds for initial data (roles, keyvalues, settings):
```bash
php artisan db:seed
```

### Step 5: Configure Backpack & Spatie
- Backpack: Run `php artisan backpack:install` if not done.
- Permissions: `php artisan migrate` (for Spatie tables if not in SQL).
- Media: Configure Spatie media in config/filesystems.php (e.g., disk 'public').
- Scout: For search, install Meilisearch/Algolia, run `php artisan scout:import "App\Models\Core\Document"`.

### Step 6: Set Up Google Cloud Vision (Optional for AI Tagging)
1. Create Google Cloud project, enable Vision API.
2. Generate service account key (JSON).
3. Upload to `storage/app/google/key.json`.
4. Toggle in DB: Insert into system_settings (key: 'ai_tagging_enabled', value: 1) or use admin panel.

### Step 7: Run the Application
```bash
php artisan serve
```
Access at http://localhost:8000. Admin: /admin (login with seeded user).

For API: http://localhost:8000/api/v1 (Swagger at /api/docs).

### Step 8: Additional Setup
- Cron for Expiry: In app/Console/Kernel.php, add schedule for daily checks (call DocService::checkExpiries()).
- Queue: `php artisan queue:work` for notifications/jobs.
- Testing: Run `php artisan test`.

## Usage
- **Admin Panel**: Use Backpack for CRUD (e.g., /admin/documents).
- **API**: See Swagger for endpoints (e.g., POST /api/v1/docs/upload).
- **Custom**: Use DocService in controllers for logic.

## Contributing
Fork, branch, PR. Follow Laravel standards.

## License
MIT. See LICENSE file.