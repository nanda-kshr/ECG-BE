<?php
// get_image.php - Fetch image metadata or binary by image_id
header('Access-Control-Allow-Origin: *');
header('Vary: Origin');
header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization, X-Requested-With, Accept');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$is_debug = (getenv('DEBUG') === '1');

try {
    require_once __DIR__ . '/db.php';

    $imageId = isset($_GET['image_id']) ? (int)$_GET['image_id'] : 0;
    $download = isset($_GET['download']) ? (int)$_GET['download'] : 0; // 1 to stream file

    if ($imageId <= 0) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'image_id is required and must be a positive integer']);
        exit;
    }

    if ($download) {
        $sql = 'SELECT id, patient_id, technician_id, task_id, image_path, image_name, file_size, mime_type, created_at, comment
            FROM ecg_images WHERE id = :id LIMIT 1';
        $st = $pdo->prepare($sql);
        $st->execute(['id'=>$imageId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(404);
            echo json_encode(['success'=>false,'error'=>'Image not found']);
            exit;
        }

        $uploadsRoot = realpath(__DIR__ . '/../uploads/ecg_images');
        $fullFromDb = __DIR__ . '/../' . ltrim($row['image_path'], '/');
        $fullPath = realpath($fullFromDb);
        if ($fullPath === false || $uploadsRoot === false || strpos($fullPath, $uploadsRoot) !== 0 || !is_file($fullPath)) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(404);
            echo json_encode(['success'=>false,'error'=>'Image file not found on disk']);
            exit;
        }

        // Stream file
        $mime = $row['mime_type'] ?: 'application/octet-stream';
        header('Content-Type: '.$mime);
        header('Content-Length: '.filesize($fullPath));
        $filename = $row['image_name'] ?: basename($fullPath);
        header('Content-Disposition: inline; filename="'.basename($filename).'"');
        readfile($fullPath);
        exit;
    } else {
        $sql = 'SELECT id, patient_id, technician_id, task_id, image_path, image_name, file_size, mime_type, created_at, comment
                FROM ecg_images WHERE id = :id LIMIT 1';
        $st = $pdo->prepare($sql);
        $st->execute(['id'=>$imageId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        header('Content-Type: application/json; charset=utf-8');
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success'=>false,'error'=>'Image not found']);
        } else {
            echo json_encode(['success'=>true,'image'=>$row]);
        }
        exit;
    }

} catch (Throwable $e) {
    error_log('get_image error: '.$e->getMessage());
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    $resp = ['success'=>false,'error'=>'Server error']; if ($is_debug) { $resp['detail']=$e->getMessage(); }
    echo json_encode($resp);
}
?>
