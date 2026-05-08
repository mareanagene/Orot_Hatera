import os
from contextlib import closing
from functools import wraps
import json
from urllib import request as urlrequest
from urllib.error import URLError, HTTPError

import mysql.connector
from flask import Flask, jsonify, redirect, render_template, request, session, url_for
from werkzeug.security import check_password_hash, generate_password_hash


def load_dotenv(path: str = ".env") -> None:
    if not os.path.exists(path):
        return
    with open(path, "r", encoding="utf-8") as f:
        for line in f:
            raw = line.strip()
            if not raw or raw.startswith("#") or "=" not in raw:
                continue
            key, value = raw.split("=", 1)
            key = key.strip()
            value = value.strip().strip('"').strip("'")
            if key and key not in os.environ:
                os.environ[key] = value


def get_env(name: str, default: str = "") -> str:
    value = os.getenv(name, default).strip()
    return value


load_dotenv()

DB_CONFIG = {
    "host": get_env("DB_HOST", "db-mysql-nyc3-45861-do-user-37030471-0.f.db.ondigitalocean.com"),
    "port": int(get_env("DB_PORT", "25060")),
    "user": get_env("DB_USER", "doadmin"),
    "password": get_env("DB_PASSWORD", ""),
    "database": get_env("DB_NAME", "defaultdb"),
    "ssl_disabled": False,
}

app = Flask(__name__)
app.secret_key = get_env("FLASK_SECRET_KEY", "change-this-secret-key")
_db_ready = False
EDITABLE_SECTION_IDS = [
    "brand_title",
    "brand_tagline",
    "hero_title",
    "hero_image",
    "contact_title",
    "contact_name_placeholder",
    "contact_company_placeholder",
    "contact_phone_value",
    "live_title",
    "live_name_label",
    "live_details_label",
    "live_submit_label",
    "projects_title",
    "recent_projects_body",
    "switch_lang_button",
]
TRANSLATE_API_URL = get_env("TRANSLATE_API_URL", "")
TRANSLATE_API_KEY = get_env("TRANSLATE_API_KEY", "")
TRANSLATE_API_MODEL = get_env("TRANSLATE_API_MODEL", "gpt-4o-mini")


def get_connection():
    return mysql.connector.connect(**DB_CONFIG)


