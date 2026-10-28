#!/usr/bin/env python3
"""
สร้าง "เว็บตัวอย่าง" (หน้าเว็บนิ่ง) สำหรับ GitHub Pages จากเว็บ PHP ที่รันอยู่ในเครื่อง

GitHub Pages รัน PHP/MySQL ไม่ได้ สคริปต์นี้จึงเปิดหน้าจริงจาก server ในเครื่อง (ข้อมูลสมมติ)
แล้วบันทึกเป็นไฟล์ HTML ลงโฟลเดอร์ docs/ พร้อมป้าย "เว็บตัวอย่าง" — ฟอร์มทุกอันไม่ส่งจริง

ขั้นตอน (ดู README หัวข้อ "เว็บตัวอย่างบน GitHub Pages"):
  1. ฐานข้อมูลทดสอบ: schema.sql + seed.example.sql + demo-data.sql
  2. .env: MAIL_DRIVER=log, TG_PREVIEW_MODULES=1
  3. php -S 127.0.0.1:8000 -t public public/index.php
  4. python3 tools/build_demo.py            → ได้ docs/
"""
import glob
import http.cookiejar
import os
import re
import base64
import shutil
import sys
import urllib.parse
import urllib.request

BASE = os.environ.get("DEMO_BASE", "http://127.0.0.1:8000")
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(ROOT, "docs")
DEMO_EMAIL = "staff1@example.com"
SITE = os.environ.get("DEMO_SITE_PATH", "/Together-hub-demo/")  # ที่อยู่เว็บบน GitHub Pages

# หน้าในเว็บ PHP → โฟลเดอร์ในเว็บตัวอย่าง ("" = หน้าแรกของเว็บตัวอย่าง)
ROUTES = {
    "/login": "",
    "/": "home/",
    "/hr/leave": "hr/leave/",
    "/hr/leave/new": "hr/leave/new/",
    "/meetings": "meetings/",
    "/registry": "registry/",
}
# ฟอร์ม → หน้าที่จะพาไปหลังกดส่ง (ในเว็บตัวอย่าง)
FORM_NEXT = {
    "/login": "login-sent/",
    "/login/verify": "home/",
    "/auth/signout": "",
    "/hr/leave/new": "hr/leave/sent/",
    "/auth/confirm": "home/",
}

jar = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))


def get(path):
    with opener.open(BASE + path) as r:
        return r.read().decode("utf-8")


def post(path, data):
    req = urllib.request.Request(BASE + path, data=urllib.parse.urlencode(data).encode())
    with opener.open(req) as r:
        return r.read().decode("utf-8")


def csrf(html):
    return re.search(r'name="_csrf" value="([a-f0-9]+)"', html).group(1)


def latest_code():
    files = sorted(glob.glob(os.path.join(ROOT, "storage/mail/*.eml")))
    raw = open(files[-1], encoding="utf-8").read()
    part = re.search(r"text/plain; charset=UTF-8\r?\nContent-Transfer-Encoding: base64\r?\n\r?\n(.*?)\r?\n--", raw, re.S).group(1)
    text = base64.b64decode("".join(part.split())).decode("utf-8")
    return re.search(r": (\d{6})\n", text).group(1)


def rel(page_dir, target_dir):
    """ลิงก์แบบสัมพัทธ์ (เว็บอยู่ใต้ /<ชื่อ repo>/ จึงใช้ลิงก์ที่ขึ้นต้นด้วย / ไม่ได้)"""
    depth = page_dir.count("/")
    return ("../" * depth) + target_dir or "./"


BANNER = (
    '<div style="background:#040606;color:#fff;font-size:14px;line-height:1.5;padding:8px 16px;text-align:center;position:relative;z-index:60">'
    '<b style="color:#ff7e1c">เว็บตัวอย่าง (Demo)</b> · ข้อมูลสมมติทั้งหมด · '
    "ปุ่มบันทึกและส่งข้อมูลไม่ทำงานจริง — ระบบจริงติดตั้งบนเซิร์ฟเวอร์บริษัท</div>"
)


def convert(html, page_dir):
    html = html.replace("<head>", '<head>\n<meta name="tg-demo" content="1">', 1)
    html = re.sub(r'<meta name="csrf-token"[^>]*>\n?', "", html)
    html = re.sub(r'<input type="hidden" name="_csrf"[^>]*>', "", html)
    html = re.sub(r"<body>", "<body>\n" + BANNER, html, count=1)
    # ข้อความสำหรับผู้ดูแล (โหมดทดสอบอีเมล) ไม่ต้องแสดงในเว็บตัวอย่าง
    html = re.sub(r'<p class="border border-line-strong px-3 py-2[^"]*">\s*โหมดทดสอบ.*?</p>', "", html, flags=re.S)

    def fix_url(m):
        attr, url = m.group(1), m.group(2)
        path, _, query = url.partition("?")
        if attr == "action":
            nxt = FORM_NEXT.get(path, "")
            return f'action="#" data-demo-next="{rel(page_dir, nxt)}"'
        if path.startswith(("/assets/", "/mascots/", "/brand/", "/email/")):
            return f'{attr}="{rel(page_dir, path[1:])}"'
        if path == "/login":
            return f'{attr}="{rel(page_dir, "")}"'
        if path in ROUTES:
            return f'{attr}="{rel(page_dir, ROUTES[path])}"'
        return f'{attr}="#" data-demo-missing'

    return re.sub(r'(href|src|action)="(/[^"]*)"', fix_url, html)


def save(page_dir, html):
    path = os.path.join(OUT, page_dir, "index.html")
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        f.write(convert(html, page_dir))
    print("  สร้าง", page_dir or "(หน้าแรก)")


def main():
    if os.path.isdir(OUT):
        shutil.rmtree(OUT)
    os.makedirs(OUT)

    # 1) หน้าเข้าสู่ระบบ → ขอรหัส → หน้า "เช็กอีเมล"
    login = get("/login")
    save("", login)
    post("/login", {"_csrf": csrf(login), "email": DEMO_EMAIL, "next": "/"})
    sent = get("/login")
    save("login-sent/", sent)

    # 2) เข้าสู่ระบบด้วยรหัสจากอีเมล (เก็บไว้ในไฟล์ เพราะ MAIL_DRIVER=log)
    post("/login/verify", {"_csrf": csrf(sent), "email": DEMO_EMAIL, "token": latest_code(), "next": "/"})

    # 3) หน้าอื่น ๆ
    for route, page_dir in ROUTES.items():
        if route == "/login":
            continue
        save(page_dir, get(route))
    save("hr/leave/sent/", get("/hr/leave?sent=1"))

    # 4) ไฟล์ประกอบ
    for d in ("assets", "mascots", "brand"):
        shutil.copytree(os.path.join(ROOT, "public", d), os.path.join(OUT, d))
    open(os.path.join(OUT, ".nojekyll"), "w").close()  # ให้ GitHub Pages ส่งไฟล์ตรง ๆ ไม่แปลงอะไร
    with open(os.path.join(OUT, "404.html"), "w", encoding="utf-8") as f:
        f.write(f'<!doctype html><meta charset="utf-8"><title>ไม่พบหน้านี้</title>'
                f'<p style="font-family:sans-serif;padding:24px">ไม่พบหน้านี้ในเว็บตัวอย่าง — <a href="{SITE}">กลับหน้าแรก</a></p>')
    print("เสร็จ:", OUT)


if __name__ == "__main__":
    try:
        main()
    except Exception as e:  # noqa: BLE001
        print("สร้างไม่สำเร็จ:", e, file=sys.stderr)
        sys.exit(1)
