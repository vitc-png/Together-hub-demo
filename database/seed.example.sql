-- ตัวอย่างข้อมูลตั้งต้น — ⚠️ ข้อมูลสมมติทั้งหมด (อีเมล @example.com ใช้ส่งจริงไม่ได้)
-- ใช้จริง: คัดลอกเป็น seed.sql (อย่าขึ้น Git) แล้วใส่ข้อมูลที่ฝ่ายสำนักงานอนุมัติ
-- รัน:  mysql -u <ผู้ใช้> -p together_hub < database/seed.example.sql

SET NAMES utf8mb4;

INSERT INTO departments (id, code, name) VALUES
  ('d0000000-0000-4000-8000-000000000001', 'PaM',   'ฝ่ายบริหาร'),
  ('d0000000-0000-4000-8000-000000000002', 'GM.OD', 'ฝ่ายสำนักงาน'),
  ('d0000000-0000-4000-8000-000000000003', 'GM.DD', 'ฝ่ายออกแบบ'),
  ('d0000000-0000-4000-8000-000000000004', 'GM.CM', 'ฝ่ายควบคุมงาน');

-- role: staff | manager (หัวหน้าฝ่าย) | executive (ผู้บริหาร) | admin (ฝ่ายสำนักงาน/HR ดูแลระบบ)
-- อีเมลต้องเป็นตัวพิมพ์เล็ก และตรงกับที่ใช้เข้าระบบ
INSERT INTO employees (id, email, full_name, position, department_id, role) VALUES
  ('e0000000-0000-4000-8000-000000000001', 'md@example.com',     'ผู้บริหาร ตัวอย่าง', 'กรรมการผู้จัดการ', 'd0000000-0000-4000-8000-000000000001', 'executive'),
  ('e0000000-0000-4000-8000-000000000002', 'office@example.com', 'สำนักงาน ตัวอย่าง',  'GM ฝ่ายสำนักงาน',  'd0000000-0000-4000-8000-000000000002', 'admin'),
  ('e0000000-0000-4000-8000-000000000003', 'design@example.com', 'ออกแบบ ตัวอย่าง',    'GM ฝ่ายออกแบบ',    'd0000000-0000-4000-8000-000000000003', 'manager'),
  ('e0000000-0000-4000-8000-000000000004', 'staff1@example.com', 'สมชาย ตัวอย่าง',     'สถาปนิก',          'd0000000-0000-4000-8000-000000000003', 'staff');

-- สิทธิ์วันลาปีปัจจุบัน (ตัวเลขสมมติ — ฝ่าย HR กำหนดตามระเบียบบริษัท ไม่ต่ำกว่ากฎหมายแรงงาน)
INSERT INTO leave_entitlements (employee_id, year, leave_type, days)
SELECT e.id, YEAR(CURDATE()), t.code,
       CASE t.code WHEN 'annual' THEN 10 WHEN 'sick' THEN 30 WHEN 'personal' THEN 6 END
FROM employees e CROSS JOIN leave_types t
WHERE t.code IN ('annual', 'sick', 'personal');

-- วันหยุดบริษัท (ตัวอย่าง — ใส่ตามประกาศวันหยุดประจำปี)
INSERT INTO holidays (day, name) VALUES ('2026-12-31', 'วันสิ้นปี');
