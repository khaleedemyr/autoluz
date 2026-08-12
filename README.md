# Autoluz Laravel

Portal editorial otomotif (Laravel + Inertia + Vue).

## Requirements

- PHP 8.3+
- Composer
- Node.js 20+
- MySQL 8+

## Setup

1. Install dependencies:

```bash
composer install
npm install
```

2. Copy environment file and generate key (if needed):

```bash
cp .env.example .env
php artisan key:generate
```

3. Configure `.env`:

- `APP_NAME=Autoluz`
- `APP_LOCALE=id`
- MySQL: `DB_DATABASE=autoluz_laravel`, `DB_USERNAME`, `DB_PASSWORD`
- Optional WordPress import: `WP_DB_*` and `WP_TABLE_PREFIX`

4. Create database and migrate:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS autoluz_laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed
```

5. Build frontend:

```bash
npm run build
# or for development:
npm run dev
```

6. Serve:

```bash
php artisan serve
```

Open http://127.0.0.1:8000

## WordPress Import

```bash
php artisan import:wordpress --dry-run
php artisan import:wordpress --limit=100
php artisan import:wordpress
```

Imports categories then articles with upsert on legacy WordPress IDs.

## Main Routes

| Path | Description |
|------|-------------|
| `/` | Homepage |
| `/berita/{slug}` | Article detail |
| `/kategori/{slug}` | Category listing |
| `/cari?q=` | Search |
| `/sitemap.xml` | Sitemap |

## Content Sources

No demo content is seeded. Use:

```bash
php artisan import:wordpress
php artisan youtube:sync
```

WordPress DB fills articles/categories. YouTube channel `apih mototv` fills the video stage.

Download article images locally (featured + content `<img>`):

```bash
php artisan storage:link
php artisan import:wordpress-media --dry-run
php artisan import:wordpress-media
```

Optional: `--limit=50` or `--all` to reprocess.

## Admin Portal

```bash
php artisan migrate
php artisan admin:create --email=admin@autoluz.local --password=password
```

Login: http://localhost:8000/login  
Admin: http://localhost:8000/admin

Fitur admin:
- Dashboard statistik
- CRUD Artikel (judul, slug, excerpt, HTML, kategori, status, featured image, featured flag)
- CRUD Kategori
- CRUD Video YouTube
