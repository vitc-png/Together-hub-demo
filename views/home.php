<?php
/** หน้าแรก Together Hub — ตัวแปรจาก home() ใน src/routes.php */
$firstName = explode(' ', $me['full_name'])[0];
$liveActions = array_values(array_filter($quickActions, fn ($q) => is_live_href($q['href'])));
$toneCls = ['pending' => 'text-status-pending font-bold', 'revise' => 'text-status-revise font-bold', 'muted' => 'text-gray-700'];
?>
<!-- ── ต้อนรับ ─────────────────────────────────────────── -->
<section aria-labelledby="hello" class="flex flex-wrap border border-line bg-white">
  <div class="flex min-w-0 flex-[999_1_520px] flex-col justify-center gap-5 px-6 py-10 md:px-12 md:py-14">
    <div class="relative pl-6 pt-6">
      <?= corner_bracket() ?>
      <p class="text-[13px] font-bold tracking-[0.12em] text-gray-700"><?= e(thai_date_long()) ?></p>
      <h1 id="hello" class="mt-1 text-[28px] font-bold leading-snug md:text-[40px]">สวัสดี คุณ<?= e($firstName) ?></h1>
    </div>
    <p class="max-w-[34em] text-lg text-charcoal">
      <?php if ($waitingForMe + $myPending === 0): ?>
        วันนี้ไม่มีงานค้าง — ทุกอย่างของบริษัทอยู่ในที่เดียว
      <?php else: ?>
        มีงานรอคุณ <b><?= (int) $waitingForMe ?> เรื่อง</b> และหนังสือของคุณกำลังรออนุมัติ <b><?= (int) $myPending ?> ฉบับ</b> —
        ทุกอย่างของบริษัทอยู่ในที่เดียว
      <?php endif ?>
    </p>
    <div class="mt-2 flex flex-wrap gap-3">
      <?php partial('checkin', ['time' => $checkInTime]) ?>
      <a href="#tasks" class="flex h-12 items-center rounded-xs border border-line-strong bg-white px-6 font-bold no-underline">ดูงานรอฉัน</a>
    </div>
  </div>

  <?php if ($liveActions): ?>
    <!-- บล็อกส้ม 1 บล็อกต่อหน้า (กฎแบรนด์) — ตัวอักษรบนส้มเป็นสี ink เสมอ / แสดงเฉพาะโมดูลที่เปิดแล้ว -->
    <div class="flex min-w-0 flex-[1_1_320px] flex-col bg-orange px-6 pb-2 pt-6 text-ink md:px-8">
      <h2 class="text-[13px] font-bold tracking-[0.12em]">ทำได้ทันที</h2>
      <ul class="mt-3 flex flex-col">
        <?php foreach ($liveActions as $q): ?>
          <li class="border-t-2 border-ink last:border-b-2">
            <a href="<?= e($q['href']) ?>" class="group flex min-h-[64px] items-center justify-between gap-4 py-3 text-ink no-underline">
              <span class="text-[22px] font-bold leading-snug group-hover:underline md:text-[26px]"><?= e($q['label']) ?></span>
              <span aria-hidden="true" class="shrink-0 text-lg font-bold">→</span>
            </a>
          </li>
        <?php endforeach ?>
      </ul>
    </div>
  <?php endif ?>
</section>