def init_db() -> None:
    with closing(get_connection()) as conn:
        with closing(conn.cursor()) as cur:
            cur.execute(
                """
                CREATE TABLE IF NOT EXISTS website_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(120) NOT NULL,
                    details TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
                """
            )
            cur.execute(
                """
                CREATE TABLE IF NOT EXISTS site_content (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    page_name VARCHAR(100) NOT NULL,
                    lang_code VARCHAR(10) NOT NULL DEFAULT 'he',
                    section_id VARCHAR(100) NOT NULL,
                    headline VARCHAR(255) NOT NULL,
                    body_text TEXT NULL,
                    image_url VARCHAR(500) NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_page_section_lang (page_name, lang_code, section_id),
                    KEY idx_page_name (page_name),
                    KEY idx_lang_code (lang_code),
                    KEY idx_section_id (section_id)
                ) ENGINE=InnoDB
                  DEFAULT CHARSET=utf8mb4
                  COLLATE=utf8mb4_unicode_ci
                """
            )
            cur.execute(
                """
                CREATE TABLE IF NOT EXISTS users (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    username VARCHAR(100) NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_users_username (username)
                ) ENGINE=InnoDB
                  DEFAULT CHARSET=utf8mb4
                  COLLATE=utf8mb4_unicode_ci
                """
            )
            cur.execute(
                """
                CREATE TABLE IF NOT EXISTS farm_cards (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    page_name VARCHAR(100) NOT NULL,
                    lang_code VARCHAR(10) NOT NULL DEFAULT 'he',
                    card_key VARCHAR(100) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    body_text TEXT NULL,
                    bg_color VARCHAR(20) NOT NULL DEFAULT '#223f69',
                    text_color VARCHAR(20) NOT NULL DEFAULT '#f4f7fb',
                    width_units TINYINT UNSIGNED NOT NULL DEFAULT 1,
                    sort_order INT NOT NULL DEFAULT 0,
                    image_url VARCHAR(500) NULL,
                    image_scale INT NOT NULL DEFAULT 100,
                    caption VARCHAR(255) NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_farm_cards_identity (page_name, lang_code, card_key),
                    KEY idx_farm_cards_page_lang (page_name, lang_code, sort_order)
                ) ENGINE=InnoDB
                  DEFAULT CHARSET=utf8mb4
                  COLLATE=utf8mb4_unicode_ci
                """
            )
            cur.execute(
                """
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                  AND TABLE_NAME = 'farm_cards'
                  AND COLUMN_NAME = 'card_type'
                """,
                (DB_CONFIG["database"],),
            )
            has_card_type = cur.fetchone()[0] > 0
            if not has_card_type:
                cur.execute("ALTER TABLE farm_cards ADD COLUMN card_type VARCHAR(20) NOT NULL DEFAULT 'farm' AFTER card_key")

            cur.execute(
                """
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                  AND TABLE_NAME = 'farm_cards'
                  AND COLUMN_NAME = 'row_group'
                """,
                (DB_CONFIG["database"],),
            )
            has_row_group = cur.fetchone()[0] > 0
            if not has_row_group:
                cur.execute("ALTER TABLE farm_cards ADD COLUMN row_group INT NOT NULL DEFAULT 1 AFTER sort_order")

            cur.execute(
                """
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                  AND TABLE_NAME = 'site_content'
                  AND COLUMN_NAME = 'lang_code'
                """,
                (DB_CONFIG["database"],),
            )
            has_lang_code = cur.fetchone()[0] > 0
            if not has_lang_code:
                cur.execute(
                    "ALTER TABLE site_content ADD COLUMN lang_code VARCHAR(10) NOT NULL DEFAULT 'he' AFTER page_name"
                )

            cur.execute(
                """
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = %s
                  AND TABLE_NAME = 'site_content'
                  AND INDEX_NAME = 'uq_page_section'
                """,
                (DB_CONFIG["database"],),
            )
            has_old_unique = cur.fetchone()[0] > 0
            if has_old_unique:
                cur.execute("ALTER TABLE site_content DROP INDEX uq_page_section")

            cur.execute(
                """
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = %s
                  AND TABLE_NAME = 'site_content'
                  AND INDEX_NAME = 'uq_page_section_lang'
                """,
                (DB_CONFIG["database"],),
            )
            has_new_unique = cur.fetchone()[0] > 0
            if not has_new_unique:
                cur.execute(
                    "ALTER TABLE site_content ADD UNIQUE KEY uq_page_section_lang (page_name, lang_code, section_id)"
                )

            cur.execute(
                """
                SELECT COUNT(*)
                FROM site_content
                WHERE page_name = %s
                  AND lang_code = %s
                  AND headline REGEXP '[א-ת]'
                """,
                ("farm_1", "he"),
            )
            he_with_hebrew = cur.fetchone()[0]
            if he_with_hebrew == 0:
                cur.execute(
                    """
                    UPDATE site_content
                    SET lang_code = 'en'
                    WHERE page_name = %s
                      AND lang_code = %s
                      AND NOT EXISTS (
                          SELECT 1
                          FROM (
                              SELECT section_id
                              FROM site_content
                              WHERE page_name = %s AND lang_code = %s
                          ) AS en_rows
                          WHERE en_rows.section_id = site_content.section_id
                      )
                    """,
                    ("farm_1", "he", "farm_1", "en"),
                )
            seed_rows = [
                ("farm_1", "he", "brand_title", "אורות הטירה בע\"מ", None, None),
                ("farm_1", "he", "brand_tagline", "מאירים את הדרך בבטחה", None, None),
                (
                    "farm_1",
                    "he",
                    "hero_title",
                    "פתרונות תאורת כבישים\nותשתיות מתקדמים",
                    None,
                    "/static/hero-reference.png",
                ),
                ("farm_1", "he", "contact_title", "צור קשר", None, None),
                ("farm_1", "he", "contact_name_placeholder", "איש קשר", None, None),
                ("farm_1", "he", "contact_company_placeholder", "חברה", None, None),
                ("farm_1", "he", "contact_phone_value", "08-638-2777", None, None),
                ("farm_1", "he", "live_title", "נתונים בזמן אמת", None, None),
                ("farm_1", "he", "live_name_label", "שם פרויקט", None, None),
                ("farm_1", "he", "live_details_label", "פירוט פרויקט", None, None),
                ("farm_1", "he", "live_submit_label", "הוסף נתון", None, None),
                ("farm_1", "he", "projects_title", "פרויקטים אחרונים", None, None),
                ("farm_1", "he", "recent_projects_body", "פרויקטים אחרונים", "אין עדיין פריטים.", None),
                ("farm_1", "he", "switch_lang_button", "English", None, None),
                ("farm_1", "en", "brand_title", "Orot HaTira Ltd", None, None),
                ("farm_1", "en", "brand_tagline", "Lighting the road safely", None, None),
                (
                    "farm_1",
                    "en",
                    "hero_title",
                    "Advanced highway lighting\nand infrastructure solutions",
                    None,
                    "/static/hero-reference.png",
                ),
                ("farm_1", "en", "contact_title", "Contact", None, None),
                ("farm_1", "en", "contact_name_placeholder", "Contact Person", None, None),
                ("farm_1", "en", "contact_company_placeholder", "Company", None, None),
                ("farm_1", "en", "contact_phone_value", "08-638-2777", None, None),
                ("farm_1", "en", "live_title", "Real-time Data", None, None),
                ("farm_1", "en", "live_name_label", "Project Name", None, None),
                ("farm_1", "en", "live_details_label", "Project Details", None, None),
                ("farm_1", "en", "live_submit_label", "Add Data", None, None),
                ("farm_1", "en", "projects_title", "Recent Projects", None, None),
                ("farm_1", "en", "recent_projects_body", "Recent Projects", "No items yet.", None),
                ("farm_1", "en", "switch_lang_button", "עברית", None, None),
            ]
            cur.executemany(
                """
                INSERT INTO site_content (page_name, lang_code, section_id, headline, body_text, image_url)
                VALUES (%s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                  headline = VALUES(headline),
                  body_text = VALUES(body_text),
                  image_url = VALUES(image_url)
                """,
                seed_rows,
            )
            cur.executemany(
                """
                INSERT INTO farm_cards (
                    page_name, lang_code, card_key, card_type, title, body_text, bg_color, text_color,
                    width_units, sort_order, row_group, image_url, image_scale, caption, is_active
                )
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                  card_type = VALUES(card_type),
                  title = VALUES(title),
                  body_text = VALUES(body_text),
                  bg_color = VALUES(bg_color),
                  text_color = VALUES(text_color),
                  width_units = VALUES(width_units),
                  sort_order = VALUES(sort_order),
                  row_group = VALUES(row_group),
                  image_url = VALUES(image_url),
                  image_scale = VALUES(image_scale),
                  caption = VALUES(caption),
                  is_active = VALUES(is_active)
                """,
                [
                    ("farm_1", "he", "contact", "farm", "צור קשר", "איש קשר\nחברה\n08-638-2777", "#223f69", "#f4f7fb", 1, 1, 1, None, 100, None, 1),
                    ("farm_1", "he", "live", "farm", "נתונים בזמן אמת", "שם פרויקט\nפירוט פרויקט", "#3b4048", "#f4f7fb", 1, 2, 1, None, 100, None, 1),
                    ("farm_1", "he", "projects", "farm", "פרויקטים אחרונים", "אין עדיין פריטים.", "#223f69", "#f4f7fb", 1, 3, 1, None, 100, None, 1),
                    ("farm_1", "en", "contact", "farm", "Contact", "Contact Person\nCompany\n08-638-2777", "#223f69", "#f4f7fb", 1, 1, 1, None, 100, None, 1),
                    ("farm_1", "en", "live", "farm", "Real-time Data", "Project Name\nProject Details", "#3b4048", "#f4f7fb", 1, 2, 1, None, 100, None, 1),
                    ("farm_1", "en", "projects", "farm", "Recent Projects", "No items yet.", "#223f69", "#f4f7fb", 1, 3, 1, None, 100, None, 1),
                ],
            )
            cur.execute("SELECT COUNT(*) FROM users")
            users_count = cur.fetchone()[0]
            if users_count == 0:
                default_username = get_env("ADMIN_USERNAME", "admin")
                default_password = get_env("ADMIN_PASSWORD", "admin123")
                cur.execute(
                    "INSERT INTO users (username, password_hash) VALUES (%s, %s)",
                    (default_username, generate_password_hash(default_password)),
                )
            conn.commit()


