<?php
declare(strict_types=1);

// ── ตัวช่วยแสดงผล ───────────────────────────────────────────────────────────

/** ใส่ข้อความลงหน้าเว็บอย่างปลอดภัย (กันสคริปต์แปลกปลอม) — ใช้กับทุกค่าที่มาจากผู้ใช้/ฐานข้อมูล */
function e(mixed $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** แสดงหน้า: views/<name>.php ใส่ในโครงหน้ามาตรฐาน (views/layout.php) */
function render(string $view, array $vars = [], string $layout = 'layout'): never
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require APP_ROOT . "/views/{$view}.php";
    $content = (string) ob_get_clean();
    if ($layout === '') {
        echo $content;
    } else {
        require APP_ROOT . "/views/{$layout}.php";
    }
    exit;
}

function partial(string $name, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require APP_ROOT . "/views/partials/{$name}.php";
}

function asset(string $path): string
{
    $file = APP_ROOT . '/public' . $path;
    $v = is_file($file) ? substr((string) filemtime($file), -6) : '0';
    return e($path . '?v=' . $v);
}

// ── มาสคอต (เพิ่มตัวใหม่ที่นี่ที่เดียว ไฟล์อยู่ใน public/mascots/) ───────────────
const MASCOTS = [
    'khwanKhao' => ['name' => 'น้องขวัญข้าว', 'role' => 'ผู้ช่วยฝ่าย HR', 'file' => 'khwan-khao', 'ratio' => 469 / 512],
    'wao' => ['name' => 'น้องเว้า', 'role' => 'ผู้ช่วยงานติดต่อลูกค้าและคู่ค้า', 'file' => 'wao', 'ratio' => 517 / 512],
    'khamPhaeng' => ['name' => 'น้องคำแพง', 'role' => 'เลขาคู่ใจ จดครบทุกมติ ติดตามทุกงาน', 'file' => 'kham-phaeng', 'ratio' => 1484 / 598],
    'khwanKhaoFull' => ['name' => 'น้องขวัญข้าว', 'role' => 'ผู้ช่วยฝ่าย HR', 'file' => 'khwan-khao-full', 'ratio' => 1.8019],
    'waoFull' => ['name' => 'น้องเว้า', 'role' => 'ผู้ช่วยงานติดต่อลูกค้าและคู่ค้า', 'file' => 'wao-full', 'ratio' => 1.7105],
    'manual' => ['name' => 'มาสคอตคู่มือ', 'role' => 'คู่มือการใช้งานและแบบฟอร์ม', 'file' => 'manual', 'ratio' => 1.5766],
    'khamKhian' => ['name' => 'อ้ายคำเขียน', 'role' => 'ร่างเป็นเรื่อง เลขเป็นระบบ ครบทุกหนังสือออก', 'file' => 'kham-khian', 'ratio' => 1391 / 839],
    'khaoJi' => ['name' => 'ข้าวจี่', 'role' => 'เพื่อนพาเรียนรู้', 'file' => 'khao-ji', 'ratio' => 938 / 687],
];

/** รูปมาสคอต — decorative = มีชื่อเขียนกำกับอยู่แล้ว โปรแกรมอ่านหน้าจอข้ามได้ */
function mascot(string $id, int $size, string $class = '', bool $decorative = true): string
{
    $m = MASCOTS[$id];
    $src = '/mascots/' . $m['file'] . ($size <= 128 ? '-256' : '-512') . '.webp';
    $alt = $decorative ? '' : $m['name'] . ' ' . $m['role'];
    return sprintf(
        '<img src="%s" width="%d" height="%d" alt="%s" class="select-none %s" draggable="false" loading="lazy">',
        e($src), $size, (int) round($size * $m['ratio']), e($alt), e($class)
    );
}

// ── ไอคอนเส้น (aria-hidden เพราะมีคำกำกับข้าง ๆ เสมอ) ─────────────────────────
function icon(string $name, string $class = 'size-5'): string
{
    $paths = [
        'search' => ['2', '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>'],
        'bell' => ['2', '<path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/>'],
        'clock' => ['2', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
        'check' => ['2.4', '<path d="M5 12l5 5 9-10"/>'],
        'doc' => ['1.8', '<path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/>'],
        'people' => ['1.8', '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.6 3.4-5.5 6.5-5.5s5.7 1.9 6.5 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18.5 14.8c1.6.8 2.6 2.6 3 5.2"/>'],
        'building' => ['1.8', '<path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-5h6v5M9 10h.01M15 10h.01"/>'],
        'spark' => ['1.8', '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 16l.7 2.3L22 19l-2.3.7L19 22l-.7-2.3L16 19l2.3-.7z"/>'],
        'book' => ['1.8', '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 19V5M8 7h7M8 11h5"/>'],
        'home' => ['2', '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/>'],
        'menu' => ['2', '<path d="M4 6h16M4 12h16M4 18h16"/>'],
        'wrench' => ['2', '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z"/>'],
    ];
    [$w, $d] = $paths[$name];
    return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . $w
        . '" stroke-linecap="round" stroke-linejoin="round" class="' . e($class) . '">' . $d . '</svg>';
}

/**
 * ลิงก์ไปโมดูล — ถ้าโมดูลยังไม่เปิด จะเป็นข้อความกดไม่ได้ พร้อมบอกโปรแกรมอ่านหน้าจอ
 * $inner เป็น HTML ที่ผ่าน e() แล้ว
 */
function module_link(string $href, string $inner, string $class = '', string $attrs = ''): string
{
    if (is_live_href($href)) {
        return '<a href="' . e($href) . '" class="' . e($class) . '" ' . $attrs . '>' . $inner . '</a>';
    }
    return '<span role="link" aria-disabled="true" title="กำลังพัฒนา" class="cursor-not-allowed text-gray-700 ' . e($class) . '" ' . $attrs . '>'
        . $inner . '<span class="sr-only"> (กำลังพัฒนา ยังใช้งานไม่ได้)</span></span>';
}

const STATUS_STYLES = [
    'pending' => ['bg-status-pending-bg text-status-pending', 'รออนุมัติ'],
    'approved' => ['bg-status-approved-bg text-status-approved', 'อนุมัติแล้ว'],
    'revise' => ['bg-status-revise-bg text-status-revise', 'ส่งกลับแก้ไข'],
    'rejected' => ['bg-status-rejected-bg text-status-rejected', 'ไม่อนุมัติ'],
    'cancelled' => ['bg-surface text-gray-700', 'ยกเลิกแล้ว'],
];

function status_badge(string $status, ?string $label = null): string
{
    [$cls, $def] = STATUS_STYLES[$status] ?? STATUS_STYLES['pending'];
    return '<span class="inline-flex rounded-xs px-2.5 py-0.5 text-[13px] font-bold ' . $cls . '">' . e($label ?? $def) . '</span>';
}

/** กรอบมุมส้ม (ลายเซ็นแบรนด์) — ใช้ได้ไม่เกิน 2 ครั้งต่อหน้า */
function corner_bracket(string $size = 'md'): string
{
    $arm = $size === 'md' ? 'w-12 h-2' : 'w-9 h-1.5';
    $leg = $size === 'md' ? 'w-2 h-12' : 'w-1.5 h-9';
    return '<span aria-hidden="true" class="absolute left-0 top-0 bg-orange ' . $arm . '"></span>'
        . '<span aria-hidden="true" class="absolute left-0 top-0 bg-orange ' . $leg . '"></span>';
}
