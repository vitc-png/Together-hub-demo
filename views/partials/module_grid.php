<?php
// ── ระบบของบริษัท ──────────────────────────────────────────────────────────────
// โมดูลที่ยังไม่เปิด: แสดงให้รู้ว่ามีอะไรกำลังมา แต่กดไม่ได้
// เปิดใช้เมื่อไร ย้ายออกจากรายการนี้ไปทำเป็นการ์ดลิงก์ (แบบการ์ดรายงานการประชุม)
$upcoming = [
    ['ระบบหนังสือออก', 'ร่างหนังสือด้วย AI ส่งอนุมัติตามผังองค์กร ออก PDF ลงนาม', 'doc', 'khamKhian', 'อ้ายคำเขียน — ร่างเป็นเรื่อง เลขเป็นระบบ ครบทุกหนังสือออก'],
    ['HR', 'ข้อมูลพนักงาน ลา ลงเวลา ประเมินผล และอบรม', 'people', 'khwanKhaoFull', null],
    ['ลูกค้าและคู่ค้า', 'ทะเบียนกลางหน่วยงานรัฐ เอกชน ผู้รับจ้าง ที่ปรึกษา', 'building', 'waoFull', null],
    ['ผู้ช่วย AI', 'ร่างข้อความ สรุปประชุม ถามระเบียบบริษัท', 'spark', 'khaoJi', null],
    ['คู่มือและแบบฟอร์ม', 'วิธีใช้ทุกระบบแบบทีละขั้น พร้อมรูปประกอบ', 'book', 'manual', null],
];
?>
<section id="modules" aria-labelledby="mod-h" class="flex flex-col gap-5">
  <div class="flex flex-col gap-1">
    <h2 id="mod-h" class="text-[28px] font-bold leading-snug">ระบบของบริษัท</h2>
    <p class="text-gray-700">เปิดใช้ได้แล้ว 1 ระบบ · ระบบอื่นทยอยเปิดเร็ว ๆ นี้</p>
  </div>

  <div class="grid grid-cols-[repeat(auto-fit,minmax(min(260px,100%),1fr))] gap-4">
    <!-- การ์ดใหญ่เต็มแถว: รายงานการประชุม + น้องคำแพง (โมดูลที่เปิดใช้แล้ว) -->
    <a href="/meetings" class="group col-span-full flex flex-wrap items-end gap-x-8 gap-y-4 overflow-hidden bg-charcoal bg-[linear-gradient(rgba(255,255,255,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.06)_1px,transparent_1px)] bg-[size:32px_32px] px-8 pt-8 text-white no-underline transition-transform hover:-translate-y-0.5">
      <?= mascot('khamPhaeng', 150, '-mb-2 w-[120px] shrink-0 origin-bottom group-hover:animate-wiggle sm:w-[130px]') ?>
      <span class="flex min-w-0 flex-[1_1_320px] flex-col gap-2 pb-8">
        <span class="flex flex-wrap items-center gap-2">
          <span class="w-fit rounded-xs bg-orange px-2.5 py-0.5 text-[13px] font-bold text-ink">✦ ฟีเจอร์ใหม่</span>
          <span class="w-fit rounded-xs border border-white/60 px-2.5 py-0.5 text-[13px] font-bold">เปิดใช้แล้ว</span>
        </span>
        <span class="text-[44px] font-bold leading-none text-orange md:text-[56px]">น้องคำแพง</span>
        <span class="text-[20px] font-bold leading-snug md:text-[22px]">เลขาคู่ใจ จดครบทุกมติ ติดตามทุกงาน</span>
        <span class="mt-2 text-[13px] font-bold tracking-[0.12em] text-white/70">โมดูล: รายงานการประชุม</span>
        <span class="max-w-[34em]">บันทึกวาระ มติ และงานที่ต้องติดตาม ออกรายงาน PDF แบบหนังสือราชการ ประธานรับรองในระบบ</span>
        <span class="mt-1 text-[15px] font-bold text-orange">เปิดรายงานการประชุม →</span>
      </span>
    </a>

    <!-- โมดูลที่กำลังพัฒนา: ไม่ใช่ลิงก์ (กดไม่ได้) และบอกโปรแกรมอ่านหน้าจอว่ายังใช้ไม่ได้ -->
    <?php foreach ($upcoming as [$title, $detail, $ic, $mid, $tagline]): ?>
      <div role="group" aria-label="<?= e($title) ?> — กำลังพัฒนา ยังใช้งานไม่ได้" class="relative flex cursor-not-allowed flex-col justify-between gap-5 overflow-hidden border border-dashed border-line-strong bg-white p-6 sm:min-h-56 sm:p-7">
        <span class="flex items-start justify-between gap-3">
          <?= icon($ic, 'size-9 text-gray-700') ?>
          <span class="inline-flex items-center gap-1.5 rounded-xs border border-dashed border-line-strong bg-surface px-2.5 py-1 text-[13px] font-bold text-gray-700">
            <?= icon('wrench', 'size-4') ?> กำลังพัฒนา
          </span>
        </span>
        <span class="flex flex-col gap-1.5 pr-16">
          <span class="text-xl font-bold leading-snug text-charcoal"><?= e($title) ?></span>
          <?php if ($tagline): ?><span class="text-[15px] font-bold text-charcoal"><?= e($tagline) ?></span><?php endif ?>
          <span class="text-[15px] text-gray-700"><?= e($detail) ?></span>
        </span>
        <?= mascot($mid, 80, 'pointer-events-none absolute -bottom-2 right-2 w-[68px] opacity-50 grayscale') ?>
      </div>
    <?php endforeach ?>
  </div>
</section>
