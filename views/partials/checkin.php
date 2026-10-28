<?php /** ปุ่มลงเวลาเข้างาน — บันทึกที่ server (เวลาของ server ไม่ใช่เวลาเครื่องผู้ใช้) — $time */ ?>
<div data-checkin class="contents">
  <div data-checkin-done class="<?= $time ? 'flex' : 'hidden' ?> basis-full flex-wrap items-center gap-3">
    <span role="status" class="flex h-12 items-center justify-center gap-2 rounded-xs bg-status-approved-bg px-5 font-bold text-status-approved">
      <?= icon('check') ?> ลงเวลาแล้ว <span data-checkin-time><?= e($time ?? '') ?></span> น.
    </span>
    <!-- น้องขวัญข้าวเด้งขึ้นมาทักเฉพาะตอนเพิ่งกด -->
    <span data-checkin-greet class="hidden items-center gap-2 animate-pop">
      <?= mascot('khwanKhao', 56) ?>
      <span class="rounded-xs border border-line bg-surface px-3 py-2 text-sm text-charcoal"><b>น้องขวัญข้าว:</b> <span data-greet-text></span></span>
    </span>
  </div>
  <?php if (!$time): ?>
    <button type="button" data-checkin-btn class="flex h-12 items-center justify-center gap-2 rounded-xs bg-orange px-6 text-base font-bold text-ink hover:brightness-95 disabled:opacity-60">
      <?= icon('clock') ?> <span data-label>ลงเวลาเข้างาน</span>
    </button>
    <p role="alert" data-checkin-error class="hidden basis-full text-sm font-bold text-status-rejected"></p>
  <?php endif ?>
</div>
