<?php /** อีเมลเข้าสู่ระบบ — $name, $url, $code, $ttl (ดัดแปลงจากแม่แบบ Supabase เดิม) */ ?>
<!DOCTYPE html>
<html lang="th" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<meta name="x-apple-disable-message-reformatting">
<title>ลิงก์เข้าสู่ระบบ Together Hub</title>
<style>
  /* มือถือ: ขยายเต็มจอ ปุ่มกว้างเต็ม */
  @media only screen and (max-width: 620px) {
    .tg-card { width: 100% !important; }
    .tg-pad { padding-left: 22px !important; padding-right: 22px !important; }
    .tg-btn { width: 100% !important; }
    .tg-btn a { display: block !important; padding-left: 12px !important; padding-right: 12px !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background:#f6f5f3;-webkit-text-size-adjust:100%;">
<!-- ข้อความตัวอย่างที่เห็นในกล่องจดหมาย (ซ่อนในเนื้ออีเมล) -->
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#f6f5f3;">กดปุ่มเพื่อเข้าสู่ระบบ Together Hub — ลิงก์ใช้ได้ครั้งเดียว&#8199;&#65279;&#847;&#8199;&#65279;&#847;&#8199;&#65279;&#847;</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f6f5f3;">
<tr><td align="center" style="padding:28px 12px;">

  <table role="presentation" class="tg-card" width="560" cellpadding="0" cellspacing="0" border="0" style="width:560px;max-width:560px;background:#ffffff;border:1px solid #e2e0dc;">
    <!-- แถบส้มแบรนด์ -->
    <tr><td style="height:8px;line-height:8px;font-size:0;background:#ff7e1c;">&nbsp;</td></tr>

    <!-- โลโก้ -->
    <tr><td class="tg-pad" align="left" style="padding:28px 40px 8px 40px;">
      <img src="<?= e(app_url('')) ?>/email/together-hub-logo.png" width="240" height="65" alt="Together Hub" style="display:block;width:240px;height:65px;border:0;outline:none;">
    </td></tr>

    <!-- มาสคอต + หัวข้อ -->
    <tr><td class="tg-pad" align="center" style="padding:20px 40px 0 40px;">
      <img src="<?= e(app_url('')) ?>/email/khwan-khao-envelope.png" width="182" height="156" alt="น้องขวัญข้าวถือซองจดหมาย" style="display:block;width:182px;height:156px;border:0;">
    </td></tr>
    <tr><td class="tg-pad" align="center" style="padding:16px 40px 0 40px;font-family:'Prompt','Sarabun',Tahoma,'Leelawadee UI',Arial,sans-serif;font-size:26px;line-height:36px;font-weight:700;color:#040606;">
      ลิงก์เข้าสู่ระบบมาแล้วค่ะ
    </td></tr>
    <tr><td class="tg-pad" align="center" style="padding:8px 40px 0 40px;font-family:'Prompt','Sarabun',Tahoma,'Leelawadee UI',Arial,sans-serif;font-size:16px;line-height:26px;color:#313131;">
      สวัสดีค่ะ คุณ<?= e($name) ?> น้องขวัญข้าวเอง<br>กดปุ่มด้านล่างเพื่อเข้าสู่ระบบ <b>Together Hub</b> ได้เลยค่ะ
    </td></tr>

    <!-- ปุ่มหลัก: ตัวดำบนส้ม (กฎแบรนด์) -->
    <tr><td class="tg-pad" align="center" style="padding:28px 40px 8px 40px;">
      <table role="presentation" class="tg-btn" cellpadding="0" cellspacing="0" border="0">
        <tr><td align="center" bgcolor="#ff7e1c" style="background:#ff7e1c;border-radius:4px;">
          <a href="<?= e($url) ?>" target="_blank" style="display:inline-block;padding:16px 44px;font-family:'Prompt','Sarabun',Tahoma,'Leelawadee UI',Arial,sans-serif;font-size:18px;line-height:24px;font-weight:700;color:#040606;text-decoration:none;border-radius:4px;">เข้าสู่ระบบ</a>
        </td></tr>
      </table>
    </td></tr>

    <!-- รหัส 6 หลัก: ใช้เมื่อขอเข้าระบบบนคอม แต่เปิดอีเมลบนมือถือ -->
    <tr><td class="tg-pad" align="center" style="padding:20px 40px 0 40px;font-family:'Prompt','Sarabun',Tahoma,'Leelawadee UI',Arial,sans-serif;font-size:15px;line-height:24px;color:#313131;">
      ขอเข้าระบบบนเครื่องอื่น? <b style="color:#040606;">พิมพ์รหัสนี้</b> ในหน้าจอที่รออยู่แทนการกดปุ่ม
    </td></tr>
    <tr><td class="tg-pad" align="center" style="padding:10px 40px 0 40px;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0">
        <tr><td align="center" style="padding:12px 28px;border:2px dashed #8f8a83;border-radius:4px;background:#ffffff;font-family:'Courier New',Courier,monospace;font-size:34px;line-height:40px;font-weight:700;letter-spacing:10px;color:#040606;"><?= e($code) ?></td></tr>
      </table>
    </td></tr>

    <!-- สิ่งที่ควรรู้ -->
    <tr><td class="tg-pad" style="padding:20px 40px 0 40px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f6f5f3;">
        <tr><td style="padding:16px 20px 8px 20px;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td valign="top" style="padding:0 10px 8px 0;font-family:'Prompt','Sarabun',Tahoma,'Leelawadee UI',Arial,sans-serif;font-size:15px;line-height:24px;color:#040606;font-weight:700;">1.</td><td style="padding:0 0 8px 0;font-family:'Prompt','Sarabun',Tahoma,'Leelawadee UI',Arial,sans-serif;font-size:15px;line-height:24px;color:#313131;">ปุ่มและรหัส ใช้ได้ <b>อย่างใดอย่างหนึ่ง ครั้งเดียว</b> และมีอายุ <b><?= (int) $ttl ?> นาที</b></td></tr><tr><td valign="top" style="padding:0 10px 8px 0;font-family:'Prompt','Sarabun',Tahoma,'Leelawadee UI',Arial,sans-serif;font-size:15px;line-height:24px;color:#040606;font-weight:700;">2.</td><td style="padding:0 0 8px 0;font-family:'Prompt','Sarabun',Tahoma,'Leelawadee UI',Arial,sans-serif;font-size:15px;line-height:24px;color:#313131;">กดบน <b>เครื่องที่ต้องการใช้งาน</b> — กดบนมือถือ จะเข้าระบบบนมือถือ</td></tr><tr><td valign="top" style="padding:0 10px 8px 0;font-family:'Prompt','Sarabun',Tahoma,'Leelawadee UI',Arial,sans-serif;font-size:15px;line-height:24px;color:#040606;font-weight:700;">3.</td><td style="padding:0 0 8px 0;font-family:'Prompt','Sarabun',Tahoma,'Leelawadee UI',Arial,sans-serif;font-size:15px;line-height:24px;color:#313131;">ลิงก์หมดอายุ? ขอใหม่ได้ที่หน้าเข้าสู่ระบบ</td></tr></table>
        </td></tr>
      </table>
    </td></tr>

    <!-- ลิงก์สำรอง -->
    <tr><td class="tg-pad" style="padding:20px 40px 0 40px;font-family:'Prompt','Sarabun',Tahoma,'Leelawadee UI',Arial,sans-serif;font-size:13px;line-height:20px;color:#5c5c5c;">
      ปุ่มกดไม่ได้? คัดลอกลิงก์นี้ไปวางในเบราว์เซอร์:<br>
      <a href="<?= e($url) ?>" target="_blank" style="color:#1d5fa8;word-break:break-all;"><?= e($url) ?></a>
    </td></tr>

    <!-- ความปลอดภัย -->
    <tr><td class="tg-pad" style="padding:20px 40px 28px 40px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #e2e0dc;">
        <tr><td style="padding-top:16px;font-family:'Prompt','Sarabun',Tahoma,'Leelawadee UI',Arial,sans-serif;font-size:13px;line-height:20px;color:#5c5c5c;">
          ไม่ได้ขอเข้าสู่ระบบ? เพิกเฉยอีเมลนี้ได้เลย บัญชีของคุณยังปลอดภัยค่ะ<br>
          <b style="color:#313131;">อย่าส่งต่ออีเมลนี้ หรือบอกรหัสให้ใคร</b> — ใครได้ไปเข้าระบบในชื่อคุณได้ ทีมงานจะไม่ขอลิงก์หรือรหัสจากคุณเด็ดขาดค่ะ
        </td></tr>
      </table>
    </td></tr>
  </table>

  <!-- ท้ายอีเมล -->
  <table role="presentation" class="tg-card" width="560" cellpadding="0" cellspacing="0" border="0" style="width:560px;max-width:560px;">
    <tr><td align="center" style="padding:16px 12px;font-family:'Prompt','Sarabun',Tahoma,'Leelawadee UI',Arial,sans-serif;font-size:12px;line-height:18px;color:#5c5c5c;">
      อีเมลอัตโนมัติจาก Together Hub · ระบบภายใน บจก.ทูเก็ตเตอร์ แอสโซซิเอทส์<br>กรุณาอย่าตอบกลับอีเมลนี้ · สงสัยติดต่อฝ่ายสำนักงาน
    </td></tr>
  </table>

</td></tr>
</table>
</body>
</html>
