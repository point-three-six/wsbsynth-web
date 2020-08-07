<!DOCTYPE html>
<html>
    <head>
        <title>
            WSBSynth - Live Stock Mentions
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
                width:950px;
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

            .text-center {
                text-align:center;
            }

            h1,h2,h3 {
                margin-top:50px;
                color:rgba(209, 157, 60, .7);
            }

            ::-moz-selection { background-color: rgb(230, 183, 97);}
            ::selection { background-color:rgb(230, 183, 97); }
        </style>
        <script src="https://kit.fontawesome.com/b48fc18af5.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.3/dist/Chart.bundle.min.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script>
            $(document).ready(function(){
                var requested_stocks = [];
                
                var colors = [
                    "#3e95cd", "#8e5ea2", "#3cba9f", "#e8c3b9",
                    "#c45850", "#af382f", "#f61797", "#84c8b8",
                    "#975c6b", "#0c4b6a", "#3fa8da", "#f7c9d7",
                    "#2dad42", "#f1e376", "#30f906"
                ];

                var chart_day = new Chart(document.getElementById("chart-day"), {
                    responsive: true,
                    type: 'line',
                    data: {
                        labels: build_labels_day(),
                        datasets: []
                    },
                    options: {
                        scales: {
                            xAxes: [
                                {
                                    display: true,
                                    type: 'time',
                                    time: {
                                        parser: 'MM/DD/YYYY HH:mm',
                                        tooltipFormat: 'll HH:mm',
                                        unit: 'hour',
                                        unitStepSize: 1,
                                        displayFormats: {
                                            'hour': 'HH:mm'
                                        }
                                    }
                                }
                            ]
                        }
                    }
                });

                var chart_hour = new Chart(document.getElementById("chart-hour"), {
                    responsive: true,
                    type: 'line',
                    data: {
                        labels: build_labels_hour(),
                        datasets: []
                    },
                    options: {
                        scales: {
                            xAxes: [
                                {
                                    display: true,
                                    type: 'time',
                                    time: {
                                        parser: 'MM/DD/YYYY HH:mm',
                                        tooltipFormat: 'll HH:mm',
                                        unit: 'minute',
                                        unitStepSize: 5,
                                        displayFormats: {
                                            'minute': 'H:mm'
                                        }
                                    }
                                }
                            ]
                        }
                    }
                });

                get_data(0);
                get_data(1);
                get_table_data();

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
                            "window" : type
                        },
                        dataType: "json",
                        success: build_datasets
                    });
                }

                function get_table_data(){
                    $.ajax({
                        url: "../ajax/get_table.php",
                        type: "POST",
                        data : {},
                        dataType: "json",
                        success: update_table
                    });
                }

                function build_datasets(r){
                    var i = 0;
                    var data = r.data_values;
                    var window = r.data_window;
                    var chart = (window == 1) ? chart_day : chart_hour;                    

                    // clear data
                    chart.data.datasets = [];

                    for(var symbol in data){
                        var mentions = data[symbol];
                        var color = i % colors.length;
                        
                        // the last element is the current minute/hour "in progress"
                        // so we don't need to show this.
                        mentions.pop();

                        var dataset = {
                            data: mentions,
                            label: symbol,
                            borderColor: colors[color],
                            fill: false
                        };

                        i++;

                        chart.data.datasets.push(dataset);
                    }
                    chart.update(0);
                }

                function update_table(r){
                    var data = r.data_values;
                    var total = r.total;
                    var table = $('#mentions-table');

                    var html = '<tr>';

                    for(var i = 0; i < data.length; i++){
                        var symbol = data[i].symbol;
                        var mentions = data[i].mentions;

                        // show 3 per line
                        if(i % 4 == 0) html += '</tr><tr>';
                        
                        //('+ ((mentions/total)*100).toFixed(2) +'%)
                        html += '<td style="color:#918d8a;">'+ (i+1) +'.</td><td><a href="stock.php?symbol='+ symbol +'">'+ symbol +'</a></td><td style="padding-right:50px;">'+ mentions +'</td>';
                    }

                    table.html(html);
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
            <a name="mentions-day"></a>
            <h2>
                Top 10 stock mentions past 24 hours
                <a href="#mentions-day"><i class="fa fa-link" aria-hidden="true"></i></a>
            </h2>
            <div style="color:white;">Tip: Click a stock symbol in a chart key to remove it from chart</span>
            <div class="margin-top">
                <canvas id="chart-day"></canvas>
            </div>
            <a name="mentions-hour"></a>
            <h2>
                Top 10 stock mentions past hour
                <a href="#mentions-hour"><i class="fa fa-link" aria-hidden="true"></i></a>
            </h2>
            <div class="margin-top">
                <canvas id="chart-hour"></canvas>
            </div>
            <a name="list"></a>
            <h2>
                Stocks mentioned in the last 24 hours
                <a href="#list"><i class="fa fa-link" aria-hidden="true"></i></a>
            </h2>
            <div class="margin-top">
                <div>
                    (click a stock to view individual comments for that stock)
                </div>
                <table id="mentions-table" class="margin-top"></table>
            </div>
        </div>
    </body>
</html>