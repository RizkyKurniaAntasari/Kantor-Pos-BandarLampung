# WebGIS Kantor Pos Bandar Lampung

Aplikasi peta interaktif buat nampilin lokasi kantor pos di Bandar Lampung. Dibuat pakai PHP, Leaflet.js, sama Supabase buat database-nya.

## Fitur

- **Peta Interaktif**: Pake Leaflet.js, bisa ganti layer (OSM sama Dark Mode), ada geocoder buat cari alamat, bisa fullscreen juga
- **CRUD Kantor Pos**: Bisa tambah, edit, sama hapus marker kantor pos. Semua operasi butuh password admin
- **Rating**: Bisa kasih rating 1-5 bintang buat setiap kantor pos
- **Komentar**: Bisa kasih komentar buat setiap lokasi, bisa sekalian kasih rating juga
- **Pencarian**: Bisa cari kantor pos berdasarkan nama atau lokasi
- **Near Me**: Fitur buat cari kantor pos terdekat dari posisi kamu (pake geolokasi browser)

## Teknologi

- **Frontend**: HTML, JavaScript vanilla, Leaflet.js, Tailwind CSS (via CDN)
- **Backend**: PHP 7.4+
- **Database**: Supabase PostgreSQL (via REST API)
- **Data Spasial**: GeoJSON buat kecamatan (read-only)

## Struktur Proyek

```
sigkantorposbalam/
├── index.php              # File utama (dipindah ke api/index.php untuk Vercel)
├── api/
│   ├── index.php          # Entry point utama
│   ├── kantorpos.php      # CRUD kantor pos
│   ├── kecamatan.php      # Data kecamatan
│   ├── rating.php          # Rating system
│   └── comments.php        # Comment system
├── assets/
│   ├── images/            # Gambar kantor pos (1-13.png)
│   └── js/
│       ├── script.js       # Logic utama map sama CRUD
│       └── features.js      # Rating sama komentar
├── config/
│   └── supabase-rest.php  # Koneksi ke Supabase
├── data/
│   ├── kecamatanbalam.geojson  # Data polygon kecamatan
│   └── poinkantorpos.geojson   # Backup data (opsional)
├── database/
│   ├── migration.sql      # SQL buat setup tabel di Supabase
│   └── migrate_from_geojson.php  # Script migrate data
├── vercel.json            # Config buat deploy ke Vercel
└── README.md
```

## Setup

### 1. Clone Repository

```bash
git clone https://github.com/Deadelvi1/sigkantorposbalam.git
cd sigkantorposbalam
```

### 2. Setup Supabase

1. Buat project baru di [Supabase](https://supabase.com)
2. Buka SQL Editor di dashboard
3. Copy isi file `database/migration.sql` dan jalankan
4. Pastikan tabel-tabel ini udah dibuat:
   - `kantor_pos`
   - `ratings`
   - `comments`

### 3. Setup Environment Variables

Buat file `.env` di root project (copy dari `.env.example`):

```
SUPABASE_URL=https://[PROJECT_REF].supabase.co
SUPABASE_SERVICE_ROLE_KEY=[YOUR_SERVICE_ROLE_KEY]
```

Cara dapetin:
- Buka Supabase Dashboard → Project Settings → API
- Copy **Project URL** sama **service_role** key

### 4. Migrate Data (Opsional)

Kalau ada data di `poinkantorpos.geojson`, bisa di-migrate ke database:

```bash
php database/migrate_from_geojson.php
```

### 5. Jalankan Server

```bash
php -S localhost:8000
```

Buka browser: `http://localhost:8000`

## API Endpoints

### Kecamatan
- `GET /api/kecamatan.php` - Ambil data GeoJSON kecamatan

### Kantor Pos
- `GET /api/kantorpos.php` - Ambil semua kantor pos
- `POST /api/kantorpos.php` - Tambah kantor pos baru
  ```json
  {
    "nama": "Kantor Pos ...",
    "lokasi": "Alamat ...",
    "coordinates": [lng, lat],
    "password": "password_admin"
  }
  ```
- `PUT /api/kantorpos.php` - Update kantor pos
- `DELETE /api/kantorpos.php` - Hapus kantor pos

### Rating
- `GET /api/rating.php?fid={fid}` - Ambil statistik rating
- `POST /api/rating.php` - Submit rating baru
  ```json
  {
    "fid": 1,
    "rating": 5
  }
  ```

### Komentar
- `GET /api/comments.php?fid={fid}` - Ambil semua komentar
- `POST /api/comments.php` - Tambah komentar baru
- `PUT /api/comments.php` - Update komentar
- `DELETE /api/comments.php` - Hapus komentar

## Database

Proyek ini pake **Supabase PostgreSQL** buat nyimpen data:

- **Kantor Pos**: Disimpan di tabel `kantor_pos`
- **Rating**: Disimpan di tabel `ratings`
- **Komentar**: Disimpan di tabel `comments`
- **Kecamatan**: Tetap pake file GeoJSON (`data/kecamatanbalam.geojson`)

Kenapa pake database? Biar data nggak ilang pas refresh atau deployment, lebih reliable, dan bisa di-scale.

## Deploy ke Vercel

Proyek ini udah disiapin buat deploy ke Vercel. File `vercel.json` udah dikonfigurasi.

**Catatan Penting:**
- Set environment variables di Vercel Dashboard:
  - `SUPABASE_URL`
  - `SUPABASE_SERVICE_ROLE_KEY`
- File `.env` nggak akan ter-commit (udah di `.gitignore`)
- Pastikan semua tabel udah dibuat di Supabase sebelum deploy

## Troubleshooting

**Map nggak muncul?**
- Cek console browser, pastikan Leaflet.js ter-load
- Pastikan container `#map` ada di HTML

**Data nggak ke-load?**
- Cek file `.env` udah ada dan isinya benar
- Pastikan koneksi ke Supabase berhasil
- Cek tabel-tabel udah dibuat di Supabase

**Rating/Komentar nggak tersimpan?**
- Pastikan environment variables udah di-set
- Cek Service Role Key udah benar
- Cek error di browser console

**Error 500 di API?**
- Cek koneksi ke Supabase
- Pastikan semua tabel udah dibuat
- Cek log error di server

## Catatan

- Password admin buat CRUD: `akuanaksehattubuhkukuat666`
- Data GeoJSON kecamatan dari hasil ekspor QGIS
- Proyek ini dibuat buat keperluan akademik (Tugas 2: Implementasi GIS dengan PHP, MySQL, Leaflet.js, GeoJSON)
