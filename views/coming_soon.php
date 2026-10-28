<?php /** หน้า "กำลังพัฒนา" สำหรับโมดูลที่ยังไม่เปิด — $moduleName, $detail, $mascotId */ ?>
<div class="flex flex-col gap-8">
  <nav aria-label="ตำแหน่งหน้า" class="text-sm text-gray-700">
    <a href="/">หน้าแรก</a> <span aria-hidden="true">›</span> <span class="font-bold text-ink"><?= e($moduleName) ?></span>
  </nav>
  <h1 class="text-[28px] font-bold leading-snug"><?= e($moduleName) ?></h1>
  <?php partial('empty_state', [
      'mascotId' => $mascotId,
      'title' => empty($notFound) ? 'ระบบนี้กำลังพัฒนาอยู่' : 'ไม่พบหน้าที่ต้องการค่ะ',
      'message' => empty($notFound) ? $detail . ' ตอนนี้ยังใช้งานไม่ได้ เมื่อเปิดใช้แล้วจะแจ้งในหน้าแรก' : $detail,
      'actions' => [['กลับหน้าแรก', '/']],
  ]) ?>
</div>
