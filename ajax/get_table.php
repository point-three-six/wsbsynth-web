<?php
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

$host = 'localhost';
$db   = 'wsbdd';
// $user = 'root';
// $pass = 'mysql99';
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

$sql_total = "SELECT
COUNT(cs.id) AS total
FROM comments_symbols cs
LEFT JOIN comments c ON c.id = cs.comment_id
WHERE (c.`date` BETWEEN DATE_SUB(NOW(), INTERVAL 1 DAY) AND NOW())";

$stmt = $pdo->prepare($sql_total);
$stmt->execute();
$total = $stmt->fetchColumn();

$sql = "SELECT
s.symbol,
count(cs.symbol_id) as mentions
FROM symbols s
LEFT JOIN comments_symbols cs ON cs.symbol_id = s.id
LEFT JOIN comments c ON c.id = cs.comment_id
WHERE (c.`date` BETWEEN DATE_SUB(NOW(), INTERVAL 1 DAY) AND NOW())
GROUP BY (s.symbol)
ORDER BY mentions DESC,s.symbol ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll();

echo json_encode([
    "data_values" => $data,
    "total" => $total
]);