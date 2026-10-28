<?php
/**
 * โครงหน้ามาตรฐานทุกหน้า: แถบบน + เนื้อหา + ท้ายหน้า + PDPA + เมนูมือถือ
 * ตัวแปร: $me, $section, $title, $content
 */
partial('head', ['title' => $title ?? '']);
?>
<body>
<div class="min-h-screen">
  <?php partial('header', ['me' => $me, 'current' => $section ?? 'home', 'unread' => 4]) ?>
  <main class="mx-auto flex max-w-[1200px] flex-col gap-12 px-4 pb-12 pt-6 md:px-6 md:pt-8">
    <?= $content ?>
  </main>
  <?php partial('footer') ?>
  <?php partial('consent') ?>
  <?php partial('tabbar', ['current' => $section ?? 'home']) ?>
</div>
</body>
</html>
