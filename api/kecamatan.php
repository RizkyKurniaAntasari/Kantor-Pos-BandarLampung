<?php
/**
 * Endpoint untuk mengambil data GeoJSON Kecamatan Bandar Lampung
 * Mengembalikan data dari file GeoJSON
 */

header('Content-Type: application/geo+json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$geojsonFile = __DIR__ . '/../data/kecamatanbalam.geojson';

if (!file_exists($geojsonFile)) {
    http_response_code(404);
    echo json_encode([
        'error' => true,
        'message' => 'File GeoJSON tidak ditemukan'
    ]);
    exit;
}

$geojsonData = file_get_contents($geojsonFile);
$json = json_decode($geojsonData, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error parsing GeoJSON: ' . json_last_error_msg()
    ]);
    exit;
}

echo $geojsonData;

