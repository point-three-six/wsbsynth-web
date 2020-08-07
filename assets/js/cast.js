window['__onGCastApiAvailable'] = function(isAvailable) {
  if (isAvailable) {
    init();
  }
};

function init(){
    console.log("init") 

    cast.framework.CastContext.getInstance().setOptions({
        receiverApplicationId: "58558A08",
        autoJoinPolicy: chrome.cast.AutoJoinPolicy.ORIGIN_SCOPED
    });

    var player = new cast.framework.RemotePlayer();
    var playerController = new cast.framework.RemotePlayerController(player);

    playerController.addEventListener(
        cast.framework.RemotePlayerEventType.IS_CONNECTION_CHANGED, function() {
            console.log("connection changed");
            console.log(cast.framework.CastContext.getInstance().getSessionState());
    });

    playerController.playOrPause();
}