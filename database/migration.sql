-- Migration untuk WebGIS Kantor Pos Bandar Lampung
-- Jalankan di Supabase SQL Editor

-- Tabel untuk kantor pos (menyimpan data GeoJSON)
-- Menggunakan SERIAL untuk auto-increment, tapi bisa di-override dengan nilai manual
CREATE TABLE IF NOT EXISTS kantor_pos (
    fid SERIAL PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    lokasi VARCHAR(255) NOT NULL,
    longitude DECIMAL(10, 8) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel untuk ratings
CREATE TABLE IF NOT EXISTS ratings (
    id SERIAL PRIMARY KEY,
    fid INTEGER NOT NULL REFERENCES kantor_pos(fid) ON DELETE CASCADE,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    user_id VARCHAR(255) NOT NULL,
    tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    source VARCHAR(50) DEFAULT 'direct',
    UNIQUE(fid, user_id)
);

-- Tabel untuk comments
CREATE TABLE IF NOT EXISTS comments (
    id SERIAL PRIMARY KEY,
    fid INTEGER NOT NULL REFERENCES kantor_pos(fid) ON DELETE CASCADE,
    nama VARCHAR(255) NOT NULL,
    komentar TEXT NOT NULL,
    rating INTEGER CHECK (rating IS NULL OR (rating >= 1 AND rating <= 5)),
    tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index untuk performa
CREATE INDEX IF NOT EXISTS idx_ratings_fid ON ratings(fid);
CREATE INDEX IF NOT EXISTS idx_ratings_user_id ON ratings(user_id);
CREATE INDEX IF NOT EXISTS idx_comments_fid ON comments(fid);
CREATE INDEX IF NOT EXISTS idx_kantor_pos_fid ON kantor_pos(fid);

-- Function untuk update updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Trigger untuk update updated_at
CREATE TRIGGER update_kantor_pos_updated_at BEFORE UPDATE ON kantor_pos
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_comments_updated_at BEFORE UPDATE ON comments
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