def get_page_content(page_name: str, lang_code: str) -> dict:
    content = {
        "brand_title": "",
        "brand_tagline": "",
        "hero_title": "",
        "hero_image_url": "",
        "contact_title": "",
        "contact_name_placeholder": "",
        "contact_company_placeholder": "",
        "contact_phone_value": "",
        "live_title": "",
        "live_name_label": "",
        "live_details_label": "",
        "live_submit_label": "",
        "projects_title": "",
        "recent_projects_body": "",
    }

    with closing(get_connection()) as conn:
        with closing(conn.cursor(dictionary=True)) as cur:
            cur.execute(
                """
                SELECT section_id, headline, body_text, image_url
                FROM site_content
                WHERE page_name = %s AND lang_code = %s
                ORDER BY id ASC
                """,
                (page_name, lang_code),
            )
            rows = cur.fetchall()

    for row in rows:
        section_id = (row.get("section_id") or "").strip()
        if not section_id:
            continue
        value = row.get("headline") or row.get("body_text") or ""
        content[section_id] = value
        if row.get("body_text"):
            content[f"{section_id}_body"] = row["body_text"]
        if row.get("image_url"):
            content[f"{section_id}_image_url"] = row["image_url"]
            if section_id in {"hero_title", "hero_image"}:
                content["hero_image_url"] = row["image_url"]

    if not content["recent_projects_body"]:
        content["recent_projects_body"] = content.get("projects_title_body", "")

    return content


