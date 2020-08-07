<?php
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

if(!isset($_REQUEST['window'])) die;

$window = ($_REQUEST['window'] == "1") ? 1 : 0;
$req_symbol = $_REQUEST['symbol'];

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

if($window == 1){
     if($req_symbol){
          $rs_mentions = get_stock_hr_mentions($pdo, $_REQUEST['symbol']);

          echo json_encode([
               "data_window" => 1,
               "data_values" => $rs_mentions
          ]);
     } else {
          get_top_stocks_day($pdo);
     }
}else{
     if($req_symbol){
          $rs_mentions = get_stock_min_mentions($pdo, $_REQUEST['symbol']);

          echo json_encode([
               "data_window" => 0,
               "data_values" => $rs_mentions
          ]);
     } else {
          get_top_stocks_hour($pdo);
     }
}


function get_top_stocks_day($pdo){
     $sql_top_month = "SELECT
     s.symbol
     FROM symbols s
     LEFT JOIN comments_symbols cs ON cs.symbol_id = s.id
     LEFT JOIN comments c ON c.id = cs.comment_id
     WHERE (c.`date` BETWEEN DATE_SUB(NOW(), INTERVAL 1 DAY) AND NOW())
     GROUP BY (s.symbol)
     ORDER BY count(cs.symbol_id)
     DESC LIMIT 10";

     $stmt = $pdo->prepare($sql_top_month);
     $stmt->execute();
     $top_stocks = $stmt->fetchAll();

     $stock_mentions_by_hour = [];

     foreach($top_stocks as $stock){
          $symbol = $stock['symbol'];

          $stock_mentions_by_hour[$symbol] = get_stock_hr_mentions($pdo, $symbol);
     }

     echo json_encode([
          "data_window" => 1,
          "data_values" => $stock_mentions_by_hour
     ]);
}

function get_top_stocks_hour($pdo){
     $sql_top_hour = "SELECT
     s.symbol
     FROM symbols s
     LEFT JOIN comments_symbols cs ON cs.symbol_id = s.id
     LEFT JOIN comments c ON c.id = cs.comment_id
     WHERE (c.`date` BETWEEN DATE_SUB(NOW(), INTERVAL 1 HOUR) AND NOW())
     GROUP BY (s.symbol)
     ORDER BY count(cs.symbol_id)
     DESC LIMIT 10";

     $stmt = $pdo->prepare($sql_top_hour);
     $stmt->execute();
     $top_stocks = $stmt->fetchAll();

     $stock_mentions_by_minute = [];

     foreach($top_stocks as $stock){
          $symbol = $stock['symbol'];

          $stock_mentions_by_minute[$symbol] = get_stock_min_mentions($pdo, $symbol);
     }

     echo json_encode([
          "data_window" => 0,
          "data_values" => $stock_mentions_by_minute
     ]);
}

function get_stock_hr_mentions($pdo, $stock){
     $sql_top_24_hrs = "SELECT
     s.symbol,
     count(cs.symbol_id) AS mentions,
     DAY(c.`date`) as `day`,
     HOUR(c.`date`) as `hour`
     FROM symbols s
     LEFT JOIN comments_symbols cs ON cs.symbol_id = s.id
     LEFT JOIN comments c ON c.id = cs.comment_id
     WHERE
         (c.`date` BETWEEN DATE_SUB(NOW(), INTERVAL 24 HOUR) AND NOW())
        AND
        s.symbol = ?
     GROUP BY `day`,`hour`
     ORDER BY `day`,`hour`
     ASC;";

     $stmt = $pdo->prepare($sql_top_24_hrs);
     $stmt->execute([$stock]);
     $mentions_results = $stmt->fetchAll();

     // will need these later obv
     $cur_day = date('d');
     $cur_hour = date('H');

     // populate each hour initially with 0 in
     // case data for that hour does not exist
     $mentions = [];
     
     for($i = 0; $i <= 23; $i++){
          $mentions[$i] = 0;
     }

     foreach($mentions_results as $result){
          $hour = $result['hour'];

          // get index it needs to in array
          $idx = ($result['day'] == $cur_day - 1) ? $hour - $cur_hour : 23 - ($cur_hour - $hour);

          $mentions[$idx] = $result['mentions'];
     }

     return $mentions;
}

function get_stock_min_mentions($pdo, $stock){
     $sql_top_24_hrs = "SELECT
     s.symbol,
     count(cs.symbol_id) AS mentions,
     HOUR(c.`date`) as `hour`,
     MINUTE(c.`date`) as `minute`
     FROM symbols s
     LEFT JOIN comments_symbols cs ON cs.symbol_id = s.id
     LEFT JOIN comments c ON c.id = cs.comment_id
     WHERE
         (c.`date` BETWEEN DATE_SUB(NOW(), INTERVAL 1 HOUR) AND NOW())
        AND
        s.symbol = ?
     GROUP BY `hour`,`minute`
     ORDER BY `hour`,`minute`
     ASC;";

     $cur_hour = date('H');
     $cur_minute = date('i');

     $stmt = $pdo->prepare($sql_top_24_hrs);
     $stmt->execute([$stock]);
     $mentions_results = $stmt->fetchAll();

     // populate each minute initially with 0 in
     // case data for that hour does not exist
     $mentions = [];

     for($i = 0; $i <= 59; $i++){
          $mentions[$i] = 0;
     }

     // number of minutes that go into the previous hour
     //$minutes_last_hour = 60 - $cur_minute;

     foreach($mentions_results as $result){
          $min = $result['minute'];

          // get index it needs to in array
          $idx = ($result['hour'] == $cur_hour - 1) ? $min - $cur_minute : 59 - ($cur_minute - $min);

          $mentions[$idx] = $result['mentions'];
     }

     // let's reduce it to 5 minute intervals
     $mentions_reduced = [];

     $reduce_by = 5;
     for($i = 0; $i < count($mentions) / $reduce_by; $i++){
          $total = 0;
          for($x = $i * $reduce_by; $x < $i * $reduce_by + 5; $x++){
               $total += $mentions[$x];
          }
          $mentions_reduced[$i] = $total;
     }

     return $mentions_reduced;
}