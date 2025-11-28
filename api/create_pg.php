<?php
// create_pg.php - Allow doctors to create PG (physician's assistant) accounts
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
    $data = json_decode($raw, true);
    if (!is_array($data)) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Invalid JSON']); exit; }

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $createdBy = isset($data['created_by']) ? (int)$data['created_by'] : (isset($data['doctor_id']) ? (int)$data['doctor_id'] : 0); // doctor id creating this PG

    if ($createdBy <= 0 || $name === '' || $email === '' || $password === '') { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing required fields']); exit; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Invalid email']); exit; }
    if (strlen($password) < 8) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Password must be at least 8 characters']); exit; }

    // Verify creator is a doctor
    $stmt = $pdo->prepare('SELECT id, role FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $createdBy]);
    $creator = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$creator || $creator['role'] !== 'doctor') { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Only doctors can create PG accounts']); exit; }

    // Ensure email not already used
    $chk = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $chk->execute(['email' => $email]);
    if ($chk->fetch()) { http_response_code(409); echo json_encode(['success'=>false,'error'=>'Email already registered']); exit; }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (:name,:email,:hash,:role,NOW())');
    $ins->execute(['name'=>$name,'email'=>$email,'hash'=>$hash,'role'=>'pg']);
    
    $pgId = (int)$pdo->lastInsertId();
    
    $linkPg = $pdo->prepare('INSERT INTO doctor_pg (d_id, pg_id, created_at) VALUES (:did, :pgid, NOW())');
    $linkPg->execute(['did' => $createdBy, 'pgid' => $pgId]);

    echo json_encode(['success'=>true,'user_id' => $pgId]);

} catch (Throwable $e) {
    error_log('create_pg error: '.$e->getMessage());
    http_response_code(500);
    $resp = ['success'=>false,'error'=>'Server error'];
    if ($is_debug) $resp['detail'] = $e->getMessage();
    echo json_encode($resp);
}
?>
