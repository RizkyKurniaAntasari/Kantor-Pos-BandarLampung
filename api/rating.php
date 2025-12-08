<?php
/**
 * Endpoint untuk mengelola rating kantor pos
 * Menggunakan Supabase REST API (PostgREST) via HTTPS
 */

// 1. Matikan error HTML agar response tetap JSON walau ada error
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// 2. Gunakan Absolute Path agar aman di Vercel
require_once __DIR__ . '/../config/supabase-rest.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // Tambahkan OPTIONS untuk preflight
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

// Handle Preflight Request (CORS)
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

switch ($method) {
    case 'GET':
        getRating();
        break;
    
    case 'POST':
        submitRating();
        break;
    
    default:
        http_response_code(405);
        echo json_encode([
            'error' => true,
            'message' => 'Method not allowed'
        ]);
        break;
}

/**
 * GET - Ambil rating statistics untuk kantor pos
 */
function getRating() {
    try {
        $fid = isset($_GET['fid']) ? intval($_GET['fid']) : null;
        
        if (!$fid) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'fid diperlukan'
            ]);
            exit;
        }
        
        $supabase = getSupabaseRest();
        
        $ratings = $supabase->get('ratings', [
            'select' => 'id,rating,user_id,source,tanggal',
            'fid' => 'eq.' . $fid,
            'order' => 'tanggal.desc'
        ]);
        
        if (empty($ratings)) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'average' => 0,
                    'count' => 0,
                    'distribution' => ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0],
                    'ratings' => []
                ]
            ]);
            exit;
        }
        
        $count = count($ratings);
        $sum = 0;
        $distribution = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0];
        
        foreach ($ratings as $rating) {
            $ratingValue = (int)$rating['rating'];
            $sum += $ratingValue;
            $distribution[(string)$ratingValue]++;
        }
        
        $average = $count > 0 ? round($sum / $count, 2) : 0;
        
        $ratingData = [
            'average' => $average,
            'count' => $count,
            'distribution' => $distribution,
            'ratings' => array_map(function($r) {
                return [
                    'id' => (string)$r['id'],
                    'rating' => (int)$r['rating'],
                    'user_id' => $r['user_id'],
                    'source' => $r['source'] ?? 'direct',
                    'tanggal' => $r['tanggal']
                ];
            }, $ratings)
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $ratingData
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * POST - Submit rating baru
 */
function submitRating() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['fid']) || !isset($input['rating'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'fid dan rating diperlukan'
            ]);
            exit;
        }
        
        $fid = intval($input['fid']);
        $rating = intval($input['rating']);
        
        if ($rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Rating harus antara 1 sampai 5'
            ]);
            exit;
        }
        
        $userId = isset($input['user_id']) ? htmlspecialchars($input['user_id'], ENT_QUOTES, 'UTF-8') : 'anonymous_' . md5($_SERVER['REMOTE_ADDR'] . time());
        $source = isset($input['source']) ? htmlspecialchars($input['source'], ENT_QUOTES, 'UTF-8') : 'direct';
        
        $supabase = getSupabaseRest();
        
        // Cek apakah user ini sudah pernah rating lokasi ini?
        $existing = $supabase->get('ratings', [
            'select' => 'id',
            'fid' => 'eq.' . $fid,
            'user_id' => 'eq.' . $userId
        ]);
        
        $data = [
            'fid' => $fid,
            'rating' => $rating,
            'user_id' => $userId,
            'source' => $source
        ];
        
        // --- PERBAIKAN LOGIKA IF-ELSE DISINI ---
        if (!empty($existing)) {
            // Jika sudah ada, UPDATE (Patch)
            $supabase->patch('ratings', $data, [
                'fid' => 'eq.' . $fid,
                'user_id' => 'eq.' . $userId
            ]);
        } else {
            // Jika belum ada, INSERT (Post)
            $supabase->post('ratings', $data);
        }
        
        // Ambil data terbaru untuk menghitung rata-rata baru
        $ratings = $supabase->get('ratings', [
            'select' => 'rating',
            'fid' => 'eq.' . $fid
        ]);
        
        $count = count($ratings);
        $sum = 0;
        $distribution = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0];
        
        foreach ($ratings as $r) {
            $ratingValue = (int)$r['rating'];
            $sum += $ratingValue;
            $distribution[(string)$ratingValue]++;
        }
        
        $average = $count > 0 ? round($sum / $count, 2) : 0;
        
        $ratingData = [
            'average' => $average,
            'count' => $count,
            'distribution' => $distribution
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Rating berhasil disimpan',
            'data' => $ratingData
        ]);
        
    } catch (Exception $e) {
        // --- CATCH INI SEKARANG SUDAH PUNYA PASANGAN TRY ---
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
?>