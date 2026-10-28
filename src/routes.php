<?php
declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════════════
// การทำงานของแต่ละหน้า (เรียกจาก public/index.php)
// ═══════════════════════════════════════════════════════════════════════════

// ── หน้าแรก ───────────────────────────────────────────────────────────────────
// ⚠️ "งานของฉัน" และประกาศยังเป็นข้อมูลตัวอย่าง — ต่อเมื่อระบบหนังสือ/ประกาศมีตารางในฐานข้อมูล
function home(): void
{
    $me = require_me();
    $sample = require APP_ROOT . '/src/sample-data.php';
    $empty = ($_GET['demo'] ?? '') === 'empty';
    $stats = $sample['stats'];
    // การ์ดวันลาพักร้อนใช้ตัวเลขจริงจากฐานข้อมูล
    foreach (leave_balances($me) as $b) {
        if ($b['type'] === 'annual') $stats[2]['value'] = fmt_days(max(0, $b['entitled'] - $b['used']));
    }
    if ($empty) {
        $stats = array_map(fn ($s) => isset($s['unit']) ? $s : [...$s, 'value' => '0', 'note' => 'ไม่มีรายการ', 'tone' => 'muted'], $stats);
    }
    render('home', [
        'me' => $me,
        'section' => 'home',
        'title' => 'Together Hub',
        'checkInTime' => today_check_in($me),
        'waitingForMe' => $empty ? 0 : 3,
        'myPending' => $empty ? 0 : 2,
        'quickActions' => $sample['quickActions'],
        'stats' => $stats,
        'tasks' => $empty ? [] : $sample['tasks'],
        'announcements' => $empty ? [] : $sample['announcements'],
    ]);
}

// ── เข้าสู่ระบบ ───────────────────────────────────────────────────────────────
function login_page(): void
{
    if (current_user()) redirect(safe_next($_GET['next'] ?? '/'));
    // สถานะ "ส่งแล้ว รอใส่รหัส" อยู่ใน session — รีเฟรชหน้าก็ยังอยู่, กด "ใช้อีเมลอื่น" = ล้าง
    if (isset($_GET['change'])) unset($_SESSION['login_pending']);
    $sent = $_SESSION['login_pending'] ?? null;
    if ($sent && time() - (int) $sent['at'] > login_ttl_minutes() * 60) {
        unset($_SESSION['login_pending']);
        $sent = null;
    }
    render('login', [
        'title' => 'เข้าสู่ระบบ',
        'next' => safe_next($_GET['next'] ?? '/'),
        'error' => $_GET['error'] ?? null,
        'sent' => $sent,
        'codeError' => flash('code_error'),
        'formError' => flash('login_error'),
        'mailLog' => env('MAIL_DRIVER', 'log') !== 'smtp',
    ], 'layout_bare');
}

function login_send(): void
{
    $email = normalize_email((string) ($_POST['email'] ?? ''));
    $next = safe_next($_POST['next'] ?? '/');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        flash('login_error', ['msg' => 'รูปแบบอีเมลไม่ถูกต้อง', 'email' => $email]);
        redirect('/login?' . http_build_query(['next' => $next]));
    }
    try {
        request_login_code($email, $next);
    } catch (Throwable $ex) {
        // ส่งอีเมลไม่ได้ (เช่น ตั้งค่า SMTP ผิด) — บันทึกไว้ให้ผู้ดูแลดู แต่ตอบผู้ใช้เหมือนเดิม
        app_log('login mail: ' . $ex->getMessage());
    }
    // ตอบเหมือนกันทุกกรณี — ไม่บอกคนนอกว่าอีเมลไหนอยู่ในระบบ
    $_SESSION['login_pending'] = ['email' => $email, 'at' => time()];
    redirect('/login?' . http_build_query(['next' => $next]));
}

function login_verify(): void
{
    $email = normalize_email((string) ($_POST['email'] ?? ''));
    $code = preg_replace('/\D/', '', (string) ($_POST['token'] ?? ''));
    $next = safe_next($_POST['next'] ?? '/');
    $back = function (string $msg) use ($email, $next): never {
        flash('code_error', $msg);
        redirect('/login?' . http_build_query(['next' => $next]));
    };
    if (strlen($code) !== OTP_LENGTH) $back('กรุณาใส่รหัสให้ครบ ' . OTP_LENGTH . ' หลักค่ะ');
    $to = verify_login_code($email, $code);
    if (!$to) $back('รหัสไม่ถูกต้องหรือหมดอายุแล้วค่ะ ตรวจอีกครั้ง หรือขอรหัสใหม่');
    redirect($to);
}

