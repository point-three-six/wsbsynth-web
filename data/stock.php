<?php
    ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

    // must supply a symbol
    if(!isset($_GET["symbol"])) header("Location: index.php");

    $symbol = $_GET["symbol"];

    $ip = md5($_SERVER['REMOTE_ADDR']);

    // Open the file for reading 
    file_put_contents("log_queries.txt", $symbol . " (". $ip .")\n", FILE_APPEND);

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

    // check for existance of stock symbol
    $stmt = $pdo->prepare("SELECT id,company FROM symbols WHERE symbol = ? LIMIT 1");
    $stmt->execute([$symbol]);
    $symbol_data = $stmt->fetch();

    // doesn't exist
    if(!$symbol_data) {
        header("Location: index.php");
    }

    // get pagination info
    $sql_total_comments = "SELECT COUNT(id) as total FROM comments_symbols WHERE symbol_id = ?";

    $stmt = $pdo->prepare($sql_total_comments);
    $stmt->execute([$symbol_data['id']]);
    $total_comments = $stmt->fetchColumn();

    $page = ($_GET['page'])? (int)$_GET['page'] : 1;
    $per_page = 20;
    $num_pages = ceil($total_comments / $per_page);
    $query_start = ($page - 1) * $per_page;

    // load comments
    $sql_comments = "SELECT dd.link,c.comment_id,c.username,c.text,c.date FROM comments_symbols cs
    LEFT JOIN comments c ON cs.comment_id = c.id
    LEFT JOIN threads dd ON c.dd_id = dd.dd_id
    WHERE cs.symbol_id = ?
    ORDER BY c.date DESC
    LIMIT ". $query_start .",". $per_page .";";

    $stmt = $pdo->prepare($sql_comments);
    $stmt->execute([$symbol_data['id']]);
    $comments = $stmt->fetchAll();

    function timeago($time){

        $time = time() - $time; // to get the time since that moment
        $time = ($time<1)? 1 : $time;
        $tokens = array (
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        );

        foreach ($tokens as $unit => $text) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
        }

    }

    // Open the file
    $filename = "rainbow-names.csv";
    $fp = @fopen($filename, 'r'); 

    // Add each line to an array
    if ($fp) {
        $rainbow_names = explode("\n", fread($fp, filesize($filename)));
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <title>
            WSBSynth - <?= $symbol ?> mentions
        </title>
        <link rel="shortcut icon" href="../assets/images/favicon.png" />
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300&display=swap" rel="stylesheet"><style>
            body, html {
                background-color:#232222;
                padding:0;
                margin:0;
                font-family: 'Roboto', sans-serif;
            }

            body {
                margin-bottom:100px;
            }

            a {
                color:rgb(209, 157, 60);
                text-decoration:none;
            }

            nav {
                text-align:center;
                padding:10px 0;
            }

            #changelog {
                margin-top:30px;
                color:#918d8a;
            }

            #container {
                margin:0 auto;
                width:800px;
            }

            .margin-top {
                margin-top:10px;
            }

            .margin-top-20 {
                margin-top:20px;
            }

            .mini-logo {
                width:90px;
                height:90px;
            }

            .hidden {
                display:none;
            }

            .flex-container {
                display:flex;
                width:100%;
                flex-direction: row;
            }

            .flex { 
                flex:1;
            }

            .text-left {
                text-align:left;
            }

            .text-right {
                text-align:right;
            }

            h1 {
                margin-top:50px;
                color:rgba(209, 157, 60, .7);
            }

            #comments .comment-container{
                padding:8px 0;
            }

            #comments .comment{
                background-color:#181717;
                border-bottom:1px solid rgb(58, 50, 35);
                color:#b4b2b2;
                margin:0 auto;
                border-radius:10px;
                margin-bottom:15px;
            }

            #comments .comment .info {
                margin:8px 0px;
            }

            #comments .comment .author {
                margin-left:15px;
            }

            #comments .comment .permalink {
                margin-right:15px;
            }

            #comments .comment .body {
                margin:8px 15px 8px 15px;
                font-size:1.25em;
                overflow: hidden;
                overflow-wrap: break-word;
            }

            #comments .comment .time {
                margin:8px 15px 8px 15px;
                color:#868686;
            }

            #comments .comment .tickers .ticker {
                font-size:.80em;
                background-color:rgb(58, 50, 35);
                border-radius:8px;
                color:white;
                padding:2px 6px;
                margin:0 3px;
                cursor:pointer;
            }

            #pagination ul {
                list-style-type:none;
                margin:0 0;
                margin-top:30px;
                padding:0 0;
                text-align:center;
            }

            #pagination li {
                display:inline-block;
            }

            #pagination li a {
                font-size:1.2em;
                padding:8px;
                margin:4px;
                border:1px solid rgb(209, 157, 60);
            }

            #pagination li.selected a {
                color:white;
            }

            ::-moz-selection { background-color: rgb(230, 183, 97);}
            ::selection { background-color:rgb(230, 183, 97); }

            /*compatibility*/
            @keyframes rainbow {
                0% {
                background-position: 500% 0%;
                }
                100% {
                background-position: 0% 0%;
                }
            }
            
            .rainbow {
                background: #000;
                border-bottom-left-radius: 6px !important;
                border-bottom-right-radius: 6px !important;
            }
            
            .rainbow::after {
                content: "";
                display: block;
                border-bottom-left-radius: 10px;
                border-bottom-right-radius: 10px;
                height: 3px;
                width: 100%;
                background: linear-gradient( 60deg, #ff2400, #e81d1d, #e8b71d, #e3e81d, #1de840, #1ddde8, #2b1de8, #dd00f3, #dd00f3, #ff2400);
                background-size: 500% 500%;
                animation-name: rainbow;
                animation-duration: 20s;
                animation-iteration-count: infinite;
                animation-timing-function: linear;
            }

            .comment.rainbow .author a, .comment.rainbow .permalink .fas {
                background: linear-gradient( 60deg, #ff2400, #e81d1d, #e8b71d, #e3e81d, #1de840, #1ddde8, #2b1de8, #dd00f3, #dd00f3, #ff2400);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
                animation: rainbow_text_animation 6s ease-in-out infinite;
                background-size: 400% 100%;
            }

            @keyframes rainbow_text_animation {
                0%,100% {
                    background-position: 0 0;
                }

                50% {
                    background-position: 100% 0;
                }
            }
        </style>
         <script src="https://kit.fontawesome.com/b48fc18af5.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.3/dist/Chart.bundle.min.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script>
            $(document).ready(function(){
                var requested_stocks = [];

                var page_symbol = "<?= $symbol ?>";
                
                // var colors = [
                //     "#3e95cd", "#8e5ea2", "#3cba9f", "#e8c3b9",
                //     "#c45850", "#af382f", "#f61797", "#84c8b8",
                //     "#975c6b", "#0c4b6a", "#3fa8da", "#f7c9d7",
                //     "#2dad42", "#f1e376", "#30f906"
                // ];

                var chart_day = new Chart(document.getElementById("chart-day"), {
                    responsive: true,
                    type: 'line',
                    data: {
                        labels: build_labels_day(),
                        datasets: []
                    },
                    options: {
                        legend : {
                            display : false
                        },
                        scales: {
                            xAxes: [
                                {
                                    display: true,
                                    type: 'time',
                                    time: {
                                        parser: 'MM/DD/YYYY HH:mm',
                                        tooltipFormat: 'll HH:mm',
                                        unit: 'hour',
                                        unitStepSize: 2,
                                        displayFormats: {
                                            'hour': 'HH:mm'
                                        }
                                    }
                                }
                            ]
                        }
                    }
                });

                get_data(1, page_symbol);

                function build_labels_day(){
                    var labels = [];

                    for(var i = 22; i >= 0; i--){
                        var d = new Date();
                        d.setHours(d.getHours() - i);
                        labels.push(d);
                    }

                    return labels;
                }

                function build_labels_hour(){
                    var labels = [];

                    for(var i = 10; i >= 0; i--){
                        var d = new Date();
                        d.setMinutes(d.getMinutes() - i * 5);
                        labels.push(d);
                    }

                    return labels;
                }

                function get_data(type){
                    $.ajax({
                        url: "../ajax/get_mentions.php",
                        type: "POST",
                        data : {
                            "window" : type,
                            "symbol" : page_symbol
                        },
                        dataType: "json",
                        success: build_datasets
                    });
                }

                function build_datasets(r){
                    var i = 0;
                    var data = r.data_values;
                    var window = r.data_window;
                    var chart = chart_day;

                    data.pop();

                    var dataset = {
                        data: data,
                        label: page_symbol,
                        borderColor: "#fcba03",
                        fill: false
                    };
                    
                    chart.data.datasets.push(dataset);
                    chart.update(0);
                }
                
                //setInterval(get_data, 60000);
            });
        </script>
    </head>
    <body>
        <nav>
            <a href="/">
                <img style="vertical-align:middle" src="../assets/images/wsb.png" class="mini-logo">
            </a>
        </nav>
        <div id="container">
            <h1>Stock mentions for <?=$symbol_data['company']?></h1>
            <canvas id="chart-day"></canvas>
            <a name="comments"></a>
            <div style="color:#868686;margin-top:15px;">
                showing <?=number_format(count($comments))?> results of <?= number_format($total_comments) ?>
            </div>
            <div id="comments" class="margin-top-20 hidden" style="display: block;">
            <?php
                foreach($comments as $comment){
                    $username = htmlentities($comment['username']);
                    $profile_link = htmlentities(urlencode($comment['username']));
                    $comment_link = $comment['link'] . $comment['comment_id'];
                    $body = htmlentities($comment['text']);

                    // reddit styling spport
                    preg_match("/\[.*\](.*)/", $body, $matches);

                    $time = timeago(strtotime($comment['date'])) . ' ago';
                    
            ?>
                <div class="comment<?=(in_array(strtolower($comment['username']), $rainbow_names)) ? ' rainbow' : '' ?>">
                    <div class="comment-container">
                        <div class="info flex-container">
                            <div class="author flex">
                                <a href="https://reddit.com/u/<?=$profile_link?>" target="_blank"><?=$username?></a>
                            </div>
                            <div class="permalink flex text-right">
                                <a href="<?= $comment_link ?>" target="_blank"><i class="fas fa-external-link-alt" aria-hidden="true"></i></a>
                            </div>
                        </div>
                        <div class="body">
                            <?=$body?>
                        </div>
                        <div class="time">
                        <?= $time ?>
                        </div>
                    </div>
                </div>
            <?php
                }
            ?>
            </div>
            <div id="pagination">
                <ul>
                    <?php
                        if($num_pages > 2) echo '<li><a href="stock.php?symbol='. $symbol .'&page=1#comments">&#171;</a></li>';

                        $start = max(min($page-2, $num_pages-5), 1);
                        $end = min($start+5, $num_pages);

                        for($i = $start; $i <= $end; $i++){
                            $link = "stock.php?symbol=". $symbol ."&page=". $i."#comments";
                            $class = ($i == $page) ? "selected" : "";
                            ?>
                            <li class="<?=$class?>">
                                <a href="<?=$link?>"><?=$i?></a>
                            </li>
                            <?php
                        }
                        if($num_pages > 2) echo '<li><a href="stock.php?symbol='. $symbol .'&page='. $num_pages .'#comments">&#187;</a></li>';
                    ?>
                </ul>
            </div>
        </div>
    </body>
</html>