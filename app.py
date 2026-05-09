import os
from contextlib import closing
from functools import wraps
import json
from uuid import uuid4
import time

import mysql.connector
from flask import Flask, jsonify, redirect, render_template, request, session, url_for
from werkzeug.security import check_password_hash, generate_password_hash
from werkzeug.utils import secure_filename


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

DEFAULT_HERO_IMAGE_URL = (
    "https://d3m9l0v76dty0.cloudfront.net/system/photos/12981922/original/"
    "4ff5da7517599bc31bc3f8880056e880.jpg"
)

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
]
ALLOWED_UPLOAD_EXTENSIONS = {"png", "jpg", "jpeg", "gif", "webp"}


def debug_log(run_id: str, hypothesis_id: str, location: str, message: str, data: dict) -> None:
    payload = {
        "sessionId": "567fb0",
        "runId": run_id,
        "hypothesisId": hypothesis_id,
        "location": location,
        "message": message,
        "data": data,
        "timestamp": int(time.time() * 1000),
    }
    with open("debug-567fb0.log", "a", encoding="utf-8") as f:
        f.write(json.dumps(payload, ensure_ascii=False) + "\n")


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
                    image_x INT NOT NULL DEFAULT 0,
                    image_radius INT NOT NULL DEFAULT 0,
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
                  AND TABLE_NAME = 'farm_cards'
                  AND COLUMN_NAME = 'image_x'
                """,
                (DB_CONFIG["database"],),
            )
            has_image_x = cur.fetchone()[0] > 0
            if not has_image_x:
                cur.execute("ALTER TABLE farm_cards ADD COLUMN image_x INT NOT NULL DEFAULT 0 AFTER image_scale")
            cur.execute(
                """
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                  AND TABLE_NAME = 'farm_cards'
                  AND COLUMN_NAME = 'image_radius'
                """,
                (DB_CONFIG["database"],),
            )
            has_image_radius = cur.fetchone()[0] > 0
            if not has_image_radius:
                cur.execute("ALTER TABLE farm_cards ADD COLUMN image_radius INT NOT NULL DEFAULT 0 AFTER image_x")

            cur.execute(
                """
                CREATE TABLE IF NOT EXISTS org_team_members (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    page_name VARCHAR(100) NOT NULL DEFAULT 'farm_1',
                    lang_code VARCHAR(10) NOT NULL DEFAULT 'he',
                    tier_index INT NOT NULL DEFAULT 0,
                    slot_index INT NOT NULL DEFAULT 0,
                    full_name VARCHAR(160) NOT NULL DEFAULT '',
                    role_title VARCHAR(255) NOT NULL DEFAULT '',
                    role_detail TEXT NULL,
                    image_url VARCHAR(500) NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_org_team_slot (page_name, lang_code, tier_index, slot_index),
                    KEY idx_org_team_page (page_name, lang_code, tier_index, slot_index)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                """
            )

            cur.execute(
                """
                CREATE TABLE IF NOT EXISTS contact_inquiries (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    full_name VARCHAR(160) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    phone VARCHAR(40) NULL,
                    note TEXT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_contact_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                """
            )

            cur.execute(
                """
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                  AND TABLE_NAME = 'contact_inquiries'
                  AND COLUMN_NAME = 'phone'
                """,
                (DB_CONFIG["database"],),
            )
            has_contact_phone = cur.fetchone()[0] > 0
            if not has_contact_phone:
                cur.execute(
                    "ALTER TABLE contact_inquiries ADD COLUMN phone VARCHAR(40) NULL AFTER email"
                )

            cur.execute(
                """
                CREATE TABLE IF NOT EXISTS portfolio_projects (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    page_name VARCHAR(100) NOT NULL DEFAULT 'farm_1',
                    lang_code VARCHAR(10) NOT NULL DEFAULT 'he',
                    sort_order INT NOT NULL DEFAULT 0,
                    title VARCHAR(255) NOT NULL,
                    summary VARCHAR(500) NOT NULL DEFAULT '',
                    body_text TEXT NULL,
                    image_url VARCHAR(500) NULL,
                    gallery_json TEXT NULL,
                    PRIMARY KEY (id),
                    KEY idx_portfolio_page_lang (page_name, lang_code, sort_order)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                """
            )

            cur.execute(
                """
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                  AND TABLE_NAME = 'portfolio_projects'
                  AND COLUMN_NAME = 'gallery_json'
                """,
                (DB_CONFIG["database"],),
            )
            has_portfolio_gallery = cur.fetchone()[0] > 0
            if not has_portfolio_gallery:
                cur.execute(
                    "ALTER TABLE portfolio_projects ADD COLUMN gallery_json TEXT NULL AFTER image_url"
                )
            cur.execute(
                """
                UPDATE portfolio_projects
                SET gallery_json = JSON_ARRAY(image_url)
                WHERE (gallery_json IS NULL OR gallery_json = '')
                  AND image_url IS NOT NULL
                  AND TRIM(image_url) <> ''
                """
            )

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

            seed_rows = [
                ("farm_1", "he", "brand_title", "אורות הטירה בע\"מ", None, None),
                ("farm_1", "he", "brand_tagline", "מאירים את הדרך בבטחה", None, None),
                (
                    "farm_1",
                    "he",
                    "hero_title",
                    "פתרונות תאורת כבישים\nותשתיות מתקדמים",
                    None,
                    DEFAULT_HERO_IMAGE_URL,
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
                    width_units, sort_order, row_group, image_url, image_scale, image_x, image_radius, caption, is_active
                )
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
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
                  image_x = VALUES(image_x),
                  image_radius = VALUES(image_radius),
                  caption = VALUES(caption),
                  is_active = VALUES(is_active)
                """,
                [
                    ("farm_1", "he", "contact", "farm", "צור קשר", "איש קשר\nחברה\n08-638-2777", "#223f69", "#f4f7fb", 1, 1, 1, None, 100, 0, 0, None, 1),
                    ("farm_1", "he", "live", "farm", "נתונים בזמן אמת", "שם פרויקט\nפירוט פרויקט", "#3b4048", "#f4f7fb", 1, 2, 1, None, 100, 0, 0, None, 1),
                    ("farm_1", "he", "projects", "farm", "פרויקטים אחרונים", "אין עדיין פריטים.", "#223f69", "#f4f7fb", 1, 3, 1, None, 100, 0, 0, None, 1),
                ],
            )
            cur.executemany(
                """
                INSERT INTO org_team_members (
                    page_name, lang_code, tier_index, slot_index, full_name, role_title, role_detail, image_url
                )
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                  full_name = VALUES(full_name),
                  role_title = VALUES(role_title),
                  role_detail = VALUES(role_detail),
                  image_url = VALUES(image_url)
                """,
                [
                    ("farm_1", "he", 0, 0, "שם מלא", "מייסד", "תפקיד ותרומה בארגון — יש לעדכן.", None),
                    ("farm_1", "he", 0, 1, "שם מלא", "מנכ\"ל", "ניהול שוטף ואחריות תפעולית.", None),
                    ("farm_1", "he", 1, 0, "שם מלא", "מנהל/ת מחלקה", "תיאור תפקיד.", None),
                    ("farm_1", "he", 1, 1, "שם מלא", "מנהל/ת מחלקה", "תיאור תפקיד.", None),
                    ("farm_1", "he", 1, 2, "שם מלא", "מנהל/ת מחלקה", "תיאור תפקיד.", None),
                    ("farm_1", "he", 2, 0, "שם מלא", "חבר צוות", "תיאור תפקיד.", None),
                    ("farm_1", "he", 2, 1, "שם מלא", "חבר צוות", "תיאור תפקיד.", None),
                    ("farm_1", "he", 2, 2, "שם מלא", "חבר צוות", "תיאור תפקיד.", None),
                ],
            )

            cur.execute("SELECT COUNT(*) FROM portfolio_projects")
            portfolio_empty = cur.fetchone()[0] == 0
            if portfolio_empty:
                demo_img = "/static/hero-reference.png"
                demo_gallery = json.dumps([demo_img], ensure_ascii=False)
                cur.executemany(
                    """
                    INSERT INTO portfolio_projects (
                        page_name, lang_code, sort_order, title, summary, body_text, image_url, gallery_json
                    )
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                    """,
                    [
                        (
                            "farm_1",
                            "he",
                            0,
                            "תאורת כבישים מהירים",
                            "פריסת גופי תאורת LED לאורך דרכים בין־עירוניות.",
                            "תכנון תאורה לאורך דרכים מהירים עם דגש על אחידות, צמצום סינוור וחיסכון באנרגיה. הפרויקט כולל בחירת גופים, ניהול תאורה חכמה והתאמה לתקני רשות המסילות והדרכים.",
                            demo_img,
                            demo_gallery,
                        ),
                        (
                            "farm_1",
                            "he",
                            1,
                            "צמתים חכמים",
                            "תאורה לצמתים עמוסים ומורכבים.",
                            "פתרונות תאורה לצמתים רב־מפלסיים ועמוסים, כולל תאום עם תמרורים ותשתיות חכמות. ליווי מהנדסי מהסקיצה ועד הפקת איכות בשטח.",
                            demo_img,
                            demo_gallery,
                        ),
                        (
                            "farm_1",
                            "he",
                            2,
                            "תשתיות רשותיות",
                            "שדרוג תאורה ברשויות מקומיות ומוסדות ציבור.",
                            "שדרוג מערכות תאורה קיימות ברשויות: חיסכון בעלויות תפעול, שיפור איכות האור למגורים ולבטיחות ציבורית, והתאמה לסטנדרטים העדכניים.",
                            demo_img,
                            demo_gallery,
                        ),
                        (
                            "farm_1",
                            "he",
                            3,
                            "נתיבי אופניים והולכי רגל",
                            "תאורה מותאמת לבטיחות משתמשי הדרך הרכים.",
                            "תכנון תאורה לאורך מדרכות, מעברי חציה ונתיבי אופניים — עם התחשבות באחידות חזותית ובנוחות ללא סינוור.",
                            demo_img,
                            demo_gallery,
                        ),
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
        "hero_image": "",
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
        if section_id == "hero_image":
            content["hero_image"] = (row.get("image_url") or "").strip()
        else:
            value = row.get("headline") or row.get("body_text") or ""
            content[section_id] = value
        if row.get("body_text"):
            content[f"{section_id}_body"] = row["body_text"]
        if row.get("image_url"):
            content[f"{section_id}_image_url"] = row["image_url"]
            if section_id in {"hero_title", "hero_image"}:
                content["hero_image_url"] = row["image_url"]

    hi = (content.get("hero_image") or "").strip()
    if hi == "hero_image":
        content["hero_image"] = ""
        hi = ""

    hiu = (content.get("hero_image_url") or "").strip()
    if hiu == "hero_image":
        content["hero_image_url"] = ""
        hiu = ""

    if hi:
        content["hero_image_url"] = hi
    elif hiu:
        content["hero_image"] = hiu

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


def get_org_team_members(page_name: str, lang_code: str) -> list[dict]:
    with closing(get_connection()) as conn:
        with closing(conn.cursor(dictionary=True)) as cur:
            cur.execute(
                """
                SELECT id, tier_index, slot_index, full_name, role_title, role_detail, image_url
                FROM org_team_members
                WHERE page_name = %s AND lang_code = %s
                ORDER BY tier_index ASC, slot_index ASC, id ASC
                """,
                (page_name, lang_code),
            )
            return cur.fetchall()


def group_org_team_by_tier(members: list[dict]) -> list[list[dict]]:
    if not members:
        return []
    from collections import defaultdict

    tiers: dict[int, list[dict]] = defaultdict(list)
    for m in members:
        tiers[int(m["tier_index"])].append(m)
    out: list[list[dict]] = []
    for k in sorted(tiers.keys()):
        row = sorted(tiers[k], key=lambda x: int(x["slot_index"]))
        out.append(row)
    return out


def replace_org_team_members(page_name: str, lang_code: str, members: list[dict]) -> None:
    with closing(get_connection()) as conn:
        with closing(conn.cursor()) as cur:
            cur.execute(
                "DELETE FROM org_team_members WHERE page_name = %s AND lang_code = %s",
                (page_name, lang_code),
            )
            for m in members:
                cur.execute(
                    """
                    INSERT INTO org_team_members (
                        page_name, lang_code, tier_index, slot_index, full_name, role_title, role_detail, image_url
                    )
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                    """,
                    (
                        page_name,
                        lang_code,
                        int(m["tier_index"]),
                        int(m["slot_index"]),
                        (m.get("full_name") or "").strip(),
                        (m.get("role_title") or "").strip(),
                        ((m.get("role_detail") or "").strip() or None),
                        ((m.get("image_url") or "").strip() or None),
                    ),
                )
            conn.commit()


MAX_PORTFOLIO_IMAGES = 40
MAX_PORTFOLIO_IMAGE_URL_LEN = 500


def _normalize_portfolio_image_urls(raw: object) -> list[str]:
    if raw is None:
        return []
    if isinstance(raw, str):
        try:
            raw = json.loads(raw)
        except json.JSONDecodeError:
            return []
    if not isinstance(raw, list):
        return []
    out: list[str] = []
    for u in raw:
        s = str(u).strip()
        if not s or len(s) > MAX_PORTFOLIO_IMAGE_URL_LEN:
            continue
        out.append(s)
        if len(out) >= MAX_PORTFOLIO_IMAGES:
            break
    return out


def _gallery_images_from_row(row: dict) -> list[str]:
    urls = _normalize_portfolio_image_urls(row.get("gallery_json"))
    if urls:
        return urls
    if row.get("image_url"):
        u = str(row["image_url"]).strip()
        if u:
            return [u]
    return []


def _portfolio_project_row_has_content(p: dict) -> bool:
    if (p.get("title") or "").strip():
        return True
    if (p.get("summary") or "").strip():
        return True
    if (p.get("body_text") or "").strip():
        return True
    if p.get("images"):
        return True
    return False


def get_portfolio_projects(page_name: str, lang_code: str) -> list[dict]:
    with closing(get_connection()) as conn:
        with closing(conn.cursor(dictionary=True)) as cur:
            cur.execute(
                """
                SELECT id, sort_order, title, summary, body_text, image_url, gallery_json
                FROM portfolio_projects
                WHERE page_name = %s AND lang_code = %s
                ORDER BY sort_order ASC, id ASC
                """,
                (page_name, lang_code),
            )
            rows = cur.fetchall()
    for row in rows:
        row["images"] = _gallery_images_from_row(row)
    return rows


def replace_portfolio_projects(page_name: str, lang_code: str, projects: list[dict]) -> None:
    with closing(get_connection()) as conn:
        with closing(conn.cursor()) as cur:
            cur.execute(
                "DELETE FROM portfolio_projects WHERE page_name = %s AND lang_code = %s",
                (page_name, lang_code),
            )
            for i, p in enumerate(projects):
                urls = _normalize_portfolio_image_urls(p.get("images"))
                cover = urls[0] if urls else None
                gallery_json = json.dumps(urls, ensure_ascii=False) if urls else None
                cur.execute(
                    """
                    INSERT INTO portfolio_projects (
                        page_name, lang_code, sort_order, title, summary, body_text, image_url, gallery_json
                    )
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                    """,
                    (
                        page_name,
                        lang_code,
                        i,
                        (p.get("title") or "").strip(),
                        (p.get("summary") or "").strip(),
                        ((p.get("body_text") or "").strip() or None),
                        cover,
                        gallery_json,
                    ),
                )
            conn.commit()


def get_farm_cards(page_name: str, lang_code: str) -> list[dict]:
    with closing(get_connection()) as conn:
        with closing(conn.cursor(dictionary=True)) as cur:
            cur.execute(
                """
                SELECT id, card_key, card_type, title, body_text, bg_color, text_color, width_units, sort_order, row_group,
                       image_url, image_scale, image_x, image_radius, caption, is_active
                FROM farm_cards
                WHERE page_name = %s AND lang_code = %s
                ORDER BY row_group ASC, sort_order ASC, id ASC
                """,
                (page_name, lang_code),
            )
            return cur.fetchall()


def replace_farm_cards(page_name: str, lang_code: str, cards: list[dict]) -> None:
    # region agent log
    debug_log(
        run_id="editor-save-pre",
        hypothesis_id="H2",
        location="app.py:replace_farm_cards",
        message="Replacing farm cards",
        data={"page_name": page_name, "lang_code": lang_code, "cards_count": len(cards)},
    )
    # endregion
    with closing(get_connection()) as conn:
        with closing(conn.cursor()) as cur:
            cur.execute("DELETE FROM farm_cards WHERE page_name = %s AND lang_code = %s", (page_name, lang_code))
            for idx, card in enumerate(cards, start=1):
                cur.execute(
                    """
                    INSERT INTO farm_cards (
                        page_name, lang_code, card_key, card_type, title, body_text, bg_color, text_color,
                        width_units, sort_order, row_group, image_url, image_scale, image_x, image_radius, caption, is_active
                    )
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
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
                        card.get("image_x", 0),
                        card.get("image_radius", 0),
                        card["caption"] or None,
                        1 if card.get("is_active", True) else 0,
                    ),
                )
            conn.commit()


def is_allowed_upload(filename: str) -> bool:
    if "." not in filename:
        return False
    ext = filename.rsplit(".", 1)[1].lower()
    return ext in ALLOWED_UPLOAD_EXTENSIONS


@app.before_request
def ensure_db_ready() -> None:
    global _db_ready
    if not _db_ready:
        init_db()
        _db_ready = True


@app.route("/")
def index():
    content = get_page_content("farm_1", "he")
    farm_cards = get_farm_cards("farm_1", "he")
    projects = [line.strip() for line in content.get("recent_projects_body", "").splitlines() if line.strip()]
    return render_template(
        "index.html",
        content=content,
        projects=projects,
        farm_cards=farm_cards,
        current_user=get_current_user(),
    )


@app.route("/team")
def team():
    content = get_page_content("farm_1", "he")
    members = get_org_team_members("farm_1", "he")
    tiers = group_org_team_by_tier(members)
    return render_template(
        "team.html",
        content=content,
        team_tiers=tiers,
        current_user=get_current_user(),
    )


@app.route("/projects")
def projects_page():
    content = get_page_content("farm_1", "he")
    portfolio_projects = get_portfolio_projects("farm_1", "he")
    return render_template(
        "projects.html",
        content=content,
        portfolio_projects=portfolio_projects,
        current_user=get_current_user(),
    )


@app.route("/editor/team", methods=["GET", "POST"])
@editor_required
def editor_team():
    current_user = get_current_user()
    message = ""
    page_name = (request.values.get("page_name") or "farm_1").strip() or "farm_1"
    lang_code = "he"

    if request.method == "POST":
        n = int(request.form.get("members_count") or "0")
        raw: list[dict] = []
        for i in range(n):
            raw.append(
                {
                    "tier_index": int((request.form.get(f"tier_{i}") or "0").strip() or "0"),
                    "full_name": (request.form.get(f"full_name_{i}") or "").strip(),
                    "role_title": (request.form.get(f"role_title_{i}") or "").strip(),
                    "role_detail": (request.form.get(f"role_detail_{i}") or "").strip(),
                    "image_url": (request.form.get(f"image_url_{i}") or "").strip(),
                    "_order": i,
                }
            )
        from collections import defaultdict

        by_tier: dict[int, list[dict]] = defaultdict(list)
        for item in sorted(raw, key=lambda x: (x["tier_index"], x["_order"])):
            item.pop("_order", None)
            by_tier[item["tier_index"]].append(item)
        members_out: list[dict] = []
        for tier in sorted(by_tier.keys()):
            for slot, item in enumerate(by_tier[tier]):
                members_out.append(
                    {
                        **item,
                        "tier_index": tier,
                        "slot_index": slot,
                    }
                )
        replace_org_team_members(page_name, lang_code, members_out)
        message = "עץ הארגון נשמר."

    members = get_org_team_members(page_name, lang_code)

    with closing(get_connection()) as conn:
        with closing(conn.cursor(dictionary=True)) as cur:
            cur.execute("SELECT DISTINCT page_name FROM site_content ORDER BY page_name ASC")
            pages = [r["page_name"] for r in cur.fetchall()]
    if page_name not in pages:
        pages.append(page_name)
        pages.sort()

    return render_template(
        "editor_team.html",
        current_user=current_user,
        message=message,
        members=members,
        page_name=page_name,
        pages=pages,
    )


@app.route("/editor/projects", methods=["GET", "POST"])
@editor_required
def editor_projects():
    current_user = get_current_user()
    message = ""
    page_name = (request.values.get("page_name") or "farm_1").strip() or "farm_1"
    lang_code = "he"

    if request.method == "POST":
        n = int(request.form.get("projects_count") or "0")
        raw: list[dict] = []
        for i in range(n):
            gal_raw = request.form.get(f"gallery_json_{i}") or "[]"
            try:
                gal = json.loads(gal_raw)
            except json.JSONDecodeError:
                gal = []
            images = _normalize_portfolio_image_urls(gal)
            raw.append(
                {
                    "title": (request.form.get(f"title_{i}") or "").strip(),
                    "summary": (request.form.get(f"summary_{i}") or "").strip(),
                    "body_text": (request.form.get(f"body_text_{i}") or "").strip(),
                    "images": images,
                    "_order": i,
                }
            )
        raw_sorted = sorted(raw, key=lambda x: x["_order"])
        projects_out: list[dict] = []
        for item in raw_sorted:
            item.pop("_order", None)
            projects_out.append(item)
        projects_out = [p for p in projects_out if _portfolio_project_row_has_content(p)]
        replace_portfolio_projects(page_name, lang_code, projects_out)
        message = "הפרויקטים נשמרו."

    portfolio_rows = get_portfolio_projects(page_name, lang_code)

    with closing(get_connection()) as conn:
        with closing(conn.cursor(dictionary=True)) as cur:
            cur.execute("SELECT DISTINCT page_name FROM site_content ORDER BY page_name ASC")
            pages = [r["page_name"] for r in cur.fetchall()]
    if page_name not in pages:
        pages.append(page_name)
        pages.sort()

    return render_template(
        "editor_projects.html",
        current_user=current_user,
        message=message,
        projects=portfolio_rows,
        page_name=page_name,
        pages=pages,
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
        error = "שם משתמש או סיסמה שגויים."

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
            error = "יש למלא שם משתמש וסיסמה באורך לפחות 6 תווים."
        else:
            with closing(get_connection()) as conn:
                with closing(conn.cursor()) as cur:
                    try:
                        cur.execute(
                            "INSERT INTO users (username, password_hash) VALUES (%s, %s)",
                            (username, generate_password_hash(password)),
                        )
                        conn.commit()
                        success = "המשתמש נוצר בהצלחה."
                    except mysql.connector.IntegrityError:
                        error = "שם המשתמש כבר קיים."

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
    lang_code = "he"

    if request.method == "POST":
        # region agent log
        debug_log(
            run_id="editor-post",
            hypothesis_id="H1",
            location="app.py:editor",
            message="Editor POST received",
            data={
                "action": request.form.get("action"),
                "page_name": page_name,
                "lang_code": lang_code,
                "form_keys_count": len(list(request.form.keys())),
            },
        )
        # endregion
        if request.form.get("action") == "save":
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
            card_count = int(request.form.get("cards_count") or "0")
            cards_payload = []
            for i in range(card_count):
                card_key = (request.form.get(f"card_key_{i}") or f"card_{i+1}").strip() or f"card_{i+1}"
                card_type = (request.form.get(f"card_type_{i}") or "farm").strip().lower()
                if card_type not in {"farm", "text", "image", "heading", "divider"}:
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
                image_x = int((request.form.get(f"card_x_{i}") or "0").strip() or "0")
                image_x = max(-100, min(100, image_x))
                image_radius = int((request.form.get(f"card_radius_{i}") or "0").strip() or "0")
                image_radius = max(0, min(50, image_radius))
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
                        "image_x": image_x,
                        "image_radius": image_radius,
                        "caption": caption,
                        "is_active": is_active,
                    }
                )
            # region agent log
            debug_log(
                run_id="editor-save-pre",
                hypothesis_id="H3",
                location="app.py:editor",
                message="Built cards payload from form",
                data={
                    "declared_card_count": card_count,
                    "payload_count": len(cards_payload),
                    "card_types": [c.get("card_type", "") for c in cards_payload[:10]],
                },
            )
            # endregion
            replace_farm_cards(page_name, lang_code, cards_payload)
            message = "נשמר בהצלחה."

    with closing(get_connection()) as conn:
        with closing(conn.cursor(dictionary=True)) as cur:
            cur.execute("SELECT DISTINCT page_name FROM site_content ORDER BY page_name ASC")
            pages = [r["page_name"] for r in cur.fetchall()]
    if page_name not in pages:
        pages.append(page_name)
        pages.sort()

    content = get_page_content(page_name, lang_code)
    farm_cards = get_farm_cards(page_name, lang_code)
    if not (content.get("hero_image") or "").strip():
        content["hero_image"] = content.get("hero_image_url", "")

    return render_template(
        "editor.html",
        current_user=current_user,
        message=message,
        content=content,
        pages=pages,
        page_name=page_name,
        farm_cards=farm_cards,
        default_hero_image_url=DEFAULT_HERO_IMAGE_URL,
    )


def _looks_like_email(value: str) -> bool:
    value = value.strip().lower()
    if len(value) < 5 or value.count("@") != 1:
        return False
    local, _, domain = value.partition("@")
    return bool(local and domain and "." in domain and " " not in value)


def _normalize_contact_phone(value: str) -> str | None:
    v = (value or "").strip()
    if not v:
        return None
    if len(v) > 40:
        return None
    if "\n" in v or "\r" in v:
        return None
    return v


@app.post("/api/contact")
def contact_inquiry():
    payload = request.get_json(silent=True) or {}
    full_name = (payload.get("full_name") or payload.get("name") or "").strip()
    email = (payload.get("email") or "").strip()
    phone_raw = payload.get("phone")
    note = (payload.get("note") or "").strip() or None
    if not full_name or len(full_name) > 160:
        return jsonify({"error": "יש למלא שם."}), 400
    if not email or not _looks_like_email(email) or len(email) > 255:
        return jsonify({"error": "יש להזין כתובת אימייל תקינה."}), 400
    phone = _normalize_contact_phone(str(phone_raw) if phone_raw is not None else "")
    if phone_raw is not None and str(phone_raw).strip() and phone is None:
        return jsonify({"error": "מספר טלפון לא תקין."}), 400
    with closing(get_connection()) as conn:
        with closing(conn.cursor()) as cur:
            cur.execute(
                """
                INSERT INTO contact_inquiries (full_name, email, phone, note)
                VALUES (%s, %s, %s, %s)
                """,
                (full_name, email.lower(), phone, note),
            )
            conn.commit()
    return jsonify({"ok": True})


@app.get("/editor/contacts")
@editor_required
def editor_contacts():
    current_user = get_current_user()
    with closing(get_connection()) as conn:
        with closing(conn.cursor(dictionary=True)) as cur:
            cur.execute(
                """
                SELECT id, full_name, email, phone, note, created_at
                FROM contact_inquiries
                ORDER BY created_at DESC
                LIMIT 200
                """
            )
            rows = cur.fetchall()
    return render_template("editor_contacts.html", current_user=current_user, inquiries=rows)


@app.post("/api/upload-image")
@editor_required
def upload_image():
    file = request.files.get("image")
    # region agent log
    debug_log(
        run_id="upload-image",
        hypothesis_id="H4",
        location="app.py:upload_image",
        message="Upload endpoint hit",
        data={"has_file": bool(file), "filename": (file.filename if file else "")},
    )
    # endregion
    if not file or not file.filename:
        return jsonify({"error": "No file uploaded"}), 400
    if not is_allowed_upload(file.filename):
        return jsonify({"error": "Unsupported file type"}), 400

    uploads_dir = os.path.join(app.root_path, "static", "uploads")
    os.makedirs(uploads_dir, exist_ok=True)
    safe_name = secure_filename(file.filename)
    unique_name = f"{uuid4().hex}_{safe_name}"
    full_path = os.path.join(uploads_dir, unique_name)
    file.save(full_path)
    return jsonify({"url": f"/static/uploads/{unique_name}"})


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
