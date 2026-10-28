# Together Hub — รุ่น PHP + MySQL

เว็บภายในของ บจก.ทูเก็ตเตอร์ แอสโซซิเอทส์ ที่แปลงมาจากรุ่น Next.js + Supabase
เป็น **PHP 8.2+ แบบไม่ใช้เฟรมเวิร์ก** และ **MySQL 8 / MariaDB 10.6+** สำหรับติดตั้งบน VPS หรือเซิร์ฟเวอร์ของบริษัท

> ⚠️ ข้อมูลในไฟล์ตัวอย่างทั้งหมดเป็นข้อมูลสมมติ ห้ามใส่ข้อมูลพนักงานหรือลูกค้าจริงในโค้ด

## รอบนี้แปลงอะไรแล้ว

| ส่วน | สถานะ |
|---|---|
| เข้าสู่ระบบด้วยอีเมล (ปุ่มในอีเมล + รหัส 6 หลัก) | ✅ ทำงานได้ (แทน Supabase Auth) |
| หน้าแรก (ต้อนรับ ลงเวลา การ์ดโมดูล ประกาศ) | ✅ ลงเวลาบันทึกจริง / งานของฉันและประกาศยังเป็นข้อมูลตัวอย่าง |
| การลา: ดูวันลาคงเหลือ ขอลา แนบใบรับรองแพทย์ | ✅ ทำงานได้ (ปิดไว้ด้วย `TG_PREVIEW_MODULES` จนกว่าจะเปิดใช้) |
| หน้ารายงานการประชุม (ประตูไปโมดูลจริง) | ✅ ตั้ง `MEETINGS_APP_URL` |
| แถบ PDPA + บันทึกความยินยอม | ✅ |
| ทะเบียนลูกค้าและคู่ค้า | ⏳ แสดง "กำลังพัฒนา" (ย้ายรอบถัดไป) |
| ทัวร์แนะนำกับมาสคอต | ⏳ ยังไม่ได้ย้าย |
| หน้าอนุมัติใบลาของหัวหน้า | ⏳ ยังไม่มีทั้งรุ่นเดิมและรุ่นนี้ (ขั้นถัดไป) |

## สิ่งที่ต้องมี

