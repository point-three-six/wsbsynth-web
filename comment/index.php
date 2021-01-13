<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="Description" CONTENT="Live audio stream of the wallstreetbets daily discussion thread.">
        <title>
            WSBSynth
        </title>
        <link rel="shortcut icon" href="assets/images/favicon.png" />
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300&display=swap" rel="stylesheet" />
        <link href="assets/css/style.css?v=25" rel="stylesheet" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/css/selectize.default.min.css" rel="stylesheet" />
        <script src="https://kit.fontawesome.com/b48fc18af5.js" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.3.0/socket.io.slim.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://kit.fontawesome.com/ea745513dc.js" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/js/standalone/selectize.min.js"></script>
        <script src="assets/js/client.js?v=61"></script>
    </head>
    <body>
        <div id="container">
            <div class="margin-top">
                <nav class="flex-container">
                    <div class="flex text-left">
                        <img style="vertical-align:middle" src="assets/images/wsb_headphones.png" class="mini-logo">
                        <a href="https://www.reddit.com/r/wallstreetbets/" target="_blank"><span style="color:#b4b2b2;">r/wallstreetbets</span></a>
                    </div>
                    <div id="info" class="flex text-center">
                        <span id="conn-status">disconnected</span>
                        <span id="listeners" class="hidden">
                            <div class="tooltip">
                                <i class="fas fa-user-tie"></i>
                                <span id="num-listeners">0</span>
                                <div class="tooltiptext">Active listeners</div>
                            </div>
                      
                            <div class="tooltip">
                                <i class="fas fa-bookmark"></i>
                                <span id="num-backlogged">0</span>
                                <div class="tooltiptext">Comment backlog<br/>(max <span id="num-max-backlogged"></span>)</div>
                            </div>
                        </span>
                    </div>
                    <div class="flex text-right" style="margin-top:7px;">
                        <!--<a href="#" id="btn-settings"><img src="assets/images/cog.png" width="40" height="40"/></a>-->
                        <a href="donations.htm" class="tooltip" target="_blank">
                            <i class="fas fa-donate donation" style="font-size:22px;color:hsl(42, 100%, 56%);" aria-hidden="true"></i>
                            <div class="progress tooltip">
                                <div class="progress-done"> 
                                    
                                </div>
                            </div>
                            <div class="tooltiptext">% of monthly donation goal</div>
                        </a>
                    </div>
                </nav>
            </div>

            <div id="home">
                <div id="comments" class="margin-top-20 hidden">
                    <div id="comment-gbopybh" class="comment ">
                        <div class="comment-container">
                            <div class="info flex-container">
                                <div class="author flex">
                                    <a href="https://reddit.com/u/hammydwnjizzblanket" target="_blank">hammydwnjizzblanket</a>
                                </div>
                                <div class="permalink flex text-right">
                                    <a href="#" name="share" style="display:inline-block;margin-top:2px;margin-right:8px;">
                                        <i class="fas fa-share-alt-square" aria-hidden="true"></i>
                                    </a>
                                    <a href="https://reddit.com/r/wallstreetbets/comments/jqihje/what_are_your_moves_tomorrow_november_09_2020/gbopybh/" target="_blank">
                                    <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="body">Grey Pilgrim</div>
                            <div class="tickers"></div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>

        <div id="player">
            <button class="play_button"></button>
        </div>

        <audio id="audio"></audio>
    </body>
</html>