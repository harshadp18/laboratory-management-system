<?php
/**
 * Data provider for Laboratory Management System.
 * Bridges PostgreSQL database when connected, or supplies comprehensive
 * demo data matching the approved Figma design specifications.
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

function getSystemStats(): array
{
    $db = getDbOrNull();
    if ($db) {
        try {
            return [
                'pending_bookings' => (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'Pending'")->fetchColumn(),
                'open_complaints' => (int) $db->query("SELECT COUNT(*) FROM complaints WHERE status <> 'Resolved'")->fetchColumn(),
                'open_complaints_detail' => '1 open · 2 in progress',
                'systems_under_repair' => (int) $db->query("SELECT COUNT(*) FROM workstations WHERE status = 'Under Repair'")->fetchColumn(),
                'available_labs' => (int) $db->query("SELECT COUNT(*) FROM laboratories WHERE status = 'Available'")->fetchColumn(),
                'total_labs' => (int) $db->query("SELECT COUNT(*) FROM laboratories")->fetchColumn(),
            ];
        } catch (Throwable $e) {
            // fallback to demo data if tables don't exist yet
        }
    }

    return [
        'pending_bookings' => 3,
        'open_complaints' => 3,
        'open_complaints_detail' => '1 open · 2 in progress',
        'systems_under_repair' => 3,
        'systems_under_repair_detail' => 'Across Lab A, B and C',
        'available_labs' => 3,
        'total_labs' => 4,
        'available_detail' => 'Available now · 10:30 AM',
    ];
}

function getLaboratories(): array
{
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

function getComplaints(): array
{
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
