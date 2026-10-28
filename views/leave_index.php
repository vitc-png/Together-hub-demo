<?php /** หน้า "การลาของฉัน" — $balances, $requests, $sent */ ?>
<div class="flex flex-col gap-8">
  <nav aria-label="ตำแหน่งหน้า" class="text-sm text-gray-700">
    <a href="/">หน้าแรก</a> <span aria-hidden="true">›</span> HR <span aria-hidden="true">›</span> <span class="font-bold text-ink">การลา</span>
  </nav>

  <div class="flex flex-wrap items-end justify-between gap-4">
    <div class="flex items-center gap-4">
      <?= mascot('khwanKhao', 80, 'hidden sm:block') ?>
      <div>
        <h1 class="text-[28px] font-bold leading-snug">การลาของฉัน</h1>
        <p class="text-gray-700">ดูวันลาที่เหลือ ขอลา และติดตามว่าอนุมัติหรือยัง</p>
      </div>
    </div>
    <a href="/hr/leave/new" class="flex h-12 items-center gap-2 rounded-xs bg-orange px-6 font-bold text-ink no-underline hover:brightness-95">+ ขอลาใหม่</a>
  </div>

  <?php if ($sent): ?>
    <p role="status" class="flex items-center gap-3 border border-line bg-status-approved-bg px-5 py-3 text-status-approved">
      <?= mascot('khwanKhao', 44) ?>
      <span><b>ส่งใบลาแล้วค่ะ</b> — ติดตามสถานะได้ที่รายการด้านล่างนะคะ</span>
    </p>
  <?php endif ?>

  <!-- วันลาคงเหลือ -->
  <section aria-labelledby="bal-h" class="flex flex-col gap-4">
    <h2 id="bal-h" class="text-xl font-bold">วันลาคงเหลือปีนี้</h2>
    <?php if (!$balances): ?>
      <p class="border border-dashed border-line-strong bg-white px-5 py-4 text-[15px] text-gray-700">ยังไม่ได้กำหนดสิทธิ์วันลาของปีนี้ — ติดต่อฝ่ายสำนักงานค่ะ</p>
    <?php else: ?>
      <div class="grid grid-cols-[repeat(auto-fit,minmax(min(220px,100%),1fr))] gap-3 md:gap-4">
        <?php foreach ($balances as $b):
            $left = max(0, $b['entitled'] - $b['used']);
            $pct = $b['entitled'] > 0 ? min(100, $b['used'] / $b['entitled'] * 100) : 0; ?>
          <div class="flex flex-col gap-2 border border-line bg-white px-5 py-4">
            <span class="text-[15px] font-bold"><?= e($b['label']) ?></span>
            <span class="text-[36px] font-bold leading-tight"><?= fmt_days($left) ?> <span class="text-lg">วัน</span></span>
            <div role="meter" aria-label="<?= e($b['label']) ?> ใช้ไป" aria-valuemin="0" aria-valuemax="<?= $b['entitled'] ?>" aria-valuenow="<?= $b['used'] ?>" class="h-2 w-full overflow-hidden rounded-full bg-line">
              <div class="h-full bg-charcoal" style="width: <?= round($pct, 1) ?>%"></div>
            </div>
            <span class="text-sm text-gray-700">ใช้ไป <?= fmt_days($b['used']) ?> จาก <?= fmt_days($b['entitled']) ?> วัน</span>
            <?php if ($b['pending'] > 0): ?>
              <span class="text-sm font-bold text-status-pending">รออนุมัติอีก <?= fmt_days($b['pending']) ?> วัน</span>
            <?php endif ?>
          </div>
        <?php endforeach ?>
      </div>
    <?php endif ?>
  </section>

  <!-- ใบลาของฉัน -->
  <section aria-labelledby="hist-h" class="flex flex-col gap-4">
    <h2 id="hist-h" class="text-xl font-bold">ใบลาของฉัน</h2>
    <?php if (!$requests):
        partial('empty_state', [
            'title' => 'ยังไม่เคยขอลาค่ะ',
            'message' => 'เมื่อส่งใบลาแล้ว จะเห็นสถานะตรงนี้ว่าหัวหน้าอนุมัติหรือยัง ลองกด “ขอลาใหม่” ดูได้เลยนะคะ ใช้เวลาไม่ถึง 1 นาที',
        ]);
    else: ?>
      <ul class="flex flex-col gap-3">
        <?php foreach ($requests as $r): ?>
          <li class="flex flex-col gap-2 border border-line bg-white px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <span class="text-[17px] font-bold"><?= e(LEAVE_TYPES[$r['type']] ?? $r['type']) ?> · <?= e(thai_date_range($r['start'], $r['end'])) ?></span>
              <?= status_badge($r['status']) ?>
            </div>
            <span class="text-[15px] text-gray-700">
              <?= fmt_days($r['days']) ?> วัน<?= $r['halfDay'] !== 'full' ? ' (' . e(HALF_DAYS[$r['halfDay']]) . ')' : '' ?> · <?= e($r['reason']) ?>
            </span>
            <span class="text-sm text-gray-700">
              <?= e($r['ref']) ?> · ส่งเมื่อ <?= e(thai_date_short($r['submittedAt'])) ?> · ผู้อนุมัติ <?= e($r['approverName']) ?>
              <?php if ($r['hasCert']): ?> · <a href="/hr/leave/cert?id=<?= e($r['id']) ?>" class="font-bold">ใบรับรองแพทย์</a><?php endif ?>
            </span>
            <?php if ($r['note']): ?>
              <p class="bg-status-revise-bg px-3 py-2 text-sm text-status-revise"><b>ความเห็นผู้อนุมัติ:</b> <?= e($r['note']) ?></p>
            <?php endif ?>
          </li>
        <?php endforeach ?>
      </ul>
    <?php endif ?>
  </section>
</div>
