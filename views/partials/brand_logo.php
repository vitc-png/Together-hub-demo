<?php
/**
 * โลโก้หลัก Together Hub
 * $animate = เล่น "ไฟฟ้าวิ่งระหว่างเขาน้อง" ครั้งเดียวต่อการเปิดเว็บ (หน้าแรก)
 * $zap = เล่นซ้ำทุกครั้งที่กดส่งฟอร์มเข้าสู่ระบบ
 * สคริปต์อยู่ใน public/assets/app.js (ค้นคำว่า brand-logo)
 */
$class ??= 'h-10 w-auto lg:h-12';
?>
<span class="relative inline-block w-fit self-start" data-brand-logo<?= !empty($animate) ? ' data-animate' : '' ?><?= !empty($zap) ? ' data-zap' : '' ?>>
  <img src="/brand/together-hub-logo-160.webp" width="591" height="160" alt="Together Hub" class="select-none <?= e($class) ?>" draggable="false">
  <svg aria-hidden="true" viewBox="0 0 1645 445" class="pointer-events-none absolute inset-0 hidden h-full w-full overflow-visible" fill="none">
    <defs>
      <filter id="tg-zap-blur" x="-20%" y="-50%" width="140%" height="200%"><feGaussianBlur stdDeviation="9"/></filter>
    </defs>
    <circle class="tg-horn-l" cx="1318" cy="78" r="62" fill="#ff7e1c" filter="url(#tg-zap-blur)"/>
    <circle class="tg-horn-r" cx="1577" cy="78" r="62" fill="#ff7e1c" filter="url(#tg-zap-blur)"/>
    <?php $bolt = 'M1368,118 C1335,118 1300,114 1293,90 C1287,66 1318,48 1354,40 L1380,24 L1402,42 L1428,16 L1450,38 L1474,14 L1497,36 L1518,22 L1541,40 C1577,48 1608,66 1602,90 C1595,114 1560,118 1527,118'; ?>
    <path class="tg-zap" d="<?= $bolt ?>" pathLength="100" stroke="#ff7e1c" stroke-width="34" stroke-linecap="round" stroke-linejoin="round" filter="url(#tg-zap-blur)"/>
    <path class="tg-zap" d="<?= $bolt ?>" pathLength="100" stroke="#ff9a3d" stroke-width="13" stroke-linecap="round" stroke-linejoin="round"/>
    <path class="tg-zap" d="<?= $bolt ?>" pathLength="100" stroke="#fff4d6" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
</span>
