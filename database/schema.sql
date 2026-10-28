-- ============================================================================
-- Together Hub — โครงฐานข้อมูล (MySQL 8.0+ / MariaDB 10.6+)
-- รันครั้งเดียวกับฐานข้อมูลใหม่:  mysql -u <ผู้ใช้> -p together_hub < database/schema.sql
--
-- หลักการสิทธิ์ (MySQL ไม่มี Row Level Security แบบ Supabase → ตรวจในโค้ด PHP ทุกจุด ดู src/permissions.php)
--   staff      เห็น/แก้เฉพาะของตัวเอง
--   manager    + เห็นของคนในฝ่าย และอนุมัติใบลาของคนในฝ่าย
--   executive  เห็นทุกฝ่าย อนุมัติได้ทุกฝ่าย
--   admin      (ฝ่ายสำนักงาน/HR) จัดการข้อมูลพนักงาน วันลา วันหยุด
--
-- ⚠️ ผู้ใช้ฐานข้อมูลที่เว็บใช้ ให้สิทธิ์แค่ SELECT, INSERT, UPDATE (ไม่ให้ DELETE, DROP) — ดู README
-- ============================================================================

SET NAMES utf8mb4;

-- ── ฝ่าย ────────────────────────────────────────────────────────────────────
CREATE TABLE departments (
  id          CHAR(36)     NOT NULL PRIMARY KEY,
  code        VARCHAR(20)  NOT NULL UNIQUE,          -- PaM, GM.OD, GM.DD, GM.CM
  name        VARCHAR(120) NOT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── พนักงาน ─────────────────────────────────────────────────────────────────
-- ไม่มีตารางรหัสผ่าน: เข้าระบบด้วยรหัส/ลิงก์ทางอีเมลเท่านั้น (ตาราง login_tokens)
CREATE TABLE employees (
  id             CHAR(36)     NOT NULL PRIMARY KEY,
  email          VARCHAR(190) NOT NULL UNIQUE,         -- ตัวพิมพ์เล็กเสมอ (โค้ดแปลงให้ก่อนบันทึก)
  full_name      VARCHAR(160) NOT NULL,
  nickname       VARCHAR(60)  NULL,
  position       VARCHAR(160) NOT NULL,
  department_id  CHAR(36)     NULL,
  role           ENUM('staff','manager','executive','admin') NOT NULL DEFAULT 'staff',
  manager_id     CHAR(36)     NULL,                    -- ผู้อนุมัติใบลา (ว่าง = หัวหน้าฝ่ายตามผัง)
  active         TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at  DATETIME     NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_emp_dept FOREIGN KEY (department_id) REFERENCES departments(id),
  CONSTRAINT fk_emp_manager FOREIGN KEY (manager_id) REFERENCES employees(id),
  KEY idx_emp_dept (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── รหัสเข้าระบบทางอีเมล (แทน Supabase Auth) ───────────────────────────────
-- เก็บเฉพาะค่า HMAC ของรหัสและลิงก์ ไม่เก็บรหัสจริง — ถ้าฐานข้อมูลรั่ว ก็เอาไปใช้เข้าระบบไม่ได้
CREATE TABLE login_tokens (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  employee_id  CHAR(36)     NOT NULL,
  code_hash    CHAR(64)     NOT NULL,                  -- รหัส 6 หลัก
  link_hash    CHAR(64)     NOT NULL UNIQUE,           -- ลิงก์ในอีเมล
  next_path    VARCHAR(255) NOT NULL DEFAULT '/',
  attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0,   -- ใส่รหัสผิดกี่ครั้ง (เกิน 5 ใช้ไม่ได้)
  expires_at   DATETIME     NOT NULL,
  used_at      DATETIME     NULL,
  request_ip   VARCHAR(45)  NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tok_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  KEY idx_tok_emp (employee_id, created_at),
  KEY idx_tok_ip (request_ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── ประเภทการลา + สิทธิ์วันลาต่อปี ────────────────────────────────────────────
CREATE TABLE leave_types (
  code                  VARCHAR(20)  NOT NULL PRIMARY KEY,   -- annual, sick, personal, maternity, other
  name                  VARCHAR(60)  NOT NULL,
  needs_cert_from_days  DECIMAL(4,1) NULL,                   -- ลาป่วยกี่วันต้องแนบใบรับรองแพทย์
  sort                  INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leave_entitlements (
  employee_id  CHAR(36)     NOT NULL,
  year         SMALLINT     NOT NULL,                  -- ค.ศ.
  leave_type   VARCHAR(20)  NOT NULL,
  days         DECIMAL(5,1) NOT NULL,
  PRIMARY KEY (employee_id, year, leave_type),
  CONSTRAINT fk_ent_emp FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  CONSTRAINT fk_ent_type FOREIGN KEY (leave_type) REFERENCES leave_types(code),
  CONSTRAINT chk_ent_days CHECK (days >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── ใบลา ────────────────────────────────────────────────────────────────────
CREATE TABLE leave_requests (
  id            CHAR(36)     NOT NULL PRIMARY KEY,
  ref_no        VARCHAR(20)  NOT NULL UNIQUE,          -- L-2569-0001 (โค้ดตั้งให้ ดู doc_counters)
  employee_id   CHAR(36)     NOT NULL,
  leave_type    VARCHAR(20)  NOT NULL,
  start_date    DATE         NOT NULL,
  end_date      DATE         NOT NULL,
  half_day      ENUM('full','morning','afternoon') NOT NULL DEFAULT 'full',
  days          DECIMAL(4,1) NOT NULL,
  reason        VARCHAR(300) NOT NULL,
  cert_path     VARCHAR(255) NULL,                     -- ไฟล์ใบรับรองแพทย์ใน storage/leave-certs (นอกโฟลเดอร์เว็บ)
  status        ENUM('pending','approved','revise','rejected','cancelled') NOT NULL DEFAULT 'pending',
  approver_id   CHAR(36)     NULL,
  approver_note VARCHAR(500) NULL,
  decided_at    DATETIME     NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_lr_emp FOREIGN KEY (employee_id) REFERENCES employees(id),
  CONSTRAINT fk_lr_type FOREIGN KEY (leave_type) REFERENCES leave_types(code),
  CONSTRAINT fk_lr_approver FOREIGN KEY (approver_id) REFERENCES employees(id),
  CONSTRAINT chk_lr_dates CHECK (end_date >= start_date),
  CONSTRAINT chk_lr_days CHECK (days > 0),
  CONSTRAINT chk_lr_half CHECK (half_day = 'full' OR start_date = end_date),   -- ครึ่งวันได้เฉพาะลาวันเดียว
  KEY idx_lr_emp (employee_id, start_date),
  KEY idx_lr_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ไม่มีการลบใบลา: ใบลาเป็นหลักฐาน ยกเลิกได้ด้วยสถานะ cancelled เท่านั้น

-- ตัวนับเลขที่เอกสารรายปี (ใบลา, และหนังสือออกในอนาคต) — ล็อกแถวตอนออกเลข กันเลขซ้ำ
CREATE TABLE doc_counters (
  name   VARCHAR(20) NOT NULL,
  year   SMALLINT    NOT NULL,                         -- พ.ศ.
  value  INT         NOT NULL DEFAULT 0,
  PRIMARY KEY (name, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── ลงเวลา (ใช้เวลาของ server เสมอ ผู้ใช้แก้เวลาเองไม่ได้) ─────────────────────
CREATE TABLE attendance (
  id           CHAR(36)  NOT NULL PRIMARY KEY,
  employee_id  CHAR(36)  NOT NULL,
  work_date    DATE      NOT NULL,
  check_in     DATETIME  NOT NULL,
  check_out    DATETIME  NULL,
  CONSTRAINT fk_att_emp FOREIGN KEY (employee_id) REFERENCES employees(id),
  UNIQUE KEY uq_att_day (employee_id, work_date)       -- ลงเวลาเข้าได้วันละครั้ง
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── ความยินยอม PDPA (เก็บเป็นหลักฐาน: ใคร ยินยอมอะไร เวอร์ชันไหน เมื่อไร) ───────
-- แก้/ลบไม่ได้ — ถอนความยินยอม = บันทึกแถวใหม่ granted = 0
CREATE TABLE consents (
  id              CHAR(36)    NOT NULL PRIMARY KEY,
  employee_id     CHAR(36)    NOT NULL,
  policy_version  VARCHAR(40) NOT NULL,                -- เช่น privacy-2569-v1
  purpose         VARCHAR(20) NOT NULL,                -- 'essential' | 'analytics'
  granted         TINYINT(1)  NOT NULL,
  created_at      DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_con_emp FOREIGN KEY (employee_id) REFERENCES employees(id),
  KEY idx_con_emp (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── วันหยุดบริษัท (ใช้นับวันลา) ─────────────────────────────────────────────
CREATE TABLE holidays (
  day   DATE         NOT NULL PRIMARY KEY,
  name  VARCHAR(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── ข้อมูลตั้งต้น (ประเภทการลา) ─────────────────────────────────────────────
INSERT INTO leave_types (code, name, needs_cert_from_days, sort) VALUES
  ('annual',    'ลาพักร้อน', NULL, 1),
  ('sick',      'ลาป่วย',    3,    2),
  ('personal',  'ลากิจ',     NULL, 3),
  ('maternity', 'ลาคลอด',    NULL, 4),
  ('other',     'ลาอื่น ๆ',  NULL, 5);
