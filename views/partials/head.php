<?php /** ส่วนหัว HTML ที่ใช้ทุกหน้า — $title */ ?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e(($title ?? '') === 'Together Hub' || empty($title) ? 'Together Hub' : $title . ' · Together Hub') ?></title>
<link rel="stylesheet" href="<?= asset('/assets/app.css') ?>">
<script src="<?= asset('/assets/app.js') ?>" defer></script>
</head>
