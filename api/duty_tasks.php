<?php
// duty_tasks.php - Duty doctor fetches tasks assigned to him
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

    $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0; // optional explicit doctor id

    // Determine current duty doctor if doctor_id not provided
    if ($doctorId <= 0) {
        $st = $pdo->query("SELECT id FROM users WHERE role = 'doctor' AND is_duty = 1 LIMIT 1");
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) { echo json_encode(['success'=>true,'tasks'=>[],'count'=>0,'message'=>'No duty doctor set']); exit; }
        $doctorId = (int)$row['id'];
    }

    // verify doctor is duty doctor
    $chk = $pdo->prepare("SELECT is_duty FROM users WHERE id = :id AND role='doctor'");
    $chk->execute(['id'=>$doctorId]);
    $doc = $chk->fetch(PDO::FETCH_ASSOC);
    if (!$doc) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'Doctor not found']); exit; }
    if ((int)$doc['is_duty'] !== 1) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Doctor is not current duty doctor']); exit; }

    // Task-level status removed; only filter by assigned doctor; optional image status filter
    $where = ['t.assigned_doctor_id = :did']; $params=['did'=>$doctorId];
    $imageStatus = isset($_GET['image_status']) ? trim((string)$_GET['image_status']) : null;
    $allowedImageStatuses = ['pending','assigned','in_progress','completed'];
    if ($imageStatus && in_array($imageStatus, $allowedImageStatuses, true)) {
        $where[] = "EXISTS (SELECT 1 FROM ecg_images e WHERE e.task_id = t.id AND e.status = :img_status)";
        $params['img_status'] = $imageStatus;
    } else {
        $imageStatus = null;
    }

        $sql = "SELECT t.id, t.patient_id, t.priority, t.technician_id, t.assigned_at, t.created_at, t.technician_notes, t.comment,
               p.name as patient_name, p.patient_id as patient_code,
               utech.name as technician_name, udoc.name as assigned_doctor_name
            FROM tasks t
            LEFT JOIN patients p ON p.id = t.patient_id
            LEFT JOIN users utech ON utech.id = t.technician_id
            LEFT JOIN users udoc ON udoc.id = t.assigned_doctor_id
            WHERE ".implode(' AND ', $where)." ORDER BY t.created_at DESC";
    $st2 = $pdo->prepare($sql); $st2->execute($params);
    $tasks = $st2->fetchAll(PDO::FETCH_ASSOC);

    // Attach last 10 image names and comments per patient
    if (!empty($tasks)) {
        $patientIds = [];
        foreach ($tasks as $t) { if (!empty($t['patient_id'])) { $patientIds[(int)$t['patient_id']] = true; } }
        $patientIds = array_keys($patientIds);

        if (!empty($patientIds)) {
            $pPlaceholders = [];
            $imgParams = [];
            foreach ($patientIds as $i => $pid) {
                $phKey = 'pid'.$i;
                $pPlaceholders[] = ':' . $phKey;
                $imgParams[$phKey] = $pid;
            }
            $placeholders = implode(',', $pPlaceholders);
            $imgSql = "SELECT id, patient_id, image_name, comment, status, created_at FROM ecg_images WHERE patient_id IN ($placeholders)";
            if ($imageStatus !== null) { $imgSql .= ' AND status = :img_status'; $imgParams['img_status'] = $imageStatus; }
            $imgSql .= ' ORDER BY patient_id, created_at DESC, id DESC';
            $imgStmt = $pdo->prepare($imgSql);
            $imgStmt->execute($imgParams);
            $rows = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

            $byPatient = [];
            foreach ($rows as $r) {
                $pid = (int)$r['patient_id'];
                if (!isset($byPatient[$pid])) { $byPatient[$pid] = []; }
                if (count($byPatient[$pid]) < 10) {
                    $byPatient[$pid][] = [
                        'image_id' => (int)$r['id'],
                        'image_name' => $r['image_name'],
                        'comment' => $r['comment'],
                        'status' => $r['status'],
                        'created_at' => $r['created_at']
                    ];
                }
            }

            foreach ($tasks as &$t) {
                $pid = (int)$t['patient_id'];
                $t['patient_last_images'] = $byPatient[$pid] ?? [];
            }
            unset($t);
        }
    }

    echo json_encode(['success'=>true,'tasks'=>$tasks,'count'=>count($tasks)]);

} catch (Throwable $e) {
    error_log('duty_tasks error: '.$e->getMessage());
    http_response_code(500);
    $resp = ['success'=>false,'error'=>'Server error']; if ($is_debug) { $resp['detail']=$e->getMessage(); }
    echo json_encode($resp);
}
