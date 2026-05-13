#Requires -Version 5.1
# התקנה מהירה לאתר אורות הטירה — מריצים מתוך תיקיית php-backend (לחצי ימין על הקובץ > Run with PowerShell, או: powershell -ExecutionPolicy Bypass -File .\install-portable.ps1)
$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

Write-Host "=== התקנת Orot_Hatera (Laravel) ===" -ForegroundColor Cyan

$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php) {
    Write-Host ""
    Write-Host "לא נמצא PHP במחשב." -ForegroundColor Red
    Write-Host "התקיני PHP 8.3 או חדש יותר, ואז הריצי שוב את הסקריפט." -ForegroundColor Yellow
    Write-Host "דוגמה (Windows, winget): winget install PHP.PHP.8.3 --accept-package-agreements" -ForegroundColor Yellow
    Write-Host "או התקנה מ: https://windows.php.net/download/" -ForegroundColor Yellow
    exit 1
}

& php -r "if (version_compare(PHP_VERSION, '8.3.0', '<')) { fwrite(STDERR, 'נדרש PHP 8.3+. גרסה נוכחית: ' . PHP_VERSION . PHP_EOL); exit(1); }"
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

if (-not (Test-Path "composer.phar")) {
    Write-Host "מוריד Composer (composer.phar)..." -ForegroundColor Cyan
    Invoke-WebRequest -Uri "https://getcomposer.org/download/latest-stable/composer.phar" -OutFile "composer.phar" -UseBasicParsing
}

Write-Host "מריצה composer install (עשוי לקחת כמה דקות)..." -ForegroundColor Cyan
& php composer.phar install --no-dev --optimize-autoloader --no-interaction
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

if (-not (Test-Path ".env")) {
    Write-Host "יוצר .env מ-.env.example" -ForegroundColor Cyan
    Copy-Item ".env.example" ".env"
}

$dbPath = Join-Path $PSScriptRoot "database\database.sqlite"
if (-not (Test-Path $dbPath)) {
    Write-Host "יוצר קובץ SQLite ריק: database\database.sqlite" -ForegroundColor Cyan
    New-Item -ItemType File -Path $dbPath -Force | Out-Null
}

Write-Host "מפתח אפליקציה (APP_KEY)..." -ForegroundColor Cyan
& php artisan key:generate --force
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host "מיגרציות מסד נתונים..." -ForegroundColor Cyan
& php artisan migrate --force
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host ""
Write-Host "ההתקנה הסתיימה בהצלחה." -ForegroundColor Green
Write-Host "להפעלת שרת מקומי:  php artisan serve" -ForegroundColor Green
Write-Host "ואז בדפדפן: http://127.0.0.1:8000" -ForegroundColor Green
Write-Host ""
Write-Host "לפריסה באינטרנט: Document Root של השרת צריך להצביע על תיקיית public בתוך php-backend." -ForegroundColor DarkGray
