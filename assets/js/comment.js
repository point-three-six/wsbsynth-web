$(document).ready(function(){
    var playing = false;
    var loaded = false;

    function play(){
        document.getElementById('audio').play();
    }
    
    function pause(){
        document.getElementById('audio').pause();
    }

    function audio_loaded(){
        loaded = true;

        if(playing){
            play();
        }

        console.log('loaded')
    }

    function audio_complete(){
        playing = false;
        $(".play_button").removeClass("paused");
    }

    function toggle(){
        playing = !playing;

        if(playing){
            play();
        } else {
            pause();
        }

        $(".play_button").toggleClass("paused");
    }

    $(document).on('keyup', function(e){
        if(e.which == 32){
            e.preventDefault();
            toggle();
        }
    });
    $(".play_button").click(toggle);
    document.getElementById('audio').addEventListener('ended', audio_complete);
});