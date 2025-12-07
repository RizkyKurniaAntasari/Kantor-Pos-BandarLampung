<?php
/**
 * Script untuk migrate data dari poinkantorpos.geojson ke Supabase database
 * Menggunakan REST API
 * 
 * Usage: php database/migrate_from_geojson.php
 */

require_once __DIR__ . '/../config/supabase-rest.php';

echo "Starting migration from GeoJSON to database...\n\n";

try {
    $supabase = getSupabaseRest();
    
    // Baca file GeoJSON
    $geojsonFile = __DIR__ . '/../data/poinkantorpos.geojson';
    
    if (!file_exists($geojsonFile)) {
        die("Error: File $geojsonFile tidak ditemukan\n");
    }
    
    $geojsonData = json_decode(file_get_contents($geojsonFile), true);
    
    if (!$geojsonData || !isset($geojsonData['features'])) {
        die("Error: Format GeoJSON tidak valid\n");
    }
    
    $features = $geojsonData['features'];
    $total = count($features);
    $success = 0;
    $skipped = 0;
    $errors = 0;
    
    echo "Found $total kantor pos in GeoJSON file\n";
    echo "Migrating to database...\n\n";
    
    foreach ($features as $feature) {
        $props = $feature['properties'];
        $coords = $feature['geometry']['coordinates'];
        
        $fid = isset($props['fid']) ? (int)$props['fid'] : null;
        $nama = $props['nama'] ?? '';
        $lokasi = $props['lokasi'] ?? '';
        // Round to 8 decimal places to match DECIMAL(10,8)
        $longitude = round((float)$coords[0], 8);
        $latitude = round((float)$coords[1], 8);
        
        if (!$fid || !$nama || !$lokasi) {
            echo "⚠ Skipping invalid feature: fid=$fid, nama=$nama\n";
            $skipped++;
            continue;
        }
        
        try {
            // Check if exists
            $existing = $supabase->get('kantor_pos', [
                'select' => 'fid',
                'fid' => 'eq.' . $fid
            ]);
            
            if (!empty($existing)) {
                // Update existing
                $supabase->patch('kantor_pos', [
                    'nama' => $nama,
                    'lokasi' => $lokasi,
                    'longitude' => $longitude,
                    'latitude' => $latitude
                ], [
                    'fid' => 'eq.' . $fid
                ]);
                echo "✓ Updated: FID $fid - $nama\n";
            } else {
                // Insert new (with explicit fid)
                $supabase->post('kantor_pos', [
                    'fid' => $fid,
                    'nama' => $nama,
                    'lokasi' => $lokasi,
                    'longitude' => $longitude,
                    'latitude' => $latitude
                ]);
                echo "✓ Inserted: FID $fid - $nama\n";
            }
            
            $success++;
            
        } catch (Exception $e) {
            echo "✗ Error migrating FID $fid: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    echo "\n========================================\n";
    echo "Migration completed!\n";
    echo "Success: $success\n";
    echo "Skipped: $skipped\n";
    echo "Errors: $errors\n";
    echo "Total: $total\n";
    echo "========================================\n";
    
    // Migrate ratings and comments from JSON files
    echo "\nMigrating ratings and comments from JSON files...\n";
    
    // Migrate ratings
    $ratingDir = __DIR__ . '/../data/rating';
    if (is_dir($ratingDir)) {
        $ratingFiles = glob($ratingDir . '/*.json');
        $ratingCount = 0;
        
        foreach ($ratingFiles as $file) {
            $fid = (int)basename($file, '.json');
            $ratingData = json_decode(file_get_contents($file), true);
            
            if ($ratingData && isset($ratingData['ratings'])) {
                foreach ($ratingData['ratings'] as $rating) {
                    try {
                        $supabase->post('ratings', [
                            'fid' => $fid,
                            'rating' => (int)$rating['rating'],
                            'user_id' => $rating['user_id'],
                            'source' => $rating['source'] ?? 'direct',
                            'tanggal' => $rating['tanggal']
                        ]);
                        $ratingCount++;
                    } catch (Exception $e) {
                        // Skip if duplicate or error
                    }
                }
            }
        }
        echo "✓ Migrated $ratingCount ratings\n";
    }
    
    // Migrate comments
    $commentsDir = __DIR__ . '/../data/comments';
    if (is_dir($commentsDir)) {
        $commentFiles = glob($commentsDir . '/*.json');
        $commentCount = 0;
        
        foreach ($commentFiles as $file) {
            $fid = (int)basename($file, '.json');
            $commentsData = json_decode(file_get_contents($file), true);
            
            if ($commentsData && is_array($commentsData)) {
                foreach ($commentsData as $comment) {
                    try {
                        $supabase->post('comments', [
                            'fid' => $fid,
                            'nama' => $comment['nama'],
                            'komentar' => $comment['komentar'],
                            'rating' => isset($comment['rating']) ? (int)$comment['rating'] : null,
                            'tanggal' => $comment['tanggal']
                        ]);
                        $commentCount++;
                    } catch (Exception $e) {
                        // Skip if duplicate or error
                    }
                }
            }
        }
        echo "✓ Migrated $commentCount comments\n";
    }
    
    echo "\nAll migrations completed!\n";
    
} catch (Exception $e) {
    echo "\n========================================\n";
    echo "✗ Migration FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "========================================\n";
    exit(1);
}

