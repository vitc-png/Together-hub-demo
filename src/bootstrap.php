<?php
declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════════════
// จุดเริ่มต้นของทุกหน้า: โหลดค่าตั้ง (.env) ฐานข้อมูล session และหัวข้อความปลอดภัย
// ═══════════════════════════════════════════════════════════════════════════

define('APP_ROOT', dirname(__DIR__));

// ── อ่านไฟล์ .env (KEY=VALUE ต่อบรรทัด, # = หมายเหตุ) ─────────────────────────
// ไฟล์ .env อยู่นอกโฟลเดอร์ public/ จึงเปิดจากเว็บไม่ได้
(function (): void {
    $file = APP_ROOT . '/.env';
    if (!is_file($file)) return;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $v = trim($v, "\"'");
        if (getenv($k) === false) putenv("$k=$v");
        $_ENV[$k] = $_ENV[$k] ?? $v;
    }
})();

function env(string $key, ?string $default = null): ?string
{
    $v = $_ENV[$key] ?? getenv($key);
    return ($v === false || $v === null || $v === '') ? $default : (string) $v;
}

date_default_timezone_set('Asia/Bangkok');
mb_internal_encoding('UTF-8');

const APP_VERSION = 'V_0.1';
const OTP_LENGTH = 6;          // ความยาวรหัสในอีเมล
const RESEND_SECONDS = 60;     // ขอรหัสซ้ำได้ทุกกี่วินาที
const MAX_CODE_ATTEMPTS = 5;   // ใส่รหัสผิดได้กี่ครั้งก่อนต้องขอใหม่
const SICK_CERT_DAYS = 3;      // ลาป่วยตั้งแต่กี่วันต้องแนบใบรับรองแพทย์
const CERT_MAX_BYTES = 5 * 1024 * 1024;
const PRIVACY_VERSION = 'privacy-2569-v1';

$debug = env('APP_DEBUG') === '1';
ini_set('display_errors', $debug ? '1' : '0');
error_reporting(E_ALL);

// ระบบจริงต้องตั้ง APP_KEY (ใช้เข้ารหัสลิงก์/รหัสในอีเมล) — ไม่ตั้ง = หยุดทำงาน ดีกว่าทำงานแบบไม่ปลอดภัย
if (strlen((string) env('APP_KEY')) < 32) {
    http_response_code(500);
    exit('ยังไม่ได้ตั้งค่า APP_KEY ในไฟล์ .env (ดูวิธีใน README)');
}

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/view.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/permissions.php';
require __DIR__ . '/mailer.php';
require __DIR__ . '/leave.php';

// ── หัวข้อความปลอดภัยทุกหน้า ───────────────────────────────────────────────
if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    // สคริปต์โหลดได้จากเว็บเราเท่านั้น (กันสคริปต์แปลกปลอม) — style inline ใช้แค่ความกว้างแถบ
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
    header('X-Robots-Tag: noindex, nofollow'); // ระบบภายใน ไม่ให้ Google เก็บ
    if (is_https()) header('Strict-Transport-Security: max-age=31536000');

    start_session();
}
