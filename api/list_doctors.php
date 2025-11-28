<?php
// list_doctors.php - Get list of all doctors with optional search and duty status
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

    // Query params
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    $where = ["u.role = 'doctor'"];
    $params = [];

    if ($search !== '') {
        $where[] = "(u.name LIKE :search OR u.email LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }

    $whereClause = implode(' AND ', $where);

    // Check if duty_roster table exists (schema uses user_id and no shift column)
    $hasDutyRoster = false;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'duty_roster'");
        $hasDutyRoster = (bool)$stmt->fetch();
    } catch (Throwable $ignore) {}

    $sql = "SELECT 
                u.id, u.name, u.email, u.created_at, u.is_duty
            FROM users u
            WHERE $whereClause
            ORDER BY u.name ASC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue(":$key", $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM users u WHERE $whereClause";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $val) {
        $countStmt->bindValue(":$key", $val);
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'doctors' => $doctors,
        'total' => $total,
        'count' => count($doctors)
    ]);

} catch (Throwable $e) {
    error_log('list_doctors error: ' . $e->getMessage());
    http_response_code(500);
    $resp = ['success' => false, 'error' => 'Server error'];
    if ($is_debug) { $resp['detail'] = $e->getMessage(); }
    echo json_encode($resp);
}
