<?php
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

$host = 'localhost';
$db   = 'wsbdd';
$user = 'justin';
$pass = 'mysqlrootytooty1';

$dsn = "mysql:host=$host;dbname=$db;";
$options = [
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$sql_total = "SELECT SUM(`mc_gross`) as total FROM `donos` WHERE MONTH(`date`) = MONTH(CURRENT_DATE()) AND YEAR(`date`) = YEAR(CURRENT_DATE())";

$stmt = $pdo->prepare($sql_total);
$stmt->execute();
$total = $stmt->fetchColumn();

echo json_encode([
    "donations_month" => intval($total)
]);