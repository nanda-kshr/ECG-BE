<?php
// doctor_assign_pg.php - Doctor assigns a PG to a task (so both doctor and PG are responsible)
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

    $taskId = isset($data['task_id']) ? (int)$data['task_id'] : 0;
    $pgId = isset($data['pg_id']) ? (int)$data['pg_id'] : 0;
    $doctorId = isset($data['doctor_id']) ? (int)$data['doctor_id'] : 0; // acting doctor

    if ($taskId <= 0 || $pgId <= 0 || $doctorId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing required fields']); exit; }

    // Verify task exists
    $stmt = $pdo->prepare('SELECT assigned_doctor_id FROM tasks WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Task not found']); exit; }

    // Only the doctor assigned to the task may assign a PG to it
    if ((int)$task['assigned_doctor_id'] !== $doctorId) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Only the assigned doctor can assign a PG to this task']);
        exit;
    }

    // Verify PG exists and has role 'pg'
    $pst = $pdo->prepare('SELECT id, role FROM users WHERE id = :id LIMIT 1');
    $pst->execute(['id' => $pgId]);
    $pg = $pst->fetch(PDO::FETCH_ASSOC);
    if (!$pg || $pg['role'] !== 'pg') { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Assigned user must be a PG']); exit; }

    // Update task
    $pdo->beginTransaction();
    $upd = $pdo->prepare('UPDATE tasks SET assigned_pg_id = :pg, assigned_at = NOW() WHERE id = :id');
    $upd->execute(['pg' => $pgId, 'id' => $taskId]);

    // Log history
    $h = $pdo->prepare('INSERT INTO task_history (task_id, changed_by, old_status, new_status, comment) VALUES (:tid, :uid, NULL, NULL, :comment)');
    $h->execute(['tid' => $taskId, 'uid' => $doctorId, 'comment' => 'PG assigned to task: user_id='.$pgId]);

    $pdo->commit();

    echo json_encode(['success'=>true, 'task_id' => $taskId, 'assigned_pg_id' => $pgId]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('doctor_assign_pg error: '.$e->getMessage());
    http_response_code(500);
    $resp = ['success'=>false,'error'=>'Server error'];
    if ($is_debug) $resp['detail'] = $e->getMessage();
    echo json_encode($resp);
}
?>
