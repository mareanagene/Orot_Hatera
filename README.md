# Orot HaTira CMS Website

A Flask + MySQL web platform for managing a dynamic, bilingual (Hebrew/English) infrastructure website.

The system includes a public site, secure login, user management, and a visual CMS editor with preview/review workflow before saving to production content.

---

## Features

- Dynamic homepage content from MySQL (no hardcoded page text)
- Visual CMS editor (`/editor`) with:
  - Preview (editor-only, not saved)
  - Review (field-by-field changes)
  - Save to DB / Discard changes
  - Per-block collapse / delete controls
- Fast page creation from the editor (`+ New Page`)
- Dynamic block system:
  - `farm` blocks
  - `text` blocks
  - `image` blocks
  - `heading` blocks
  - `divider / bar` blocks
- Layout controls per block:
  - row group, width, order, colors, image scale, image horizontal position, image roundness, caption
- Image workflow:
  - upload from local computer (not URL-only)
  - URL input still supported
- User authentication and session-based access
- User management page (`/users`)
- Bilingual support (`he` / `en`)
- Full-page translation action (`Translate HE -> EN`) via external API
- Secure password storage (hashed)

---

## Tech Stack

- Python (Flask)
- MySQL (`mysql-connector-python`)
- Jinja2 templates
- Vanilla JavaScript + CSS
- Werkzeug security utilities

---

## Project Structure

```text
.
|-- app.py
|-- db.py
|-- requirements.txt
|-- .env.example
|-- sql/
|   `-- setup_site_content.sql
|-- static/
|   |-- app.js
|   |-- styles.css
|   `-- ...
`-- templates/
    |-- index.html
    |-- editor.html
    |-- login.html
    `-- users.html
```

---

## Getting Started

### 1) Install dependencies

```bash
pip install -r requirements.txt
```

### 2) Configure environment

Create `.env` from `.env.example` and set your values:

```env
DB_HOST=...
DB_PORT=25060
DB_USER=...
DB_PASSWORD=...
DB_NAME=defaultdb
FLASK_PORT=5500
FLASK_SECRET_KEY=replace_with_strong_secret

# Translation API (OpenAI-compatible endpoint)
TRANSLATE_API_URL=
TRANSLATE_API_KEY=
TRANSLATE_API_MODEL=gpt-4o-mini

# Optional default admin bootstrap
ADMIN_USERNAME=admin
ADMIN_PASSWORD=admin123
```

### 3) Run the app

```bash
python app.py
```

Open:
- Home: `http://127.0.0.1:5500/`
- Login: `http://127.0.0.1:5500/login`
- Users: `http://127.0.0.1:5500/users`
- Editor: `http://127.0.0.1:5500/editor`

---

## CMS Workflow

1. Edit content/blocks in `/editor`
2. Click **Preview (Editor only)** to see visual changes without DB save
3. Click **Review** to inspect exact before/after field changes (field-level diff)
4. Choose:
   - **Save DB** (publish to DB / official site)
   - **Discard Changes** (reset unsaved edits)

Optional editor actions:
- **Translate HE -> EN (Full Page)** to sync all text/blocks into English
- **+ New Page** to create a new page namespace and start editing its content

---

## Translation Flow

The editor supports full-page translation from Hebrew to English:

- Button: **Translate HE -> EN (Full Page)**
- Translates:
  - page sections (`site_content`)
  - dynamic blocks (`farm_cards`)

If API values are missing, translation falls back to original text.

---

## Security Notes

- `.env` is ignored via `.gitignore`
- Never commit secrets or API keys
- Passwords are stored as hashes
- Change default admin credentials in production
- Use HTTPS and secure session settings for production deployments

---

## Git Safety

Before pushing:
- verify `.env` is not tracked
- rotate any credentials that were ever exposed
- keep only `.env.example` in repository

---

## License

Private/internal project (update this section if you want to publish under a specific license).
