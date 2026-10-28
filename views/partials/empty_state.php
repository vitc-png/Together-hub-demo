<?php
/**
 * หน้าว่าง (ยังไม่มีข้อมูล) — มาสคอตบอกว่าตอนนี้เป็นอย่างไร และพาไปทำขั้นต่อไป
 * $title, $message, $actions = [[label, href], ...] (ปุ่มแรก = ปุ่มหลัก), $mascotId, $compact
 */
$mascotId ??= 'khwanKhao';
$compact ??= false;
$actions ??= [];
?>
<div class="flex flex-wrap items-center gap-x-6 gap-y-3 border border-line bg-white <?= $compact ? 'px-5 py-5' : 'px-6 py-8 md:px-10' ?>">
  <?= mascot($mascotId, $compact ? 72 : 112, 'shrink-0') ?>
  <div class="flex min-w-0 flex-[1_1_280px] flex-col gap-1.5">
    <p class="font-bold <?= $compact ? 'text-lg' : 'text-xl' ?>"><?= e($title) ?></p>
    <p class="text-[15px] text-gray-700"><?= e($message) ?></p>
    <?php if ($actions): ?>
      <div class="mt-2 flex flex-wrap gap-2">
        <?php foreach ($actions as $i => [$label, $href]):
            echo module_link($href, e($label), 'flex h-11 items-center rounded-xs px-5 text-[15px] font-bold no-underline ' . ($i === 0 ? 'bg-charcoal text-white hover:bg-ink' : 'border border-line-strong bg-white'));
        endforeach; ?>
      </div>
    <?php endif ?>
  </div>
</div>
