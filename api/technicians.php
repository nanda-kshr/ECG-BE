<?php
// technicians.php - Admin CRUD for technicians
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Vary: Origin');
header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization, X-Requested-With, Accept');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Max-Age: 86400');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$is_debug = (getenv('DEBUG') === '1');

try {
    require_once __DIR__ . '/db.php';

    $method = $_SERVER['REQUEST_METHOD'];

    // helper to verify admin
    $requireAdmin = function($adminId) use ($pdo) {
        if ($adminId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing admin_id']); exit; }
        $st = $pdo->prepare('SELECT role FROM users WHERE id = :id');
        $st->execute(['id'=>$adminId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['role'] !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Only admins allowed']); exit; }
    };

    if ($method === 'GET') {
        // list or get by id
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        if ($id > 0) {
            $st = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = :id AND role = 'technician' LIMIT 1");
            $st->execute(['id'=>$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Technician not found']); exit; }
            echo json_encode(['success'=>true,'technician'=>$row]);
            exit;
        }

        $where = ["role = 'technician'"];
        $params = [];
        if ($search !== '') { $where[] = '(name LIKE :q OR email LIKE :q)'; $params['q'] = "%$search%"; }
        $whereSql = implode(' AND ', $where);
        $sql = "SELECT id, name, email, role, created_at FROM users WHERE $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $st = $pdo->prepare($sql);
        foreach ($params as $k=>$v) { $st->bindValue(":$k", $v); }
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        echo json_encode(['success'=>true,'technicians'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    // parse JSON body for POST/PUT/DELETE unless it's form-encoded
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) { $data = $_POST; }

    if ($method === 'POST') {
        $adminId = isset($data['admin_id']) ? (int)$data['admin_id'] : 0; $requireAdmin($adminId);
        $name = trim((string)($data['name'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');
        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            http_response_code(400); echo json_encode(['success'=>false,'error'=>'Invalid name/email/password']); exit;
        }
        $exists = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $exists->execute(['email'=>$email]);
        if ($exists->fetch()) { http_response_code(409); echo json_encode(['success'=>false,'error'=>'Email already exists']); exit; }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, created_at) VALUES (:name,:email,:hash,'technician', NOW())");
        $ins->execute(['name'=>$name,'email'=>$email,'hash'=>$hash]);
        echo json_encode(['success'=>true,'technician_id'=>(int)$pdo->lastInsertId()]);
        exit;
    }

    if ($method === 'PUT') {
        $adminId = isset($data['admin_id']) ? (int)$data['admin_id'] : 0; $requireAdmin($adminId);
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing id']); exit; }
        $fields = [];$params=['id'=>$id];
        if (isset($data['name'])) { $fields[] = 'name = :name'; $params['name'] = trim((string)$data['name']); }
        if (isset($data['email'])) {
            $email = trim((string)$data['email']);
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Invalid email']); exit; }
            $fields[] = 'email = :email'; $params['email'] = $email;
        }
        if (isset($data['password']) && $data['password'] !== '') { $fields[] = 'password_hash = :hash'; $params['hash'] = password_hash((string)$data['password'], PASSWORD_DEFAULT); }
        if (empty($fields)) { echo json_encode(['success'=>false,'error'=>'No changes']); exit; }
        $sql = 'UPDATE users SET '.implode(', ',$fields).' WHERE id = :id AND role = "technician"';
        $st = $pdo->prepare($sql); $st->execute($params);
        echo json_encode(['success'=>true]);
        exit;
    }

    if ($method === 'DELETE') {
        $id = isset($data['id']) ? (int)$data['id'] : (isset($_GET['id'])?(int)$_GET['id']:0);
        $adminId = isset($data['admin_id']) ? (int)$data['admin_id'] : (isset($_GET['admin_id'])?(int)$_GET['admin_id']:0); $requireAdmin($adminId);
        if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing id']); exit; }
        $del = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'technician'");
        $del->execute(['id'=>$id]);
        echo json_encode(['success'=>true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method not allowed']);

} catch (Throwable $e) {
    error_log('technicians api error: '.$e->getMessage());
    http_response_code(500);
    $resp = ['success'=>false,'error'=>'Server error'];
    if ($is_debug) { $resp['detail'] = $e->getMessage(); }
    echo json_encode($resp);
}
