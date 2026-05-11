# Orot HaTira CMS Website

A Laravel + MySQL web platform for managing a dynamic Hebrew infrastructure website.

The system includes a public site, secure login, user management, contact lead capture, and a CMS editor for homepage, team, and projects content.

---

## Features

- Public pages:
  - Homepage (`/`)
  - Projects page (`/projects`)
  - Team page (`/team`)
- Admin/CMS pages:
  - Main editor (`/editor`)
  - Team editor (`/editor/team`)
  - Projects editor (`/editor/projects`)
  - Contact inquiries viewer (`/editor/contacts`)
  - User management (`/users`)
- API endpoints:
  - `POST /api/contact`
  - `POST /api/upload-image`
  - `GET /api/items`
- MySQL-backed content for homepage, team members, projects, and inquiries
- Session-based authentication with admin-only access control
- Legacy user password hash compatibility, with automatic upgrade on successful login

---

## Tech Stack

- PHP 8.4
- Laravel 13
- MySQL
- Blade templates
- Vanilla JavaScript + CSS

---

## Project Structure

```text
.
|-- php-backend/
|   |-- app/
|   |-- bootstrap/
|   |-- config/
|   |-- resources/views/
|   |-- routes/
|   |-- tests/
|   `-- ...
|-- sql/
|   `-- setup_site_content.sql
|-- static/
|   |-- app.js
|   |-- styles.css
|   `-- ...
|-- php.ini
`-- composer.phar
```

---

## Getting Started

### 1) Install dependencies

```bash
cd php-backend
composer install
```

If `composer` is not installed globally on Windows, you can use the local bootstrap:

```bash
cd php-backend
php -c ..\php.ini ..\composer.phar install
```

### 2) Configure environment

Use `php-backend/.env.example` as the base for `php-backend/.env`, then set your MySQL connection:

```env
APP_URL=http://127.0.0.1:8000
APP_LOCALE=he

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=25060
DB_DATABASE=defaultdb
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=file
SESSION_LIFETIME=720
```

### 3) Generate the application key

```bash
cd php-backend
php artisan key:generate
```

### 4) Run the app

```bash
cd php-backend
php artisan serve --host=127.0.0.1 --port=8000
```

Open:
- Home: `http://127.0.0.1:8000/`
- Projects: `http://127.0.0.1:8000/projects`
- Team: `http://127.0.0.1:8000/team`
- Login: `http://127.0.0.1:8000/login`
- Users: `http://127.0.0.1:8000/users`
- Editor: `http://127.0.0.1:8000/editor`
- Team Editor: `http://127.0.0.1:8000/editor/team`
- Projects Editor: `http://127.0.0.1:8000/editor/projects`
- Contact Inquiries: `http://127.0.0.1:8000/editor/contacts`

---

## Testing

```bash
cd php-backend
vendor/phpunit/phpunit/phpunit --configuration phpunit.xml
```

---

## Security Notes

- `.env` files are ignored via `.gitignore`
- Never commit secrets or API keys
- Passwords are stored as hashes
- Existing legacy `scrypt` hashes are supported during migration
- Use HTTPS and secure session settings in production

---

## License

Private/internal project (update this section if you want to publish under a specific license).
