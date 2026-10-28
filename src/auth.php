<?php
declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════════════
// เข้าสู่ระบบด้วยอีเมล (ไม่มีรหัสผ่านให้ลืม/รั่ว) — แทน Supabase Auth
//   1. พนักงานกรอกอีเมล → ถ้าอยู่ในรายชื่อและยังทำงานอยู่ ส่งอีเมลที่มี "ปุ่มเข้าสู่ระบบ" + "รหัส 6 หลัก"
//   2. กดปุ่มในอีเมล (เครื่องเดียวกัน) หรือพิมพ์รหัส (คนละเครื่อง) อย่างใดอย่างหนึ่ง ใช้ได้ครั้งเดียว
//   3. ตอบเหมือนกันทุกกรณี — ไม่บอกคนนอกว่าอีเมลไหนอยู่ในระบบ
// ═══════════════════════════════════════════════════════════════════════════

/** HMAC ด้วย APP_KEY — เก็บค่านี้แทนรหัสจริง */
function token_hash(string $value): string
{
    return hash_hmac('sha256', $value, (string) env('APP_KEY'));
}

function login_ttl_minutes(): int
{
    return max(5, min(60, (int) env('LOGIN_CODE_TTL_MINUTES', '15')));
}

function normalize_email(string $email): string
{
    return mb_strtolower(trim($email));
}

/**
 * ขอรหัสเข้าระบบ — คืน true เสมอเมื่อรูปแบบอีเมลถูก (ไม่บอกว่ามีในระบบไหม)
 * จำกัด: อีเมลเดิมขอได้ทุก RESEND_SECONDS วินาที, IP เดิมไม่เกิน 10 ครั้ง/ชั่วโมง
 */
function request_login_code(string $email, string $next): void
{
    $emp = q_one('SELECT id, email, full_name FROM employees WHERE email = ? AND active = 1', [$email]);
    $ip = client_ip();

    $ipCount = (int) q_one(
        'SELECT COUNT(*) AS n FROM login_tokens WHERE request_ip = ? AND created_at > NOW() - INTERVAL 1 HOUR',
        [$ip]
    )['n'];
    if ($ipCount >= 10 || !$emp) return;

    $recent = q_one(
        'SELECT 1 FROM login_tokens WHERE employee_id = ? AND created_at > NOW() - INTERVAL ? SECOND',
        [$emp['id'], RESEND_SECONDS - 2]
    );
    if ($recent) return;

    // ขอรหัสใหม่ = รหัสเก่าที่ยังไม่ใช้หมดอายุทันที (มีรหัสใช้ได้ทีละชุด)
    q('UPDATE login_tokens SET expires_at = NOW() WHERE employee_id = ? AND used_at IS NULL AND expires_at > NOW()', [$emp['id']]);

    $code = str_pad((string) random_int(0, 10 ** OTP_LENGTH - 1), OTP_LENGTH, '0', STR_PAD_LEFT);
    $link = bin2hex(random_bytes(32));
    q(
        'INSERT INTO login_tokens (employee_id, code_hash, link_hash, next_path, expires_at, request_ip)
         VALUES (?, ?, ?, ?, NOW() + INTERVAL ? MINUTE, ?)',
        [$emp['id'], token_hash($emp['id'] . ':' . $code), token_hash($link), $next, login_ttl_minutes(), $ip]
    );

    $url = app_url('/auth/confirm?t=' . $link);
    send_login_email($emp['email'], $emp['full_name'], $url, $code, login_ttl_minutes());
}

/** ตรวจรหัส 6 หลัก — คืน next path ถ้าถูก, null ถ้าผิด/หมดอายุ */
function verify_login_code(string $email, string $code): ?string
{
    $emp = q_one('SELECT id FROM employees WHERE email = ? AND active = 1', [$email]);
    if (!$emp) return null;

    $pdo = db();
    $pdo->beginTransaction();
    $tok = q_one(
        'SELECT id, code_hash, next_path, attempts FROM login_tokens
         WHERE employee_id = ? AND used_at IS NULL AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1 FOR UPDATE',
        [$emp['id']]
    );
    if (!$tok || (int) $tok['attempts'] >= MAX_CODE_ATTEMPTS) {
        $pdo->commit();
        return null;
    }
    if (!hash_equals($tok['code_hash'], token_hash($emp['id'] . ':' . $code))) {
        // นับครั้งที่ผิด — กันการเดารหัส (เดาได้ไม่เกิน 5 ครั้งต่อรหัส)
        q('UPDATE login_tokens SET attempts = attempts + 1 WHERE id = ?', [$tok['id']]);
        $pdo->commit();
        return null;
    }
    q('UPDATE login_tokens SET used_at = NOW() WHERE id = ?', [$tok['id']]);
    $pdo->commit();

    sign_in($emp['id']);
    return safe_next($tok['next_path']);
}

