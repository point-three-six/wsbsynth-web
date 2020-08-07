(function(){
    // state vars
    var isPaused = true;
    var gestured = false;
    var playing = false;

    // setting
    var volume = 1;
    var speed = 1.0;
    var audio_easter_eggs = true;

    // data
    var comments = [];
    var loaded_audio = []; // just a list of comment ids who have had their audio loaded
    var num_audio_loading = 0; // number of audio files being loaded currently
    var current_comment_id = "";
    var cur_audio_object = false;

    // current voiceId to use
    var voice = "Brian";

    // this number represents the # of messages we are willing to
    // queue for reading. during peak hours messages may not be read
    // fast enough so any messages in the backlog when num_backlogged
    // reaches this max will begin to get removed in favor of newer messages.
    var max_backlogged = 25;

    // the maximum number of audio files we want to
    // load in advanced.
    var max_preloaded_audio = 10;

    var num_comments_processed = 0;
    var max_comments_displayed = 15;

    function init(){
        var socket = io('https://wsbsynth.com:3000');
        //var socket = io('http://localhost:3000');

        socket.on('info', function(d){
            $('#num-listeners').text(d.listeners);
            $('#current_dd').text(d.current_dd_title);
            $('#current_dd').attr('href', d.current_dd_url);
        });
        socket.on('chunk', process_chunk);
        socket.on('mentions', update_mentions);
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

        if(!msg){
            console.log("failed to load id: " + id)
            get_and_play_next_message();
            return false;
        }

        // update some state vars
        current_comment_id = id;
        playing = true;
        
        var symbols = msg.symbols;

        var symbols_html = '';

        for(symbol in symbols){
            symbols_html += '<span class="ticker"><a href="data/stock.php?symbol='+ symbol  +'" target="_blank">'+ symbol +'</a></span>';
        }

        var body = safe_tags(msg.body);
            body = nl2br(body);

        // if flair
        var flair = '';
        if(msg.flair){
            flair = '<div class="flair">'+ safe_tags(msg.flair) +'</div>';
        }
        
        var extra = '';
        if(msg.rainbow){
            extra += 'rainbow';
        }

        var html =  '<div id="comment-'+ id +'" class="comment '+ extra +'">' +
        '<div class="comment-container">' +
        '<div class="info flex-container">'+
        '<div class="author flex"><a href="https://reddit.com/u/'+ safe_tags(msg.username) +'" target="_blank">'+ safe_tags(msg.username) +'</a>'+ flair +'</div>' +
        '<div class="permalink flex text-right"><a href="https://reddit.com'+ msg.permalink +'" target="_blank"><i class="fas fa-external-link-alt"></i></a></div>' +
        '</div>' +
        '<div class="body">'+ body +'</div>' +
        '<div class="tickers">'+ symbols_html +'</div>' +
        '</div>' +
        '</div>';

        $('#comments').prepend(html);

        var msgAudio = msg.Audio;
        cur_audio_object = msgAudio;

        // update audio element
        document.getElementById('audio').src = msgAudio.src;

        // start playback
        play();

        // limit # of comment elements displayed
        if(num_comments_processed > max_comments_displayed){
            $('#comments').children().last().remove();
        }

        $('#num-backlogged').text(comments.length);
    }
    
    function process_chunk(chunk){
        var num = chunk.length;
        var num_backlogged = comments.length;

        // can't allow more comments tha the max allowed in comment backlog
        var num_until_max_backlog = max_backlogged - num_backlogged;

        for(var i = 0; i < chunk.length && num_until_max_backlog > 0; i++){
            comments.push(chunk[i]);
            num_until_max_backlog--;
        }

        update_preload_audio_queue();
        $('#num-backlogged').text(comments.length);


        if(num_comments_processed == 0){
            $('#loading').hide();
            $('#comments').show();
            $('#extra').show();
        }
    }

    function get_comment(id){
        for(var i = 0; i < comments.length; i++){
            if(comments[i].id == id){
                return comments[i];
            }
        }
        return false;
    }

    function remove_comment(id){
        for(var i = 0; i < comments.length; i++){
            if(comments[i].id == id){
                comments.splice(i, 1);

                return true;
            }
        }
        return false;
    }

    function load_audio(id){
        if(loaded_audio.length + num_audio_loading >= max_preloaded_audio) return;
        for(var i = 0; i < comments.length; i++){
            if(comments[i].id == id){
                num_audio_loading++;
                var audio = new Audio();
                audio.comment_id = id;
                audio.addEventListener('canplaythrough', audio_loaded, false);

                // check for "special" audio
                var msg = get_comment(id);

                if(audio_easter_eggs && msg.special){
                    audio.src = "assets/audio/"+ msg.special;
                } else {
                    audio.src = "synthesized/"+ id +"_"+ voice +".mp3";
                }
                
                comments[i].Audio = audio;
                audio.load();
            }
        }
    }

    function audio_loaded(){
        num_audio_loading--;

        loaded_audio.push(this.comment_id);

        if(!playing && !isPaused){
            get_and_play_next_message();
        }
    }

    function audio_complete(){
        var id = current_comment_id;

        remove_comment(id);

        num_comments_processed++;

        $('#num-backlogged').text(comments.length);

        // preload audio check
        update_preload_audio_queue();

        setTimeout(function(){
            playing = false;
            get_and_play_next_message();
        }, 425);
    }

    function update_preload_audio_queue(){
        var num_files_to_load = max_preloaded_audio - (loaded_audio.length + num_audio_loading);
        for(var i = 0; i < comments.length && num_files_to_load > 0; i++){
            var comment = comments[i];

            if(!comment.hasOwnProperty('Audio')){
                num_files_to_load--;
                load_audio(comment.id);
            }
        }
    }

    function get_and_play_next_message(){
        if(isPaused || loaded_audio.length == 0) return;
        // remove comment id from loaded_audio
        var id = loaded_audio[0];
        loaded_audio.splice(0, 1);
        play_message(id);
    }

    function update_mentions(mentions){
        var html = '';

        for(var i = 0; i < mentions.length; i++){
            var mention = mentions[i];

            html += '<a href="data/stock.php?symbol='+ mention[0] +'" target="_blank"><div class="ticker">' +
            '<div class="count">'+ mention[1] +'</div>' +
            '<div class="symbol">'+ mention[0] +'</div>' +
            '</div></a>';
        }

        $('#ticker-counts').html(html);
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

    function play(){
        var audio = document.getElementById('audio');
        audio.playbackRate = speed;
        audio.volume = volume;
        audio.play();

    }

    function pause(){
        document.getElementById('audio').pause();
    }

    function setVolume(lvl){
        volume = (lvl/100);
        $('#audio-slider-val').text(lvl);
        document.getElementById('audio').volume = volume;
    }

    function setSpeed(val){
        speed = (val / 10);
        $('#playback-speed-val').text(val/10);
        document.getElementById('audio').playbackRate = speed;
    }

    function setMaxBacklog(max){
        max_backlogged = max;
        $('#num-max-backlogged').text(max);
    }

    function toggle(){
        isPaused = !isPaused;

        if(!gestured){
            gestured = true;
            $('#welcome').hide();
            $('#loading').show();

            // fix for safari. play() needs to be called within gesture event.
            play();

            init();
        } else if(!isPaused) {
            // no message is playing, resume by getting next message
            // otherwise resume playing current message
            if(!playing){
                get_and_play_next_message();
            } else{
                play();
            }
        } else {
            // stop playback of current message
            if(playing){
                pause();
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
        $('#settings-back').on('click', function(){
            $("#settings").toggleClass("hidden");
            $("#home").toggleClass("hidden");
        });
        $('#select-voice').on('change', function(){
            voice = $('#select-voice').val();

            //changing voices means we need to reprocess
            //all audio. so reset currently loading audio
            //and already loaded audio.
            for(var i = 0; i < comments.length; i++){
                if(comments[i].hasOwnProperty('Audio')){
                    comments[i].Audio = null;
                    delete comments[i].Audio;

                    // currently being loaded
                    if(loaded_audio.indexOf(comments[i].id) >= 0){
                        num_audio_loading--;
                    }
                }
            }
            loaded_audio = [];
            update_preload_audio_queue();
        });
        $('#audio-slider').on('change', function(){
            var val = $('#audio-slider').val();
            setVolume(val);
        });
        $('#audio-easter-eggs').on('change', function(){
            var val = $('#audio-easter-eggs').val();
            audio_easter_eggs = (val == "enabled");
        });
        $('#playback-speed').on('change', function(){
            var val = $('#playback-speed').val();
            setSpeed(val);
        });
        $('#max-backlog').on('change', function(){
            var val = $('#max-backlog').val();
            setMaxBacklog(val);
        });
        document.getElementById('audio').addEventListener('ended', audio_complete);
        $('#num-max-backlogged').text(max_backlogged);
    });
})();