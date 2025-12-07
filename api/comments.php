<?php
/**
 * Endpoint untuk mengelola komentar kantor pos
 * Menggunakan Supabase REST API (PostgREST) via HTTPS
 * CRUD komentar dengan optional rating
 */

require_once __DIR__ . '/../config/supabase-rest.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getComments();
        break;
    
    case 'POST':
        createComment();
        break;
    
    case 'PUT':
        updateComment();
        break;
    
    case 'DELETE':
        deleteComment();
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
 * GET - Ambil komentar untuk kantor pos
 */
function getComments() {
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
        
        $comments = $supabase->get('comments', [
            'select' => 'id,nama,komentar,rating,tanggal',
            'fid' => 'eq.' . $fid,
            'order' => 'tanggal.desc'
        ]);
        
        $result = array_map(function($comment) {
            return [
                'id' => (int)$comment['id'],
                'nama' => $comment['nama'],
                'komentar' => $comment['komentar'],
                'rating' => isset($comment['rating']) && $comment['rating'] !== null ? (int)$comment['rating'] : null,
                'tanggal' => $comment['tanggal']
            ];
        }, $comments);
        
        echo json_encode([
            'success' => true,
            'data' => $result
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
 * POST - Buat komentar baru
 */
function createComment() {
    try {
        if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
            $input = json_decode(file_get_contents('php://input'), true);
            $fid = isset($input['fid']) ? intval($input['fid']) : null;
            $nama = isset($input['nama']) ? htmlspecialchars($input['nama'], ENT_QUOTES, 'UTF-8') : '';
            $komentar = isset($input['komentar']) ? htmlspecialchars($input['komentar'], ENT_QUOTES, 'UTF-8') : '';
            $rating = isset($input['rating']) ? intval($input['rating']) : null;
        } else {
            $fid = isset($_POST['fid']) ? intval($_POST['fid']) : null;
            $nama = isset($_POST['nama']) ? htmlspecialchars($_POST['nama'], ENT_QUOTES, 'UTF-8') : '';
            $komentar = isset($_POST['komentar']) ? htmlspecialchars($_POST['komentar'], ENT_QUOTES, 'UTF-8') : '';
            $rating = isset($_POST['rating']) ? intval($_POST['rating']) : null;
        }
        
        if (!$fid || !$nama || !$komentar) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'fid, nama, dan komentar diperlukan'
            ]);
            exit;
        }
        
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Rating harus antara 1 sampai 5'
            ]);
            exit;
        }
        
        $supabase = getSupabaseRest();
        
        $data = [
            'fid' => $fid,
            'nama' => $nama,
            'komentar' => $komentar,
            'rating' => $rating
        ];
        
        $result = $supabase->post('comments', $data);
        
        $newRow = is_array($result) && isset($result[0]) ? $result[0] : $result;
        
        $newComment = [
            'id' => (int)$newRow['id'],
            'nama' => $nama,
            'komentar' => $komentar,
            'rating' => $rating,
            'tanggal' => $newRow['tanggal']
        ];
        
        if ($rating !== null && $rating >= 1 && $rating <= 5) {
            updateRatingFromComment($fid, $rating);
        }
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Komentar berhasil ditambahkan',
            'data' => $newComment
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
 * PUT - Update komentar
 */
function updateComment() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['fid']) || !isset($input['commentId'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'fid dan commentId diperlukan'
            ]);
            exit;
        }
        
        $fid = intval($input['fid']);
        $commentId = intval($input['commentId']);
        
        $supabase = getSupabaseRest();
        
        $updateData = [];
        
        if (isset($input['komentar'])) {
            $updateData['komentar'] = htmlspecialchars($input['komentar'], ENT_QUOTES, 'UTF-8');
        }
        
        if (isset($input['rating'])) {
            $rating = intval($input['rating']);
            if ($rating >= 1 && $rating <= 5) {
                $updateData['rating'] = $rating;
            }
        }
        
        if (empty($updateData)) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Tidak ada data yang diupdate'
            ]);
            exit;
        }
        
        $supabase->patch('comments', $updateData, [
            'id' => 'eq.' . $commentId,
            'fid' => 'eq.' . $fid
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Komentar berhasil diupdate'
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
 * DELETE - Hapus komentar
 */
function deleteComment() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['fid']) || !isset($input['commentId'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'fid dan commentId diperlukan'
            ]);
            exit;
        }
        
        $fid = intval($input['fid']);
        $commentId = intval($input['commentId']);
        
        $supabase = getSupabaseRest();
        
        $existing = $supabase->get('comments', [
            'select' => 'id',
            'id' => 'eq.' . $commentId,
            'fid' => 'eq.' . $fid
        ]);
        
        if (empty($existing)) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Komentar tidak ditemukan'
            ]);
            exit;
        }
        
        // Delete
        $supabase->delete('comments', [
            'id' => 'eq.' . $commentId,
            'fid' => 'eq.' . $fid
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Komentar berhasil dihapus'
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
 * Helper function untuk update rating dari komentar
 */
function updateRatingFromComment($fid, $rating) {
    try {
        $userId = 'comment_' . md5($_SERVER['REMOTE_ADDR'] . time() . rand());
        
        $supabase = getSupabaseRest();
        
        $existing = $supabase->get('ratings', [
            'select' => 'id',
            'fid' => 'eq.' . $fid,
            'user_id' => 'eq.' . $userId
        ]);
        
        $data = [
            'fid' => $fid,
            'rating' => $rating,
            'user_id' => $userId,
            'source' => 'comment'
        ];
        
        if (!empty($existing)) {
            $supabase->patch('ratings', $data, [
                'fid' => 'eq.' . $fid,
                'user_id' => 'eq.' . $userId
            ]);
        } else {
            $supabase->post('ratings', $data);
        }
        
    } catch (Exception $e) {
        error_log("Error updating rating from comment: " . $e->getMessage());
    }
}
