<?php
declare(strict_types=1);

// การเชื่อมต่อฐานข้อมูล MySQL/MariaDB (เชื่อมครั้งเดียวต่อการเปิดหน้า)
// ใช้ prepared statement ทุกคำสั่ง (กันการแทรกคำสั่ง SQL)
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        env('DB_HOST', '127.0.0.1'),
        env('DB_PORT', '3306'),
        env('DB_NAME', 'together_hub')
    );
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    if (env('DB_SOCKET')) $dsn = sprintf('mysql:unix_socket=%s;dbname=%s;charset=utf8mb4', env('DB_SOCKET'), env('DB_NAME', 'together_hub'));

    $pdo = new PDO($dsn, env('DB_USER', 'together_hub'), env('DB_PASS', ''), $opts);
    // เวลาในฐานข้อมูลเป็นเวลาไทย ตรงกับ PHP
    $pdo->exec("SET time_zone = '+07:00'");
    return $pdo;
}

/** รันคำสั่งพร้อมค่า แล้วคืน statement */
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function q_one(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function q_all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function uuid4(): string
{
    $b = random_bytes(16);
    $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
    $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}
