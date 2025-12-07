<?php
/**
 * Endpoint untuk mengambil data GeoJSON Kantor Pos
 * Menggunakan Supabase REST API (PostgREST) via HTTPS
 * Mendukung GET untuk read dan POST untuk create/update/delete
 */

require_once __DIR__ . '/../config/supabase-rest.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$adminPassword = 'akuanaksehattubuhkukuat666';
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getKantorPos();
        break;
    
    case 'POST':
        createKantorPos();
        break;
    
    case 'PUT':
        updateKantorPos();
        break;
    
    case 'DELETE':
        deleteKantorPos();
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
 * GET - Mengembalikan semua data kantor pos sebagai GeoJSON
 */
function getKantorPos() {
    try {
        $supabase = getSupabaseRest();
        
        // Ambil semua kantor pos
        $kantorPos = $supabase->get('kantor_pos', ['select' => 'fid,nama,lokasi,longitude,latitude', 'order' => 'fid']);
        
        // Ambil rating stats
        $ratings = $supabase->get('ratings', [
            'select' => 'fid,rating',
        ]);
        
        // Group ratings by fid
        $ratingStats = [];
        foreach ($ratings as $rating) {
            $fid = $rating['fid'];
            if (!isset($ratingStats[$fid])) {
                $ratingStats[$fid] = ['sum' => 0, 'count' => 0];
            }
            $ratingStats[$fid]['sum'] += $rating['rating'];
            $ratingStats[$fid]['count']++;
        }
        
        // Ambil comment counts
        $comments = $supabase->get('comments', [
            'select' => 'fid',
        ]);
        
        $commentCounts = [];
        foreach ($comments as $comment) {
            $fid = $comment['fid'];
            $commentCounts[$fid] = ($commentCounts[$fid] ?? 0) + 1;
        }
        
        // Build GeoJSON features
        $features = [];
        foreach ($kantorPos as $kp) {
            $fid = (int)$kp['fid'];
            $properties = [
                'fid' => $fid,
                'nama' => $kp['nama'],
                'lokasi' => $kp['lokasi'],
                'id' => $fid
            ];
            
            // Add rating stats
            if (isset($ratingStats[$fid]) && $ratingStats[$fid]['count'] > 0) {
                $properties['rating'] = [
                    'average' => round($ratingStats[$fid]['sum'] / $ratingStats[$fid]['count'], 2),
                    'count' => $ratingStats[$fid]['count']
                ];
            }
            
            // Add comment count
            if (isset($commentCounts[$fid]) && $commentCounts[$fid] > 0) {
                $properties['stats'] = [
                    'totalComments' => $commentCounts[$fid]
                ];
            }
            
            $features[] = [
                'type' => 'Feature',
                'properties' => $properties,
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [
                        (float)$kp['longitude'],
                        (float)$kp['latitude']
                    ]
                ]
            ];
        }
        
        $geojson = [
            'type' => 'FeatureCollection',
            'name' => 'poinkantorpos',
            'crs' => [
                'type' => 'name',
                'properties' => [
                    'name' => 'urn:ogc:def:crs:OGC:1.3:CRS84'
                ]
            ],
            'features' => $features
        ];
        
        header('Content-Type: application/geo+json; charset=utf-8');
        echo json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * POST - Menambah data kantor pos baru
 */
function createKantorPos() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Data tidak boleh kosong'
            ]);
            exit;
        }

        if (!validateAdminPassword($input)) {
            exit;
        }
        
        if (!isset($input['nama']) || !isset($input['lokasi']) || !isset($input['coordinates'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Data tidak lengkap. Diperlukan: nama, lokasi, coordinates [lng, lat]'
            ]);
            exit;
        }
        
        $supabase = getSupabaseRest();
        
        // Round coordinates to 8 decimal places
        $data = [
            'nama' => htmlspecialchars($input['nama'], ENT_QUOTES, 'UTF-8'),
            'lokasi' => htmlspecialchars($input['lokasi'], ENT_QUOTES, 'UTF-8'),
            'longitude' => round(floatval($input['coordinates'][0]), 8),
            'latitude' => round(floatval($input['coordinates'][1]), 8)
        ];
        
        $result = $supabase->post('kantor_pos', $data);
        
        if (is_array($result) && !empty($result)) {
            $newRow = $result[0];
        } else if (is_array($result) && empty($result)) {
            throw new Exception("Insert berhasil tapi tidak ada data yang dikembalikan");
        } else {
            $newRow = $result;
        }
        
        if (!isset($newRow['fid'])) {
            throw new Exception("Response tidak valid: fid tidak ditemukan. Response: " . json_encode($result));
        }
        
        $fid = (int)$newRow['fid'];
        
        $newFeature = [
            'type' => 'Feature',
            'properties' => [
                'fid' => $fid,
                'nama' => $input['nama'],
                'lokasi' => $input['lokasi'],
                'id' => $fid
            ],
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [
                    floatval($input['coordinates'][0]),
                    floatval($input['coordinates'][1])
                ]
            ]
        ];
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Data berhasil ditambahkan',
            'data' => $newFeature
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
 * PUT - Mengupdate data kantor pos
 */
