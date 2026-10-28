<?php
declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════════════
// ส่งอีเมล
//   MAIL_DRIVER=smtp → ส่งจริงผ่าน SMTP (เช่น Google Workspace, Microsoft 365, ผู้ให้บริการอีเมล)
//   MAIL_DRIVER=log  → ไม่ส่ง เขียนไฟล์ไว้ที่ storage/mail/ (ใช้ตอนทดสอบในเครื่องเท่านั้น)
// ═══════════════════════════════════════════════════════════════════════════

function send_login_email(string $to, string $name, string $url, string $code, int $ttl): void
{
    $html = render_email('login', ['name' => $name, 'url' => $url, 'code' => $code, 'ttl' => $ttl]);
    $text = "สวัสดีค่ะ คุณ{$name}\n\nเข้าสู่ระบบ Together Hub: {$url}\nหรือพิมพ์รหัส: {$code}\n\n"
        . "ใช้ได้ครั้งเดียว ภายใน {$ttl} นาที\nไม่ได้ขอเข้าระบบ? เพิกเฉยอีเมลนี้ได้เลยค่ะ อย่าบอกรหัสให้ใคร";
    // ไม่ใส่รหัสในหัวเรื่อง (หัวเรื่องโชว์บนหน้าจอล็อกมือถือ คนข้าง ๆ เห็นได้)
    send_mail($to, 'ลิงก์และรหัสเข้าสู่ระบบ Together Hub', $html, $text);
}

function render_email(string $name, array $vars): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require APP_ROOT . "/views/email/{$name}.php";
    return (string) ob_get_clean();
}

function send_mail(string $to, string $subject, string $html, string $text): void
{
    $from = (string) env('MAIL_FROM', 'no-reply@example.com');
    $fromName = (string) env('MAIL_FROM_NAME', 'Together Hub');
    $boundary = 'tg_' . bin2hex(random_bytes(12));

    $headers = [
        'Date: ' . date(DATE_RFC2822),
        'From: ' . mime_word($fromName) . " <{$from}>",
        "To: <{$to}>",
        'Subject: ' . mime_word($subject),
        'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . (parse_url(app_url(), PHP_URL_HOST) ?: 'localhost') . '>',
        'MIME-Version: 1.0',
        "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
    ];
    $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($text))
        . "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($html))
        . "--{$boundary}--\r\n";
    $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;

    if (env('MAIL_DRIVER', 'log') === 'smtp') {
        smtp_send($from, $to, $message);
        return;
    }
    // log: เก็บไว้ดูตอนทดสอบ (มีรหัสจริงอยู่ในไฟล์ — ห้ามใช้บนระบบจริง)
    $dir = APP_ROOT . '/storage/mail';
    if (!is_dir($dir)) mkdir($dir, 0700, true);
    file_put_contents($dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.eml', $message);
}

function mime_word(string $s): string
{
    return '=?UTF-8?B?' . base64_encode($s) . '?=';
}

/** ส่งผ่าน SMTP แบบง่าย: พอร์ต 465 (SSL) หรือ 587 (STARTTLS) พร้อม AUTH LOGIN */
function smtp_send(string $from, string $to, string $message): void
{
    $host = (string) env('SMTP_HOST');
    $port = (int) env('SMTP_PORT', '587');
    $secure = env('SMTP_SECURE', $port === 465 ? 'ssl' : 'tls');
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;

    $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) throw new RuntimeException("เชื่อมต่อ SMTP ไม่ได้: {$errstr}");
    stream_set_timeout($fp, 20);

    $read = function () use ($fp): string {
        $out = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $out .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        return $out;
    };
    $cmd = function (string $c, array $ok) use ($fp, $read): string {
        if ($c !== '') fwrite($fp, $c . "\r\n");
        $res = $read();
        if (!in_array((int) substr($res, 0, 3), $ok, true)) {
            throw new RuntimeException('SMTP ตอบกลับผิดพลาด: ' . trim($res));
        }
        return $res;
    };

    $helo = parse_url(app_url(), PHP_URL_HOST) ?: 'localhost';
    $cmd('', [220]);
    $cmd("EHLO {$helo}", [250]);
    if ($secure === 'tls') {
        $cmd('STARTTLS', [220]);
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
            throw new RuntimeException('เปิดการเข้ารหัส TLS ไม่สำเร็จ');
        }
        $cmd("EHLO {$helo}", [250]);
    }
    if (env('SMTP_USER')) {
        $cmd('AUTH LOGIN', [334]);
        $cmd(base64_encode((string) env('SMTP_USER')), [334]);
        $cmd(base64_encode((string) env('SMTP_PASS')), [235]);
    }
    $cmd("MAIL FROM:<{$from}>", [250]);
    $cmd("RCPT TO:<{$to}>", [250, 251]);
    $cmd('DATA', [354]);
    // บรรทัดที่ขึ้นต้นด้วยจุด ต้องเติมจุดอีกตัว (กฎ SMTP)
    $data = preg_replace('/^\./m', '..', $message);
    $cmd($data . "\r\n.", [250]);
    $cmd('QUIT', [221]);
    fclose($fp);
}