def get_current_user():
    user_id = session.get("user_id")
    if not user_id:
        return None
    with closing(get_connection()) as conn:
        with closing(conn.cursor(dictionary=True)) as cur:
            cur.execute("SELECT id, username FROM users WHERE id = %s", (user_id,))
            return cur.fetchone()


def login_required(view_func):
    @wraps(view_func)
    def wrapper(*args, **kwargs):
        if not session.get("user_id"):
            return redirect(url_for("login", next=request.path))
        return view_func(*args, **kwargs)

    return wrapper


def editor_required(view_func):
    @wraps(view_func)
    def wrapper(*args, **kwargs):
        user = get_current_user()
        if not user:
            return redirect(url_for("login", next=request.path))
        if user["username"] not in {"sozy", "admin"}:
            return redirect(url_for("index"))
        return view_func(*args, **kwargs)

    return wrapper


def upsert_site_content_row(page_name: str, lang_code: str, section_id: str, headline: str, body_text: str, image_url: str):
    with closing(get_connection()) as conn:
        with closing(conn.cursor()) as cur:
            cur.execute(
                """
                INSERT INTO site_content (page_name, lang_code, section_id, headline, body_text, image_url)
                VALUES (%s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                  headline = VALUES(headline),
                  body_text = VALUES(body_text),
                  image_url = VALUES(image_url)
                """,
                (
                    page_name,
                    lang_code,
                    section_id,
                    headline or "",
                    body_text or None,
                    image_url or None,
                ),
            )
            conn.commit()


def get_farm_cards(page_name: str, lang_code: str) -> list[dict]:
    with closing(get_connection()) as conn:
        with closing(conn.cursor(dictionary=True)) as cur:
            cur.execute(
                """
                SELECT id, card_key, card_type, title, body_text, bg_color, text_color, width_units, sort_order, row_group,
                       image_url, image_scale, caption, is_active
                FROM farm_cards
                WHERE page_name = %s AND lang_code = %s
                ORDER BY row_group ASC, sort_order ASC, id ASC
                """,
                (page_name, lang_code),
            )
            return cur.fetchall()


