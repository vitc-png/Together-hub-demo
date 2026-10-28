<?php
/**
 * ฟอร์มขอลา — $balances, $approver, $errors, $old
 * ตัวเลขสรุปในหน้าคำนวณด้วยสคริปต์ (public/assets/app.js) เพื่อให้เห็นทันที แต่ server นับใหม่เองทุกครั้งตอนส่ง
 */
$input = 'h-12 w-full rounded-xs border border-line-strong bg-white px-3 text-base outline-none focus:border-ink focus:ring-2 focus:ring-orange';
$first = next_workday_iso();
$type = $old['type'] ?? '';
$start = $old['start'] ?? $first;
$end = $old['end'] ?? $start;
$half = $old['halfDay'] ?? 'full';
$err = fn (string $k) => isset($errors[$k]) ? '<p id="err-' . $k . '" class="text-sm font-bold text-status-rejected">' . e($errors[$k]) . '</p>' : '';
$bal = [];
foreach ($balances as $b) $bal[$b['type']] = $b['entitled'] - $b['used'] - $b['pending'];
$types = array_unique([...array_column($balances, 'type'), 'maternity', 'other']);
?>
<div class="flex flex-col gap-8">
  <nav aria-label="ตำแหน่งหน้า" class="text-sm text-gray-700">
    <a href="/">หน้าแรก</a> <span aria-hidden="true">›</span> <a href="/hr/leave">การลา</a> <span aria-hidden="true">›</span> <span class="font-bold text-ink">ขอลาใหม่</span>
  </nav>
  <div>
    <h1 class="text-[28px] font-bold leading-snug">ขอลาใหม่</h1>
    <p class="text-gray-700">กรอก 3 อย่าง: ประเภท · วันที่ · เหตุผล แล้วกดส่งค่ะ</p>
  </div>

  <form action="/hr/leave/new" method="post" enctype="multipart/form-data" novalidate data-leave-form
        data-balances="<?= e(json_encode($bal)) ?>" data-today="<?= e(today_iso()) ?>" data-cert-days="<?= SICK_CERT_DAYS ?>"
        class="flex max-w-[760px] flex-col gap-8">
    <?= csrf_field() ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= CERT_MAX_BYTES ?>">
    <?php if (isset($errors['form'])): ?><p class="font-bold text-status-rejected"><?= e($errors['form']) ?></p><?php endif ?>

    <!-- 1. ประเภทการลา -->
    <fieldset class="flex flex-col gap-3" <?= isset($errors['type']) ? 'aria-describedby="err-type"' : '' ?>>
      <legend class="mb-3 text-xl font-bold">1. ประเภทการลา</legend>
      <div class="grid grid-cols-[repeat(auto-fit,minmax(min(200px,100%),1fr))] gap-3">
        <?php foreach ($types as $t): ?>
          <label class="flex cursor-pointer items-center gap-3 border-2 border-line bg-white px-4 py-3 has-[:checked]:border-ink has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-orange">
            <input type="radio" name="type" value="<?= e($t) ?>" <?= $type === $t ? 'checked' : '' ?> class="size-5 accent-ink">
            <span class="flex flex-col">
              <span class="font-bold"><?= e(LEAVE_TYPES[$t]) ?></span>
              <span class="text-sm text-gray-700"><?= isset($bal[$t]) ? 'เหลือ ' . fmt_days($bal[$t]) . ' วัน' : 'ตามระเบียบบริษัท' ?></span>
            </span>
          </label>
        <?php endforeach ?>
      </div>
      <?= $err('type') ?>
    </fieldset>

    <!-- 2. วันที่ -->
    <fieldset class="flex flex-col gap-4">
      <legend class="mb-3 text-xl font-bold">2. วันที่ลา</legend>
      <div class="grid grid-cols-[repeat(auto-fit,minmax(min(220px,100%),1fr))] gap-4">
        <label class="flex flex-col gap-1.5">
          <span class="font-bold">ตั้งแต่วันที่</span>
          <input type="date" name="start" value="<?= e($start) ?>" class="<?= $input ?>" <?= isset($errors['start']) ? 'aria-invalid="true" aria-describedby="err-start"' : '' ?>>
          <?= $err('start') ?>
        </label>
        <label class="flex flex-col gap-1.5">
          <span class="font-bold">ถึงวันที่</span>
          <input type="date" name="end" value="<?= e($end) ?>" min="<?= e($start) ?>" class="<?= $input ?>" <?= isset($errors['end']) ? 'aria-invalid="true" aria-describedby="err-end"' : '' ?>>
          <?= $err('end') ?>
        </label>
      </div>

      <!-- ครึ่งวัน: เลือกได้เฉพาะลาวันเดียว (สคริปต์ซ่อนเมื่อเลือกหลายวัน) -->
      <div role="radiogroup" aria-label="ช่วงเวลา" data-half class="flex flex-wrap gap-2">
        <?php foreach (HALF_DAYS as $h => $hl): ?>
          <label class="flex h-11 cursor-pointer items-center gap-2 rounded-xs border border-line-strong bg-white px-4 has-[:checked]:border-ink has-[:checked]:bg-surface has-[:checked]:font-bold">
            <input type="radio" name="halfDay" value="<?= $h ?>" <?= $half === $h ? 'checked' : '' ?> class="accent-ink"> <?= e($hl) ?>
          </label>
        <?php endforeach ?>
      </div>

      <p class="text-[15px]">รวม <b class="text-lg"><span data-days>–</span> วันทำงาน</b> <span class="text-gray-700">(ไม่นับเสาร์–อาทิตย์ · ระบบหักวันหยุดบริษัทให้ตอนส่ง)</span></p>
      <p data-past hidden class="bg-status-revise-bg px-3 py-2 text-sm text-status-revise">เป็นการลาย้อนหลัง หัวหน้าอาจขอเหตุผลเพิ่มเติมนะคะ</p>
    </fieldset>

    <!-- 3. เหตุผล + ไฟล์แนบ -->
    <fieldset class="flex flex-col gap-4">
      <legend class="mb-3 text-xl font-bold">3. เหตุผล</legend>
      <label class="flex flex-col gap-1.5">
        <span class="font-bold">เหตุผลการลา</span>
        <textarea name="reason" rows="3" maxlength="300" placeholder="เช่น พาคุณแม่ไปพบแพทย์"
                  class="w-full rounded-xs border border-line-strong bg-white px-3 py-2.5 text-base outline-none focus:border-ink focus:ring-2 focus:ring-orange"
                  <?= isset($errors['reason']) ? 'aria-invalid="true" aria-describedby="err-reason"' : '' ?>><?= e($old['reason'] ?? '') ?></textarea>
        <?= $err('reason') ?>
      </label>
      <label data-cert class="<?= isset($errors['cert']) ? 'flex' : 'hidden' ?> flex-col gap-1.5">
        <span class="font-bold">ใบรับรองแพทย์ (รูปถ่ายหรือ PDF ไม่เกิน 5 MB)</span>
        <input type="file" name="cert" accept="image/jpeg,image/png,image/webp,image/heic,application/pdf"
               class="text-[15px] file:mr-3 file:h-11 file:rounded-xs file:border file:border-line-strong file:bg-white file:px-4 file:font-bold"
               <?= isset($errors['cert']) ? 'aria-describedby="err-cert"' : '' ?>>
        <?= $err('cert') ?>
      </label>
    </fieldset>

    <!-- 4. สรุป + ส่ง -->
    <section aria-labelledby="sum-h" class="flex flex-col gap-4 border border-line bg-white p-5">
      <h2 id="sum-h" class="text-xl font-bold">4. ตรวจแล้วส่ง</h2>
      <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-[15px]">
        <dt class="text-gray-700">ประเภท</dt><dd class="font-bold" data-sum-type>—</dd>
        <dt class="text-gray-700">จำนวน</dt><dd class="font-bold"><span data-sum-days>–</span> วัน</dd>
        <dt class="text-gray-700" data-sum-left-label hidden>เหลือหลังลา</dt><dd class="font-bold" data-sum-left hidden></dd>
        <dt class="text-gray-700">ผู้อนุมัติ</dt>
        <dd class="font-bold"><?= e($approver['name']) ?> <span class="font-normal text-gray-700">(<?= e($approver['position']) ?>)</span></dd>
      </dl>
      <p data-over hidden class="flex items-center gap-3 bg-status-revise-bg px-3 py-2 text-sm text-status-revise">
        <?= mascot('khwanKhao', 40) ?>
        วันลาประเภทนี้ไม่พอค่ะ ส่งได้ แต่ส่วนที่เกินอาจถูกนับเป็นลาไม่รับค่าจ้าง ลองคุยกับหัวหน้าก่อนนะคะ
      </p>
      <div class="flex flex-wrap gap-3">
        <button type="submit" data-busy-text="กำลังส่ง…" class="h-12 rounded-xs bg-orange px-8 text-base font-bold text-ink hover:brightness-95 disabled:opacity-60">ส่งใบลา</button>
        <a href="/hr/leave" class="flex h-12 items-center rounded-xs border border-line-strong bg-white px-6 font-bold no-underline">ยกเลิก</a>
      </div>
    </section>
  </form>
</div>
