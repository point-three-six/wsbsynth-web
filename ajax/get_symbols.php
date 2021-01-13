<?php
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

$query = $_POST['query'];

if(strlen($query) == 0) die;

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

$sql_symbols = "SELECT symbol FROM symbols WHERE symbol LIKE ? ORDER BY symbol ASC";

$stmt = $pdo->prepare($sql_symbols);
$stmt->execute(['%'.$query.'%']);
$symbols = $stmt->fetchAll(PDO::FETCH_NUM);

echo json_encode($symbols);