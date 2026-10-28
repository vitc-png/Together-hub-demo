<?php /** หน้าที่เปิดจากลิงก์ในอีเมล — ต้องกดปุ่มก่อน ลิงก์จึงถูกใช้ (กันระบบสแกนอีเมลใช้ลิงก์ไปก่อน) — $t */ ?>
<main class="flex min-h-screen items-center justify-center bg-surface px-5 py-10">
  <div class="flex w-full max-w-[420px] flex-col items-center gap-6 border border-line bg-white px-6 py-10 text-center">
    <?php partial('brand_logo', ['class' => 'h-12 w-auto']) ?>
    <?= mascot('khwanKhao', 112, 'animate-pop') ?>
    <div class="flex flex-col gap-1">
      <h1 class="text-[24px] font-bold leading-snug">ยืนยันการเข้าสู่ระบบ</h1>
      <p class="text-[15px] text-gray-700">กดปุ่มด้านล่างเพื่อเข้าสู่ระบบบนเครื่องนี้ค่ะ</p>
    </div>
    <form action="/auth/confirm" method="post" class="w-full">
      <?= csrf_field() ?>
      <input type="hidden" name="t" value="<?= e($t) ?>">
      <button type="submit" data-busy-text="กำลังเข้าสู่ระบบ…" class="h-12 w-full rounded-xs bg-orange text-base font-bold text-ink hover:brightness-95 disabled:opacity-60">เข้าสู่ระบบ</button>
    </form>
    <a href="/login" class="text-sm font-bold underline underline-offset-4">ไม่ได้ขอเข้าระบบ? กลับหน้าเข้าสู่ระบบ</a>
  </div>
</main>