- PHP 8.2 ขึ้นไป พร้อมส่วนเสริม `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`
- MySQL 8.0+ หรือ MariaDB 10.6+
- เว็บเซิร์ฟเวอร์ Nginx (+ PHP-FPM) หรือ Apache
- HTTPS (เช่น Let's Encrypt) — จำเป็น เพราะลิงก์เข้าสู่ระบบส่งทางอีเมล
- บัญชีอีเมลสำหรับส่ง (SMTP) เช่น Google Workspace หรือ Microsoft 365
- **ไม่ต้องใช้ Node.js** ตอนใช้งานจริง (ใช้แค่ตอนแก้หน้าตาแล้วสร้าง CSS ใหม่)

## ติดตั้งทีละขั้น

### 1. วางไฟล์

แตกไฟล์ไว้ เช่น `/var/www/together-hub` แล้วตั้งให้เว็บเซิร์ฟเวอร์ชี้ไปที่โฟลเดอร์ **`public/`** เท่านั้น
(โฟลเดอร์ `src/`, `views/`, `storage/`, `database/` และไฟล์ `.env` ต้องเปิดจากเว็บไม่ได้)

```bash
# ให้ PHP เขียนได้เฉพาะ storage/
sudo chown -R www-data:www-data /var/www/together-hub/storage
sudo chmod -R 700 /var/www/together-hub/storage
```

### 2. สร้างฐานข้อมูล

```sql
-- รันด้วยผู้ใช้ root ของ MySQL (ใส่รหัสผ่านจริงแทน <รหัสผ่าน>)
CREATE DATABASE together_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'together_hub'@'localhost' IDENTIFIED BY '<รหัสผ่าน>';
-- เว็บได้สิทธิ์แค่ อ่าน/เพิ่ม/แก้ ไม่มีสิทธิ์ลบหรือแก้โครงตาราง
GRANT SELECT, INSERT, UPDATE ON together_hub.* TO 'together_hub'@'localhost';
FLUSH PRIVILEGES;
```

```bash
mysql -u root -p together_hub < database/schema.sql
# ข้อมูลตั้งต้น: คัดลอก seed.example.sql เป็น seed.sql แล้วแก้เป็นรายชื่อจริงที่ฝ่ายสำนักงานอนุมัติ
mysql -u root -p together_hub < database/seed.sql
```

### 3. ตั้งค่า `.env`

```bash
cp .env.example .env
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"   # ได้ค่าสำหรับ APP_KEY
```

แก้ใน `.env`: `APP_URL`, `APP_KEY`, `DB_PASS`, ค่า SMTP แล้วตั้ง `MAIL_DRIVER=smtp`
ห้ามส่งไฟล์ `.env` ในแชทหรืออีเมล และห้ามขึ้น Git

### 4. ตั้งเว็บเซิร์ฟเวอร์ (ตัวอย่าง Nginx)

```nginx
# ไฟล์: /etc/nginx/sites-available/together-hub
server {
    listen 443 ssl http2;
    server_name hub.example.com;
    root /var/www/together-hub/public;
    index index.php;
    client_max_body_size 6m;              # ไฟล์แนบใบรับรองแพทย์ไม่เกิน 5 MB

    location / { try_files $uri /index.php$is_args$args; }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\. { deny all; }
    # ssl_certificate ... (Let's Encrypt)
}
```

Apache: ชี้ DocumentRoot ไปที่ `public/` แล้วเปิด `mod_rewrite` (มีไฟล์ `public/.htaccess` ให้แล้ว)
ตั้ง `upload_max_filesize = 6M` และ `post_max_size = 8M` ใน php.ini

### 5. งานตั้งเวลา (cron) ล้างรหัสเข้าระบบเก่า

ผู้ใช้ฐานข้อมูลของเว็บลบข้อมูลไม่ได้ จึงให้ผู้ดูแลตั้ง cron ด้วยผู้ใช้อื่น:

```bash
# ทุกคืนตี 3: ลบรหัสเข้าระบบที่หมดอายุเกิน 7 วัน
0 3 * * * mysql --defaults-extra-file=/root/.my.cnf together_hub -e "DELETE FROM login_tokens WHERE expires_at < NOW() - INTERVAL 7 DAY"
```

### 6. ทดสอบในเครื่อง (ไม่ต้องติดตั้งเว็บเซิร์ฟเวอร์)

```bash
cp .env.example .env    # ใส่ APP_KEY, DB_PASS / MAIL_DRIVER=log
php -S localhost:8000 -t public public/index.php
```

เปิด http://localhost:8000 → กรอกอีเมลตัวอย่าง (เช่น `staff1@example.com`)
อีเมลจะไม่ถูกส่งจริง แต่เก็บเป็นไฟล์ในโฟลเดอร์ `storage/mail/` เปิดดูรหัส 6 หลักได้จากไฟล์นั้น
ดูหน้าการลา: ตั้ง `TG_PREVIEW_MODULES=1` ใน `.env` (ห้ามเปิดบนระบบจริงจนกว่าจะพร้อม)

## โครงไฟล์

```
public/            ← เว็บเซิร์ฟเวอร์ชี้มาที่นี่ที่เดียว
  index.php        ทางเข้าเดียว + ตารางหน้า
  assets/          app.css (สร้างจาก tailwind/), app.js, ฟอนต์ Prompt
  mascots/ brand/ email/   รูปภาพ
src/
  bootstrap.php    โหลด .env, ตั้งค่าความปลอดภัย, session
  routes.php       การทำงานของแต่ละหน้า
  auth.php         เข้าสู่ระบบด้วยอีเมล (รหัส/ลิงก์ใช้ครั้งเดียว)
  permissions.php  กฎสิทธิ์ (แทน Row Level Security ของ Supabase)
  leave.php        การลา ลงเวลา ความยินยอม PDPA
  mailer.php       ส่งอีเมลผ่าน SMTP
  view.php helpers.php db.php sample-data.php
views/             หน้าเว็บ (HTML + PHP)
database/          schema.sql, seed.example.sql
storage/           session, ใบรับรองแพทย์, log (นอกเว็บ ห้ามเปิดจากภายนอก)
tailwind/          แหล่ง CSS — แก้แล้วรัน npm install && npm run build:css
```

เพิ่มโมดูลที่เปิดใช้แล้ว: ใส่ path ใน `LIVE_PATHS` (ไฟล์ `src/helpers.php`) ที่เดียว

## ความปลอดภัยและ PDPA (ที่ทำไว้แล้ว)

- ไม่มีรหัสผ่าน: เข้าระบบด้วยรหัส/ลิงก์ทางอีเมล ใช้ได้ครั้งเดียว หมดอายุตาม `LOGIN_CODE_TTL_MINUTES` (ค่าเริ่ม 15 นาที)
- ฐานข้อมูลเก็บแค่ค่า HMAC ของรหัส ไม่เก็บรหัสจริง / ใส่รหัสผิดเกิน 5 ครั้งต้องขอใหม่ / ขอซ้ำได้ทุก 60 วินาที / IP เดียวขอได้ไม่เกิน 10 ครั้งต่อชั่วโมง
- ตอบเหมือนกันทุกอีเมล ไม่บอกคนนอกว่าอีเมลไหนอยู่ในระบบ
- ลิงก์ในอีเมลต้องกดปุ่มยืนยันก่อน (กันระบบสแกนอีเมลใช้ลิงก์ไปก่อน)
- ทุกฟอร์มมีรหัส CSRF / ทุกคำสั่ง SQL ใช้ prepared statement / ทุกข้อความผ่าน `e()` กันสคริปต์แปลกปลอม
- คุกกี้ HttpOnly + SameSite / หัวข้อความ CSP, X-Frame-Options, HSTS
- ใบรับรองแพทย์เก็บนอกโฟลเดอร์เว็บ ตรวจชนิดไฟล์จากเนื้อไฟล์จริง เปิดได้เฉพาะเจ้าของและผู้ที่ดูแล
- พนักงานทั่วไปเห็นแค่ชื่อและตำแหน่งของผู้อนุมัติ ไม่เห็นข้อมูลอื่นของคนอื่น
- ปิดใช้งานพนักงาน (`active = 0`) → ออกจากระบบทันทีในการเปิดหน้าถัดไป

## เทียบกับรุ่น Supabase

| Supabase | รุ่น PHP |
|---|---|
| `auth.users` + `signInWithOtp` | ตาราง `login_tokens` + `src/auth.php` |
| Row Level Security, `can_manage()`, `my_approver()` | `src/permissions.php` (ตรวจในโค้ดทุกจุด) |
| trigger ออกเลขใบลา `L-2569-0001` | ตาราง `doc_counters` + `next_doc_number()` |
| view `leave_balances` | `leave_balances()` ใน `src/leave.php` |
| Storage bucket `leave-certs` | โฟลเดอร์ `storage/leave-certs/` |
| trigger `force_server_time` | ใช้ `NOW()` ของ server ตอนบันทึกเสมอ |
| uuid `gen_random_uuid()` | `uuid4()` ใน PHP |

⚠️ ข้อควรระวังที่ต่างจากเดิม: Supabase กันสิทธิ์ที่ฐานข้อมูลแม้โค้ดหน้าเว็บพลาด
แต่ MySQL ไม่มีกลไกนี้ **ทุกหน้าใหม่ต้องเรียก `require_me()` และฟังก์ชันใน `permissions.php` ก่อนอ่าน/แก้ข้อมูลของคนอื่นเสมอ**

## ขั้นถัดไปที่แนะนำ

1. หน้าอนุมัติใบลาสำหรับหัวหน้า + แจ้งเตือนทางอีเมล
2. หน้าผู้ดูแล (ฝ่ายสำนักงาน) จัดการรายชื่อพนักงาน สิทธิ์วันลา และวันหยุด
3. ย้ายทะเบียนลูกค้าและคู่ค้า และทัวร์แนะนำ
4. ทดสอบส่งอีเมลจริงผ่าน SMTP ของบริษัท และสำรองฐานข้อมูลอัตโนมัติทุกวัน
