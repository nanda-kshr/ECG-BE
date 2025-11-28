<?php
// create_task.php - Technician creates a task manually (without bulk image upload) and auto-assigns to duty doctor
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

    // Detect multipart vs JSON
    $isMultipart = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;
    if ($isMultipart) {
        $data = $_POST; // form fields
    } else {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true); if (!is_array($data)) { $data = $_POST; }
    }

    $technicianId = isset($data['technician_id']) ? (int)$data['technician_id'] : 0;
    $patientId = isset($data['patient_id']) ? (int)$data['patient_id'] : 0; // patients.id
    $priority = strtolower(trim((string)($data['priority'] ?? 'normal')));
    $notes = trim((string)($data['technician_notes'] ?? ''));
    $comment = trim((string)($data['comment'] ?? ''));

    if ($technicianId <= 0 || $patientId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing technician_id or patient_id']); exit; }

    // verify technician
    $st = $pdo->prepare("SELECT role FROM users WHERE id = :id");
    $st->execute(['id'=>$technicianId]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    if (!$u || $u['role'] !== 'technician') { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Only technicians can create tasks']); exit; }

    // verify patient
    $stp = $pdo->prepare('SELECT id FROM patients WHERE id = :id');
    $stp->execute(['id'=>$patientId]);
    if (!$stp->fetch()) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Patient not found']); exit; }

    $allowedPriorities = ['low','normal','high','urgent']; if (!in_array($priority,$allowedPriorities,true)) { $priority='normal'; }

    if (!$isMultipart || !isset($_FILES['image'])) {
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'At least one image is required (multipart/form-data with file field `image`)']);
        exit;
    }

    $pdo->beginTransaction();

    // create task (task-level status removed; image-level status is used)
    $ins = $pdo->prepare('INSERT INTO tasks (patient_id, technician_id, priority, technician_notes, comment) VALUES (:pid,:tid,:prio,:notes,:comment)');
    $ins->execute(['pid'=>$patientId,'tid'=>$technicianId,'prio'=>$priority,'notes'=>$notes,'comment'=>$comment !== '' ? $comment : null]);
    $taskId = (int)$pdo->lastInsertId();

    // history
    $pdo->prepare('INSERT INTO task_history (task_id, changed_by, old_status, new_status, comment) VALUES (:tid,:uid,NULL,:new,:comment)')->execute(['tid'=>$taskId,'uid'=>$technicianId,'new'=>'pending','comment'=>'Task created manually']);

    $imagesUploaded = 0;
    $allowedTypes = ['image/jpeg','image/jpg','image/png','image/gif'];
    $uploadDir = __DIR__ . '/../uploads/ecg_images/';
    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

    // Normalize files array to support single and multiple uploads (image or image[])
    $files = $_FILES['image'];
    $fileCount = 0;
    if (is_array($files['name'])) {
        $fileCount = count($files['name']);
    } else {
        $fileCount = ($files['name'] !== '' ? 1 : 0);
    }

    if ($fileCount === 0) {
        throw new RuntimeException('At least one image is required');
    }

    // Prepare DB statement once
    $imgIns = $pdo->prepare('INSERT INTO ecg_images (patient_id, technician_id, task_id, image_path, image_name, file_size, mime_type) VALUES (:pid,:tid,:task_id,:path,:name,:size,:mime)');

    // Track saved disk paths so we can remove them on rollback
    $savedDiskPaths = [];


    for ($i = 0; $i < $fileCount; $i++) {
        // support both single and multiple upload formats
        $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $origName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $mime = is_array($files['type']) ? $files['type'][$i] : $files['type'];
        $size = is_array($files['size']) ? (int)$files['size'][$i] : (int)$files['size'];

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('File upload error for file: ' . $origName);
        }

        if (!in_array($mime, $allowedTypes, true)) {
            throw new RuntimeException('Unsupported file type for file: ' . $origName);
        }

        $ext = pathinfo($origName, PATHINFO_EXTENSION);
        $newFileName = 'TASK'.$taskId.'_'.time().'_'.($i+1).'.'.$ext;
        $targetPath = $uploadDir . $newFileName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new RuntimeException('Failed to move uploaded file: ' . $origName);
        }

        // insert DB row
        $relativePath = 'uploads/ecg_images/'.$newFileName;
        $imgIns->execute([
            'pid' => $patientId,
            'tid' => $technicianId,
            'task_id' => $taskId,
            'path' => $relativePath,
            'name' => $origName,
            'size' => $size,
            'mime' => $mime
        ]);

        $imagesUploaded++;
        $savedDiskPaths[] = $targetPath;
    }

    // auto assign to current duty doctor
    $dutyStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'doctor' AND is_duty = 1 LIMIT 1");
    $dutyStmt->execute();
    $duty = $dutyStmt->fetch(PDO::FETCH_ASSOC);
    if ($duty && isset($duty['id'])) {
        $did = (int)$duty['id'];
        $pdo->prepare('UPDATE tasks SET assigned_doctor_id = :did, assigned_at = NOW() WHERE id = :tid')->execute(['did'=>$did,'tid'=>$taskId]);
        // optional: set patient assigned_doctor_id
        $pdo->prepare('UPDATE patients SET assigned_doctor_id = :did, status = :stP WHERE id = :pid')->execute(['did'=>$did,'stP'=>'in_progress','pid'=>$patientId]);
        $pdo->prepare('INSERT INTO task_history (task_id, changed_by, old_status, new_status, comment) VALUES (:tid,:uid,:old,:new,:comment)')->execute(['tid'=>$taskId,'uid'=>$technicianId,'old'=>null,'new'=>'assigned','comment'=>'Auto-assigned duty doctor']);
    }

    $pdo->commit();
    echo json_encode(['success'=>true,'task_id'=>$taskId,'images_uploaded'=>$imagesUploaded]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    // cleanup any files moved to disk during this request
    if (isset($savedDiskPaths) && is_array($savedDiskPaths) && count($savedDiskPaths) > 0) {
        foreach ($savedDiskPaths as $p) {
            if ($p && file_exists($p)) { @unlink($p); }
        }
    }
    error_log('create_task error: '.$e->getMessage());
    http_response_code(500);
    $resp = ['success'=>false,'error'=>'Server error']; if ($is_debug) { $resp['detail']=$e->getMessage(); }
    echo json_encode($resp);
}
?>
