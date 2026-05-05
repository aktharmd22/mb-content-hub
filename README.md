# Malayznbeat Content Hub

Content workflow management platform for the Malayznbeat team. Manages articles through 8 workflow stages with role-based access for sales, tech writers, tech lead, and admin.

## Requirements

- PHP 8.2+
- MySQL 8
- Composer
- Node.js 18+
- npm

## Local setup

**1. Clone and install dependencies**
```bash
cd malayzn-content-hub
composer install
npm install
```

**2. Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database credentials:
```
DB_DATABASE=malayzn_content_hub
DB_USERNAME=root
DB_PASSWORD=
```

**3. Create the database**

In XAMPP MySQL or any MySQL client:
```sql
CREATE DATABASE malayzn_content_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**4. Run migrations and seed**
```bash
php artisan migrate
php artisan db:seed
```

**5. Build assets**
```bash
npm run build
```

**6. Start the server**
```bash
php artisan serve
```

Visit http://localhost:8000 — login with `admin` / `admin123`.

## Default credentials

| Username | Password | Role |
|----------|----------|------|
| admin | admin123 | Admin |

## Roles

| Role | Access |
|------|--------|
| admin | Full access, user management, all articles |
| sales | Submit articles, track client approvals |
| tech_writer | Write and submit assigned articles |
| tech_lead | Review and approve articles |

## Development

Run the dev server with hot reload:
```bash
npm run dev
```

In a second terminal:
```bash
php artisan serve
```

## Tech stack

- **Backend:** Laravel 12, PHP 8.2
- **Frontend:** Blade, Livewire 4, Alpine.js, Tailwind CSS v3
- **Database:** MySQL 8
- **File storage:** Google Drive API (Module 3+)
- **Queue:** Database driver
