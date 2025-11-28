<?php
// Send CORS headers early so preflight requests don't get blocked
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Vary: Origin');
header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization, X-Requested-With, Accept');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// Toggle debug: set environment var DEBUG=1 or change to true temporarily
$is_debug = (getenv('DEBUG') === '1');

try {
    require_once __DIR__ . '/db.php'; // ensure this file defines $pdo (PDO)

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Invalid JSON']); exit; }

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    // Only allow 'doctor' or 'technician' via public registration. Do NOT accept 'admin'.
    $requestedRole = strtolower(trim($data['role'] ?? ''));
    $allowedPublicRoles = ['doctor', 'technician'];
    // Role is mandatory for signup. It must be one of the allowed public roles.
    if ($requestedRole === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Role is required']);
        exit;
    }
    if (!in_array($requestedRole, $allowedPublicRoles, true)) {
        http_response_code(400);
        $resp = ['success' => false, 'error' => 'Invalid role requested'];
        if ($is_debug) $resp['detail'] = ['allowed' => $allowedPublicRoles];
        echo json_encode($resp);
        exit;
    }
    $role = $requestedRole;

    if ($name === '' || $email === '' || $password === '') { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing required fields']); exit; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Invalid email']); exit; }
    if (strlen($password) < 8) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Password must be at least 8 characters']); exit; }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) { http_response_code(409); echo json_encode(['success'=>false,'error'=>'Email already registered']); exit; }

    // Hash password securely (use password_hash instead of storing plain text)
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name,:email,:hash,:role)');
    $ins->execute(['name'=>$name,'email'=>$email,'hash'=>$hash,'role'=>$role]);

    echo json_encode(['success'=>true,'userId'=> (int)$pdo->lastInsertId() ]);
} catch (Throwable $e) {
    error_log('register error: '.$e->getMessage());
    http_response_code(500);
    $resp = ['success'=>false,'error'=>'Server error'];
    if ($is_debug) { $resp['detail'] = $e->getMessage(); }
    echo json_encode($resp);
}
?>