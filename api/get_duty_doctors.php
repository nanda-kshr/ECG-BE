<?php
// get_duty_doctors.php - Get current duty doctors using users.is_duty flag
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Vary: Origin');
header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization, X-Requested-With, Accept');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$is_debug = (getenv('DEBUG') === '1');

try {
    require_once __DIR__ . '/db.php';

    $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    $where = ["role = 'doctor'", 'is_duty = 1']; $params = [];
    if ($search !== '') { $where[] = '(name LIKE :q OR email LIKE :q)'; $params['q'] = "%$search%"; }

    $sql = 'SELECT id, name, email, is_duty, created_at FROM users WHERE '.implode(' AND ', $where).' ORDER BY name ASC LIMIT :limit OFFSET :offset';
    $st = $pdo->prepare($sql);
    foreach ($params as $k=>$v) { $st->bindValue(":$k", $v); }
    $st->bindValue(':limit', $limit, PDO::PARAM_INT);
    $st->bindValue(':offset', $offset, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success'=>true, 'doctors'=>$rows, 'count'=>count($rows)]);

} catch (Throwable $e) {
    error_log('get_duty_doctors error: '.$e->getMessage());
    http_response_code(500);
    $resp = ['success'=>false,'error'=>'Server error']; if ($is_debug) { $resp['detail']=$e->getMessage(); }
    echo json_encode($resp);
}
?>
