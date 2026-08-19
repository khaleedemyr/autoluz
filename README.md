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
| `/toko` | Online shop (approved stores) |
| `/toko/m/{slug}` | Partner store page |
| `/toko/{slug}` | Product detail |
| `/toko/keranjang` | Cart (grouped by store) |
| `/toko/checkout` | Checkout: one shipping quote per store, one Midtrans payment |
| `/toko/bayar/{number}` | Combined checkout payment |
| `/toko/pesanan` | Visitor order dashboard |
| `/seller` | Seller dashboard (store owner) |
| `/sitemap.xml` | Sitemap |

## Shop (Midtrans + RajaOngkir)

Set in `.env`:

```
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
RAJAONGKIR_API_KEY=
RAJAONGKIR_BASE_URL=https://rajaongkir.komerce.id/api/v1
```

Admin: Produk, Toko Partner, Kategori Toko, Pesanan, Pengaturan Toko (kota asal + kurir).

Toko partner: Autoluz official + verified brand/community shops. Admin membuat toko, assign owner, lalu approve/suspend. Seller mengelola produk, pesanan, dan kota asal di `/seller`. Keranjang/checkout dipecah per toko (ongkir masing-masing), dibayar sekali via Midtrans.

Setiap toko harus punya kota asal RajaOngkir. Pengaturan toko resmi di admin juga menyalin origin ke store `autoluz`.

Midtrans Payment Notification URL: `https://autoluz.id/toko/midtrans/notification`

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


sync tiap pull

rm -rf ~/domains/autoluz.id/public_html/build
cp -a ~/domains/autoluz.id/app/public/build ~/domains/autoluz.id/public_html/build

cd ~/domains/autoluz.id/app
git pull origin main
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
rm -rf ~/domains/autoluz.id/public_html/build
cp -a ~/domains/autoluz.id/app/public/build ~/domains/autoluz.id/public_html/build