def replace_farm_cards(page_name: str, lang_code: str, cards: list[dict]) -> None:
    with closing(get_connection()) as conn:
        with closing(conn.cursor()) as cur:
            cur.execute("DELETE FROM farm_cards WHERE page_name = %s AND lang_code = %s", (page_name, lang_code))
            for idx, card in enumerate(cards, start=1):
                cur.execute(
                    """
                    INSERT INTO farm_cards (
                        page_name, lang_code, card_key, card_type, title, body_text, bg_color, text_color,
                        width_units, sort_order, row_group, image_url, image_scale, caption, is_active
                    )
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                    """,
                    (
                        page_name,
                        lang_code,
                        card["card_key"],
                        card.get("card_type", "farm"),
                        card["title"],
                        card["body_text"],
                        card["bg_color"],
                        card["text_color"],
                        card["width_units"],
                        card.get("sort_order", idx),
                        card.get("row_group", 1),
                        card["image_url"] or None,
                        card["image_scale"],
                        card["caption"] or None,
                        1 if card.get("is_active", True) else 0,
                    ),
                )
            conn.commit()


def translate_text(text: str, source_lang: str, target_lang: str) -> str:
    if not text.strip():
        return text
    if not TRANSLATE_API_URL or not TRANSLATE_API_KEY:
        return text

    payload = {
        "model": TRANSLATE_API_MODEL,
        "messages": [
            {
                "role": "system",
                "content": f"You are a translation engine. Translate from {source_lang} to {target_lang}. Return only translated text.",
            },
            {"role": "user", "content": text},
        ],
        "temperature": 0.1,
    }
    req = urlrequest.Request(
        TRANSLATE_API_URL,
        data=json.dumps(payload).encode("utf-8"),
        headers={
            "Content-Type": "application/json",
            "Authorization": f"Bearer {TRANSLATE_API_KEY}",
        },
        method="POST",
    )
    try:
        with urlrequest.urlopen(req, timeout=30) as resp:
            data = json.loads(resp.read().decode("utf-8"))
            choices = data.get("choices") or []
            if choices:
                return (choices[0].get("message", {}).get("content") or text).strip()
    except (URLError, HTTPError, TimeoutError, ValueError):
        return text
    return text


def translate_page_content(page_name: str, source_lang: str, target_lang: str) -> None:
    with closing(get_connection()) as conn:
        with closing(conn.cursor(dictionary=True)) as cur:
            cur.execute(
                """
                SELECT section_id, headline, body_text, image_url
                FROM site_content
                WHERE page_name = %s AND lang_code = %s
                ORDER BY id ASC
                """,
                (page_name, source_lang),
            )
            rows = cur.fetchall()

    for row in rows:
        section_id = (row.get("section_id") or "").strip()
        if not section_id:
            continue
        upsert_site_content_row(
            page_name=page_name,
            lang_code=target_lang,
            section_id=section_id,
            headline=translate_text(row.get("headline") or "", source_lang, target_lang),
            body_text=translate_text(row.get("body_text") or "", source_lang, target_lang) if row.get("body_text") else None,
            image_url=row.get("image_url") or None,
        )

    source_cards = get_farm_cards(page_name, source_lang)
    translated_cards = []
    for card in source_cards:
        translated_cards.append(
            {
                **card,
                "title": translate_text(card.get("title") or "", source_lang, target_lang),
                "body_text": translate_text(card.get("body_text") or "", source_lang, target_lang),
                "caption": translate_text(card.get("caption") or "", source_lang, target_lang) if card.get("caption") else "",
            }
        )
    replace_farm_cards(page_name, target_lang, translated_cards)


@app.before_request
def ensure_db_ready() -> None:
    global _db_ready
    if not _db_ready:
        init_db()
        _db_ready = True


@app.route("/")
def index():
    lang = (request.args.get("lang") or "he").strip().lower()
    if lang not in {"he", "en"}:
        lang = "he"
    content = get_page_content("farm_1", lang)
    farm_cards = get_farm_cards("farm_1", lang)
    projects = [line.strip() for line in content.get("recent_projects_body", "").splitlines() if line.strip()]
    toggle_lang = "en" if lang == "he" else "he"
    page_dir = "rtl" if lang == "he" else "ltr"
    return render_template(
        "index.html",
        content=content,
        projects=projects,
        farm_cards=farm_cards,
        current_lang=lang,
        toggle_lang=toggle_lang,
        page_dir=page_dir,
        current_user=get_current_user(),
    )