function updateKantorPos() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Data tidak boleh kosong'
            ]);
            exit;
        }

        if (!validateAdminPassword($input)) {
            exit;
        }

        if (!isset($input['fid'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'ID (fid) diperlukan untuk update'
            ]);
            exit;
        }
        
        $supabase = getSupabaseRest();
        
        // Build update data
        $updateData = [];
        
        if (isset($input['nama'])) {
            $updateData['nama'] = htmlspecialchars($input['nama'], ENT_QUOTES, 'UTF-8');
        }
        
        if (isset($input['lokasi'])) {
            $updateData['lokasi'] = htmlspecialchars($input['lokasi'], ENT_QUOTES, 'UTF-8');
        }
        
        if (isset($input['coordinates'])) {
            $updateData['longitude'] = floatval($input['coordinates'][0]);
            $updateData['latitude'] = floatval($input['coordinates'][1]);
        }
        
        if (empty($updateData)) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Tidak ada data yang diupdate'
            ]);
            exit;
        }
        
        $supabase->patch('kantor_pos', $updateData, ['fid' => 'eq.' . intval($input['fid'])]);
        
        $updated = $supabase->get('kantor_pos', ['select' => '*', 'fid' => 'eq.' . intval($input['fid'])]);
        
        if (empty($updated)) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Data tidak ditemukan'
            ]);
            exit;
        }
        
        $row = $updated[0];
        
        $feature = [
            'type' => 'Feature',
            'properties' => [
                'fid' => (int)$row['fid'],
                'nama' => $row['nama'],
                'lokasi' => $row['lokasi'],
                'id' => (int)$row['fid']
            ],
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [
                    (float)$row['longitude'],
                    (float)$row['latitude']
                ]
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Data berhasil diupdate',
            'data' => $feature
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
 * DELETE - Menghapus data kantor pos
 */
function deleteKantorPos() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Data tidak boleh kosong'
            ]);
            exit;
        }

        if (!validateAdminPassword($input)) {
            exit;
        }

        if (!isset($input['fid'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'ID (fid) diperlukan untuk delete'
            ]);
            exit;
        }
        
        $supabase = getSupabaseRest();
        
        $existing = $supabase->get('kantor_pos', ['select' => 'fid', 'fid' => 'eq.' . intval($input['fid'])]);
        
        if (empty($existing)) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Data tidak ditemukan'
            ]);
            exit;
        }
        
        // Delete
        $supabase->delete('kantor_pos', ['fid' => 'eq.' . intval($input['fid'])]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Data berhasil dihapus'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

function validateAdminPassword(&$input) {
    $password = $input['password'] ?? null;
    unset($input['password']);

    if ($password === null) {
        http_response_code(401);
        echo json_encode([
            'error' => true,
            'message' => 'Password diperlukan untuk melakukan perubahan data'
        ]);
        return false;
    }

    global $adminPassword;

    if ($password !== $adminPassword) {
        http_response_code(401);
        echo json_encode([
            'error' => true,
            'message' => 'Password salah'
        ]);
        return false;
    }

    return true;
}
