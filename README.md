# WebGIS Sistem Informasi Geografis - Kantor Pos Bandar Lampung

Aplikasi WebGIS interaktif untuk menampilkan dan mengelola data lokasi kantor pos di Bandar Lampung. Dibangun dengan PHP, Leaflet.js, dan Supabase PostgreSQL sebagai bagian dari Tugas 2: Implementasi GIS dengan PHP, MySQL, Leaflet.js, GeoJSON.

## 📋 Deskripsi Proyek

Aplikasi ini menampilkan peta interaktif yang menampilkan:
- **Layer Kecamatan**: Batas administrasi kecamatan di Bandar Lampung (polygon)
- **Layer Kantor Pos**: Titik lokasi kantor pos di Bandar Lampung (point)

Setiap lokasi kantor pos dapat dilihat detailnya, diberi rating, dan dikomentari oleh pengguna.

## 🚀 Fitur Utama

### 1. Peta Interaktif
- Peta interaktif menggunakan Leaflet.js
- Dua base layer: OpenStreetMap dan Dark Mode Map
- Layer control untuk toggle visibility
- Geocoder untuk pencarian lokasi
- Fullscreen mode

### 2. CRUD Data Kantor Pos
- **Create**: Tambah marker baru dengan klik di peta
- **Read**: Lihat daftar kantor pos di sidebar dan popup di peta
- **Update**: Edit informasi kantor pos (nama, lokasi, koordinat)
- **Delete**: Hapus marker kantor pos
- **Admin Password**: Semua operasi CRUD memerlukan password admin

### 3. Rating System
- Rating 1-5 bintang untuk setiap lokasi
- Batasan: 1 rating per lokasi per 24 jam per browser session
- Menampilkan rata-rata rating dan jumlah rating
- Rating tersimpan global (sinkron antar device)

### 4. Comment System
- Komentar untuk setiap lokasi kantor pos
- Opsional rating dalam komentar
- Menampilkan nama, tanggal, dan isi komentar
- Komentar tersimpan global (sinkron antar device)

### 5. Search & Filter
- Pencarian kantor pos berdasarkan nama atau lokasi
- Filter layer (toggle kecamatan dan kantor pos)
- Geocoder untuk mencari alamat
- Fitur "Near Me" untuk menemukan kantor pos terdekat dari posisi pengguna

## 🛠️ Teknologi yang Digunakan

### Frontend
- **HTML5**: Struktur halaman
- **JavaScript (Vanilla)**: Logika aplikasi
- **Leaflet.js**: Library peta interaktif
- **Tailwind CSS**: Styling (via CDN)
- **Leaflet Fullscreen**: Kontrol fullscreen
- **Leaflet Geocoder**: Pencarian lokasi

### Backend
- **PHP 7.4+**: Server-side logic
- **Supabase PostgreSQL**: Database untuk data dinamis
- **Supabase REST API**: Koneksi database via HTTPS (PostgREST)

### Data Format
- **GeoJSON**: Format data spasial
- **PostgreSQL**: Database untuk data dinamis

## 📁 Struktur Proyek

```
siguap/
├── index.php                    # Halaman utama WebGIS
├── api/                         # Endpoint PHP
│   ├── kecamatan.php            # Endpoint data kecamatan (GET)
│   ├── kantorpos.php            # Endpoint CRUD kantor pos (GET, POST, PUT, DELETE)
│   ├── rating.php               # Endpoint rating (GET, POST)
│   └── comments.php             # Endpoint komentar (GET, POST, PUT, DELETE)
├── assets/
│   ├── images/                  # Gambar kantor pos (1-13.png)
│   └── js/
│       ├── script.js             # JavaScript utama (map, CRUD)
│       └── features.js           # JavaScript fitur (rating, komentar)
├── config/
│   └── supabase-rest.php        # Supabase REST API client
├── data/                        # Data GeoJSON (read-only)
│   ├── kecamatanbalam.geojson   # Data polygon kecamatan
│   └── poinkantorpos.geojson    # Backup data kantor pos (opsional)
├── database/
│   ├── migration.sql            # SQL migration untuk Supabase
│   └── migrate_from_geojson.php # Script migrate data dari GeoJSON ke database
├── vercel.json                  # Konfigurasi Vercel deployment
└── README.md                    # Dokumentasi proyek
```

## 🔧 Instalasi & Setup

### Persyaratan
- PHP 7.4 atau lebih tinggi
- Web server (Apache/Nginx) atau PHP built-in server
- Browser modern dengan dukungan JavaScript ES6+
- Akun Supabase (gratis)

