<?php
// doctors.php - Admin CRUD for doctors and duty control
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

    $requireAdmin = function($adminId) use ($pdo) {
        if ($adminId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing admin_id']); exit; }
        $st = $pdo->prepare('SELECT role FROM users WHERE id = :id');
        $st->execute(['id'=>$adminId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['role'] !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Only admins allowed']); exit; }
    };

    if ($method === 'GET') {
        // Get doctors; filter duty with ?duty=1 to get current duty doctor(s)
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $duty = isset($_GET['duty']) ? (int)$_GET['duty'] : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $where = ["role = 'doctor'"]; $params = [];
        if ($search !== '') { $where[] = '(name LIKE :q OR email LIKE :q)'; $params['q'] = "%$search%"; }
        if ($duty !== null) { $where[] = 'is_duty = :is_duty'; $params['is_duty'] = $duty ? 1 : 0; }
        $sql = 'SELECT id, name, email, role, is_duty, created_at FROM users WHERE '.implode(' AND ', $where).' ORDER BY name ASC LIMIT :limit OFFSET :offset';
        $st = $pdo->prepare($sql);
        foreach ($params as $k=>$v) { $st->bindValue(":$k", $v); }
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        echo json_encode(['success'=>true,'doctors'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true); if (!is_array($data)) { $data = $_POST; }

    if ($method === 'POST') {
        // Create doctor
        $adminId = isset($data['admin_id']) ? (int)$data['admin_id'] : 0; $requireAdmin($adminId);
        $name = trim((string)($data['name'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');
        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Invalid name/email/password']); exit; }
        $exists = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $exists->execute(['email'=>$email]);
        if ($exists->fetch()) { http_response_code(409); echo json_encode(['success'=>false,'error'=>'Email already exists']); exit; }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (name, email, password_hash, role, created_at) VALUES (:name,:email,:hash,'doctor', NOW())")->execute(['name'=>$name,'email'=>$email,'hash'=>$hash]);
        echo json_encode(['success'=>true,'doctor_id'=>(int)$pdo->lastInsertId()]);
        exit;
    }

    if ($method === 'PUT') {
        // Update doctor details or set duty doctor
        $adminId = isset($data['admin_id']) ? (int)$data['admin_id'] : 0; $requireAdmin($adminId);
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $setDuty = isset($data['set_duty']) ? (int)$data['set_duty'] : null; // if provided, toggle duty

        if ($setDuty !== null) {
            // Switch duty doctor: clear others then set
            $pdo->beginTransaction();
            $pdo->exec("UPDATE users SET is_duty = 0 WHERE role = 'doctor'");
            if ($id > 0 && $setDuty === 1) {
                $st = $pdo->prepare("UPDATE users SET is_duty = 1 WHERE id = :id AND role = 'doctor'");
                $st->execute(['id'=>$id]);
            }
            $pdo->commit();
            echo json_encode(['success'=>true]);
            exit;
        }

        if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing id']); exit; }
        $fields=[]; $params=['id'=>$id];
        if (isset($data['name'])) { $fields[]='name = :name'; $params['name']=trim((string)$data['name']); }
        if (isset($data['email'])) { $email=trim((string)$data['email']); if ($email===''||!filter_var($email,FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Invalid email']); exit; } $fields[]='email = :email'; $params['email']=$email; }
        if (isset($data['password']) && $data['password']!=='') { $fields[]='password_hash = :hash'; $params['hash']=password_hash((string)$data['password'], PASSWORD_DEFAULT); }
        if (empty($fields)) { echo json_encode(['success'=>false,'error'=>'No changes']); exit; }
        $sql = 'UPDATE users SET '.implode(', ',$fields).' WHERE id = :id AND role = "doctor"';
        $st = $pdo->prepare($sql); $st->execute($params);
        echo json_encode(['success'=>true]);
        exit;
    }

    if ($method === 'DELETE') {
        // Remove a doctor
        $rawId = isset($data['id']) ? (int)$data['id'] : (isset($_GET['id'])?(int)$_GET['id']:0);
        $adminId = isset($data['admin_id']) ? (int)$data['admin_id'] : (isset($_GET['admin_id'])?(int)$_GET['admin_id']:0); $requireAdmin($adminId);
        if ($rawId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing id']); exit; }
        // If deleting current duty doctor, also clear duty flag
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE users SET is_duty = 0 WHERE id = :id AND role = 'doctor'")->execute(['id'=>$rawId]);
        $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'doctor'")->execute(['id'=>$rawId]);
        $pdo->commit();
        echo json_encode(['success'=>true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method not allowed']);

} catch (Throwable $e) {
    error_log('doctors api error: '.$e->getMessage());
    http_response_code(500);
    $resp = ['success'=>false,'error'=>'Server error'];
    if ($is_debug) { $resp['detail'] = $e->getMessage(); }
    echo json_encode($resp);
}
