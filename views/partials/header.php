<?php
/** แถบบน — $me, $current, $unread */
$nav = [
    ['หน้าแรก', '/', 'home'],
    ['หนังสือออก', '/letters', 'letters'],
    ['HR', '/hr/leave', 'hr'],
    ['ลูกค้าและคู่ค้า', '/registry', 'registry'],
    ['ผู้ช่วย AI', '/ai', 'ai'],
];
?>
<header class="border-b border-line bg-white">
  <div class="mx-auto flex max-w-[1200px] flex-wrap items-center gap-x-3 gap-y-3 px-4 py-3 md:gap-x-8 md:px-6">
    <a href="/" aria-label="Together Hub หน้าแรก" class="flex shrink-0 items-center no-underline">
      <?php partial('brand_logo', ['animate' => $current === 'home']) ?>
    </a>

    <!-- เมนูบนจอคอม (กว้าง 1024px ขึ้นไป) — จอเล็กกว่านั้นใช้แถบล่างแทน -->
    <nav aria-label="เมนูหลัก" class="hidden flex-1 flex-nowrap gap-x-5 whitespace-nowrap text-[15px] lg:flex">
      <?php foreach ($nav as [$label, $href, $id]):
          $on = $id === $current;
          echo module_link($href, e($label), 'py-2 no-underline ' . ($on ? 'border-b-[3px] border-orange font-bold' : 'hover:text-charcoal'), $on ? 'aria-current="page"' : '');
      endforeach; ?>
    </nav>

    <div class="ml-auto flex items-center gap-2 md:gap-3">
      <label class="hidden h-11 w-60 items-center gap-2 rounded-xs border border-line-strong bg-white px-3 xl:flex">
        <?= icon('search', 'size-[18px] text-gray-700') ?>
        <span class="sr-only">ค้นหา</span>
        <input type="search" placeholder="ค้นหนังสือ คน ลูกค้า…" class="min-w-0 flex-1 bg-transparent text-[15px] outline-none">
      </label>
      <button type="button" aria-label="การแจ้งเตือน <?= (int) $unread ?> รายการ" class="relative flex size-11 items-center justify-center rounded-xs border border-line-strong bg-white">
        <?= icon('bell') ?>
        <?php if ($unread > 0): ?>
          <span class="absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-status-rejected text-xs font-bold text-white"><?= (int) $unread ?></span>
        <?php endif ?>
      </button>
      <span title="<?= e($me['full_name'] . ' · ' . $me['department']) ?>" class="flex items-center gap-2.5">
        <span class="flex size-10 items-center justify-center rounded-full bg-charcoal text-[15px] font-bold text-white"><?= e($me['initials']) ?></span>
        <span class="sr-only"><?= e($me['full_name']) ?> <?= e($me['position'] . ' · ' . $me['department']) ?></span>
      </span>
      <form action="/auth/signout" method="post" class="hidden lg:block">
        <?= csrf_field() ?>
        <button type="submit" class="h-11 px-2 text-sm font-bold text-gray-700 underline underline-offset-4">ออกจากระบบ</button>
      </form>
    </div>
  </div>
</header>
