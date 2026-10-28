<footer class="bg-charcoal pb-20 text-white lg:pb-0">
  <div class="mx-auto flex max-w-[1200px] flex-wrap justify-between gap-8 px-6 py-10">
    <div class="flex flex-[1_1_320px] flex-col gap-1.5">
      <span class="text-lg font-bold tracking-[0.08em]">TOGETHER <span class="text-orange">HUB</span></span>
      <span class="text-sm">บจก.ทูเก็ตเตอร์ แอสโซซิเอทส์ · ระบบภายในสำหรับพนักงาน</span>
      <span class="text-sm">เวอร์ชัน <?= e(APP_VERSION) ?> (PHP)</span>
    </div>
    <nav aria-label="ลิงก์ท้ายหน้า" class="flex flex-wrap items-start gap-x-6 gap-y-2 text-sm">
      <a href="/privacy" class="text-white">นโยบายความเป็นส่วนตัว (PDPA)</a>
      <a href="/manual" class="text-white">คู่มือการใช้งาน</a>
      <a href="/help" class="text-white">แจ้งปัญหาการใช้งาน</a>
      <form action="/auth/signout" method="post">
        <?= csrf_field() ?>
        <button type="submit" class="text-white underline">ออกจากระบบ</button>
      </form>
    </nav>
  </div>
</footer>