<!-- ── งานของฉัน ───────────────────────────────────────── -->
<section id="tasks" aria-labelledby="tasks-h" class="flex flex-col gap-5">
  <div class="flex flex-wrap items-baseline justify-between gap-2">
    <h2 id="tasks-h" class="text-[28px] font-bold leading-snug">งานของฉัน</h2>
    <?= module_link('/inbox', 'ดูทั้งหมด', 'text-[15px] font-bold') ?>
  </div>

  <div class="grid grid-cols-2 gap-3 md:grid-cols-[repeat(auto-fit,minmax(220px,1fr))] md:gap-4">
    <?php foreach ($stats as $s):
        $inner = '<span class="text-sm text-gray-700">' . e($s['label']) . '</span>'
            . '<span class="text-[32px] font-bold leading-tight md:text-[40px]">' . e($s['value'])
            . (isset($s['unit']) ? ' <span class="text-lg">' . e($s['unit']) . '</span>' : '') . '</span>'
            . '<span class="text-sm ' . $toneCls[$s['tone'] ?? 'muted'] . '">' . e($s['note']) . '</span>';
        // การ์ดวันลาไปหน้าการลาได้เมื่อเปิดดูตัวอย่างโมดูล HR
        $href = $s['href'] === '/hr/leave' && preview_modules() ? null : $s['href'];
        echo $href === null
            ? '<a href="/hr/leave" class="flex flex-col gap-1 border border-line bg-white px-4 py-4 no-underline md:px-6 md:py-5">' . $inner . '</a>'
            : module_link($href, $inner, 'flex flex-col gap-1 border border-line bg-white px-4 py-4 no-underline md:px-6 md:py-5');
    endforeach; ?>
  </div>

  <?php if (!$tasks):
      partial('empty_state', [
          'title' => 'ตอนนี้ไม่มีงานค้างค่ะ',
          'message' => 'ไม่มีเรื่องรอคุณอนุมัติ และหนังสือของคุณผ่านหมดแล้ว ถ้ามีเรื่องใหม่ น้องจะแจ้งที่กระดิ่งด้านบนนะคะ',
          'actions' => [['เขียนหนังสือใหม่', '/letters/new'], ['ขอลา', '/hr/leave/new']],
      ]);
  else: ?>
    <!-- ตารางเลื่อนแนวนอนได้บนจอแคบ -->
    <div class="overflow-x-auto border border-line bg-white">
      <table class="w-full min-w-[720px] border-collapse text-[15px]">
        <caption class="sr-only">รายการล่าสุดที่เกี่ยวกับฉัน (ข้อมูลตัวอย่าง)</caption>
        <thead>
          <tr class="text-left text-[13px] text-gray-700">
            <th scope="col" class="border-b border-line px-6 py-3 font-bold">เรื่อง</th>
            <th scope="col" class="border-b border-line px-4 py-3 font-bold">ประเภท</th>
            <th scope="col" class="border-b border-line px-4 py-3 font-bold">สถานะ</th>
            <th scope="col" class="border-b border-line px-6 py-3 text-right font-bold">อัปเดต</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tasks as $t): ?>
            <tr class="border-b border-line last:border-0 hover:bg-surface">
              <td class="px-6 py-3.5">
                <?= module_link($t['href'], e($t['title']), 'font-bold no-underline') ?><br>
                <span class="text-sm text-gray-700"><?= e($t['detail']) ?></span>
              </td>
              <td class="px-4 py-3.5"><?= e($t['type']) ?></td>
              <td class="px-4 py-3.5"><?= status_badge($t['status'], $t['statusLabel'] ?? null) ?></td>
              <td class="whitespace-nowrap px-6 py-3.5 text-right text-gray-700"><?= e($t['updated']) ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  <?php endif ?>
</section>

<?php partial('module_grid') ?>

<!-- ── เริ่มใช้งาน ─────────────────────────────────────── -->
<section aria-labelledby="start-h" class="flex flex-col gap-5">
  <h2 id="start-h" class="text-[28px] font-bold leading-snug">เริ่มใช้งานใน 3 ขั้น</h2>
  <ol class="grid grid-cols-[repeat(auto-fit,minmax(min(260px,100%),1fr))] gap-4">
    <?php foreach ([
        ['เข้าระบบด้วยอีเมลบริษัท', 'ไม่ต้องจำรหัสใหม่ ใช้บัญชีเดียวทุกระบบ'],
        ['ตรวจข้อมูลตัวเองและลายเซ็น', 'ชื่อ ตำแหน่ง ฝ่าย และอัปโหลดลายเซ็นสำหรับหนังสือ'],
        ['ลองเปิดรายงานการประชุม', 'ดูวิธีบันทึกวาระ มติ และงานที่ต้องติดตาม ในระบบที่เปิดใช้แล้ว'],
    ] as $i => [$st, $sd]): ?>
      <li class="flex flex-col gap-2 border border-line bg-white p-7">
        <span class="text-5xl font-bold leading-none text-charcoal"><?= $i + 1 ?></span>
        <span class="text-xl font-bold"><?= e($st) ?></span>
        <span class="text-[15px] text-gray-700"><?= e($sd) ?></span>
      </li>
    <?php endforeach ?>
  </ol>
</section>

<!-- ── ประกาศ ───────────────────────────────────────────── -->
<section aria-labelledby="news-h" class="flex flex-col gap-5">
  <div class="flex flex-wrap items-baseline justify-between gap-2">
    <h2 id="news-h" class="text-[28px] font-bold leading-snug">ประกาศบริษัท</h2>
    <?= module_link('/news', 'ดูทั้งหมด', 'text-[15px] font-bold') ?>
  </div>
  <?php if (!$announcements):
      partial('empty_state', [
          'compact' => true,
          'title' => 'ยังไม่มีประกาศใหม่ค่ะ',
          'message' => 'เมื่อบริษัทมีข่าวหรือกิจกรรม น้องจะนำมาแสดงตรงนี้ให้เห็นก่อนใครนะคะ',
          'actions' => [['ดูประกาศเก่า', '/news']],
      ]);
  else: ?>
    <div class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2">
      <?php foreach ($announcements as $n):
          echo module_link(
              $n['href'],
              '<span class="text-[13px] font-bold text-gray-700">' . e($n['tag']) . '</span>'
              . '<span class="text-lg font-bold leading-normal">' . e($n['title']) . '</span>'
              . '<span class="text-[15px] text-gray-700">' . e($n['detail']) . '</span>',
              'flex w-[min(340px,85vw)] shrink-0 snap-start flex-col gap-2 border border-line bg-white p-6 no-underline'
          );
      endforeach; ?>
    </div>
  <?php endif ?>
</section>