// ลิงก์ในอีเมล: เปิดหน้า (GET) แค่แสดงปุ่ม — ใช้ลิงก์จริงเมื่อ "คนกดปุ่ม" (POST) เท่านั้น
// ระบบสแกนลิงก์ของอีเมล (เช่น Microsoft Safe Links) เปิดได้แค่หน้า จึงใช้ลิงก์ไปก่อนไม่ได้
function confirm_page(): void
{
    $t = (string) ($_GET['t'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $t)) redirect('/login?error=expired');
    render('confirm', ['title' => 'ยืนยันการเข้าสู่ระบบ', 't' => $t], 'layout_bare');
}

function confirm_submit(): void
{
    $to = verify_login_link((string) ($_POST['t'] ?? ''));
    redirect($to ?? '/login?error=expired');
}

function signout(): void
{
    sign_out();
    redirect('/login');
}

// ── การลา ─────────────────────────────────────────────────────────────────────
function coming_soon_or(array $me, string $section, string $mascotId, string $moduleName, string $detail): void
{
    if (preview_modules()) return;
    render('coming_soon', compact('me', 'section', 'mascotId', 'moduleName', 'detail') + ['title' => $moduleName]);
}

function leave_index(): void
{
    $me = require_me();
    coming_soon_or($me, 'hr', 'khwanKhao', 'การลา', 'ระบบการลาและลงเวลาอยู่ระหว่างพัฒนา');
    render('leave_index', [
        'me' => $me,
        'section' => 'hr',
        'title' => 'การลาของฉัน',
        'balances' => leave_balances($me),
        'requests' => my_leave_requests($me),
        'sent' => isset($_GET['sent']),
    ]);
}

function leave_new(array $errors = [], array $old = []): void
{
    $me = require_me();
    coming_soon_or($me, 'hr', 'khwanKhao', 'ขอลาใหม่', 'ระบบการลาและลงเวลาอยู่ระหว่างพัฒนา');
    render('leave_new', [
        'me' => $me,
        'section' => 'hr',
        'title' => 'ขอลาใหม่',
        'balances' => leave_balances($me),
        'approver' => my_approver($me),
        'errors' => $errors,
        'old' => $old,
    ]);
}

function leave_submit(): void
{
    $me = require_me();
    // ปิดการส่งใบลาจนกว่าระบบจะเปิด (ซ่อนหน้าอย่างเดียวไม่พอ ต้องกันที่ server ด้วย)
    if (!preview_modules()) {
        http_response_code(403);
        exit('ระบบการลายังไม่เปิดใช้งานค่ะ');
    }
    $errors = submit_leave($me, $_POST, $_FILES['cert'] ?? null);
    if ($errors) {
        http_response_code(422);
        leave_new($errors, $_POST);
    }
    redirect('/hr/leave?sent=1');
}

function leave_cert(): void
{
    $me = require_me();
    send_leave_cert($me, (string) ($_GET['id'] ?? ''));
}

// ── โมดูลอื่น ─────────────────────────────────────────────────────────────────
function registry(): void
{
    $me = require_me();
    // ทะเบียนลูกค้าย้ายมา PHP ในรอบถัดไป — ตอนนี้แสดงหน้า "กำลังพัฒนา" เสมอ
    render('coming_soon', [
        'me' => $me, 'section' => 'registry', 'title' => 'ลูกค้าและคู่ค้า', 'mascotId' => 'wao',
        'moduleName' => 'ลูกค้าและคู่ค้า', 'detail' => 'ทะเบียนลูกค้าและคู่ค้าอยู่ระหว่างพัฒนา',
    ]);
}

function meetings(): void
{
    $me = require_me();
    render('meetings', ['me' => $me, 'section' => 'meetings', 'title' => 'รายงานการประชุม', 'appUrl' => env('MEETINGS_APP_URL')]);
}

// ── เรียกจากสคริปต์ในหน้า (ตอบเป็น JSON) ──────────────────────────────────────
function api_checkin(): void
{
    $me = current_user();
    if (!$me) json_out(['error' => 'กรุณาเข้าสู่ระบบใหม่'], 401);
    try {
        json_out(['time' => check_in($me)]);
    } catch (Throwable $ex) {
        app_log('checkin: ' . $ex->getMessage());
        json_out(['error' => 'ลงเวลาไม่สำเร็จ ลองใหม่อีกครั้งค่ะ'], 500);
    }
}

function api_consent(): void
{
    $me = current_user();
    if (!$me) json_out(['error' => 'unauthorized'], 401);
    record_consent($me);
    json_out(['ok' => true]);
}
