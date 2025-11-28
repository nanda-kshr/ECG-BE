<?php
// patients.php - Create and search patients (CR only)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Vary: Origin');
header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization, X-Requested-With, Accept');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Max-Age: 86400');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$is_debug = (getenv('DEBUG') === '1');

try {
    require_once __DIR__ . '/db.php';

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // search by name prefix
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $where = [];$params=[];
        if ($q !== '') { $where[] = 'name LIKE :q'; $params['q'] = $q.'%'; }
        $whereSql = empty($where) ? '' : ('WHERE '.implode(' AND ',$where));
        $sql = "SELECT id, patient_id, name, age, gender, phone, assigned_doctor_id, status, created_at FROM patients $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $st = $pdo->prepare($sql);
        foreach ($params as $k=>$v) { $st->bindValue(":$k", $v); }
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        echo json_encode(['success'=>true,'patients'=>$st->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($method === 'POST') {
        // create patient
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true); if (!is_array($data)) { $data = $_POST; }
        $name = trim((string)($data['name'] ?? ''));
        $age = isset($data['age']) ? (int)$data['age'] : null;
        $gender = isset($data['gender']) ? strtolower(trim((string)$data['gender'])) : null;
        $phone = isset($data['phone']) ? trim((string)$data['phone']) : '';
        if ($name === '') { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Name required']); exit; }
        if ($phone === '') { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Phone required']); exit; }
        // basic phone validation
        if (!preg_match('/^\+\d{7,15}$/', $phone)) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Phone must be in international format like +918848588455']); exit; }
        $allowedGenders = ['male','female','other']; if ($gender !== null && !in_array($gender, $allowedGenders, true)) { $gender = null; }

        // generate unique patient_id like upload_ecg
        $datePrefix = date('Ymd');
        $cnt = (int)$pdo->query("SELECT COUNT(*) FROM patients WHERE patient_id LIKE 'PAT{$datePrefix}%'")->fetchColumn();
        $pid = 'PAT'.$datePrefix.str_pad($cnt + 1, 3, '0', STR_PAD_LEFT);
        $st = $pdo->prepare('INSERT INTO patients (patient_id, name, age, gender, phone) VALUES (:pid,:name,:age,:gender,:phone)');
        $st->execute(['pid'=>$pid,'name'=>$name,'age'=>$age,'gender'=>$gender,'phone'=>$phone]);
        echo json_encode(['success'=>true,'id'=>(int)$pdo->lastInsertId(),'patient_id'=>$pid,'phone'=>$phone]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method not allowed']);

} catch (Throwable $e) {
    error_log('patients api error: '.$e->getMessage());
    http_response_code(500);
    $resp = ['success'=>false,'error'=>'Server error'];
    if ($is_debug) { $resp['detail'] = $e->getMessage(); }
    echo json_encode($resp);
}