/** ตรวจลิงก์จากอีเมล (เรียกตอน "คนกดปุ่ม" เท่านั้น ไม่ใช่ตอนเปิดหน้า) */
function verify_login_link(string $link): ?string
{
    if (!preg_match('/^[a-f0-9]{64}$/', $link)) return null;
    $pdo = db();
    $pdo->beginTransaction();
    $tok = q_one(
        'SELECT t.id, t.employee_id, t.next_path FROM login_tokens t
         JOIN employees e ON e.id = t.employee_id AND e.active = 1
         WHERE t.link_hash = ? AND t.used_at IS NULL AND t.expires_at > NOW() FOR UPDATE',
        [token_hash($link)]
    );
    if (!$tok) {
        $pdo->commit();
        return null;
    }
    q('UPDATE login_tokens SET used_at = NOW() WHERE id = ?', [$tok['id']]);
    $pdo->commit();

    sign_in($tok['employee_id']);
    return safe_next($tok['next_path']);
}

function sign_in(string $employeeId): void
{
    session_regenerate_id(true); // เลข session ใหม่ทุกครั้งที่เข้าระบบ (กันขโมย session เดิม)
    $_SESSION['employee_id'] = $employeeId;
    $_SESSION['signed_in_at'] = time();
    unset($_SESSION['_csrf'], $_SESSION['login_pending']);
    q('UPDATE employees SET last_login_at = NOW() WHERE id = ?', [$employeeId]);
}

function sign_out(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_destroy();
}

/**
 * ผู้ใช้ที่เข้าระบบอยู่ — อ่านจากฐานข้อมูลทุกครั้ง
 * (ถ้าฝ่ายสำนักงานปิดใช้งานพนักงาน จะออกจากระบบทันทีในการเปิดหน้าถัดไป)
 */
function current_user(): ?array
{
    static $me = false;
    if ($me !== false) return $me;
    $id = $_SESSION['employee_id'] ?? null;
    $maxAge = (int) env('SESSION_HOURS', '12') * 3600;
    if (!$id || time() - (int) ($_SESSION['signed_in_at'] ?? 0) > $maxAge) return $me = null;

    $row = q_one(
        'SELECT e.id, e.email, e.full_name, e.nickname, e.position, e.role, e.department_id, e.manager_id,
                d.name AS department
         FROM employees e LEFT JOIN departments d ON d.id = e.department_id
         WHERE e.id = ? AND e.active = 1',
        [$id]
    );
    if (!$row) return $me = null;
    $row['initials'] = initials($row['full_name']);
    $row['department'] ??= '';
    return $me = $row;
}

/** ใช้ต้นทุกหน้าที่ต้องเข้าระบบ */
function require_me(): array
{
    $me = current_user();
    if (!$me) {
        // มี session ที่ยังไม่หมดอายุ แต่ไม่พบพนักงาน = ถูกปิดใช้งาน/ไม่อยู่ในรายชื่อ
        $maxAge = (int) env('SESSION_HOURS', '12') * 3600;
        $had = !empty($_SESSION['employee_id']) && time() - (int) ($_SESSION['signed_in_at'] ?? 0) <= $maxAge;
        if (!empty($_SESSION['employee_id'])) {
            sign_out();
            start_session();
        }
        $next = safe_next($_SERVER['REQUEST_URI'] ?? '/');
        redirect('/login?' . http_build_query(array_filter(['next' => $next !== '/' ? $next : null, 'error' => $had ? 'notlisted' : null])));
    }
    return $me;
}
