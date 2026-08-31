<?php

$host = 'localhost';
$dbname = 'credit_finance';
$username = 'root';
$password = '';

try {

    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password
    );

    // Aktifkan mode error PDO
    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    // Hasil query berupa associative array
    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

    // Matikan emulasi prepared statement
    $pdo->setAttribute(
        PDO::ATTR_EMULATE_PREPARES,
        false
    );

} catch (PDOException $e) {

    die(
        'Koneksi database gagal: ' .
        $e->getMessage()
    );

}