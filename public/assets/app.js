// Together Hub — สคริปต์หน้าเว็บ (ไม่ต้องติดตั้งอะไรเพิ่ม)
// ทุกอย่างในไฟล์นี้เป็น "ตัวช่วยให้ใช้ง่าย" — ถ้าสคริปต์ไม่ทำงาน ฟอร์มยังส่งได้ และ server ตรวจข้อมูลเองทุกครั้ง
(function () {
  'use strict';
  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
  var csrf = ($('meta[name="csrf-token"]') || {}).content || '';
  var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var store = function (kind) {
    try { return window[kind]; } catch (e) { return null; }
  };

  // เว็บตัวอย่างบน GitHub Pages (หน้าเว็บนิ่ง ไม่มี server) — ดู tools/build_demo.py
  var DEMO = !!document.querySelector('meta[name="tg-demo"]');

  function post(url) {
    if (DEMO) {
      var now = new Date();
      var hhmm = ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2);
      return Promise.resolve(url === '/api/checkin' ? { time: hhmm } : { ok: true });
    }
    return fetch(url, {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrf, 'Accept': 'application/json' },
      credentials: 'same-origin'
    }).then(function (r) { return r.json(); });
  }

  // ── โลโก้: ไฟฟ้าวิ่งระหว่างเขาน้อง (brand-logo) ─────────────────────────────
  function zap(logo) {
    if (reducedMotion || !logo) return;
    var svg = $('svg', logo);
    var fresh = svg.cloneNode(true); // สร้างใหม่ = เริ่มภาพเคลื่อนไหวจากต้น
    fresh.classList.remove('hidden');
    svg.replaceWith(fresh);
    clearTimeout(logo._t);
    logo._t = setTimeout(function () { fresh.classList.add('hidden'); }, 2600);
  }
  $$('[data-brand-logo][data-animate]').forEach(function (logo) {
    var ss = store('sessionStorage');
    try {
      if (ss && ss.getItem('tg-logo-zap-v1')) return;
      if (ss) ss.setItem('tg-logo-zap-v1', '1');
    } catch (e) {}
    zap(logo);
  });
  $$('form[data-zap-on-submit]').forEach(function (f) {
    f.addEventListener('submit', function () { zap($('[data-brand-logo][data-zap]')); });
  });

  // ── เว็บตัวอย่าง: ไม่ส่งฟอร์มจริง พาไปหน้าผลลัพธ์ที่เตรียมไว้ ──────────────────
  if (DEMO) {
    $$('form').forEach(function (f) {
      f.addEventListener('submit', function (ev) {
        ev.preventDefault();
        var next = f.getAttribute('data-demo-next');
        setTimeout(function () { if (next) location.href = next; }, 450);
      });
    });
    $$('a[data-demo-missing]').forEach(function (a) {
      a.addEventListener('click', function (ev) { ev.preventDefault(); }); // หน้านี้ยังไม่มีในเว็บตัวอย่าง
    });
  }

  // ── ปุ่มส่งฟอร์ม: ขึ้นข้อความ "กำลัง…" และกันกดซ้ำ ────────────────────────────
  $$('form').forEach(function (f) {
    f.addEventListener('submit', function () {
      if (DEMO && !f.getAttribute('data-demo-next')) return;
      var btn = $('button[type="submit"][data-busy-text]', f);
      if (!btn) return;
      setTimeout(function () { btn.disabled = true; btn.textContent = btn.dataset.busyText; }, 0);
    });
  });

  // ── แถบ PDPA ──────────────────────────────────────────────────────────────
  var consent = $('[data-consent]');
  if (consent) {
    var ls = store('localStorage');
    var seen = null;
    try { seen = ls && ls.getItem('tg-consent-v1'); } catch (e) {}
    if (!seen) consent.hidden = false;
    $('[data-consent-accept]', consent).addEventListener('click', function () {
      try { ls && ls.setItem('tg-consent-v1', new Date().toISOString()); } catch (e) {}
      consent.hidden = true;
      post('/api/consent').catch(function () {}); // บันทึกหลักฐานที่ฐานข้อมูล
    });
  }

  // ── ลงเวลาเข้างาน ───────────────────────────────────────────────────────────
  var ci = $('[data-checkin]');
  if (ci && $('[data-checkin-btn]', ci)) {
    var btn = $('[data-checkin-btn]', ci);
    var err = $('[data-checkin-error]', ci);
    btn.addEventListener('click', function () {
      btn.disabled = true;
      $('[data-label]', btn).textContent = 'กำลังลงเวลา…';
      err.classList.add('hidden');
      post('/api/checkin').then(function (res) {
        if (!res.time) throw new Error(res.error || 'ลงเวลาไม่สำเร็จ');
        var h = new Date().getHours();
        $('[data-checkin-time]', ci).textContent = res.time;
        $('[data-greet-text]', ci).textContent = h < 12 ? 'สวัสดีตอนเช้าค่ะ ขอให้วันนี้ทำงานราบรื่นนะคะ'
          : h < 17 ? 'ลงเวลาเรียบร้อยค่ะ ช่วงบ่ายสู้ ๆ นะคะ' : 'ลงเวลาเรียบร้อยค่ะ อย่าลืมพักผ่อนด้วยนะคะ';
        $('[data-checkin-done]', ci).classList.replace('hidden', 'flex');
        $('[data-checkin-greet]', ci).classList.replace('hidden', 'flex');
        btn.remove();
      }).catch(function (e) {
        btn.disabled = false;
        $('[data-label]', btn).textContent = 'ลงเวลาเข้างาน';
        err.textContent = e.message || 'ลงเวลาไม่สำเร็จ';
        err.classList.remove('hidden');
      });
    });
  }

  // ── ช่องรหัส 6 หลัก: รับเฉพาะตัวเลข พิมพ์/วางครบแล้วส่งเอง ──────────────────
  var otpForm = $('form[data-otp-form]');
  if (otpForm) {
    var otp = $('input[name="token"]', otpForm);
    var len = parseInt(otp.getAttribute('maxlength'), 10) || 6;
    otp.addEventListener('input', function () {
      var d = otp.value.replace(/\D/g, '').slice(0, len);
      if (d !== otp.value) otp.value = d;
      if (d.length === len && otpForm.dataset.sent !== d) {
        otpForm.dataset.sent = d;
        if (otpForm.requestSubmit) otpForm.requestSubmit(); else otpForm.submit();
      }
    });
  }

  // ── นับถอยหลังปุ่ม "ส่งลิงก์อีกครั้ง" ────────────────────────────────────────
  var rs = $('form[data-resend]');
  if (rs) {
    var total = parseInt(rs.dataset.total, 10);
    var endAt = Date.now() + parseInt(rs.dataset.left, 10) * 1000;
    var rb = $('button', rs), bar = $('[data-bar]', rs);
    var tick = function () {
      var left = Math.max(0, Math.ceil((endAt - Date.now()) / 1000));
      bar.style.width = (left / total * 100) + '%';
      if (left > 0) {
        rb.textContent = 'ส่งลิงก์อีกครั้งได้ใน ' + left + ' วินาที';
        setTimeout(tick, 1000);
      } else {
        rb.disabled = false;
        rb.textContent = 'ส่งลิงก์อีกครั้ง';
      }
    };
    tick();
  }

  // ── ฟอร์มขอลา: คำนวณวันและสรุปทันทีที่กรอก ───────────────────────────────────
  var lf = $('form[data-leave-form]');
  if (lf) {
    var balances = JSON.parse(lf.dataset.balances || '{}');
    var today = lf.dataset.today, certDays = parseFloat(lf.dataset.certDays);
    var start = $('input[name="start"]', lf), end = $('input[name="end"]', lf);
    var halfBox = $('[data-half]', lf);
    var fmt = function (n) { return Number.isInteger(n) ? String(n) : n.toFixed(1); };
    var workdays = function (s, e) {
      if (!s || !e || e < s) return 0;
      var n = 0, d = new Date(s + 'T00:00:00Z'), last = new Date(e + 'T00:00:00Z');
      for (var i = 0; d <= last && i < 400; i++) {
        var w = d.getUTCDay();
        if (w !== 0 && w !== 6) n++;
        d.setUTCDate(d.getUTCDate() + 1);
      }
      return n;
    };
    var update = function () {
      var typeEl = $('input[name="type"]:checked', lf);
      var type = typeEl ? typeEl.value : '';
      if (end.value < start.value) end.value = start.value; // เลื่อนวันสิ้นสุดตามให้
      end.min = start.value;
      var oneDay = start.value === end.value;
      halfBox.hidden = !oneDay;
      if (!oneDay) $('input[name="halfDay"][value="full"]', lf).checked = true;
      var halfEl = $('input[name="halfDay"]:checked', lf);
      var half = halfEl ? halfEl.value : 'full';
      var days = oneDay && half !== 'full' ? 0.5 : workdays(start.value, end.value);
      var remaining = Object.prototype.hasOwnProperty.call(balances, type) ? balances[type] : null;
      var over = remaining !== null && days > remaining;

      $('[data-days]', lf).textContent = fmt(days);
      $('[data-sum-days]', lf).textContent = fmt(days);
      $('[data-sum-type]', lf).textContent = typeEl ? typeEl.closest('label').querySelector('.font-bold').textContent : '—';
      $('[data-sum-left-label]', lf).hidden = remaining === null;
      var left = $('[data-sum-left]', lf);
      left.hidden = remaining === null;
      if (remaining !== null) {
        left.textContent = fmt(remaining - days) + ' วัน';
        left.classList.toggle('text-status-rejected', over);
      }
      $('[data-over]', lf).hidden = !over;
      $('[data-past]', lf).hidden = !(type && type !== 'sick' && start.value < today);
      var cert = $('[data-cert]', lf);
      var needCert = type === 'sick' && days >= certDays;
      if (needCert || $('#err-cert', lf)) cert.classList.replace('hidden', 'flex');
      else cert.classList.replace('flex', 'hidden');
    };
    lf.addEventListener('change', update);
    lf.addEventListener('input', update);
    update();
  }
})();
