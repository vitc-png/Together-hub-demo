<?php
declare(strict_types=1);

// ── คำขอ / คำตอบ ─────────────────────────────────────────────────────────────
function is_https(): bool
{
    if (env('APP_FORCE_HTTPS') === '1') return true;
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $to, int $code = 303): never
{
    header('Location: ' . $to, true, $code);
    exit;
}

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** ที่อยู่เว็บเต็ม ใช้สร้างลิงก์ในอีเมล — ตั้ง APP_URL ใน .env เสมอ (ไม่เชื่อ Host ที่ผู้ใช้ส่งมา) */
function app_url(string $path = '/'): string
{
    return rtrim((string) env('APP_URL', 'http://localhost:8000'), '/') . $path;
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/** ปลายทางหลังเข้าระบบ — รับเฉพาะ path ภายในเว็บ (กันพาไปเว็บหลอก) */
function safe_next(?string $raw): string
{
    $raw = (string) $raw;
    if ($raw === '' || $raw[0] !== '/' || str_starts_with($raw, '//') || str_starts_with($raw, '/\\')) return '/';
    if (preg_match('/[\r\n]/', $raw)) return '/';
    return substr($raw, 0, 255);
}

// ── Session ───────────────────────────────────────────────────────────────────
function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('tg_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => is_https(),
        'httponly' => true,      // สคริปต์ในหน้าเว็บอ่านคุกกี้นี้ไม่ได้
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', (string) ((int) env('SESSION_HOURS', '12') * 3600));
    $dir = APP_ROOT . '/storage/sessions';
    if (is_dir($dir) && is_writable($dir)) session_save_path($dir);
    session_start();
}

function flash(string $key, mixed $value = null): mixed
{
    if (func_num_args() === 2) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $v = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $v;
}

// ── CSRF: ทุกฟอร์ม POST ต้องมีรหัสนี้ (กันเว็บอื่นแอบส่งฟอร์มแทนผู้ใช้) ───────────
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $sent)) {
        http_response_code(419);
        exit('หน้านี้หมดอายุแล้ว กรุณากลับไปเปิดหน้าใหม่แล้วลองอีกครั้ง');
    }
}

// ── โมดูลที่เปิดใช้ ───────────────────────────────────────────────────────────
// เพิ่มโมดูลที่เปิดแล้ว: ใส่ path ใน LIVE_PATHS ที่เดียว
const LIVE_PATHS = ['/', '/meetings'];

/** เปิดดูหน้า HR/ทะเบียนชั่วคราวตอนพัฒนา — ห้ามตั้งเป็น 1 บนระบบใช้งานจริง */
function preview_modules(): bool
{
    return env('TG_PREVIEW_MODULES') === '1';
}

function is_live_href(string $href): bool
{
    if (str_starts_with($href, '#')) return true;
    $path = parse_url($href, PHP_URL_PATH) ?: '/';
    return in_array($path, LIVE_PATHS, true);
}

// ── วันที่ภาษาไทย (พ.ศ.) ──────────────────────────────────────────────────────
const TH_MONTHS = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
const TH_MONTHS_SHORT = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
const TH_WEEKDAYS = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

/** "วันพุธที่ 28 ตุลาคม 2569" */
function thai_date_long(?DateTimeInterface $d = null): string
{
    $d ??= new DateTimeImmutable('now');
    return sprintf('วัน%sที่ %d %s %d', TH_WEEKDAYS[(int) $d->format('w')], (int) $d->format('j'), TH_MONTHS[(int) $d->format('n')], (int) $d->format('Y') + 543);
}

/** "2026-10-23" → "23 ต.ค. 69" */
function thai_date_short(string $iso): string
{
    $d = new DateTimeImmutable(substr($iso, 0, 10));
    return sprintf('%d %s %02d', (int) $d->format('j'), TH_MONTHS_SHORT[(int) $d->format('n')], ((int) $d->format('Y') + 543) % 100);
}

/** ช่วงวันที่: "23 ต.ค. 69", "8–9 ก.ย. 69", "30 ก.ย. 69 – 2 ต.ค. 69" */
function thai_date_range(string $start, string $end): string
{
    if ($start === $end) return thai_date_short($start);
    if (substr($start, 0, 7) === substr($end, 0, 7)) {
        return (int) substr($start, 8, 2) . '–' . thai_date_short($end);
    }
    return thai_date_short($start) . ' – ' . thai_date_short($end);
}

function today_iso(): string
{
    return date('Y-m-d');
}

/** วันทำงานถัดไป (ถ้าวันนี้เป็นวันทำงาน = วันนี้) */
function next_workday_iso(): string
{
    $d = new DateTimeImmutable('today');
    while ((int) $d->format('N') >= 6) $d = $d->modify('+1 day');
    return $d->format('Y-m-d');
}

/** แสดงจำนวนวัน: 1.5 → "1.5", 2 → "2" */
function fmt_days(float $n): string
{
    return floor($n) == $n ? (string) (int) $n : number_format($n, 1);
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    return mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1);
}
