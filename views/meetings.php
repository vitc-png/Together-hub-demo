<?php
/** ประตูเข้าโมดูลรายงานการประชุม — น้องคำแพงต้อนรับ แล้วพาไปที่โมดูลจริง ($appUrl จาก MEETINGS_APP_URL) */
$features = [
    ['จดครบทุกมติ', 'บันทึกวาระ ผู้เข้าประชุม และมติของแต่ละวาระ'],
    ['ติดตามทุกงาน', 'งานที่ได้รับมอบหมาย ผู้รับผิดชอบ และกำหนดเสร็จ'],
    ['รายงานแบบราชการ', 'ออก PDF ขนาด A4 มีปก รหัสเอกสาร ประธานรับรองในระบบ'],
];
?>
<div class="flex flex-col gap-8">
  <nav aria-label="ตำแหน่งหน้า" class="text-sm text-gray-700">
    <a href="/">หน้าแรก</a> <span aria-hidden="true">›</span> <span class="font-bold text-ink">รายงานการประชุม</span>
  </nav>

  <section class="flex flex-wrap items-end gap-x-10 gap-y-6 overflow-hidden border border-line bg-white px-8 pt-8">
    <?= mascot('khamPhaeng', 220, 'w-[150px] shrink-0 animate-pop sm:w-[220px]') ?>
    <div class="flex min-w-0 flex-[1_1_360px] flex-col gap-3 pb-8">
      <p class="text-[15px] font-bold text-gray-700">สวัสดีค่ะ หนูชื่อคำแพง</p>
      <h1 class="text-[32px] font-bold leading-tight">รายงานการประชุม</h1>
      <p class="text-lg font-bold text-charcoal">เลขาคู่ใจ จดครบทุกมติ ติดตามทุกงาน</p>
      <?php if ($appUrl && preg_match('#^https?://#', $appUrl)): ?>
        <a href="<?= e($appUrl) ?>" class="mt-2 flex h-12 w-fit items-center rounded-xs bg-orange px-6 font-bold text-ink no-underline hover:brightness-95">เปิดระบบรายงานการประชุม →</a>
      <?php else: ?>
        <p class="mt-2 border border-dashed border-line-strong bg-surface px-4 py-3 text-[15px] text-charcoal">
          ยังไม่ได้เชื่อมที่อยู่ของระบบรายงานการประชุมค่ะ — ผู้ดูแลระบบตั้งค่า
          <code class="mx-1 rounded-xs bg-white px-1.5 py-0.5 text-sm">MEETINGS_APP_URL</code> ในไฟล์ .env แล้วปุ่มเข้าใช้งานจะขึ้นตรงนี้
        </p>
      <?php endif ?>
    </div>
  </section>

  <ul class="grid grid-cols-[repeat(auto-fit,minmax(min(260px,100%),1fr))] gap-4">
    <?php foreach ($features as [$ft, $fx]): ?>
      <li class="flex flex-col gap-1.5 border border-line bg-white p-6">
        <span class="text-lg font-bold"><?= e($ft) ?></span>
        <span class="text-[15px] text-gray-700"><?= e($fx) ?></span>
      </li>
    <?php endforeach ?>
  </ul>
</div>
