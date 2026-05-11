import os
from typing import Any

import mysql.connector
from mysql.connector import MySQLConnection


def load_dotenv(path: str = ".env") -> None:
    """Load simple KEY=VALUE pairs from .env if present."""
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


load_dotenv()


DB_CONFIG: dict[str, Any] = {
    "host": os.getenv(
        "DB_HOST",
        "db-mysql-nyc3-45861-do-user-37030471-0.f.db.ondigitalocean.com",
    ),
    "port": int(os.getenv("DB_PORT", "25060")),
    "user": os.getenv("DB_USER", "doadmin"),
    "password": os.getenv("DB_PASSWORD", ""),
    "database": os.getenv("DB_NAME", "defaultdb"),
    # DigitalOcean managed MySQL requires SSL/TLS.
    "ssl_disabled": False,
}


def get_db_connection() -> MySQLConnection:
    """Create and return a new MySQL connection."""
    return mysql.connector.connect(**DB_CONFIG)


def test_db_connection() -> bool:
    """Check whether the database connection can be established."""
    conn = get_db_connection()
    try:
        return conn.is_connected()
    finally:
        conn.close()
