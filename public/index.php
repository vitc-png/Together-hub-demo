<?php
declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════════════
// ทางเข้าเดียวของเว็บ (ทุกคำขอมาที่ไฟล์นี้) — ตารางหน้าทั้งหมดอยู่ด้านล่าง
// ═══════════════════════════════════════════════════════════════════════════

// ใช้ php -S ทดสอบในเครื่อง: ให้ไฟล์ภาพ/CSS/JS ส่งตรงได้เลย
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($file !== __FILE__ && is_file($file)) return false;
}

require dirname(__DIR__) . '/src/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';

// ทุกคำขอ POST ต้องมีรหัส CSRF
if ($method === 'POST') csrf_check();

$routes = [
    'GET /' => 'home',
    'GET /login' => 'login_page',
    'POST /login' => 'login_send',
    'POST /login/verify' => 'login_verify',
    'GET /auth/confirm' => 'confirm_page',
    'POST /auth/confirm' => 'confirm_submit',
    'POST /auth/signout' => 'signout',
    'GET /hr/leave' => 'leave_index',
    'GET /hr/leave/new' => 'leave_new',
    'POST /hr/leave/new' => 'leave_submit',
    'GET /hr/leave/cert' => 'leave_cert',
    'GET /registry' => 'registry',
    'GET /meetings' => 'meetings',
    'POST /api/checkin' => 'api_checkin',
    'POST /api/consent' => 'api_consent',
];

$handler = $routes["$method $path"] ?? null;
if (!$handler) {
    http_response_code(404);
    $me = current_user();
    if ($me) render('coming_soon', ['me' => $me, 'section' => 'home', 'title' => 'ไม่พบหน้านี้', 'mascotId' => 'khwanKhao', 'moduleName' => 'ไม่พบหน้านี้', 'detail' => 'หน้านี้ไม่มีอยู่ หรือยังไม่เปิดใช้งาน', 'notFound' => true]);
    exit('ไม่พบหน้านี้');
}

try {
    require dirname(__DIR__) . '/src/routes.php';
    $handler();
} catch (Throwable $ex) {
    app_log($ex::class . ': ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine());
    http_response_code(500);
    if (env('APP_DEBUG') === '1') throw $ex;
    echo 'ระบบขัดข้องชั่วคราว กรุณาลองใหม่อีกครั้ง หรือแจ้งฝ่ายสำนักงาน';
}
