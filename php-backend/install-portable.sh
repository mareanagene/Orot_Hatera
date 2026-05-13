#!/usr/bin/env bash
# התקנה מהירה (Linux / macOS) — מתוך תיקיית php-backend: chmod +x install-portable.sh && ./install-portable.sh
set -euo pipefail
cd "$(dirname "$0")"

echo "=== התקנת Orot_Hatera (Laravel) ==="

if ! command -v php >/dev/null 2>&1; then
  echo "לא נמצא PHP. התקיני PHP 8.3+ והריצו שוב."
  exit 1
fi

php -r 'if (version_compare(PHP_VERSION, "8.3.0", "<")) { fwrite(STDERR, "נדרש PHP 8.3+. גרסה: ".PHP_VERSION.PHP_EOL); exit(1); }'

if [[ ! -f composer.phar ]]; then
  echo "מוריד Composer..."
  curl -sS https://getcomposer.org/download/latest-stable/composer.phar -o composer.phar
fi

echo "מריצה composer install..."
php composer.phar install --no-dev --optimize-autoloader --no-interaction

if [[ ! -f .env ]]; then
  echo "יוצר .env מ-.env.example"
  cp .env.example .env
fi

if [[ ! -f database/database.sqlite ]]; then
  echo "יוצר database/database.sqlite"
  touch database/database.sqlite
fi

php artisan key:generate --force
php artisan migrate --force

echo ""
echo "מוכן. להרצה מקומית: php artisan serve"
