<?php
/**
 * Data provider for Laboratory Management System.
 * Reads laboratory management records from PostgreSQL.
 */

require_once __DIR__ . '/database.php';

function getDbOrNull(): ?PDO
{
    static $db = false;
    if ($db === false) {
        try {
            $db = databaseConnection();
        } catch (Throwable $e) {
            $db = null;
        }
    }
    return $db;
}

function isDbConnected(): bool
{
    return getDbOrNull() !== null;
}

function getLabCodeMap(PDO $db): array
{
    $labs = $db->query('SELECT lab_id, lab_name FROM laboratories ORDER BY lab_id')->fetchAll();
    $map = [];
    foreach ($labs as $index => $lab) {
        $key = 'LAB-' . chr(65 + $index);
        $map[$key] = [
            'id' => (int) $lab['lab_id'],
            'code' => 'Lab ' . chr(65 + $index),
            'name' => $lab['lab_name'],
        ];
    }
    return $map;
}

function getLabDisplayKey(PDO $db, int $labId): string
{
    foreach (getLabCodeMap($db) as $key => $lab) {
        if ($lab['id'] === $labId) {
            return $key;
        }
    }
    return 'LAB-A';
}

function getLabDisplayKeyFromMap(array $labMap, int $labId): string
{
    foreach ($labMap as $key => $lab) {
        if ($lab['id'] === $labId) {
            return $key;
        }
    }
    return 'LAB-A';
}

function getFirstAssistantUserId(PDO $db): int
{
    $statement = $db->query('SELECT user_id FROM lab_assistants ORDER BY user_id LIMIT 1');
    $userId = $statement->fetchColumn();
    if ($userId === false) {
        throw new RuntimeException('No Lab Assistant account is available to assign this complaint.');
    }
    return (int) $userId;
}

function resolveLabId(PDO $db, string $labKey): int
{
    $labMap = getLabCodeMap($db);
    if (!isset($labMap[$labKey])) {
        throw new RuntimeException('Select a valid laboratory.');
    }
    return $labMap[$labKey]['id'];
}

function postField(string $name): string
{
    return trim((string) ($_POST[$name] ?? ''));
}

