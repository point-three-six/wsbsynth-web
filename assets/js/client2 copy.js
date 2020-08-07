(function(){

    var isPaused = true;
    var gestured = false;
    var playing = false;

    var comments = [];
    var loaded_comment_ids = [];
    var current_comment_id = "";

    // this number represents the # of messages we are willing to
    // queue for reading. during peak hours messages may not be read
    // fast enough so any messages in the backlog when num_backlogged
    // reaches this max will begin to get removed in favor of newer messages.
    var max_backlogged = 15;

    var num_comments_processed = 0;
    var max_comments_displayed = 15;

    var soundQueue = new createjs.LoadQueue();
    createjs.Sound.alternateExtensions = ["mp3"];
    soundQueue.installPlugin(createjs.Sound);
    soundQueue.on("fileload", sound_loaded, this);
    
    var soundInstance = 0;

    function init(){
        var socket = io('https://wsbsynth.com:3000');
        //var socket = io('http://localhost:3000');

        socket.on('info', function(d){
            $('#num-listeners').text(d.listeners);
        });
        socket.on('chunk', process_chunk);
        socket.on('connect', connected);
        socket.on('disconnect', disconnected);
    }

    function play_message(id){
        var msg = false;
        var idx = 0;
        for(var i = 0; i < comments.length; i++){
            if(comments[i].id == id){
                msg = comments[i];
                idx = i;
                break;
            }
        }

        var current_comment_id = msg.id;
        var symbols = msg.symbols;

        var symbols_html = '';

        for(symbol in symbols){
            symbols_html += '<span class="ticker">'+ symbol +'</span>';
        }

        var body = safe_tags(msg.body);
            body = nl2br(body);

        var html =  '<div class="comment">' +
        '<div class="info flex-container">'+
        '<div class="author flex"><a href="https://reddit.com/u/'+ safe_tags(msg.username) +'" target="_blank">'+ safe_tags(msg.username) +'</a></div>' +
        '<div class="permalink flex text-right"><a href="https://reddit.com'+ msg.permalink +'" target="_blank"><i class="fas fa-external-link-alt"></i></a></div>' +
        '</div>' +
        '<div class="body">'+ body +'</div>' +
        '<div class="tickers">'+ symbols_html +'</div>' +
        '</div>';

        $('#comments').prepend(html);

        // read comment
        playing = true;
        soundInstance = createjs.Sound.play(id);
        soundInstance.on("complete", sound_complete);
        try {
            soundQueue.remove(id);
        } catch(e) {
            console.log("failed to remove "+ id);
        }
        
        comments.splice(idx, 1);
        num_comments_processed++;

        if(num_comments_processed > max_comments_displayed){
            $('#comments').children().last().remove();
        }

        $('#num-backlogged').text(comments.length);
    }
    
    function process_chunk(chunk){
        var num = chunk.length;
        var num_backlogged = comments.length;

        // in an attempt to stay close to the most recent comments
        // we need to limit the backlog capacity.
        // var new_num_backlogged = num_backlogged + num;
        // if(new_num_backlogged >= max_backlogged){
        //     console.log("Backlog reached max of "+ max_backlogged)
        //     // we need to cull old messages in favor of new messages.
        //     var num_to_cull = new_num_backlogged - max_backlogged;

        //     cull_comments(num_to_cull);

        //     num_backlogged -= num_to_cull;
        // }

        num_backlogged += num;


        for(var i = 0; i < chunk.length; i++){
            comments.push(chunk[i]);
            soundQueue.loadFile({id: chunk[i].id, src: "synthesized/"+chunk[i].mp3});
        }

        if(num_comments_processed == 0){
            $('#loading').hide();
            $('#comments').show();
        }

        $('#num-backlogged').text(comments.length);
    }

    function cull_comments(num){
        console.log("culling "+ num +" comments.");
        
        for(var i = 0; i < num; i++){
            if(i >= comments.length) break;
            console.log("removing "+ comments[i].id)
            soundQueue.remove(comments[i].id);
        }
        comments.splice(0, num);
    }

    function sound_loaded(event, x) {
        if(!playing && !isPaused) play_message(event.item.id);
    }

    function sound_complete(event){
        setTimeout(function(){
            playing = false;
            get_next_message();
        }, 425);
    }

    function get_next_message(){
        if(isPaused) return;
        var loaded = soundQueue.getItems();
        if(loaded.length > 0){
            play_message(loaded[0].item.id);
        }
    }

    function connected(){
        $('#conn-status').hide();
        $('#listeners').show();
        if(comments.length == 0){
            $('#loading').text('Waiting for comments');
        }
    }

    function disconnected(){
        $('#conn-status').show();
        $('#listeners').hide();
    }

    function safe_tags(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') ;
    }

    function nl2br(str){
        return str.replace(/(?:\r\n|\r|\n)/g, '<br>');
    }

    function toggle(){
        isPaused = !isPaused;

        if(!gestured){
            gestured = true;
            $('#welcome').hide();
            $('#loading').show();
            init();
        } else if(!isPaused) {
            // no message is playing, resume by getting next message
            // otherwise resume playing current message
            if(!playing){
                get_next_message();
            } else{
                soundInstance.play();
            }
        } else {
            // stop playback of current message
            if(playing){
                soundInstance.paused = true;
            }
        }

        $(".play_button").toggleClass("paused");
        
        return false;
    }
    
    $(document).ready(function(){
        $(".play_button").click(toggle);

        $(document).on('keyup', function(e){
            if(e.which == 32){
                e.preventDefault();
                toggle();
            }
        });
        $('#btn-settings').on('click', function(){
            $("#home").toggleClass("hidden");
            $("#settings").toggleClass("hidden");
        });
    });
})();