<?php
$host    = DB_SERVER;
$ln      = DB_SERVER_USERNAME;
$pw      = DB_SERVER_PASSWORD;
$db      = DB_DATABASE;
$charset = 'utf8mb4';

try
{
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $ln, $pw, array(
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => false,
    ));
}
catch (PDOException $e)
{
    echo "Unable to connect to database  - <a href='http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "install/'>Click here to install OPS V2</a>";
    die();
}