function handlePostAction(array $user): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }
    $activeRole = $user['role'];
    $userId = (int) $user['user_id'];

    try {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], postField('csrf_token'))) {
            throw new RuntimeException('The form expired. Refresh the page and try again.');
        }
        $db = getDbOrNull();
        if (!$db) {
            throw new RuntimeException('PostgreSQL is not connected. Configure config/database.php and enable pdo_pgsql.');
        }

        $action = postField('action');
        if ($action === 'create_booking') {
            if ($activeRole !== 'Faculty') {
                throw new RuntimeException('Only Faculty accounts can request a booking.');
            }
            $labId = resolveLabId($db, postField('lab_id'));
            $purpose = postField('purpose');
            $date = postField('booking_date');
            $startTime = postField('start_time');
            $endTime = postField('end_time');
            $dateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            $startValue = DateTimeImmutable::createFromFormat('!H:i', $startTime);
            $endValue = DateTimeImmutable::createFromFormat('!H:i', $endTime);
            if ($purpose === '' || strlen($purpose) > 2000 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                || !preg_match('/^\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}$/', $endTime)
                || !$dateValue || $dateValue->format('Y-m-d') !== $date
                || !$startValue || $startValue->format('H:i') !== $startTime
                || !$endValue || $endValue->format('H:i') !== $endTime
                || $endTime <= $startTime) {
                throw new RuntimeException('Enter a purpose, valid date, and an end time after the start time.');
            }
            $startAt = $date . ' ' . $startTime . ':00+05:30';
            $endAt = $date . ' ' . $endTime . ':00+05:30';
            $overlap = $db->prepare("SELECT
                EXISTS (
                    SELECT 1 FROM bookings
                    WHERE lab_id = :booking_lab_id AND status IN ('Pending', 'Approved')
                      AND start_time < CAST(:booking_end_at AS timestamptz)
                      AND end_time > CAST(:booking_start_at AS timestamptz)
                ) OR EXISTS (
                    SELECT 1 FROM maintenance m
                    WHERE m.lab_id = :maintenance_lab_id AND m.status IN ('Scheduled', 'In Progress')
                      AND m.start_time < CAST(:maintenance_end_at AS timestamptz)
                      AND COALESCE(m.end_time, m.start_time + INTERVAL '1 hour') > CAST(:maintenance_start_at AS timestamptz)
                )");
                        $overlap->execute([
                'booking_lab_id' => $labId,
                'booking_start_at' => $startAt,
                'booking_end_at' => $endAt,
                                'maintenance_lab_id' => $labId,
                                'maintenance_start_at' => $startAt,
                                'maintenance_end_at' => $endAt,
                        ]);
            if (in_array($overlap->fetchColumn(), [true, 1, '1', 't', 'true'], true)) {
                throw new RuntimeException('That laboratory already has a booking or maintenance task during this time.');
            }
            $insert = $db->prepare("INSERT INTO bookings (user_id, lab_id, purpose, start_time, end_time)
                VALUES (:user_id, :lab_id, :purpose, CAST(:start_at AS timestamptz), CAST(:end_at AS timestamptz))");
            $insert->execute([
                'user_id' => $userId,
                'lab_id' => $labId,
                'purpose' => $purpose,
                'start_at' => $startAt,
                'end_at' => $endAt,
            ]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Booking request submitted for Lab Assistant review.'];
        } elseif ($action === 'create_complaint') {
            if ($activeRole !== 'Student') {
                throw new RuntimeException('Only Student accounts can submit a complaint.');
            }
            $labId = resolveLabId($db, postField('lab_id'));
            $workstationNo = postField('workstation_no');
            $description = postField('description');
            if ($description === '' || strlen($description) > 10000) {
                throw new RuntimeException('Enter a problem description of at most 10,000 characters.');
            }
            if ($workstationNo !== '') {
                $exists = $db->prepare('SELECT 1 FROM workstations WHERE lab_id = :lab_id AND workstation_no = :workstation_no');
                $exists->execute(['lab_id' => $labId, 'workstation_no' => $workstationNo]);
                if (!$exists->fetchColumn()) {
                    throw new RuntimeException('The selected workstation does not belong to that laboratory.');
                }
            }
            $assistantId = getFirstAssistantUserId($db);
            $insert = $db->prepare("INSERT INTO complaints (user_id, lab_id, workstation_no, problem_description, assigned_to)
                VALUES (:user_id, :lab_id, NULLIF(:workstation_no, ''), :description, :assigned_to)");
            $insert->execute([
                'user_id' => $userId,
                'lab_id' => $labId,
                'workstation_no' => $workstationNo,
                'description' => $description,
                'assigned_to' => $assistantId,
            ]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Complaint submitted and assigned to the Lab Assistant.'];
        } elseif ($action === 'create_maintenance') {
            if ($activeRole !== 'Lab Assistant') {
                throw new RuntimeException('Only Lab Assistant accounts can log maintenance.');
            }
            $location = explode('|', postField('workstation'), 2);
            if (count($location) !== 2) {
                throw new RuntimeException('Select a valid workstation.');
            }
            [$labKey, $workstationNo] = $location;
            $labId = resolveLabId($db, $labKey);
            $type = postField('maintenance_type');
            $title = postField('title');
            $description = postField('description');
            $complaintId = postField('complaint_id');
            $scheduledInput = postField('start_time');
            $scheduledAt = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $scheduledInput, new DateTimeZone('Asia/Kolkata'));
            if (!in_array($type, ['Corrective', 'Preventive'], true) || $title === '' || strlen($title) > 200
                || $description === '' || strlen($description) > 5000 || !$scheduledAt
                || $scheduledAt->format('Y-m-d\TH:i') !== $scheduledInput) {
                throw new RuntimeException('Enter a title, description, valid type, and scheduled date/time.');
            }
            if ($scheduledAt < new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata'))) {
                throw new RuntimeException('Maintenance cannot be scheduled in the past.');
            }
            $checkWorkstation = $db->prepare('SELECT 1 FROM workstations WHERE lab_id = :lab_id AND workstation_no = :workstation_no');
            $checkWorkstation->execute(['lab_id' => $labId, 'workstation_no' => $workstationNo]);
            if (!$checkWorkstation->fetchColumn()) {
                throw new RuntimeException('The selected workstation does not belong to that laboratory.');
            }
            $complaintId = $complaintId === '' ? null : filter_var($complaintId, FILTER_VALIDATE_INT);
            if ($complaintId !== null && $complaintId === false) {
                throw new RuntimeException('Select a valid linked complaint.');
            }
            $db->beginTransaction();
            $insert = $db->prepare("INSERT INTO maintenance
                (complaint_id, lab_id, workstation_no, performed_by, maintenance_type, description, start_time)
                VALUES (:complaint_id, :lab_id, :workstation_no, :performed_by, :type, :description, CAST(:start_at AS timestamptz))");
            $insert->execute([
                'complaint_id' => $complaintId,
                'lab_id' => $labId,
                'workstation_no' => $workstationNo,
                'performed_by' => $userId,
                'type' => $type,
                'description' => $title . ': ' . $description,
                'start_at' => $scheduledAt->format('Y-m-d H:i:sP'),
            ]);
            if ($complaintId !== null) {
                $update = $db->prepare("UPDATE complaints SET assigned_to = :assistant_id
                    WHERE complaint_id = :complaint_id AND lab_id = :lab_id AND workstation_no = :workstation_no
                      AND status IN ('Open', 'In Progress')");
                $update->execute([
                    'assistant_id' => $userId,
                    'complaint_id' => $complaintId,
                    'lab_id' => $labId,
                    'workstation_no' => $workstationNo,
                ]);
                if ($update->rowCount() !== 1) {
                    throw new RuntimeException('The linked complaint is not open for this workstation.');
                }
            }
            $db->commit();
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Maintenance task logged and linked records updated.'];
        } elseif ($action === 'review_booking') {
            if ($activeRole !== 'Lab Assistant') {
                throw new RuntimeException('Only Lab Assistant accounts can review bookings.');
            }
            $bookingId = filter_var(postField('booking_id'), FILTER_VALIDATE_INT);
            $status = postField('status');
            if (!$bookingId || !in_array($status, ['Approved', 'Rejected'], true)) {
                throw new RuntimeException('Choose a valid booking review action.');
            }
            $db->beginTransaction();
            $bookingQuery = $db->prepare("SELECT booking_id, lab_id, start_time, end_time, created_at
                FROM bookings WHERE booking_id = :booking_id AND status = 'Pending' FOR UPDATE");
            $bookingQuery->execute(['booking_id' => $bookingId]);
            $booking = $bookingQuery->fetch();
            if (!$booking) {
                throw new RuntimeException('That booking is no longer pending.');
            }

            if ($status === 'Approved') {
                $labLock = $db->prepare('SELECT lab_id FROM laboratories WHERE lab_id = :lab_id FOR UPDATE');
                $labLock->execute(['lab_id' => $booking['lab_id']]);

                $approvedConflict = $db->prepare("SELECT 1 FROM bookings
                    WHERE lab_id = :approved_lab_id AND status = 'Approved'
                      AND booking_id <> :approved_booking_id
                      AND start_time < CAST(:approved_end_time AS timestamptz)
                      AND end_time > CAST(:approved_start_time AS timestamptz)
                    LIMIT 1");
                $approvedConflict->execute([
                    'approved_lab_id' => $booking['lab_id'],
                    'approved_booking_id' => $bookingId,
                    'approved_start_time' => $booking['start_time'],
                    'approved_end_time' => $booking['end_time'],
                ]);
                if ($approvedConflict->fetchColumn()) {
                    throw new RuntimeException('This time slot already has an approved booking.');
                }

                $earlierPending = $db->prepare("SELECT booking_id FROM bookings
                    WHERE lab_id = :pending_lab_id AND status = 'Pending'
                      AND booking_id <> :pending_booking_id
                      AND start_time < CAST(:pending_end_time AS timestamptz)
                      AND end_time > CAST(:pending_start_time AS timestamptz)
                      AND (created_at < CAST(:target_created_at AS timestamptz)
                           OR (created_at = CAST(:target_created_at AS timestamptz)
                               AND booking_id < :target_booking_id))
                    ORDER BY created_at, booking_id
                    LIMIT 1");
                $earlierPending->execute([
                    'pending_lab_id' => $booking['lab_id'],
                    'pending_booking_id' => $bookingId,
                    'pending_start_time' => $booking['start_time'],
                    'pending_end_time' => $booking['end_time'],
                    'target_created_at' => $booking['created_at'],
                    'target_booking_id' => $bookingId,
                ]);
                if ($earlierPending->fetchColumn()) {
                    throw new RuntimeException('An earlier overlapping request must be reviewed first.');
                }
            }

            $update = $db->prepare("UPDATE bookings SET status = :status, approved_by = :assistant_id,
                    updated_at = CURRENT_TIMESTAMP
                WHERE booking_id = :booking_id AND status = 'Pending'");
            $update->execute([
                'status' => $status,
                'assistant_id' => $userId,
                'booking_id' => $bookingId,
            ]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('That booking is no longer pending.');
            }
            $db->commit();
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Booking ' . strtolower($status) . '.'];
        } elseif ($action === 'advance_maintenance') {
            if ($activeRole !== 'Lab Assistant') {
                throw new RuntimeException('Only Lab Assistant accounts can update maintenance.');
            }
            $maintenanceId = filter_var(postField('maintenance_id'), FILTER_VALIDATE_INT);
            $status = postField('status');
            if (!$maintenanceId || !in_array($status, ['In Progress', 'Completed'], true)) {
                throw new RuntimeException('Choose a valid maintenance status.');
            }
            $fromStatus = $status === 'In Progress' ? 'Scheduled' : 'In Progress';
            $db->beginTransaction();
            $update = $db->prepare("UPDATE maintenance SET status = :status,
                    start_time = CASE WHEN :is_started = 'yes' THEN clock_timestamp() ELSE start_time END,
                    end_time = CASE WHEN :is_completed = 'yes' THEN GREATEST(clock_timestamp(), start_time + INTERVAL '1 second') ELSE end_time END
                WHERE maintenance_id = :maintenance_id AND status = :from_status");
            $update->execute([
                'status' => $status,
                'is_started' => $status === 'In Progress' ? 'yes' : 'no',
                'is_completed' => $status === 'Completed' ? 'yes' : 'no',
                'maintenance_id' => $maintenanceId,
                'from_status' => $fromStatus,
            ]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('That maintenance task has already changed status.');
            }
            if ($status === 'In Progress') {
                $startComplaint = $db->prepare("UPDATE complaints SET status = 'In Progress'
                    WHERE complaint_id = (SELECT complaint_id FROM maintenance WHERE maintenance_id = :maintenance_id)
                      AND status = 'Open'");
                $startComplaint->execute(['maintenance_id' => $maintenanceId]);
            }
            if ($status === 'Completed') {
                $resolve = $db->prepare("UPDATE complaints SET status = 'Resolved', resolved_at = CURRENT_TIMESTAMP
                    WHERE complaint_id = (SELECT complaint_id FROM maintenance WHERE maintenance_id = :maintenance_id)
                      AND status IN ('Open', 'In Progress')");
                $resolve->execute(['maintenance_id' => $maintenanceId]);
            }
            $db->commit();
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Maintenance status updated to ' . strtolower($status) . '.'];
        } else {
            throw new RuntimeException('Unknown form action.');
        }
    } catch (Throwable $e) {
        if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
            $db->rollBack();
        }
        $message = $e instanceof PDOException
            ? 'The database could not save that request. Verify the selected records and database constraints.'
            : $e->getMessage();
        $_SESSION['flash'] = ['type' => 'error', 'message' => $message];
    }

    $query = [];
    if (isset($_GET['lab'])) {
        $query['lab'] = (string) $_GET['lab'];
    }
    $target = basename((string) ($_SERVER['PHP_SELF'] ?? 'index.php'));
    header('Location: ' . $target . ($query ? '?' . http_build_query($query) : ''));
    exit;
}

function getSystemStats(): array
{
    $db = getDbOrNull();
    if (!$db) {
        return [
            'pending_bookings' => '—',
            'open_complaints' => '—',
            'open_complaints_detail' => 'Database not connected',
            'systems_under_repair' => '—',
            'systems_under_repair_detail' => 'Database not connected',
            'available_labs' => '—',
            'total_labs' => '—',
            'available_detail' => 'Database not connected',
        ];
    }
    if ($db) {
        try {
            $stats = $db->query("SELECT
                (SELECT COUNT(*) FROM bookings WHERE status = 'Pending') AS pending_bookings,
                (SELECT COUNT(*) FROM complaints WHERE status IN ('Open', 'In Progress')) AS open_complaints,
                (SELECT COUNT(*) FROM workstations w WHERE EXISTS (
                    SELECT 1 FROM complaints c
                    WHERE c.lab_id = w.lab_id AND c.workstation_no = w.workstation_no
                      AND c.status IN ('Open', 'In Progress')
                ) OR EXISTS (
                    SELECT 1 FROM maintenance m
                    WHERE m.lab_id = w.lab_id AND m.workstation_no = w.workstation_no AND m.status = 'In Progress'
                )) AS systems_under_repair,
                (SELECT COUNT(*) FROM laboratories) AS total_labs")->fetch();
            $availableLabs = (int) $db->query("SELECT COUNT(*) FROM laboratories l WHERE NOT EXISTS (
                SELECT 1 FROM maintenance m
                WHERE m.lab_id = l.lab_id AND (m.status = 'In Progress' OR (m.status = 'Scheduled' AND m.start_time <= CURRENT_TIMESTAMP))
            ) AND NOT EXISTS (
                SELECT 1 FROM complaints c WHERE c.lab_id = l.lab_id AND c.status IN ('Open', 'In Progress')
            )")->fetchColumn();
            return [
                'pending_bookings' => (int) $stats['pending_bookings'],
                'open_complaints' => (int) $stats['open_complaints'],
                'open_complaints_detail' => 'Open and in-progress tickets',
                'systems_under_repair' => (int) $stats['systems_under_repair'],
                'systems_under_repair_detail' => 'Based on unresolved workstation complaints',
                'available_labs' => $availableLabs,
                'total_labs' => (int) $stats['total_labs'],
                'available_detail' => 'Derived from scheduled maintenance',
            ];
        } catch (Throwable $e) {
            return [
                'pending_bookings' => '—',
                'open_complaints' => '—',
                'open_complaints_detail' => 'Database tables unavailable',
                'systems_under_repair' => '—',
                'systems_under_repair_detail' => 'Database tables unavailable',
                'available_labs' => '—',
                'total_labs' => '—',
                'available_detail' => 'Database tables unavailable',
            ];
        }
    }

    return [
        'pending_bookings' => '—',
        'open_complaints' => '—',
        'open_complaints_detail' => 'Database unavailable',
        'systems_under_repair' => '—',
        'systems_under_repair_detail' => 'Database unavailable',
        'available_labs' => '—',
        'total_labs' => '—',
        'available_detail' => 'Database unavailable',
    ];
}

function getLaboratories(): array
{
    $db = getDbOrNull();
    if (!$db) {
        return [];
    }
    if ($db) {
        try {
            $labs = $db->query("WITH workstation_health AS (
                    SELECT w.lab_id, w.workstation_no,
                        EXISTS (
                            SELECT 1 FROM complaints c
                            WHERE c.lab_id = w.lab_id AND c.workstation_no = w.workstation_no
                              AND c.status IN ('Open', 'In Progress')
                        ) OR EXISTS (
                            SELECT 1 FROM maintenance m
                            WHERE m.lab_id = w.lab_id AND m.workstation_no = w.workstation_no
                              AND m.status = 'In Progress'
                        ) AS under_repair
                    FROM workstations w
                )
                SELECT l.lab_id, l.lab_name, l.building, l.floor, l.room_no, l.capacity,
                    COUNT(wh.workstation_no) AS workstations_count,
                    COUNT(wh.workstation_no) FILTER (WHERE NOT wh.under_repair) AS working_count,
                    COUNT(wh.workstation_no) FILTER (WHERE wh.under_repair) AS repair_count,
                    CASE WHEN EXISTS (
                        SELECT 1 FROM maintenance m
                        WHERE m.lab_id = l.lab_id AND (m.status = 'In Progress' OR (m.status = 'Scheduled' AND m.start_time <= CURRENT_TIMESTAMP))
                    ) OR EXISTS (
                        SELECT 1 FROM complaints c
                        WHERE c.lab_id = l.lab_id AND c.status IN ('Open', 'In Progress')
                    ) THEN 'Maintenance' ELSE 'Available' END AS status
                FROM laboratories l
                LEFT JOIN workstation_health wh ON wh.lab_id = l.lab_id
                GROUP BY l.lab_id, l.lab_name, l.building, l.floor, l.room_no, l.capacity
                ORDER BY l.lab_id")->fetchAll();
            $result = [];
            foreach ($labs as $index => $lab) {
                $letter = chr(65 + $index);
                $key = 'LAB-' . $letter;
                $result[$key] = [
                    'id' => $key,
                    'db_id' => (int) $lab['lab_id'],
                    'code' => 'Lab ' . $letter,
                    'name' => 'Lab ' . $letter . ' - ' . $lab['building'] . ', Room ' . $lab['room_no'],
                    'building' => $lab['building'],
                    'floor' => 'Floor ' . $lab['floor'],
                    'room' => 'Room ' . $lab['room_no'],
                    'capacity' => (int) $lab['capacity'],
                    'workstations_count' => (int) $lab['workstations_count'],
                    'working_count' => (int) $lab['working_count'],
                    'repair_count' => (int) $lab['repair_count'],
                    'status' => $lab['status'],
                ];
            }
            return $result;
        } catch (Throwable $e) {
            return [];
        }
    }

    return [
        'LAB-A' => [
            'id' => 'LAB-A',
            'code' => 'Lab A',
            'name' => 'Lab A - Main building, Room 201',
            'building' => 'Main building',
            'floor' => 'Floor 2',
            'room' => 'Room 201',
            'capacity' => 30,
            'workstations_count' => 30,
            'working_count' => 29,
            'repair_count' => 1,
            'status' => 'Available',
        ],
        'LAB-B' => [
            'id' => 'LAB-B',
            'code' => 'Lab B',
            'name' => 'Lab B - Room 202',
            'building' => 'Main building',
            'floor' => 'Floor 2',
            'room' => 'Room 202',
            'capacity' => 24,
            'workstations_count' => 24,
            'working_count' => 23,
            'repair_count' => 1,
            'status' => 'Available',
        ],
        'LAB-C' => [
            'id' => 'LAB-C',
            'code' => 'Lab C',
            'name' => 'Lab C - Room 301',
            'building' => 'South Wing',
            'floor' => 'Floor 3',
            'room' => 'Room 301',
            'capacity' => 20,
            'workstations_count' => 20,
            'working_count' => 19,
            'repair_count' => 1,
            'status' => 'Maintenance',
        ],
        'LAB-D' => [
            'id' => 'LAB-D',
            'code' => 'Lab D',
            'name' => 'Lab D - Room 302',
            'building' => 'South Wing',
            'floor' => 'Floor 3',
            'room' => 'Room 302',
            'capacity' => 20,
            'workstations_count' => 20,
            'working_count' => 20,
            'repair_count' => 0,
            'status' => 'Available',
        ],
    ];
}

function getWorkstations(string $labId = 'LAB-A'): array
{
    $db = getDbOrNull();
    if (!$db) {
        return [];
    }
    if ($db) {
        try {
            $labMap = getLabCodeMap($db);
            if (!isset($labMap[$labId])) {
                return [];
            }
            $statement = $db->prepare("SELECT w.lab_id, w.workstation_no,
                    COUNT(DISTINCT ic.component_id) AS components_count,
                    MAX(m.end_time) FILTER (WHERE m.status = 'Completed') AS last_serviced,
                                        c.complaint_id, c.problem_description, c.status AS complaint_status,
                                        EXISTS (
                                                SELECT 1 FROM maintenance active_task
                                                WHERE active_task.lab_id = w.lab_id AND active_task.workstation_no = w.workstation_no
                                                    AND active_task.status = 'In Progress'
                                        ) AS maintenance_active
                FROM workstations w
                LEFT JOIN installed_components ic
                    ON ic.lab_id = w.lab_id AND ic.workstation_no = w.workstation_no AND ic.removed_at IS NULL
                LEFT JOIN LATERAL (
                    SELECT complaint_id, problem_description, status
                    FROM complaints
                    WHERE lab_id = w.lab_id AND workstation_no = w.workstation_no
                      AND status IN ('Open', 'In Progress')
                    ORDER BY raised_at DESC LIMIT 1
                ) c ON TRUE
                LEFT JOIN maintenance m
                    ON m.lab_id = w.lab_id AND m.workstation_no = w.workstation_no
                WHERE w.lab_id = :lab_id
                GROUP BY w.lab_id, w.workstation_no, c.complaint_id, c.problem_description, c.status
                ORDER BY w.workstation_no");
            $statement->execute(['lab_id' => $labMap[$labId]['id']]);
            $rows = $statement->fetchAll();
            $workstations = [];
            foreach ($rows as $row) {
                $underRepair = $row['complaint_id'] !== null
                    || in_array($row['maintenance_active'], [true, 1, '1', 't', 'true'], true);
                $workstations[] = [
                    'id' => $row['lab_id'] . ':' . $row['workstation_no'],
                    'code' => $labMap[$labId]['code'] . ' - ' . $row['workstation_no'],
                    'lab_id' => $labId,
                    'db_lab_id' => (int) $row['lab_id'],
                    'workstation_no' => $row['workstation_no'],
                    'specs' => 'See installed component details',
                    'components_count' => (int) $row['components_count'],
                    'last_serviced' => $row['last_serviced']
                        ? date('d M Y', strtotime($row['last_serviced']))
                        : 'Not recorded',
                    'status' => $underRepair ? 'Under Repair' : 'Working',
                    'issue' => $underRepair
                        ? ($row['complaint_id']
                            ? 'C-' . (1000 + (int) $row['complaint_id']) . ' · ' . $row['problem_description'] . ' · ' . $row['complaint_status']
                            : 'Maintenance task in progress')
                        : null,
                    'complaint_id' => $row['complaint_id'] ? (int) $row['complaint_id'] : null,
                ];
            }
            return $workstations;
        } catch (Throwable $e) {
            return [];
        }
    }

    $workstations = [
        [
            'id' => 'WS-A01',
            'code' => 'Lab A - WS-01',
            'lab_id' => 'LAB-A',
            'specs' => 'Intel Core i5 · 16 GB RAM · 512 GB SSD',
            'components_count' => 9,
            'last_serviced' => '02 Oct 2026',
            'status' => 'Working',
            'issue' => null,
        ],
        [
            'id' => 'WS-A02',
            'code' => 'Lab A - WS-02',
            'lab_id' => 'LAB-A',
            'specs' => 'Intel Core i5 · 16 GB RAM · 512 GB SSD',
            'components_count' => 9,
            'last_serviced' => '02 Oct 2026',
            'status' => 'Working',
            'issue' => null,
        ],
        [
            'id' => 'WS-A03',
            'code' => 'Lab A - WS-03',
            'lab_id' => 'LAB-A',
            'specs' => 'Intel Core i5 · 16 GB RAM · 512 GB SSD',
            'components_count' => 9,
            'last_serviced' => '02 Oct 2026',
            'status' => 'Under Repair',
            'issue' => 'C-1048 · Monitor flickers · Open',
            'complaint_id' => 'C-1048',
            'maintenance_id' => 'M-201',
        ],
        [
            'id' => 'WS-A04',
            'code' => 'Lab A - WS-04',
            'lab_id' => 'LAB-A',
            'specs' => 'Intel Core i5 · 16 GB RAM · 512 GB SSD',
            'components_count' => 9,
            'last_serviced' => '01 Oct 2026',
            'status' => 'Working',
            'issue' => null,
        ],
        [
            'id' => 'WS-A05',
            'code' => 'Lab A - WS-05',
            'lab_id' => 'LAB-A',
            'specs' => 'Intel Core i5 · 16 GB RAM · 512 GB SSD',
            'components_count' => 9,
            'last_serviced' => '01 Oct 2026',
            'status' => 'Working',
            'issue' => null,
        ],
        [
            'id' => 'WS-A06',
            'code' => 'Lab A - WS-06',
            'lab_id' => 'LAB-A',
            'specs' => 'Intel Core i5 · 16 GB RAM · 512 GB SSD',
            'components_count' => 9,
            'last_serviced' => '01 Oct 2026',
            'status' => 'Working',
            'issue' => null,
        ],
    ];

    return $workstations;
}

function getWorkstationComponents(string $wsCode = 'Lab A - WS-03'): array
{
    $db = getDbOrNull();
    if (!$db) {
        return [];
    }
    if ($db && preg_match('/^Lab ([A-Z]) - (.+)$/', $wsCode, $matches)) {
        try {
            $labKey = 'LAB-' . $matches[1];
            $labMap = getLabCodeMap($db);
            if (!isset($labMap[$labKey])) {
                return [];
            }
            $statement = $db->prepare("SELECT c.category, c.brand, c.model, c.serial_number,
                    ic.installed_at, ic.removed_at
                FROM installed_components ic
                JOIN components c ON c.component_id = ic.component_id
                WHERE ic.lab_id = :lab_id AND ic.workstation_no = :workstation_no
                ORDER BY c.category, c.component_id");
            $statement->execute([
                'lab_id' => $labMap[$labKey]['id'],
                'workstation_no' => $matches[2],
            ]);
            return array_map(static function (array $component): array {
                return [
                    'category' => $component['category'],
                    'brand' => $component['brand'] ?? '—',
                    'model' => $component['model'] ?? '—',
                    'serial' => $component['serial_number'] ?? '—',
                    'installed_at' => date('d M Y', strtotime($component['installed_at'])),
                    'removed_at' => $component['removed_at']
                        ? date('d M Y', strtotime($component['removed_at']))
                        : null,
                    'is_faulty' => false,
                ];
            }, $statement->fetchAll());
        } catch (Throwable $e) {
            return [];
        }
    }

    return [];

    return [
        [
            'category' => 'Processor',
            'brand' => 'Intel',
            'model' => 'Core i5-12400',
            'serial' => 'CPU-A03-001',
            'installed_at' => '01 Aug 2026',
            'removed_at' => null,
            'is_faulty' => false,
        ],
        [
            'category' => 'RAM',
            'brand' => 'Kingston',
            'model' => '16 GB DDR4',
            'serial' => 'RAM-A03-001',
            'installed_at' => '01 Aug 2026',
            'removed_at' => null,
            'is_faulty' => false,
        ],
        [
            'category' => 'Storage',
            'brand' => 'Samsung',
            'model' => '970 EVO Plus 512 GB',
            'serial' => 'SSD-A03-001',
            'installed_at' => '01 Aug 2026',
            'removed_at' => null,
            'is_faulty' => false,
        ],
        [
            'category' => 'Monitor',
            'brand' => 'Dell',
            'model' => 'P2422H',
            'serial' => 'MON-A03-001',
            'installed_at' => '01 Aug 2026',
            'removed_at' => null,
            'is_faulty' => true,
        ],
        [
            'category' => 'Keyboard',
            'brand' => 'Logitech',
            'model' => 'K120',
            'serial' => 'KEY-A03-001',
            'installed_at' => '01 Aug 2026',
            'removed_at' => null,
            'is_faulty' => false,
        ],
        [
            'category' => 'Mouse',
            'brand' => 'Logitech',
            'model' => 'B100',
            'serial' => 'MSE-A03-001',
            'installed_at' => '01 Aug 2026',
            'removed_at' => null,
            'is_faulty' => false,
        ],
        [
            'category' => 'Network Device',
            'brand' => 'Intel',
            'model' => 'I219-V Ethernet',
            'serial' => 'NET-A03-001',
            'installed_at' => '01 Aug 2026',
            'removed_at' => null,
            'is_faulty' => false,
        ],
        [
            'category' => 'Peripheral',
            'brand' => 'Logitech',
            'model' => 'C270 webcam',
            'serial' => 'PER-A03-001',
            'installed_at' => '01 Aug 2026',
            'removed_at' => null,
            'is_faulty' => false,
        ],
        [
            'category' => 'Other',
            'brand' => 'APC',
            'model' => 'BX500 UPS',
            'serial' => 'OTH-A03-001',
            'installed_at' => '01 Aug 2026',
            'removed_at' => null,
            'is_faulty' => false,
        ],
    ];
}

function getBookings(): array
{
    $db = getDbOrNull();
    if (!$db) {
        return [];
    }
    if ($db) {
        try {
                $rows = $db->query("SELECT b.booking_id, b.user_id, b.lab_id, b.purpose, b.start_time, b.end_time, b.status, b.created_at,
                    u.first_name || ' ' || u.last_name AS faculty_name
                FROM bookings b
                JOIN users u ON u.user_id = b.user_id
                ORDER BY b.start_time DESC, b.booking_id DESC")->fetchAll();
            $labMap = getLabCodeMap($db);
            return array_map(static function (array $booking) use ($labMap): array {
                $labKey = getLabDisplayKeyFromMap($labMap, (int) $booking['lab_id']);
                $start = new DateTimeImmutable($booking['start_time']);
                $end = new DateTimeImmutable($booking['end_time']);
                return [
                    'booking_id' => (int) $booking['booking_id'],
                    'user_id' => (int) $booking['user_id'],
                    'code' => 'BK-' . str_pad((string) $booking['booking_id'], 4, '0', STR_PAD_LEFT),
                    'faculty' => $booking['faculty_name'],
                    'lab' => $labMap[$labKey]['code'] ?? 'Laboratory',
                    'lab_id' => $labKey,
                    'db_lab_id' => (int) $booking['lab_id'],
                    'purpose' => $booking['purpose'],
                    'date' => $start->format('d M Y'),
                    'time' => $start->format('H:i') . '–' . $end->format('H:i'),
                    'start_time' => $start->format('H:i'),
                    'end_time' => $end->format('H:i'),
                    'status' => $booking['status'],
                    'created_at' => $booking['created_at'],
                ];
            }, $rows);
        } catch (Throwable $e) {
            return [];
        }
    }

    return [
        [
            'code' => 'BK-302',
            'faculty' => 'Prof. Neha Rao',
            'lab' => 'Lab A',
            'lab_id' => 'LAB-A',
            'purpose' => 'Database systems',
            'date' => '06 Oct 2026',
            'time' => '10:00–12:00',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 'Pending',
        ],
        [
            'code' => 'BK-303',
            'faculty' => 'Prof. Arjun Mehta',
            'lab' => 'Lab B',
            'lab_id' => 'LAB-B',
            'purpose' => 'Computer networks',
            'date' => '07 Oct 2026',
            'time' => '13:00–15:00',
            'start_time' => '13:00',
            'end_time' => '15:00',
            'status' => 'Pending',
        ],
        [
            'code' => 'BK-304',
            'faculty' => 'Prof. Kavya Iyer',
            'lab' => 'Lab D',
            'lab_id' => 'LAB-D',
            'purpose' => 'Programming practice',
            'date' => '08 Oct 2026',
            'time' => '09:00–11:00',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'Pending',
        ],
        [
            'code' => 'BK-301',
            'faculty' => 'Prof. Neha Rao',
            'lab' => 'Lab A',
            'lab_id' => 'LAB-A',
            'purpose' => 'DBMS practical',
            'date' => '05 Oct 2026',
            'time' => '11:00–13:00',
            'start_time' => '11:00',
            'end_time' => '13:00',
            'status' => 'Approved',
        ],
        [
            'code' => 'BK-298',
            'faculty' => 'Prof. Neha Rao',
            'lab' => 'Lab A',
            'lab_id' => 'LAB-A',
            'purpose' => 'DBMS lab test',
            'date' => '02 Oct 2026',
            'time' => '09:00–11:00',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'status' => 'Completed',
        ],
        [
            'code' => 'BK-297',
            'faculty' => 'Prof. Neha Rao',
            'lab' => 'Lab B',
            'lab_id' => 'LAB-B',
            'purpose' => 'Network simulation',
            'date' => '01 Oct 2026',
            'time' => '13:00–15:00',
            'start_time' => '13:00',
            'end_time' => '15:00',
            'status' => 'Rejected',
        ],
    ];
}

function getBookingHistory(?int $requesterId = null): array
{
    $db = getDbOrNull();
    if (!$db) {
        return [];
    }

    try {
        $sql = "SELECT history_id, booking_id, purpose, requested_by, changed_by,
                    previous_status, new_status, note, changed_at
                FROM v_booking_status_history";
        if ($requesterId !== null) {
            $sql .= ' WHERE EXISTS (
                SELECT 1 FROM bookings b
                WHERE b.booking_id = v_booking_status_history.booking_id AND b.user_id = :requester_id
            )';
        }
        $sql .= ' ORDER BY changed_at DESC, history_id DESC';
        $statement = $db->prepare($sql);
        $statement->execute($requesterId === null ? [] : ['requester_id' => $requesterId]);

        return array_map(static function (array $entry): array {
            return [
                'history_id' => (int) $entry['history_id'],
                'booking_id' => (int) $entry['booking_id'],
                'code' => 'BK-' . str_pad((string) $entry['booking_id'], 4, '0', STR_PAD_LEFT),
                'purpose' => $entry['purpose'],
                'requested_by' => $entry['requested_by'],
                'changed_by' => $entry['changed_by'] ?? 'System / not recorded',
                'previous_status' => $entry['previous_status'] ?? '—',
                'new_status' => $entry['new_status'],
                'note' => $entry['note'],
                'changed_at' => date('d M Y, H:i', strtotime($entry['changed_at'])),
            ];
        }, $statement->fetchAll());
    } catch (Throwable $e) {
        return [];
    }
}

function getComplaints(?string $role = null): array
{
    $db = getDbOrNull();
    if (!$db) {
        return [];
    }
    if ($db) {
        try {
            $sql = "SELECT c.complaint_id, c.lab_id, c.workstation_no, c.problem_description,
                    c.status, c.raised_at, c.resolved_at,
                    reporter.first_name || ' ' || reporter.last_name AS student_name,
                    assignee.first_name || ' ' || assignee.last_name AS assignee_name
                FROM complaints c
                JOIN users reporter ON reporter.user_id = c.user_id
                LEFT JOIN users assignee ON assignee.user_id = c.assigned_to
                " . ($role === 'student' ? 'WHERE c.user_id = :user_id ' : '') .
                'ORDER BY c.raised_at DESC, c.complaint_id DESC';
            $statement = $db->prepare($sql);
            if ($role === 'student') {
                $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$userId) {
                    return [];
                }
                $statement->execute(['user_id' => $userId]);
            } else {
                $statement->execute();
            }
            $rows = $statement->fetchAll();
            $labMap = getLabCodeMap($db);
            return array_map(static function (array $complaint) use ($labMap): array {
                $labKey = getLabDisplayKeyFromMap($labMap, (int) $complaint['lab_id']);
                $description = $complaint['problem_description'];
                return [
                    'complaint_id' => (int) $complaint['complaint_id'],
                    'code' => 'C-' . (1000 + (int) $complaint['complaint_id']),
                    'title' => function_exists('mb_strimwidth')
                        ? mb_strimwidth($description, 0, 64, '...')
                        : (strlen($description) > 64 ? substr($description, 0, 61) . '...' : $description),
                    'lab' => $labMap[$labKey]['code'] ?? 'Laboratory',
                    'lab_id' => $labKey,
                    'workstation' => $complaint['workstation_no']
                        ? ($labMap[$labKey]['code'] ?? 'Lab') . ' / ' . $complaint['workstation_no']
                        : ($labMap[$labKey]['code'] ?? 'Lab') . ' / General issue',
                    'student' => $complaint['student_name'],
                    'assigned_to' => $complaint['assignee_name'] ?? 'Awaiting assignment',
                    'raised_at' => date('d M Y, H:i', strtotime($complaint['raised_at'])),
                    'resolved_at' => $complaint['resolved_at']
                        ? date('d M Y, H:i', strtotime($complaint['resolved_at']))
                        : '— Not yet resolved',
                    'status' => $complaint['status'],
                    'description' => $description,
                ];
            }, $rows);
        } catch (Throwable $e) {
            return [];
        }
    }

    return [
        [
            'code' => 'C-1048',
            'title' => 'Monitor flickers during use',
            'lab' => 'Lab A',
            'workstation' => 'Lab A / WS-03',
            'student' => 'Riya Patel',
            'assigned_to' => 'Aditi Shah - Lab Assistant',
            'raised_at' => '05 Oct 2026, 10:20',
            'resolved_at' => '— Not yet resolved',
            'status' => 'Open',
            'description' => 'Display blinks intermittently when brightness exceeds 50%.',
        ],
        [
            'code' => 'C-1046',
            'title' => 'Keyboard keys not responding',
            'lab' => 'Lab C',
            'workstation' => 'Lab C / WS-12',
            'student' => 'Riya Patel',
            'assigned_to' => 'Aditi Shah - Keyboard replacement in progress',
            'raised_at' => '05 Oct 2026, 09:15',
            'resolved_at' => '— Not yet resolved',
            'status' => 'In Progress',
            'description' => 'Spacebar and Enter key stuck, unable to enter commands.',
        ],
        [
            'code' => 'C-1045',
            'title' => 'Mouse pointer freezes',
            'lab' => 'Lab A',
            'workstation' => 'Lab A / WS-02',
            'student' => 'Riya Patel',
            'assigned_to' => 'Resolved by Aditi Shah - Resolution time: 1h 15m',
            'raised_at' => '02 Oct 2026, 14:10',
            'resolved_at' => '02 Oct 2026, 15:25',
            'status' => 'Resolved',
            'description' => 'Optical sensor disconnects intermittently when moved quickly.',
        ],
    ];
}

function getMaintenanceTasks(): array
{
    $db = getDbOrNull();
    if (!$db) {
        return [];
    }
    if ($db) {
        try {
            $rows = $db->query("SELECT m.maintenance_id, m.lab_id, m.workstation_no,
                    m.maintenance_type, m.description, m.start_time, m.status, m.complaint_id,
                    l.lab_name, assistant.first_name || ' ' || assistant.last_name AS assistant_name
                FROM maintenance m
                JOIN laboratories l ON l.lab_id = m.lab_id
                JOIN users assistant ON assistant.user_id = m.performed_by
                ORDER BY CASE m.status WHEN 'Scheduled' THEN 1 WHEN 'In Progress' THEN 2 ELSE 3 END,
                    m.start_time DESC, m.maintenance_id DESC")->fetchAll();
            $labMap = getLabCodeMap($db);
            return array_map(static function (array $task) use ($labMap): array {
                $labKey = getLabDisplayKeyFromMap($labMap, (int) $task['lab_id']);
                $labName = $labMap[$labKey]['code'] ?? $task['lab_name'];
                $prefix = match ($task['status']) {
                    'In Progress' => 'Started ',
                    'Completed' => 'Completed ',
                    default => '',
                };
                return [
                    'maintenance_id' => (int) $task['maintenance_id'],
                    'code' => 'M-' . str_pad((string) $task['maintenance_id'], 4, '0', STR_PAD_LEFT),
                    'type' => $task['maintenance_type'],
                    'title' => $task['description'],
                    'workstation' => $labName . ' / ' . $task['workstation_no'],
                    'lab' => $labName,
                    'lab_id' => $labKey,
                    'workstation_no' => $task['workstation_no'],
                    'description' => $task['description'],
                    'time' => $prefix . date('d M Y · H:i', strtotime($task['start_time'])),
                    'raw_start_time' => $task['start_time'],
                    'assistant' => $task['assistant_name'],
                    'linked_complaint' => $task['complaint_id']
                        ? 'C-' . (1000 + (int) $task['complaint_id'])
                        : null,
                    'complaint_id' => $task['complaint_id'] ? (int) $task['complaint_id'] : null,
                    'status' => $task['status'],
                ];
            }, $rows);
        } catch (Throwable $e) {
            return [];
        }
    }

    return [
        // Scheduled
        [
            'code' => 'M-201',
            'type' => 'Corrective',
            'title' => 'Inspect monitor connection',
            'workstation' => 'Lab A / WS-03',
            'lab' => 'Lab A',
            'description' => 'Check display cable and monitor output.',
            'time' => '05 Oct 2026 · 10:45',
            'assistant' => 'Aditi Shah',
            'linked_complaint' => 'C-1048',
            'status' => 'Scheduled',
        ],
        [
            'code' => 'M-205',
            'type' => 'Preventive',
            'title' => 'Routine lab inspection',
            'workstation' => 'Lab C / WS-08',
            'lab' => 'Lab C',
            'description' => 'Clean ports and verify system health.',
            'time' => '05 Oct 2026 · 10:30',
            'assistant' => 'Aditi Shah',
            'linked_complaint' => null,
            'status' => 'Scheduled',
        ],

        // In Progress
        [
            'code' => 'M-202',
            'type' => 'Corrective',
            'title' => 'Network connectivity diagnostics',
            'workstation' => 'Lab B / WS-07',
            'lab' => 'Lab B',
            'description' => 'Network fault escalated to Administrator after triage.',
            'time' => 'Started 05 Oct · 10:00',
            'assistant' => 'Aditi Shah',
            'linked_complaint' => 'C-1047',
            'status' => 'In Progress',
        ],
        [
            'code' => 'M-203',
            'type' => 'Corrective',
            'title' => 'Replace unresponsive keyboard',
            'workstation' => 'Lab C / WS-12',
            'lab' => 'Lab C',
            'description' => 'Replacement and input testing in progress.',
            'time' => 'Started 05 Oct · 09:45',
            'assistant' => 'Aditi Shah',
            'linked_complaint' => 'C-1046',
            'status' => 'In Progress',
        ],

        // Completed
        [
            'code' => 'M-204',
            'type' => 'Preventive',
            'title' => 'Preventive hardware inspection',
            'workstation' => 'Lab D / WS-04',
            'lab' => 'Lab D',
            'description' => 'Ports, fans and peripherals checked.',
            'time' => 'Completed 05 Oct · 09:10',
            'assistant' => 'Aditi Shah',
            'linked_complaint' => null,
            'status' => 'Completed',
        ],
        [
            'code' => 'M-200',
            'type' => 'Corrective',
            'title' => 'Replace faulty mouse',
            'workstation' => 'Lab A / WS-02',
            'lab' => 'Lab A',
            'description' => 'Mouse replaced. Complaint resolved.',
            'time' => 'Completed 02 Oct · 15:25',
            'assistant' => 'Aditi Shah',
            'linked_complaint' => 'C-1045',
            'status' => 'Completed',
        ],
    ];
}

function getRecentActivity(): array
{
    $db = getDbOrNull();
    if (!$db) {
        return [];
    }
    if ($db) {
        try {
            $rows = $db->query("SELECT activity.icon, activity.title, activity.subtitle,
                    activity.activity_at, activity.color
                FROM (
                    SELECT 'complaint' AS icon,
                        'New complaint submitted by ' || u.first_name || ' ' || u.last_name AS title,
                        'C-' || (1000 + c.complaint_id) || ' · ' || l.lab_name || ' / ' || COALESCE(c.workstation_no, 'General issue') AS subtitle,
                        c.raised_at AS activity_at, '#EF4444' AS color
                    FROM complaints c JOIN users u ON u.user_id = c.user_id JOIN laboratories l ON l.lab_id = c.lab_id
                    UNION ALL
                    SELECT 'calendar', 'Booking requested by ' || u.first_name || ' ' || u.last_name,
                        'BK-' || LPAD(b.booking_id::TEXT, 4, '0') || ' · ' || l.lab_name || ' · ' || b.purpose,
                        b.created_at, '#2563EB'
                    FROM bookings b JOIN users u ON u.user_id = b.user_id JOIN laboratories l ON l.lab_id = b.lab_id
                    UNION ALL
                    SELECT 'wrench', 'Maintenance task ' || LOWER(m.status),
                        'M-' || LPAD(m.maintenance_id::TEXT, 4, '0') || ' · ' || l.lab_name || ' / ' || m.workstation_no,
                        m.start_time, '#D97706'
                    FROM maintenance m JOIN laboratories l ON l.lab_id = m.lab_id
                ) activity
                ORDER BY activity.activity_at DESC LIMIT 5")->fetchAll();
            return array_map(static function (array $item): array {
                return [
                    'icon' => $item['icon'],
                    'title' => $item['title'],
                    'subtitle' => $item['subtitle'],
                    'time' => date('h:i A', strtotime($item['activity_at'])),
                    'color' => $item['color'],
                ];
            }, $rows);
        } catch (Throwable $e) {
            return [];
        }
    }

    return [
        [
            'icon' => 'complaint',
            'title' => 'New complaint raised by Riya Patel',
            'subtitle' => 'C-1048 · Lab A / WS-03 · Monitor flickers',
            'time' => '10:20 AM',
            'color' => '#EF4444',
        ],
        [
            'icon' => 'escalate',
            'title' => 'Network fault escalated to Administrator',
            'subtitle' => 'C-1047 · Lab B / WS-07 · Triaged by Aditi Shah',
            'time' => '10:05 AM',
            'color' => '#F97316',
        ],
        [
            'icon' => 'wrench',
            'title' => 'Keyboard replacement started',
            'subtitle' => 'M-203 · Lab C / WS-12 · Corrective maintenance',
            'time' => '09:45 AM',
            'color' => '#F59E0B',
        ],
        [
            'icon' => 'calendar',
            'title' => 'Booking requested by Prof. Neha Rao',
            'subtitle' => 'BK-302 · Lab A - 06 Oct, 10:00–12:00',
            'time' => '09:30 AM',
            'color' => '#8B5CF6',
        ],
        [
            'icon' => 'check',
            'title' => 'Preventive inspection completed',
            'subtitle' => 'M-204 · Lab D / WS-04 · Updated by Aditi Shah',
            'time' => '09:10 AM',
            'color' => '#10B981',
        ],
    ];
}
