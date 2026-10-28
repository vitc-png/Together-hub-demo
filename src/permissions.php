<?php
declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════════════
// กฎสิทธิ์ (แทน Row Level Security ของ Supabase)
// ⚠️ ทุกคำสั่งอ่าน/แก้ข้อมูลของคนอื่น ต้องผ่านฟังก์ชันในไฟล์นี้ก่อนเสมอ
// ═══════════════════════════════════════════════════════════════════════════

/** ฉันดูแล (อนุมัติ/เห็นข้อมูล) พนักงานคนนี้ไหม — อนุมัติของตัวเองไม่ได้ */
function can_manage(array $me, string $targetId): bool
{
    if ($targetId === $me['id']) return false;
    if (in_array($me['role'], ['executive', 'admin'], true)) {
        return (bool) q_one('SELECT 1 FROM employees WHERE id = ?', [$targetId]);
    }
    $t = q_one('SELECT department_id, manager_id FROM employees WHERE id = ?', [$targetId]);
    if (!$t) return false;
    if ($t['manager_id'] === $me['id']) return true;
    return $me['role'] === 'manager' && $t['department_id'] !== null && $t['department_id'] === $me['department_id'];
}

/** ดูข้อมูลของพนักงานคนนี้ได้ไหม (ของตัวเอง หรือคนที่ดูแล) */
function can_view_employee(array $me, string $targetId): bool
{
    return $targetId === $me['id'] || can_manage($me, $targetId);
}

/**
 * ผู้อนุมัติใบลาของฉัน: ตั้งไว้เฉพาะคน → หัวหน้าฝ่าย → ผู้บริหาร
 * คืนแค่ชื่อและตำแหน่ง (ไม่ส่งข้อมูลอื่นของคนอื่น ตามหลัก PDPA)
 */
function my_approver(array $me): array
{
    $row = q_one(
        "SELECT a.full_name, a.position FROM employees a
         WHERE a.active = 1 AND a.id <> :me AND (
               a.id = :mgr
            OR (:mgr2 IS NULL AND a.role = 'manager' AND a.department_id = :dept)
            OR (:mgr3 IS NULL AND a.role = 'executive'))
         ORDER BY (a.id = :mgr4) DESC, (a.role = 'manager') DESC
         LIMIT 1",
        [
            'me' => $me['id'],
            'mgr' => $me['manager_id'], 'mgr2' => $me['manager_id'], 'mgr3' => $me['manager_id'], 'mgr4' => $me['manager_id'],
            'dept' => $me['department_id'],
        ]
    );
    return $row
        ? ['name' => $row['full_name'], 'position' => $row['position']]
        : ['name' => 'ยังไม่ได้กำหนด — ติดต่อฝ่ายสำนักงาน', 'position' => '-'];
}
