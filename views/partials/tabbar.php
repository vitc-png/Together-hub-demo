<?php
/** แถบเมนูล่างสำหรับมือถือและแท็บเล็ต (ซ่อนบนจอกว้าง 1024px ขึ้นไป) — $current */
$tabs = [
    ['หน้าแรก', '/', 'home', 'home'],
    ['หนังสือ', '/letters', 'doc', 'letters'],
    ['HR', '/hr/leave', 'people', 'hr'],
    ['ลูกค้า', '/registry', 'building', 'registry'],
    ['เพิ่มเติม', '/more', 'menu', 'more'],
];
?>
<nav aria-label="เมนูหลัก" class="fixed inset-x-0 bottom-0 z-50 grid h-[72px] grid-cols-5 border-t border-line bg-white lg:hidden">
  <?php foreach ($tabs as [$label, $href, $ic, $id]):
      $on = $id === $current;
      echo module_link(
          $href,
          icon($ic, 'size-[22px]') . e($label),
          'flex flex-col items-center justify-center gap-0.5 text-xs no-underline ' . ($on ? 'border-t-[3px] border-orange font-bold text-ink' : 'text-gray-700'),
          $on ? 'aria-current="page"' : ''
      );
  endforeach; ?>
</nav>