@app.route("/login", methods=["GET", "POST"])
def login():
    if session.get("user_id"):
        return redirect(url_for("index"))

    error = ""
    next_url = request.args.get("next") or url_for("index")
    if request.method == "POST":
        username = (request.form.get("username") or "").strip()
        password = request.form.get("password") or ""
        with closing(get_connection()) as conn:
            with closing(conn.cursor(dictionary=True)) as cur:
                cur.execute(
                    "SELECT id, username, password_hash FROM users WHERE username = %s",
                    (username,),
                )
                user = cur.fetchone()
        if user and check_password_hash(user["password_hash"], password):
            session["user_id"] = user["id"]
            return redirect(next_url)
        error = "Invalid username or password."

    return render_template("login.html", error=error)


@app.get("/logout")
def logout():
    session.clear()
    return redirect(url_for("login"))


@app.route("/users", methods=["GET", "POST"])
@login_required
def manage_users():
    error = ""
    success = ""
    if request.method == "POST":
        username = (request.form.get("username") or "").strip()
        password = request.form.get("password") or ""
        if not username or len(password) < 6:
            error = "Username is required and password must be at least 6 characters."
        else:
            with closing(get_connection()) as conn:
                with closing(conn.cursor()) as cur:
                    try:
                        cur.execute(
                            "INSERT INTO users (username, password_hash) VALUES (%s, %s)",
                            (username, generate_password_hash(password)),
                        )
                        conn.commit()
                        success = "User created successfully."
                    except mysql.connector.IntegrityError:
                        error = "Username already exists."

    with closing(get_connection()) as conn:
        with closing(conn.cursor(dictionary=True)) as cur:
            cur.execute("SELECT id, username, created_at FROM users ORDER BY id DESC")
            users = cur.fetchall()

    return render_template("users.html", users=users, error=error, success=success, current_user=get_current_user())


