<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Upcoming Indonesian automotive events sourced from public schedules
     * (GAIKINDO / Seven Event / Dyandra Promosindo) as of Aug 2026.
     */
    public function run(): void
    {
        $events = [
            [
                'slug' => 'giias-surabaya-2026',
                'title' => 'GIIAS Surabaya 2026',
                'excerpt' => 'Rangkaian GIIAS The Series hadir di Surabaya menampilkan peluncuran dan display kendaraan terbaru merek nasional maupun global.',
                'body_html' => '<p>Setelah sukses digelar di ICE BSD City, Tangerang, GAIKINDO Indonesia International Auto Show (GIIAS) The Series 2026 berlanjut ke Surabaya.</p><p>Pengunjung dapat melihat produk otomotif terbaru, promo pameran, serta aktivitas test drive sesuai ketersediaan merek peserta.</p>',
                'cover_image_url' => '/storage/events/giias-surabaya-2026.jpg',
                'location' => 'Grand City Convex',
                'venue' => 'Grand City Convex Surabaya',
                'city' => 'Surabaya',
                'starts_at' => '2026-08-26 10:00:00',
                'ends_at' => '2026-08-30 21:00:00',
                'registration_url' => 'https://indonesiaautoshow.com',
                'is_featured' => true,
                'status' => 'published',
                'sort_order' => 10,
            ],
            [
                'slug' => 'giias-bandung-2026',
                'title' => 'GIIAS Bandung 2026',
                'excerpt' => 'Pameran otomotif GIIAS The Series menyapa Bandung dengan deretan mobil baru, promo, dan pengalaman display merek peserta.',
                'body_html' => '<p>GIIAS Bandung 2026 merupakan bagian dari rangkaian GIIAS The Series yang membawa pengalaman pameran otomotif kelas nasional ke Jawa Barat.</p><p>Lokasi digelar di Sudirman Grand Ballroom, Bandung.</p>',
                'cover_image_url' => '/storage/events/giias-bandung-2026.jpg',
                'location' => 'Sudirman Grand Ballroom',
                'venue' => 'Sudirman Grand Ballroom',
                'city' => 'Bandung',
                'starts_at' => '2026-09-09 10:00:00',
                'ends_at' => '2026-09-13 21:00:00',
                'registration_url' => 'https://indonesiaautoshow.com',
                'is_featured' => true,
                'status' => 'published',
                'sort_order' => 20,
            ],
            [
                'slug' => 'giias-semarang-2026',
                'title' => 'GIIAS Semarang 2026',
                'excerpt' => 'GIIAS The Series tiba di Semarang membawa display kendaraan, promo pameran, dan aktivitas otomotif untuk pengunjung Jawa Tengah.',
                'body_html' => '<p>GIIAS Semarang 2026 digelar di Muladi Dome sebagai lanjutan tour pameran otomotif GAIKINDO di berbagai kota besar Indonesia.</p>',
                'cover_image_url' => '/storage/events/giias-semarang-2026.jpg',
                'location' => 'Muladi Dome',
                'venue' => 'Muladi Dome Semarang',
                'city' => 'Semarang',
                'starts_at' => '2026-09-30 10:00:00',
                'ends_at' => '2026-10-04 21:00:00',
                'registration_url' => 'https://indonesiaautoshow.com',
                'is_featured' => false,
                'status' => 'published',
                'sort_order' => 30,
            ],
            [
                'slug' => 'giias-makassar-2026',
                'title' => 'GIIAS Makassar 2026',
                'excerpt' => 'Penutup GIIAS The Series 2026 di Indonesia Timur: pameran otomotif di Summarecon Mutiara Makassar.',
                'body_html' => '<p>GIIAS Makassar 2026 menutup rangkaian GIIAS The Series tahun 2026 dengan menghadirkan merek otomotif dan produk terbaru untuk pasar Sulawesi Selatan.</p>',
                'cover_image_url' => '/storage/events/giias-makassar-2026.jpg',
                'location' => 'Summarecon Mutiara Makassar',
                'venue' => 'Summarecon Mutiara Makassar',
                'city' => 'Makassar',
                'starts_at' => '2026-10-28 10:00:00',
                'ends_at' => '2026-11-01 21:00:00',
                'registration_url' => 'https://indonesiaautoshow.com',
                'is_featured' => false,
                'status' => 'published',
                'sort_order' => 40,
            ],
            [
                'slug' => 'gjaw-imos-2026',
                'title' => 'GJAW & IMOS 2026',
                'excerpt' => 'GAIKINDO Jakarta Auto Week dan Indonesia Motorcycle Show digelar berbarengan di ICE BSD — satu venue untuk mobil dan motor.',
                'body_html' => '<p>Pada 2026, GAIKINDO Jakarta Auto Week (GJAW) dan Indonesia Motorcycle Show (IMOS) digelar secara bersamaan di ICE BSD City, Tangerang.</p><p>Pengunjung dapat menikmati pameran mobil dan sepeda motor dalam satu periode, termasuk display produk, aksesoris, serta aktivitas industri roda dua yang diinisiasi AISI.</p>',
                'cover_image_url' => '/storage/events/gjaw-imos-2026.jpg',
                'location' => 'ICE BSD City',
                'venue' => 'Indonesia Convention Exhibition (ICE) BSD City',
                'city' => 'Tangerang',
                'starts_at' => '2026-11-20 10:00:00',
                'ends_at' => '2026-11-29 21:00:00',
                'registration_url' => 'https://indonesiamotorcycleshow.id',
                'is_featured' => true,
                'status' => 'published',
                'sort_order' => 50,
            ],
            [
                'slug' => 'giias-bali-2027',
                'title' => 'GIIAS Bali 2027',
                'excerpt' => 'Destinasi baru GIIAS The Series di Bali: pameran otomotif di The Meru Sanur pada awal 2027.',
                'body_html' => '<p>GIIAS Bali dijadwalkan menjadi destinasi baru rangkaian GIIAS The Series pada 2027, digelar di The Meru Sanur.</p><p>Kehadiran Bali menandai perluasan jangkauan pameran otomotif GAIKINDO ke wilayah Indonesia Timur.</p>',
                'cover_image_url' => '/storage/events/giias-bali-2027.jpg',
                'location' => 'The Meru Sanur',
                'venue' => 'The Meru Sanur',
                'city' => 'Bali',
                'starts_at' => '2027-01-27 10:00:00',
                'ends_at' => '2027-01-31 21:00:00',
                'registration_url' => 'https://indonesiaautoshow.com',
                'is_featured' => true,
                'status' => 'published',
                'sort_order' => 60,
            ],
            [
                'slug' => 'iims-2027',
                'title' => 'IIMS 2027',
                'excerpt' => 'Indonesia International Motor Show kembali ke JIExpo Kemayoran dengan area pameran lebih luas dan rute test drive yang diperpanjang.',
                'body_html' => '<p>Dyandra Promosindo resmi menjadwalkan Indonesia International Motor Show (IIMS) 2027 pada 6–16 Mei 2027 di JIExpo Kemayoran, Jakarta.</p><p>Edisi ini membawa perluasan area pameran hingga sekitar 158.339 m², termasuk hall baru, serta rute test drive yang meluas hingga keluar kawasan pameran.</p>',
                'cover_image_url' => '/storage/events/iims-2027.jpg',
                'location' => 'JIExpo Kemayoran',
                'venue' => 'Jakarta International Expo (JIExpo) Kemayoran',
                'city' => 'Jakarta',
                'starts_at' => '2027-05-06 10:00:00',
                'ends_at' => '2027-05-16 21:00:00',
                'registration_url' => 'https://indonesianmotorshow.com',
                'is_featured' => true,
                'status' => 'published',
                'sort_order' => 70,
            ],
            [
                'slug' => 'iims-surabaya-2027',
                'title' => 'IIMS Surabaya 2027',
                'excerpt' => 'Pembuka IIMS Series 2027 di luar Jakarta: pameran otomotif Dyandra di Grand City Convex Surabaya.',
                'body_html' => '<p>IIMS Surabaya 2027 menjadi pembuka rangkaian IIMS Series di daerah, digelar 1–6 Juni 2027 di Grand City Convex Surabaya sebelum berlanjut ke Balikpapan, Manado, dan destinasi baru Medan.</p>',
                'cover_image_url' => '/storage/events/iims-surabaya-2027.jpg',
                'location' => 'Grand City Convex',
                'venue' => 'Grand City Convex Surabaya',
                'city' => 'Surabaya',
                'starts_at' => '2027-06-01 10:00:00',
                'ends_at' => '2027-06-06 21:00:00',
                'registration_url' => 'https://indonesianmotorshow.com',
                'is_featured' => false,
                'status' => 'published',
                'sort_order' => 80,
            ],
        ];

        foreach ($events as $data) {
            Event::query()->updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
