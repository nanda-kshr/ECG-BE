<?php
// list_tasks.php - List tasks with filters (for admin/doctor/technician)
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
    // Task-level status is removed in the schema; keep filters for doctor/technician/pg only
    $status = null;
    $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
    $technicianId = isset($_GET['technician_id']) ? (int)$_GET['technician_id'] : null;
    $pgId = isset($_GET['pg_id']) ? (int)$_GET['pg_id'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    // If provided, return all tasks for the last N distinct patients matching filters
    $patientsLimit = isset($_GET['patients_limit']) ? (int)$_GET['patients_limit'] : 0;
    $groupByPatient = isset($_GET['group_by_patient']) ? (int)$_GET['group_by_patient'] : 0;
    $imageStatus = isset($_GET['image_status']) ? trim((string)$_GET['image_status']) : null;

    $where = [];
    $params = [];

    // Note: task-level status no longer exists (moved to ecg_images.status)
    // Optional filter: image_status will filter tasks to those that have at least one image with the status
    $allowedImageStatuses = ['pending','assigned','in_progress','completed'];
    if ($imageStatus && in_array($imageStatus, $allowedImageStatuses, true)) {
        $where[] = "EXISTS (SELECT 1 FROM ecg_images e WHERE e.task_id = t.id AND e.status = :img_status)";
        $params['img_status'] = $imageStatus;
    } else {
        $imageStatus = null; // ignore invalid values
    }

    if ($doctorId) {
        $where[] = 't.assigned_doctor_id = :did';
        $params['did'] = $doctorId;
    }

    if ($technicianId) {
        $where[] = 't.technician_id = :tid';
        $params['tid'] = $technicianId;
    }

    if ($pgId) {
        $where[] = 't.assigned_pg_id = :pgid';
        $params['pgid'] = $pgId;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT 
                t.id, t.patient_id, t.technician_id, t.assigned_doctor_id, t.assigned_pg_id,
                t.priority, t.technician_notes, t.assigned_at, t.created_at,
                tech.name as technician_name, tech.email as technician_email,
                doc.name as doctor_name, doc.email as doctor_email,
                pg.name as pg_name, pg.email as pg_email,
                p.name as patient_name, p.patient_id as patient_id_str, p.age as patient_age
            FROM tasks t
            LEFT JOIN users tech ON tech.id = t.technician_id
            LEFT JOIN users doc ON doc.id = t.assigned_doctor_id
            LEFT JOIN users pg ON pg.id = t.assigned_pg_id
            LEFT JOIN patients p ON p.id = t.patient_id
            $whereClause
            ORDER BY t.created_at DESC
            LIMIT :limit OFFSET :offset";

    // If patientsLimit is set, first find the last N distinct patients matching filters
    if ($patientsLimit > 0) {
        $patientSql = "SELECT t.patient_id FROM tasks t " . ($whereClause !== '' ? $whereClause : '') . " GROUP BY t.patient_id ORDER BY MAX(t.created_at) DESC LIMIT :plimit";
        $pstmt = $pdo->prepare($patientSql);
        foreach ($params as $key => $val) { $pstmt->bindValue(":$key", $val); }
        $pstmt->bindValue(':plimit', $patientsLimit, PDO::PARAM_INT);
        $pstmt->execute();
        $pRows = $pstmt->fetchAll(PDO::FETCH_COLUMN, 0);
        if (empty($pRows)) {
            echo json_encode(['success'=>true, 'tasks' => [], 'count' => 0]); exit;
        }

        $ph = [];
        $binds = [];
        foreach ($pRows as $i => $pid) { $k = 'pp'.$i; $ph[] = ':'.$k; $binds[$k] = (int)$pid; }
        $in = implode(',', $ph);
        $sql = "SELECT 
                    t.id, t.patient_id, t.technician_id, t.assigned_doctor_id, t.assigned_pg_id,
                    t.priority, t.technician_notes, t.assigned_at, t.created_at,
                    tech.name as technician_name, tech.email as technician_email,
                    doc.name as doctor_name, doc.email as doctor_email,
                    pg.name as pg_name, pg.email as pg_email,
                    p.name as patient_name, p.patient_id as patient_id_str, p.age as patient_age
                FROM tasks t
                LEFT JOIN users tech ON tech.id = t.technician_id
                LEFT JOIN users doc ON doc.id = t.assigned_doctor_id
                LEFT JOIN users pg ON pg.id = t.assigned_pg_id
                LEFT JOIN patients p ON p.id = t.patient_id
                WHERE t.patient_id IN ($in)
                ORDER BY t.created_at DESC";
        $stmt = $pdo->prepare($sql);
        foreach ($binds as $k=>$v) { $stmt->bindValue(':'.$k, $v, PDO::PARAM_INT); }
        $stmt->execute();
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (!empty($tasks)) {
        // Keep only the latest task per patient
        $latestTasks = [];
        foreach ($tasks as $taskRow) {
            $pid = (int)$taskRow['patient_id'];
            if (!isset($latestTasks[$pid]) || strtotime($taskRow['created_at']) > strtotime($latestTasks[$pid]['created_at'])) {
                $latestTasks[$pid] = $taskRow;
            }
        }
        $tasks = array_values($latestTasks);

        $patientIds = [];
        foreach ($tasks as $t) { if (!empty($t['patient_id'])) { $patientIds[(int)$t['patient_id']] = true; } }
        $patientIds = array_keys($patientIds);

        if (!empty($patientIds)) {
            // Use named placeholders so we can optionally filter by image status
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

            // attach
            // Decide if we should group by patient for top-level unique patient entries
            $shouldGroup = ($patientsLimit > 0 && isset($pRows) && is_array($pRows)) || ($groupByPatient === 1);
            if ($shouldGroup) {
                // If group requested but $pRows isn't set (no patients_limit), derive patient order from tasks
                if (!isset($pRows) || !is_array($pRows)) {
                    $pRows = [];
                    foreach ($tasks as $tr) {
                        $pidv = (int)$tr['patient_id'];
                        if (!in_array($pidv, $pRows, true)) { $pRows[] = $pidv; }
                    }
                }
                // Build patient-grouped result: one entry per patient with tasks array
                $grouped = [];
                foreach ($pRows as $pid) {
                    $pid = (int)$pid;
                    $grouped[$pid] = [
                        'patient_id' => $pid,
                        'patient_name' => null,
                        'patient_id_str' => null,
                        'patient_age' => null,
                        'patient_last_images' => $byPatient[$pid] ?? [],
                        'tasks' => []
                    ];
                }

                foreach ($tasks as $taskRow) {
                    $pid = (int)$taskRow['patient_id'];
                    if (!isset($grouped[$pid])) {
                        // include unexpected patient (safety)
                        $grouped[$pid] = [
                            'patient_id' => $pid,
                            'patient_name' => $taskRow['patient_name'] ?? null,
                            'patient_id_str' => $taskRow['patient_id_str'] ?? null,
                            'patient_age' => $taskRow['patient_age'] ?? null,
                            'patient_last_images' => $byPatient[$pid] ?? [],
                            'tasks' => []
                        ];
                    }
                    // populate patient info from task if missing
                    if ($grouped[$pid]['patient_name'] === null && isset($taskRow['patient_name'])) {
                        $grouped[$pid]['patient_name'] = $taskRow['patient_name'];
                        $grouped[$pid]['patient_id_str'] = $taskRow['patient_id_str'] ?? null;
                        $grouped[$pid]['patient_age'] = $taskRow['patient_age'] ?? null;
                    }
                    // append task (keep original task shape)
                    $grouped[$pid]['tasks'][] = $taskRow;
                }

                // replace tasks with grouped patient array
                $tasks = array_values($grouped);
            } else {
                foreach ($tasks as &$t) {
                    $pid = (int)$t['patient_id'];
                    $t['patient_last_images'] = $byPatient[$pid] ?? [];
                }
                unset($t);
            }
        }
    }

    echo json_encode(['success'=>true, 'tasks' => $tasks, 'count' => count($tasks)]);

} catch (Throwable $e) {
    error_log('list_tasks error: '.$e->getMessage());
    http_response_code(500);
    $resp = ['success'=>false,'error'=>'Server error'];
    if ($is_debug) { $resp['detail'] = $e->getMessage(); }
    echo json_encode($resp);
}
?>
