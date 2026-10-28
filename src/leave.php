<?php
declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════════════
// การลา ลงเวลา และความยินยอม PDPA — ทุกฟังก์ชันรับ $me (ผู้ใช้ที่เข้าระบบ) และทำเฉพาะข้อมูลที่มีสิทธิ์
// ═══════════════════════════════════════════════════════════════════════════

const LEAVE_TYPES = [
    'annual' => 'ลาพักร้อน',
    'sick' => 'ลาป่วย',
    'personal' => 'ลากิจ',
    'maternity' => 'ลาคลอด',
    'other' => 'ลาอื่น ๆ',
];
const HALF_DAYS = ['full' => 'เต็มวัน', 'morning' => 'ครึ่งวันเช้า', 'afternoon' => 'ครึ่งวันบ่าย'];

// ไฟล์ใบรับรองแพทย์ที่รับ (ตรวจจากเนื้อไฟล์จริง ไม่เชื่อนามสกุล)
const CERT_MIME = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/heic' => 'heic',
    'image/heif' => 'heic',
    'application/pdf' => 'pdf',
];

/** วันลาคงเหลือปีนี้ = สิทธิ์ − ที่อนุมัติแล้ว (คำนวณสดทุกครั้ง ไม่มีวันเพี้ยน) */
function leave_balances(array $me): array
{
    $rows = q_all(
        "SELECT e.leave_type, t.name AS label, e.days AS entitled,
                COALESCE(SUM(CASE WHEN r.status = 'approved' THEN r.days END), 0) AS used,
                COALESCE(SUM(CASE WHEN r.status = 'pending'  THEN r.days END), 0) AS pending
         FROM leave_entitlements e
         JOIN leave_types t ON t.code = e.leave_type
         LEFT JOIN leave_requests r
           ON r.employee_id = e.employee_id AND r.leave_type = e.leave_type AND YEAR(r.start_date) = e.year
         WHERE e.employee_id = ? AND e.year = ?
         GROUP BY e.leave_type, t.name, t.sort, e.days
         ORDER BY t.sort",
        [$me['id'], (int) date('Y')]
    );
    return array_map(fn ($r) => [
        'type' => $r['leave_type'],
        'label' => $r['label'],
        'entitled' => (float) $r['entitled'],
        'used' => (float) $r['used'],
        'pending' => (float) $r['pending'],
    ], $rows);
}

function my_leave_requests(array $me): array
{
    $approver = my_approver($me);
    $rows = q_all(
        "SELECT r.id, r.ref_no, r.leave_type, r.start_date, r.end_date, r.half_day, r.days, r.reason,
                r.status, r.approver_note, r.created_at, r.cert_path, a.full_name AS approver_name
         FROM leave_requests r LEFT JOIN employees a ON a.id = r.approver_id
         WHERE r.employee_id = ? AND r.status <> 'cancelled'
         ORDER BY r.start_date DESC LIMIT 50",
        [$me['id']]
    );
    return array_map(fn ($r) => [
        'id' => $r['id'],
        'ref' => $r['ref_no'],
        'type' => $r['leave_type'],
        'start' => $r['start_date'],
        'end' => $r['end_date'],
        'halfDay' => $r['half_day'],
        'days' => (float) $r['days'],
        'reason' => $r['reason'],
        'status' => $r['status'],
        // ผู้ที่พิจารณาแล้วใช้ชื่อจริงจากใบลา ยังไม่พิจารณา = ผู้อนุมัติตามผัง
        'approverName' => $r['approver_name'] ?? $approver['name'],
        'note' => $r['approver_note'],
        'hasCert' => $r['cert_path'] !== null,
        'submittedAt' => substr($r['created_at'], 0, 10),
    ], $rows);
}

/** นับวันลา: วันทำงาน ไม่รวมเสาร์-อาทิตย์ และวันหยุดบริษัทในตาราง holidays */
function count_leave_days(string $start, string $end, string $halfDay): float
{
    if ($start === $end && $halfDay !== 'full') return 0.5;
    $holidays = array_column(q_all('SELECT day FROM holidays WHERE day BETWEEN ? AND ?', [$start, $end]), 'day');
    $holidays = array_flip($holidays);
    $n = 0;
    $d = new DateTimeImmutable($start);
    $last = new DateTimeImmutable($end);
    for ($i = 0; $d <= $last && $i < 400; $i++, $d = $d->modify('+1 day')) {
        if ((int) $d->format('N') < 6 && !isset($holidays[$d->format('Y-m-d')])) $n++;
    }
    return (float) $n;
}