### Langkah Instalasi

1. **Clone atau download proyek**
   ```bash
   git clone <repository-url>
   cd siguap
   ```

2. **Setup Database Supabase**
   - Buat project baru di [Supabase](https://supabase.com)
   - Buka SQL Editor di Supabase Dashboard
   - Copy dan jalankan isi file `database/migration.sql`
   - Pastikan semua tabel berhasil dibuat:
     - `kantor_pos`
     - `ratings`
     - `comments`

3. **Setup Environment Variables**
   - Buat file `.env` di root project
   - Tambahkan konfigurasi berikut:
     ```
     SUPABASE_URL=https://[PROJECT_REF].supabase.co
     SUPABASE_SERVICE_ROLE_KEY=[YOUR_SERVICE_ROLE_KEY]
     ```
   - Dapatkan `SUPABASE_URL` dan `SUPABASE_SERVICE_ROLE_KEY` dari:
     - Supabase Dashboard → Project Settings → API
     - Copy **Project URL** dan **service_role** key

4. **Migrate Data (Opsional)**
   - Jika ada data di `poinkantorpos.geojson`, migrate ke database:
     ```bash
     php database/migrate_from_geojson.php
     ```

5. **Jalankan server PHP**
   ```bash
   # Menggunakan PHP built-in server
   php -S localhost:8000
   ```

6. **Buka browser**
   ```
   http://localhost:8000
   ```

## 📡 API Endpoints

### 1. Kecamatan
- **GET** `/api/kecamatan.php`
  - Mengembalikan data GeoJSON kecamatan
  - Response: `application/geo+json`

### 2. Kantor Pos
- **GET** `/api/kantorpos.php`
  - Mengembalikan semua data kantor pos (GeoJSON)
  
- **POST** `/api/kantorpos.php`
  - Menambah kantor pos baru
  - Body: `{ "nama": "...", "lokasi": "...", "coordinates": [lng, lat], "password": "..." }`
  
- **PUT** `/api/kantorpos.php`
  - Update kantor pos
  - Body: `{ "fid": 1, "nama": "...", "lokasi": "...", "coordinates": [lng, lat], "password": "..." }`
  
- **DELETE** `/api/kantorpos.php`
  - Hapus kantor pos
  - Body: `{ "fid": 1, "password": "..." }`

### 3. Rating
- **GET** `/api/rating.php?fid={fid}`
  - Mengembalikan statistik rating untuk lokasi
  - Response: `{ "success": true, "data": { "average": 4.5, "count": 10, ... } }`
  
- **POST** `/api/rating.php`
  - Submit rating baru
  - Body: `{ "fid": 1, "rating": 5 }`

### 4. Komentar
- **GET** `/api/comments.php?fid={fid}`
  - Mengembalikan semua komentar untuk lokasi
  
- **POST** `/api/comments.php`
  - Tambah komentar baru
  - Body (FormData atau JSON): `fid`, `nama`, `komentar`, `rating` (opsional)
  
- **PUT** `/api/comments.php`
  - Update komentar
  - Body: `{ "fid": 1, "commentId": 1, "komentar": "...", "rating": 5 }`
  
- **DELETE** `/api/comments.php`
  - Hapus komentar
  - Body: `{ "fid": 1, "commentId": 1 }`

## 💾 Penyimpanan Data

Proyek ini menggunakan **Supabase PostgreSQL** untuk menyimpan data dinamis:

- **Kantor Pos**: Disimpan di database (tabel `kantor_pos`)
- **Rating**: Disimpan di database (tabel `ratings`)
- **Komentar**: Disimpan di database (tabel `comments`)
- **Kecamatan**: Tetap menggunakan file GeoJSON (read-only, `data/kecamatanbalam.geojson`)

### Mengapa Database?
- Data tidak hilang saat refresh atau deployment
- Data persistent dan reliable
- Support untuk production environment
- Mudah di-scale dan di-backup
- Sinkronisasi global antar device

### Koneksi Database
- Menggunakan **Supabase REST API** (PostgREST) via HTTPS
- Support IPv4 networks (tidak perlu Session Pooler)
- Menggunakan Service Role Key untuk akses penuh

## 🎨 Fitur UI/UX

- **Dark Theme**: Desain gelap dengan accent color orange dan kuning
- **Responsive**: Sidebar scrollable, map fixed
- **Custom Modals**: Modal custom untuk CRUD (bukan browser prompt)
- **Notifications**: Notifikasi untuk feedback user
- **Loading Indicators**: Indikator loading saat fetch data
- **Custom Icons**: Icon marker custom untuk kantor pos
- **Hover Effects**: Efek hover pada marker dan sidebar items
- **Image Gallery**: Gambar kantor pos (1-13.png untuk fid 1-13, random untuk fid > 13)

## 🔒 Keamanan

- **XSS Prevention**: Escape HTML pada user input
- **Input Validation**: Validasi input di frontend dan backend
- **CORS Headers**: CORS diatur untuk API endpoints
- **Rate Limiting**: Rating dibatasi 1 per 24 jam per lokasi (client-side)
- **Admin Password**: CRUD operations memerlukan password admin

## 📝 Catatan Penting

1. **Data GeoJSON**: Data berasal dari hasil ekspor QGIS (Tugas 1)
2. **Hosting**: Proyek ini **WAJIB** di-hosting sesuai ketentuan tugas
3. **CDN**: Menggunakan CDN untuk library (Tailwind CSS, Leaflet.js) sesuai ketentuan
4. **Browser Support**: Browser modern dengan dukungan ES6+
5. **Environment Variables**: Pastikan file `.env` tidak di-commit ke repository (sudah di `.gitignore`)

## 🌐 Hosting

Proyek ini siap untuk di-hosting. Rekomendasi hosting:

- **Vercel**: Free hosting dengan PHP support (sudah ada `vercel.json`)
- **InfinityFree**: Free PHP hosting
- **000webhost**: Free hosting dengan PHP support

### Checklist Hosting:
- [ ] Upload semua file ke hosting
- [ ] Pastikan PHP version sesuai (7.4+)
- [ ] Setup environment variables di hosting (SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY)
- [ ] Jalankan migration SQL di Supabase
- [ ] Test semua endpoint API
- [ ] Test CRUD operations
- [ ] Test rating dan komentar
- [ ] Verifikasi CORS headers

## 📊 Data GeoJSON

### Format Data Kecamatan
```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "properties": {
        "NAMOBJ": "Nama Kecamatan"
      },
      "geometry": {
        "type": "Polygon",
        "coordinates": [...]
      }
    }
  ]
}
```

### Format Data Kantor Pos (dari Database)
```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "properties": {
        "fid": 1,
        "nama": "Kantor Pos Unila",
        "lokasi": "Universitas Lampung",
        "rating": {
          "average": 4.5,
          "count": 10
        },
        "stats": {
          "totalComments": 5
        }
      },
      "geometry": {
        "type": "Point",
        "coordinates": [105.243, -5.367]
      }
    }
  ]
}
```

## 🐛 Troubleshooting

### Map tidak muncul
- Pastikan Leaflet.js ter-load (cek console browser)
- Pastikan container `#map` ada di HTML
- Cek error di browser console

### Data tidak ter-load
- Pastikan endpoint PHP dapat diakses
- Cek koneksi ke Supabase (cek `.env` file)
- Pastikan tabel sudah dibuat di Supabase
- Cek browser console untuk error

### Rating/Komentar tidak tersimpan
- Pastikan environment variables sudah di-set (`SUPABASE_URL`, `SUPABASE_SERVICE_ROLE_KEY`)
- Pastikan tabel sudah dibuat di Supabase
- Cek error di browser console dan server logs
- Pastikan Service Role Key sudah benar

### CORS Error
- Pastikan header CORS sudah di-set di semua endpoint PHP
- Cek `Access-Control-Allow-Origin` header

### Database Connection Error
- Pastikan `SUPABASE_URL` dan `SUPABASE_SERVICE_ROLE_KEY` sudah benar
- Pastikan network tidak memblokir HTTPS ke Supabase
- Cek Supabase Dashboard untuk status project

## 📄 Lisensi

Proyek ini dibuat untuk keperluan akademik (Tugas 2: Implementasi GIS dengan PHP, MySQL, Leaflet.js, GeoJSON).

## 👤 Author

Dibuat sebagai bagian dari Ujian Akhir Praktikum Mata Kuliah Sistem Informasi Geografis Tahun 2025.

---

**Catatan**: Proyek ini menggunakan Supabase PostgreSQL via REST API untuk penyimpanan data. Data akan persistent dan tidak hilang saat deployment atau refresh. Setup database menggunakan REST API via HTTPS, sehingga kompatibel dengan semua network (IPv4 dan IPv6).
