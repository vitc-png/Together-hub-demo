// สร้าง public/assets/app.css จาก tailwind/input.css (ใช้ตอนพัฒนาเท่านั้น)
import { readFile, writeFile } from "node:fs/promises";
import { fileURLToPath } from "node:url";
import postcss from "postcss";
import tailwind from "@tailwindcss/postcss";

const root = fileURLToPath(new URL("..", import.meta.url));
const from = root + "tailwind/input.css";
const to = root + "public/assets/app.css";
const css = await readFile(from, "utf8");
const out = await postcss([tailwind({ base: root, optimize: { minify: true } })]).process(css, { from, to });
await writeFile(to, out.css);
console.log("สร้าง", to, (out.css.length / 1024).toFixed(1), "KB");