function valid_date(string $s): bool
{
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $s);
    return $d !== false && $d->format('Y-m-d') === $s;
}

/**
 * ตรวจและบันทึกใบลา — ตรวจซ้ำที่ server ทุกครั้ง (ห้ามเชื่อแค่หน้าเว็บ)
 * คืน [] ถ้าสำเร็จ หรือ ['ชื่อช่อง' => 'ข้อความ'] ถ้ามีข้อผิดพลาด
 */
function submit_leave(array $me, array $in, ?array $file): array
{
    $type = (string) ($in['type'] ?? '');
    $start = (string) ($in['start'] ?? '');
    $end = (string) ($in['end'] ?? '') ?: $start;
    $half = (string) ($in['halfDay'] ?? 'full');
    $reason = trim((string) ($in['reason'] ?? ''));

    $e = [];
    if (!isset(LEAVE_TYPES[$type])) $e['type'] = 'กรุณาเลือกประเภทการลา';
    if (!valid_date($start)) $e['start'] = 'กรุณาเลือกวันที่เริ่มลา';
    if (!valid_date($end)) $e['end'] = 'กรุณาเลือกวันสิ้นสุด';
    elseif ($end < $start) $e['end'] = 'วันสิ้นสุดต้องไม่ก่อนวันเริ่มลา';
    if (!isset(HALF_DAYS[$half])) $e['form'] = 'ข้อมูลครึ่งวันไม่ถูกต้อง';
    if ($half !== 'full' && $start !== $end) $half = 'full'; // ครึ่งวันได้เฉพาะลาวันเดียว
    if ($reason === '') $e['reason'] = 'กรุณาใส่เหตุผลสั้น ๆ';
    elseif (mb_strlen($reason) > 300) $e['reason'] = 'เหตุผลยาวเกิน 300 ตัวอักษร';
    if (!isset($e['start']) && !isset($e['end'])
        && (new DateTimeImmutable($start))->diff(new DateTimeImmutable($end))->days > 366) {
        $e['end'] = 'ช่วงวันที่ยาวเกินไป';
    }

    $days = (isset($e['start']) || isset($e['end'])) ? 0.0 : count_leave_days($start, $end, $half);
    if (!isset($e['start']) && !isset($e['end']) && $days == 0) $e['end'] = 'ช่วงวันที่เลือกเป็นวันหยุดทั้งหมด';

    // ไฟล์แนบ
    $hasCert = $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $mime = null;
    if ($hasCert) {
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || $file['size'] > CERT_MAX_BYTES) {
            $e['cert'] = 'ไฟล์ใหญ่เกิน 5 MB';
        } elseif ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            $e['cert'] = 'อัปโหลดไฟล์ไม่สำเร็จ ลองใหม่อีกครั้งค่ะ';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
            if (!isset(CERT_MIME[$mime])) $e['cert'] = 'รับเฉพาะรูปถ่าย (JPG, PNG, WEBP, HEIC) หรือ PDF';
        }
    }
    if ($type === 'sick' && $days >= SICK_CERT_DAYS && !$hasCert && !isset($e['cert'])) {
        $e['cert'] = 'ลาป่วยตั้งแต่ ' . SICK_CERT_DAYS . ' วัน กรุณาแนบใบรับรองแพทย์';
    }
    if ($e) return $e;

    // เก็บไฟล์นอกโฟลเดอร์เว็บ: storage/leave-certs/<รหัสพนักงาน>/<ชื่อสุ่ม>.<นามสกุล>
    $certPath = null;
    if ($hasCert && $mime) {
        $certPath = $me['id'] . '/' . uuid4() . '.' . CERT_MIME[$mime];
        $full = APP_ROOT . '/storage/leave-certs/' . $certPath;
        if (!is_dir(dirname($full))) mkdir(dirname($full), 0700, true);
        if (!move_uploaded_file($file['tmp_name'], $full)) return ['cert' => 'บันทึกไฟล์ไม่สำเร็จ ลองใหม่อีกครั้งค่ะ'];
        chmod($full, 0600);
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();
        $ref = next_doc_number('L');
        q(
            'INSERT INTO leave_requests (id, ref_no, employee_id, leave_type, start_date, end_date, half_day, days, reason, cert_path)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [uuid4(), $ref, $me['id'], $type, $start, $end, $half, $days, $reason, $certPath]
        );
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($certPath) @unlink(APP_ROOT . '/storage/leave-certs/' . $certPath);
        app_log('submit_leave: ' . $ex->getMessage());
        return ['form' => 'ส่งใบลาไม่สำเร็จ ลองใหม่อีกครั้งค่ะ'];
    }
    // TODO ขั้นถัดไป: แจ้งผู้อนุมัติทางอีเมล/LINE
    return [];
}

