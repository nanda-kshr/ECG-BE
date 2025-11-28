<?php
// update_task.php - Update task status and feedback (doctor submits result)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Vary: Origin');
header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization, X-Requested-With, Accept');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Max-Age: 86400');
// Allow credentials for same-origin requests (cookies). If using CORS with credentials,
// replace '*' origin with a specific origin.
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$is_debug = (getenv('DEBUG') === '1');

try {
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/session_auth.php';
    // Start session and require authenticated user
    start_secure_session();
    require_login();

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) { 
        http_response_code(400); 
        echo json_encode(['success'=>false,'error'=>'Invalid JSON']); 
        exit; 
    }

    $taskId = (int)($data['task_id'] ?? 0);
    // Use authenticated session user id instead of client-supplied user_id
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $newStatus = strtolower(trim($data['status'] ?? ''));
    $feedback = trim($data['feedback'] ?? '');
    $imageId = (int)($data['image_id'] ?? 0);

    if ($taskId <= 0) {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'Missing task_id']);
        exit;
    }

    // Task-level status is deprecated; we ignore status changes here

    // Get current task
    $stmtTask = $pdo->prepare('SELECT * FROM tasks WHERE id = :id LIMIT 1');
    $stmtTask->execute(['id' => $taskId]);
    $task = $stmtTask->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        http_response_code(404);
        echo json_encode(['success'=>false,'error'=>'Task not found']);
        exit;
    }

    // Verify user is authorized (doctor assigned to task or admin)
    $stmtUser = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
    $stmtUser->execute(['id' => $userId]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'User not found']);
        exit;
    }

    $isAdmin = ($user['role'] === 'admin');
    $isAssignedDoctor = ((int)$task['assigned_doctor_id'] === $userId);
    $isAssignedPg = ((int)($task['assigned_pg_id'] ?? 0) === $userId);

    if (!$isAdmin && !$isAssignedDoctor && !$isAssignedPg) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Not authorized to update this task']);
        exit;
    }

    $canProvideFeedback = $isAdmin || $isAssignedDoctor;

    $pdo->beginTransaction();

    $updates = [];
    $setSql = [];
    
    if ($feedback !== '') {
        if ($imageId <= 0) {
            throw new RuntimeException('image_id is required when providing feedback');
        }
        $updates['feedback'] = $feedback;
        $setSql[] = 'comment = :feedback';
    }

    if (!empty($setSql)) {
        $updates['tid'] = $taskId;
        $updateSql = 'UPDATE tasks SET ' . implode(', ', $setSql) . ' WHERE id = :tid';
        $stmtUpdate = $pdo->prepare($updateSql);
        $stmtUpdate->execute($updates);
    }

    // If feedback provided, also update the related image's comment and status
    $taskCompleted = false;
    if ($feedback !== '') {
        if (!$canProvideFeedback) {
            http_response_code(403);
            echo json_encode(['success'=>false,'error'=>'Only assigned doctor can provide feedback']);
            exit;
        }
        $patientId = (int)$task['patient_id'];
        // Check if image exists for any task of this patient
        $stmtCheck = $pdo->prepare("SELECT 1 FROM ecg_images ei JOIN tasks t ON ei.task_id = t.id WHERE ei.id = :img AND t.patient_id = :pid LIMIT 1");
        $stmtCheck->execute(['img' => $imageId, 'pid' => $patientId]);
        if (!$stmtCheck->fetch()) {
            throw new RuntimeException('Image not found for this patient');
        }
        // Update the image
        $uimg = $pdo->prepare("UPDATE ecg_images SET comment = :cmt, status = 'completed' WHERE id = :img");
        $uimg->execute(['cmt' => $feedback, 'img' => $imageId]);
        if ($uimg->rowCount() === 0) {
            throw new RuntimeException('Failed to update image');
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
                    ->execute(['tid'=>$taskId,'uid'=>$userId,'old'=>null,'new'=>'completed','comment'=>'All last 10 images commented and completed']);
            }
        }
    }

    // Log history
    $hist = $pdo->prepare(
        'INSERT INTO task_history (task_id, changed_by, old_status, new_status, comment) 
         VALUES (:tid, :uid, :old, :new, :comment)'
    );
    $hist->execute([
        'tid' => $taskId,
        'uid' => $userId,
        'old' => null,
        'new' => ($feedback !== '' ? 'image_completed' : 'updated'),
        'comment' => $feedback !== '' ? ('Feedback: ' . substr($feedback, 0, 255)) : 'Task updated'
    ]);

    $pdo->commit();

    echo json_encode(['success'=>true, 'message' => 'Task updated', 'task_completed' => $taskCompleted]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('update_task error: '.$e->getMessage());
    http_response_code(500);
    $resp = ['success'=>false,'error'=>'Server error'];
    if ($is_debug) { $resp['detail'] = $e->getMessage(); }
    echo json_encode($resp);
}
?>
