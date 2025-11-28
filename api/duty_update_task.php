<?php
// duty_update_task.php - Duty doctor marks an assigned task as completed with comment
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

    // Accept JSON or form data
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) { $data = $_POST; }

    $doctorId = isset($data['doctor_id']) ? (int)$data['doctor_id'] : 0;
    $taskId = isset($data['task_id']) ? (int)$data['task_id'] : 0;
    $comment = isset($data['comment']) ? trim((string)$data['comment']) : '';
    $imageId = isset($data['image_id']) ? (int)$data['image_id'] : 0;

    if ($doctorId <= 0 || $taskId <= 0) {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'Missing doctor_id or task_id']);
        exit;
    }

    // Verify doctor is current duty doctor
    $stmt = $pdo->prepare("SELECT role, is_duty FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $doctorId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$doc || $doc['role'] !== 'doctor') {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Only doctors can update task']);
        exit;
    }
    if ((int)$doc['is_duty'] !== 1) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Doctor is not current duty doctor']);
        exit;
    }

    // Load task and verify assignment
    $t = $pdo->prepare('SELECT id, assigned_doctor_id, comment FROM tasks WHERE id = :id LIMIT 1');
    $t->execute(['id'=>$taskId]);
    $task = $t->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        http_response_code(404);
        echo json_encode(['success'=>false,'error'=>'Task not found']);
        exit;
    }
    if ((int)$task['assigned_doctor_id'] !== $doctorId) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Task not assigned to this doctor']);
        exit;
    }

    // Note: task status deprecated; completion now tracked per-image in ecg_images

    $pdo->beginTransaction();

    // Optionally update task comment (no task status change)
    if ($comment !== '') {
        if ($imageId <= 0) { throw new RuntimeException('image_id is required when providing comment'); }
        $sql = 'UPDATE tasks SET comment = :comment WHERE id = :id';
        $pdo->prepare($sql)->execute(['comment'=>$comment, 'id'=>$taskId]);
    }

    // If comment provided, also update that image's comment tied to this task
    $taskCompleted = false;
    if ($comment !== '') {
        $uimg = $pdo->prepare("UPDATE ecg_images SET comment = :cmt, status = 'completed' WHERE id = :img AND task_id = :tid");
        $uimg->execute(['cmt'=>$comment, 'img'=>$imageId, 'tid'=>$taskId]);
        if ($uimg->rowCount() === 0) {
            throw new RuntimeException('Image not found for this task or not linked');
        }

        // Check if last 10 images for this task are all commented and completed
        $chk = $pdo->prepare("SELECT status, comment FROM ecg_images WHERE task_id = :tid ORDER BY created_at DESC, id DESC LIMIT 10");
        $chk->execute(['tid'=>$taskId]);
        $rows = $chk->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $allDone = true;
            foreach ($rows as $r) {
                if (($r['status'] ?? '') !== 'completed' || trim((string)($r['comment'] ?? '')) === '') { $allDone = false; break; }
            }
            if ($allDone) {
                $taskCompleted = true;
                $pdo->prepare('INSERT INTO task_history (task_id, changed_by, old_status, new_status, comment) VALUES (:tid,:uid,:old,:new,:comment)')
                    ->execute(['tid'=>$taskId,'uid'=>$doctorId,'old'=>null,'new'=>'completed','comment'=>'All last 10 images commented and completed']);
            }
        }
    }

    // History log
    $hist = $pdo->prepare('INSERT INTO task_history (task_id, changed_by, old_status, new_status, comment) VALUES (:tid, :uid, :old, :new, :comment)');
    $hist->execute([
        'tid' => $taskId,
        'uid' => $doctorId,
        'old' => null,
        'new' => ($comment !== '' ? 'image_completed' : 'updated'),
        'comment' => $comment !== '' ? ('Doctor: '.substr($comment, 0, 255)) : 'Task updated by duty doctor'
    ]);

    $pdo->commit();

    echo json_encode(['success'=>true,'message'=>'Update recorded','task_completed'=>$taskCompleted]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('duty_update_task error: '.$e->getMessage());
    http_response_code(500);
    $resp = ['success'=>false,'error'=>'Server error']; if ($is_debug) { $resp['detail'] = $e->getMessage(); }
    echo json_encode($resp);
}