/** เลขที่เอกสารรายปี พ.ศ. เช่น L-2569-0001 — ต้องเรียกภายใน transaction */
function next_doc_number(string $prefix): string
{
    $year = (int) date('Y') + 543;
    q('INSERT INTO doc_counters (name, year, value) VALUES (?, ?, 0) ON DUPLICATE KEY UPDATE value = value', [$prefix, $year]);
    $row = q_one('SELECT value FROM doc_counters WHERE name = ? AND year = ? FOR UPDATE', [$prefix, $year]);
    $n = (int) $row['value'] + 1;
    q('UPDATE doc_counters SET value = ? WHERE name = ? AND year = ?', [$n, $prefix, $year]);
    return sprintf('%s-%d-%04d', $prefix, $year, $n);
}

/** เปิดไฟล์ใบรับรองแพทย์ — เจ้าของใบลา หรือผู้ที่ดูแลเท่านั้น */
function send_leave_cert(array $me, string $leaveId): never
{
    $r = q_one('SELECT employee_id, cert_path, ref_no FROM leave_requests WHERE id = ?', [$leaveId]);
    if (!$r || !$r['cert_path'] || !can_view_employee($me, $r['employee_id'])) {
        http_response_code(404);
        exit('ไม่พบไฟล์');
    }
    $full = APP_ROOT . '/storage/leave-certs/' . $r['cert_path'];
    // กันชื่อไฟล์แปลก ๆ พาออกนอกโฟลเดอร์
    $base = realpath(APP_ROOT . '/storage/leave-certs');
    $real = realpath($full);
    if (!$real || !$base || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
        http_response_code(404);
        exit('ไม่พบไฟล์');
    }
    $ext = pathinfo($real, PATHINFO_EXTENSION);
    $mime = array_search($ext, CERT_MIME, true) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($real));
    header('Content-Disposition: attachment; filename="' . $r['ref_no'] . '-cert.' . $ext . '"');
    header('Cache-Control: private, no-store');
    readfile($real);
    exit;
}

// ── ลงเวลา (เวลาจาก server เสมอ) ──────────────────────────────────────────────
function check_in(array $me): string
{
    try {
        q('INSERT INTO attendance (id, employee_id, work_date, check_in) VALUES (?, ?, CURDATE(), NOW())', [uuid4(), $me['id']]);
    } catch (PDOException $e) {
        if ($e->getCode() !== '23000') throw $e; // ลงเวลาไปแล้ววันนี้ → ส่งเวลาเดิมกลับ ไม่ถือว่าผิด
    }
    return (string) today_check_in($me);
}

function today_check_in(array $me): ?string
{
    $row = q_one('SELECT DATE_FORMAT(check_in, "%H:%i") AS t FROM attendance WHERE employee_id = ? AND work_date = CURDATE()', [$me['id']]);
    return $row['t'] ?? null;
}

// ── ความยินยอม PDPA ───────────────────────────────────────────────────────────
function record_consent(array $me): void
{
    q(
        "INSERT INTO consents (id, employee_id, policy_version, purpose, granted) VALUES (?, ?, ?, 'essential', 1)",
        [uuid4(), $me['id'], PRIVACY_VERSION]
    );
}

function app_log(string $msg): void
{
    $dir = APP_ROOT . '/storage/logs';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    @file_put_contents($dir . '/app-' . date('Y-m') . '.log', '[' . date('c') . '] ' . $msg . "\n", FILE_APPEND);
}
