<?php
// remove_duty_doctor.php - Admin clears current duty doctor
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Vary: Origin');
header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization, X-Requested-With, Accept');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Max-Age: 86400');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$is_debug = (getenv('DEBUG') === '1');

try {
    require_once __DIR__ . '/db.php';
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true); if (!is_array($data)) { $data = $_POST; }
    $adminId = isset($data['admin_id']) ? (int)$data['admin_id'] : 0;
    if ($adminId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing admin_id']); exit; }
    $st = $pdo->prepare('SELECT role FROM users WHERE id = :id'); $st->execute(['id'=>$adminId]); $adm = $st->fetch(PDO::FETCH_ASSOC);
    if (!$adm || $adm['role'] !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Only admins allowed']); exit; }
    $pdo->exec("UPDATE users SET is_duty = 0 WHERE role = 'doctor'");
    echo json_encode(['success'=>true]);
} catch (Throwable $e) {
    error_log('remove_duty_doctor error: '.$e->getMessage());
    http_response_code(500); $resp=['success'=>false,'error'=>'Server error']; if ($is_debug) { $resp['detail']=$e->getMessage(); } echo json_encode($resp);
}