@app.route("/editor", methods=["GET", "POST"])
@editor_required
def editor():
    current_user = get_current_user()
    message = ""
    page_name = (request.values.get("page_name") or "farm_1").strip() or "farm_1"
    lang_code = (request.values.get("lang") or "he").strip().lower()
    if lang_code not in {"he", "en"}:
        lang_code = "he"

    if request.method == "POST":
        if request.form.get("action") == "translate_to_en":
            translate_page_content(page_name=page_name, source_lang="he", target_lang="en")
            message = "Hebrew page content translated and synced to English."
            lang_code = "en"

        if request.form.get("action") == "save":
            auto_translate = request.form.get("auto_translate") == "on"
            target_lang = "en" if lang_code == "he" else "he"

            for section_id in EDITABLE_SECTION_IDS:
                raw_value = request.form.get(section_id, "")
                raw_value = raw_value.strip()
                headline = raw_value
                body_text = None
                image_url = None
                if section_id == "recent_projects_body":
                    headline = request.form.get("projects_title", "").strip() or raw_value
                    body_text = raw_value
                if section_id == "hero_image":
                    headline = "hero_image"
                    image_url = raw_value

                upsert_site_content_row(
                    page_name=page_name,
                    lang_code=lang_code,
                    section_id=section_id,
                    headline=headline,
                    body_text=body_text,
                    image_url=image_url,
                )
                if auto_translate:
                    translated_headline = translate_text(headline, lang_code, target_lang) if headline else ""
                    translated_body = translate_text(body_text, lang_code, target_lang) if body_text else None
                    upsert_site_content_row(
                        page_name=page_name,
                        lang_code=target_lang,
                        section_id=section_id,
                        headline=translated_headline,
                        body_text=translated_body,
                        image_url=image_url,
                    )
            card_count = int(request.form.get("cards_count") or "0")
            cards_payload = []
            for i in range(card_count):
                card_key = (request.form.get(f"card_key_{i}") or f"card_{i+1}").strip() or f"card_{i+1}"
                card_type = (request.form.get(f"card_type_{i}") or "farm").strip().lower()
                if card_type not in {"farm", "text", "image"}:
                    card_type = "farm"
                title = (request.form.get(f"card_title_{i}") or "").strip()
                body_text = (request.form.get(f"card_body_{i}") or "").strip()
                bg_color = (request.form.get(f"card_bg_{i}") or "#223f69").strip()
                text_color = (request.form.get(f"card_text_{i}") or "#f4f7fb").strip()
                width_units = int((request.form.get(f"card_width_{i}") or "1").strip() or "1")
                width_units = max(1, min(3, width_units))
                sort_order = int((request.form.get(f"card_sort_{i}") or str(i + 1)).strip() or str(i + 1))
                row_group = int((request.form.get(f"card_row_{i}") or "1").strip() or "1")
                row_group = max(1, row_group)
                image_url = (request.form.get(f"card_image_{i}") or "").strip()
                image_scale = int((request.form.get(f"card_scale_{i}") or "100").strip() or "100")
                image_scale = max(30, min(200, image_scale))
                caption = (request.form.get(f"card_caption_{i}") or "").strip()
                is_active = request.form.get(f"card_active_{i}") == "on"
                cards_payload.append(
                    {
                        "card_key": card_key,
                        "card_type": card_type,
                        "title": title,
                        "body_text": body_text,
                        "bg_color": bg_color,
                        "text_color": text_color,
                        "width_units": width_units,
                        "sort_order": sort_order,
                        "row_group": row_group,
                        "image_url": image_url,
                        "image_scale": image_scale,
                        "caption": caption,
                        "is_active": is_active,
                    }
                )
            replace_farm_cards(page_name, lang_code, cards_payload)
            if auto_translate:
                translated_cards = []
                for card in cards_payload:
                    translated_cards.append(
                        {
                            **card,
                            "title": translate_text(card["title"], lang_code, target_lang),
                            "body_text": translate_text(card["body_text"], lang_code, target_lang),
                            "caption": translate_text(card["caption"], lang_code, target_lang),
                        }
                    )
                replace_farm_cards(page_name, target_lang, translated_cards)
            message = "Saved successfully."

    with closing(get_connection()) as conn:
        with closing(conn.cursor(dictionary=True)) as cur:
            cur.execute("SELECT DISTINCT page_name FROM site_content ORDER BY page_name ASC")
            pages = [r["page_name"] for r in cur.fetchall()]
    if page_name not in pages:
        pages.append(page_name)
        pages.sort()

    content = get_page_content(page_name, lang_code)
    farm_cards = get_farm_cards(page_name, lang_code)
    if not content.get("hero_image"):
        content["hero_image"] = content.get("hero_image_url", "")

    return render_template(
        "editor.html",
        current_user=current_user,
        message=message,
        content=content,
        pages=pages,
        page_name=page_name,
        lang_code=lang_code,
        farm_cards=farm_cards,
    )


@app.get("/api/items")
def get_items():
    with closing(get_connection()) as conn:
        with closing(conn.cursor(dictionary=True)) as cur:
            cur.execute(
                """
                SELECT id, title, details, created_at
                FROM website_items
                ORDER BY id DESC
                """
            )
            rows = cur.fetchall()
    return jsonify(rows)


@app.post("/api/items")
@login_required
def create_item():
    payload = request.get_json(silent=True) or {}
    title = (payload.get("title") or "").strip()
    details = (payload.get("details") or "").strip()

    if not title or not details:
        return jsonify({"error": "title and details are required"}), 400

    with closing(get_connection()) as conn:
        with closing(conn.cursor()) as cur:
            cur.execute(
                "INSERT INTO website_items (title, details) VALUES (%s, %s)",
                (title, details),
            )
            conn.commit()

    return jsonify({"ok": True}), 201


@app.delete("/api/items/<int:item_id>")
@login_required
def delete_item(item_id: int):
    with closing(get_connection()) as conn:
        with closing(conn.cursor()) as cur:
            cur.execute("DELETE FROM website_items WHERE id = %s", (item_id,))
            conn.commit()
            deleted = cur.rowcount

    if deleted == 0:
        return jsonify({"error": "item not found"}), 404
    return jsonify({"ok": True})


if __name__ == "__main__":
    init_db()
    port = int(get_env("FLASK_PORT", "5500"))
    app.run(host="127.0.0.1", port=port, debug=True)
