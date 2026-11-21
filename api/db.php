<?php
    require_once __DIR__ . '/loadEnv.php';

    loadEnv();
    $host = $_ENV['DB_HOST'];
    $dbname = $_ENV['DB_NAME'];
    $username = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASS'];

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $mysqli = new mysqli($host, $username, $password, $dbname/*, null, '/path to home directory/mysql/run/mysql.sock'*/); //add the parameters only when hosted on users.iee.ihu.gr
?>