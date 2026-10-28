<?php
/**
 * หน้าเข้าสู่ระบบ: จอแบ่ง 2 ฝั่ง — ซ้ายบล็อกส้มต้อนรับ ขวาช่องกรอก
 * $next, $error, $sent (['email','at'] หลังส่งแล้ว), $codeError, $formError, $mailLog
 */
$input = 'h-12 w-full rounded-xs border border-line-strong bg-white px-3 text-base outline-none focus:border-ink focus:ring-2 focus:ring-orange';
$greeter = function (string $id, string $text, bool $delay = false): string {
    return '<div class="flex flex-col items-center gap-2 animate-pop' . ($delay ? ' [animation-delay:250ms]' : '') . '">'
        . '<span class="hidden rounded-xs bg-white px-3 py-1 text-base font-bold text-ink shadow-sm lg:inline">' . e($text) . '</span>'
        . mascot($id, 150, 'h-auto w-[60px] lg:w-[150px]') . '</div>';
};
?>
<main class="grid min-h-screen grid-cols-[minmax(0,1fr)] grid-rows-[auto_1fr] bg-white lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:grid-rows-1">
  <section aria-label="ยินดีต้อนรับ" class="relative flex flex-row items-center justify-between gap-4 overflow-hidden bg-orange bg-[linear-gradient(rgba(4,6,6,0.07)_1px,transparent_1px),linear-gradient(90deg,rgba(4,6,6,0.07)_1px,transparent_1px)] bg-[size:40px_40px] px-5 py-4 text-ink lg:flex-col lg:items-stretch lg:gap-6 lg:px-14 lg:py-14">
    <p class="hidden text-[13px] font-bold tracking-[0.14em] lg:block">TOGETHER HUB · ระบบภายในสำหรับพนักงาน</p>
    <div class="flex min-w-0 flex-col gap-3">
      <h2 class="text-[22px] font-bold leading-tight lg:text-[56px]">We are<br class="hidden lg:block"> Together as one</h2>
      <p class="hidden max-w-[26em] text-lg font-bold lg:block">ลงเวลา ขอลา ส่งหนังสือ ติดต่อลูกค้า — ทุกงานของบริษัทอยู่ในที่เดียว</p>
    </div>
    <!-- น้องขวัญข้าว (HR) + น้องเว้า (ลูกค้า) ยืนต้อนรับ -->
    <div aria-hidden="true" class="flex shrink-0 items-end gap-1 lg:gap-8">
      <?= $greeter('khwanKhao', 'สวัสดีค่ะ') ?>
      <?= $greeter('wao', 'สบายดีบ่ครับ', true) ?>
    </div>
  </section>

  <section class="flex items-start justify-center px-5 py-8 lg:items-center lg:px-10 lg:py-10">
    <div class="flex w-full max-w-[420px] flex-col gap-7">
      <?php partial('brand_logo', ['zap' => true, 'class' => 'h-14 w-auto lg:h-16']) ?>
      <div class="relative pl-5 pt-5">
        <?= corner_bracket('sm') ?>
        <h1 class="text-[28px] font-bold leading-snug">เข้าสู่ระบบ</h1>
        <p class="text-[15px] text-gray-700">ไม่ต้องจำรหัสผ่าน ระบบส่งลิงก์และรหัสไปที่อีเมลค่ะ</p>
      </div>

      <?php if ($error): ?>
        <p class="bg-status-rejected-bg px-4 py-3 text-sm text-status-rejected">
          <?= $error === 'expired'
              ? 'ลิงก์หมดอายุหรือถูกใช้ไปแล้ว ขอลิงก์ใหม่ได้เลยค่ะ'
              : 'บัญชีนี้ยังไม่อยู่ในรายชื่อพนักงาน หรือถูกปิดใช้งาน ติดต่อฝ่ายสำนักงานค่ะ' ?>
        </p>
      <?php endif ?>

      <?php if (!$sent): ?>
        <!-- ขั้นที่ 1: กรอกอีเมล -->
        <form action="/login" method="post" data-zap-on-submit class="flex flex-col gap-4">
          <?= csrf_field() ?>
          <input type="hidden" name="next" value="<?= e($next) ?>">
          <label class="flex flex-col gap-1.5">
            <span class="font-bold">อีเมลที่ให้ไว้กับฝ่าย HR</span>
            <input type="email" name="email" required autocomplete="email" inputmode="email" maxlength="190"
                   value="<?= e($formError['email'] ?? '') ?>" placeholder="name@example.com"
                   <?= $formError ? 'aria-invalid="true" aria-describedby="login-err"' : '' ?> class="<?= $input ?>">
          </label>
          <?php if ($formError): ?><p id="login-err" class="text-sm font-bold text-status-rejected"><?= e($formError['msg']) ?></p><?php endif ?>
          <button type="submit" data-busy-text="กำลังส่ง…" class="h-12 rounded-xs bg-orange text-base font-bold text-ink hover:brightness-95 disabled:opacity-60">ส่งลิงก์เข้าสู่ระบบ</button>
        </form>
      <?php else:
          $left = max(0, RESEND_SECONDS - (time() - (int) $sent['at'])); ?>
        <!-- ขั้นที่ 2: เช็กอีเมล — น้องขวัญข้าวถือซองจดหมาย -->
        <div class="flex flex-col gap-5">
          <div role="status" class="flex items-center gap-4">
            <span class="relative mr-4 shrink-0 animate-pop">
              <?= mascot('khwanKhao', 104) ?>
              <svg viewBox="0 0 64 46" aria-hidden="true" class="absolute -bottom-1 -right-4 w-14 rotate-[-8deg] animate-float">
                <rect x="2" y="2" width="60" height="42" rx="4" fill="#ffffff" stroke="#040606" stroke-width="3"/>
                <path d="M3 5 L32 27 L61 5" fill="#ff7e1c" stroke="#040606" stroke-width="3" stroke-linejoin="round"/>
                <path d="M3 43 L24 22 M61 43 L40 22" stroke="#040606" stroke-width="2.5" stroke-linecap="round"/>
              </svg>
            </span>
            <div class="flex flex-col gap-1">
              <p class="text-xl font-bold leading-snug">เช็กอีเมลได้เลยค่ะ</p>
              <p class="text-[15px] text-charcoal">ถ้า <b class="break-all"><?= e($sent['email']) ?></b> อยู่ในรายชื่อพนักงาน จะได้อีเมลภายใน 1–2 นาทีค่ะ</p>
            </div>
          </div>

          <ol class="flex flex-col gap-2 bg-surface px-5 py-4 text-[15px]">
            <li><b>1.</b> เปิดกล่องอีเมล หาอีเมลจาก Together Hub</li>
            <li><b>2.</b> กดปุ่ม <b>“เข้าสู่ระบบ”</b> ในอีเมล <b>หรือ</b> พิมพ์รหัส <?= OTP_LENGTH ?> หลักด้านล่าง</li>
            <li><b>3.</b> ไม่เห็นอีเมล? ดูในกล่อง <b>จดหมายขยะ / โปรโมชัน</b></li>
          </ol>

          <!-- ช่องพิมพ์รหัส: วางทั้งชุดได้ มือถือขึ้นแป้นตัวเลข พิมพ์ครบแล้วส่งเอง -->
          <form action="/login/verify" method="post" data-otp-form data-zap-on-submit class="flex flex-col gap-2 border border-line p-4">
            <?= csrf_field() ?>
            <input type="hidden" name="email" value="<?= e($sent['email']) ?>">
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <label for="otp" class="font-bold">เปิดอีเมลบนเครื่องอื่น? พิมพ์รหัส <?= OTP_LENGTH ?> หลักจากอีเมลตรงนี้</label>
            <div class="flex gap-2">
              <input id="otp" name="token" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{<?= OTP_LENGTH ?>}"
                     maxlength="<?= OTP_LENGTH ?>" placeholder="<?= str_repeat('•', OTP_LENGTH) ?>" required autofocus
                     <?= $codeError ? 'aria-invalid="true" aria-describedby="otp-err"' : '' ?>
                     class="h-14 w-0 min-w-0 flex-1 rounded-xs border border-line-strong bg-white px-3 text-center font-mono text-[28px] tracking-[0.4em] outline-none focus:border-ink focus:ring-2 focus:ring-orange">
              <button type="submit" data-busy-text="กำลังตรวจ…" class="h-14 shrink-0 rounded-xs bg-charcoal px-5 text-base font-bold text-white enabled:hover:bg-ink disabled:opacity-50">เข้าสู่ระบบ</button>
            </div>
            <?php if ($codeError): ?><p id="otp-err" role="alert" class="text-sm font-bold text-status-rejected"><?= e($codeError) ?></p><?php endif ?>
          </form>

          <?php if ($mailLog): ?>
            <p class="border border-line-strong px-3 py-2 text-sm text-gray-700">
              โหมดทดสอบ: ยังไม่ได้ตั้งค่า SMTP ระบบจึงไม่ได้ส่งอีเมลจริง — ผู้ดูแลดูอีเมลได้ที่โฟลเดอร์ <code>storage/mail/</code>
            </p>
          <?php endif ?>

          <!-- ส่งใหม่: นับถอยหลังก่อน (กันกดรัว) -->
          <form action="/login" method="post" data-zap-on-submit class="flex flex-col gap-2" data-resend data-left="<?= $left ?>" data-total="<?= RESEND_SECONDS ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="email" value="<?= e($sent['email']) ?>">
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <button type="submit" <?= $left > 0 ? 'disabled' : '' ?> data-busy-text="กำลังส่ง…" class="h-12 rounded-xs border border-line-strong bg-white text-base font-bold enabled:hover:bg-surface disabled:text-gray-700">
              <?= $left > 0 ? "ส่งลิงก์อีกครั้งได้ใน {$left} วินาที" : 'ส่งลิงก์อีกครั้ง' ?>
            </button>
            <div aria-hidden="true" class="h-1 w-full overflow-hidden rounded-full bg-line">
              <div data-bar class="h-full bg-orange transition-[width] duration-1000 ease-linear" style="width: <?= round($left / RESEND_SECONDS * 100) ?>%"></div>
            </div>
          </form>

          <a href="/login?<?= e(http_build_query(['next' => $next, 'change' => 1])) ?>" class="flex h-11 w-fit items-center text-[15px] font-bold underline underline-offset-4">ใช้อีเมลอื่น</a>
        </div>
      <?php endif ?>

      <p class="border-t border-line pt-4 text-sm text-gray-700">บจก.ทูเก็ตเตอร์ แอสโซซิเอทส์ · เข้าไม่ได้ ติดต่อฝ่ายสำนักงาน</p>
    </div>
  </section>
</main>
