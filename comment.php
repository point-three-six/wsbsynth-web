<?php
    $id = $_GET['id'];

    if(strlen($id) == 0) header('location: https://wsbsynth.com');
    
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

    $sql = "SELECT * FROM shared WHERE comment_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $comment = $stmt->fetch();

    if(!$comment) header('location: https://wsbsynth.com');

    // now get the DD
    $sql2 = "SELECT link FROM threads WHERE dd_id = ?";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$comment['dd_id']]);
    $link = $stmt2->fetchColumn();
    
    $permalink = $link . $id;
    $username = htmlentities($comment['username']);
    $body = nl2br(htmlentities($comment['text']));
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="Description" CONTENT="Live audio stream of the wallstreetbets daily discussion thread.">
        <title>
            WSBSynth
        </title>
        <link rel="shortcut icon" href="assets/images/wsbsynth_min.png" />
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300&display=swap" rel="stylesheet" />
        <link href="assets/css/style.css?v=25" rel="stylesheet" />
        <script src="https://kit.fontawesome.com/b48fc18af5.js" crossorigin="anonymous"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://kit.fontawesome.com/ea745513dc.js" crossorigin="anonymous"></script>
        <script>
            comment_id = "<?=$id?>";
        </script>
        <script src="assets/js/comment.js?v=3"></script>
        <style>

            nav {
                width:100%;
                text-align:center;
                padding:10px 0;
            }

            .mini-logo {
                width:60px;
                height:60px;
            }

        </style>
    </head>
    <body>
        <div id="container">
            <nav>
                <a href="/">
                    <img style="vertical-align:middle" src="assets/images/wsbsynth_min.png" class="mini-logo">
                </a>
            </nav>
            <div stye="clear:both;"></div>
            <div id="home">
                <div id="comments" style="margin-top:50px;">
                    <div id="comment" class="comment ">
                        <div class="comment-container">
                            <div class="info flex-container">
                                <div class="author flex">
                                    <a href="https://reddit.com/u/<?=$username?>" target="_blank"><?=$username?></a>
                                </div>
                                <div class="permalink flex text-right">
                                    <a href="<?=$permalink?>" target="_blank">
                                    <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="body"><?=$body?></div>
                            <div class="tickers"></div>
                        </div>
                    </div>
                </div>
                <div class="text-center" style="margin-top:30px;">
                    <a href="https://wsbsynth.com"><i class="fas fa-play-circle"></i> listen to the /r/wallstreetbets daily discussion live audio</a>
                </div>
            </div>
            
        </div>

        <div id="player">
            <button class="play_button"></button>
        </div>

        <audio id="audio" preload="auto" src="<?="https://wsb.nyc3.cdn.digitaloceanspaces.com/". $id ."_Brian.mp3";?>" crossOrigin="anonymous"></audio>
    </body>
</html>