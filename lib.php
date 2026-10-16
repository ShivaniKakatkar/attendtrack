<?php
session_start();
const DB_HOST = 'localhost', DB_NAME = 'attendance_db', DB_USER = 'root', DB_PASS = '';
const SUBJECTS = ['Data Analytics', 'FSD', 'C#', 'SE', 'Cyber Security', 'WCM', 'GA'];  // edit freely
function db()
{
    static $p;
    if (!$p) {
        $p = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    }
    return $p;
}
function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function csrf()
{
    $_SESSION['t'] ??= bin2hex(random_bytes(16));
    return '<input type="hidden" name="t" value="' . $_SESSION['t'] . '">';
}
function check()
{
    if (($_POST['t'] ?? '') !== ($_SESSION['t'] ?? 'x')) die('Invalid request');
}
function need($role)
{
    if (empty($_SESSION[$role])) {
        header("Location: $role.php");
        exit;
    }
}
function head($title, $user = null)
{ ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?= e($title) ?></title>
        <link rel="stylesheet" href="assets/style.css">
    </head>

    <body>
        <header class="top"><a href="index.php" class="brand">📋 AttendTrack</a><?php if ($user): ?><span><?= e($user) ?> · <a href="logout.php">Logout</a></span><?php endif; ?></header>
        <main><?php }
            function foot()
            {
                echo '</main></body></html>';
            }
