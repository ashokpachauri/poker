<?php 
require '../includes/gen_inc.php'; 

header('Content-Type: text/javascript');

if ( !isset($idr['gamepass']) || empty($idr['gamepass'])):
  die;
endif;
?>
if ('<?php echo WEBSOCKET_ADDR; ?>' === '') {
  alert('Update WebSocket Address in OPS Settings');
  window.location.href = "lobby.php";
}
const _SELF       = 'self';
const _OTHERS     = 'others';
const _EVERYONE   = '*';
const _MOVETIME   = <?php echo MOVETIMER; ?>;
const _SHOWDOWN   = <?php echo SHOWDOWN; ?>;
const _KICKTIMER  = <?php echo KICKTIMER * 60; ?>;
const _DISCONNECT = <?php echo DISCONNECT; ?>;
const _DEBUG      = <?php echo defined('OPS_DEBUG') && OPS_DEBUG === true ? 'true' : 'false'; ?>;

var filesadded        = "";
// let usewebsockets  = <?php echo USEWEBSOCKETS === '0' ? 'true' : 'false'; ?>;
let usewebsockets     = true;
let pushpokerInterval = usewebsockets ? null : setInterval("push_poker()", 1000);
const gameID            = <?php echo $gameID; ?>;
const player            = "<?php echo $plyrname; ?>";
let socket,
    table = {},
    seat = 0,
    bots = [],
    logMsg = '',
    leaveAfter = 0,
    exit = false,
    autoAction, proctorAction, lastSend;

setInterval(function() {
  let seconds  = Math.floor(Date.now() / 1000);
  let lastmove = typeof table.lastmove !== 'undefined' ? table.lastmove : seconds;
  let diff     = seconds - lastmove;
  let comp     = table.hand > 4 && table.hand < 12 ? _MOVETIME + 1 : 10;

  if (diff < comp)
    return;
  
  socketSend({type: "proctor"}, _EVERYONE);
}, 5000);

setTimeout(function()
{
  clearInterval(pushpokerInterval);
  socket = new WebSocket('<?php echo WEBSOCKET_ADDR; ?>?gamepass=<?php echo $idr['gamepass']; ?>&player=' + player);

  socket.onopen = function (e) {
    socketSend({type: "enter", gameID: gameID});
  };

  socket.onclose = function (e) {
    if (exit)
      return;
    
    window.location.reload();
  }

  socket.onmessage = function (e) {
    let data = JSON.parse(e.data);

    if (_DEBUG)
      console.log('down', data.type, data);

    if (typeof(data.table) !== 'undefined') {
      table = data.table;

      if (typeof(table.botgod) !== 'undefined') {
        if (typeof(data.players) !== 'undefined') {
          bots = data.players.filter(function(player) {
            return player.bot;
          }).map(function(player) {
            return player.seat;
          });
        }
      }
    }
    
    if (data.type === 'is-alone')
    {
      setTimeout(function(){
        return socketSend({type: 'alone'}, _EVERYONE);
      }, 100);
    }

    if ( !isAddonActive('tournament-advanced') )
      showLog(data);

    switch (data.type) {
      case 'chat':
        OpsGame.chat(data);
        break;
      
      case 'enter':
        OpsGame.enter(data);
        break;
      
      case 'join':
        OpsGame.join(data);
        break;

      case 'leave':
        OpsGame.leave(data);
        break;
      
      case 'exit':
        exit = true;
        if (data.player === player) {
          socket.close();
          window.location.href = "lobby.php?action=leave";
        }
        break;
      
      case 'start':
        OpsGame.start(data);
        break;

      case 'restart':
        OpsGame.restart(data);
        break;
      
      case 'assign-dealer':
        OpsGame.assignDealer(data);
        break;
      
      case 'assign-chipleader':
        OpsGame.assignChipleader(data);
        break;
      
      case 'post-small-blinds':
        OpsGame.postSmallBlinds(data);
        break;
      
      case 'post-big-blinds':
        OpsGame.postBigBlinds(data);
        break;
      
      case 'deal-cards':
        OpsGame.dealCards(data);
        break;
      
      case 'get-my-cards':
        OpsGame.getMyCards(data);
        break;

      case 'get-my-turn':
        OpsGame.getMyTurn(data);
        break;

      case 'start-timer':
        OpsGame.startTimer(data);
        break;

      case 'player-action':
        OpsGame.playerAction(data);
        break;

      case 'showdown':
        OpsGame.showdown(data);
        break;
      
      case 'finish':
        OpsGame.finish(data);
        break;
      
      case 'restart':
        OpsGame.restart(data);
        break;
      
      case 'alone':
        OpsGame.alone(data);
        break;

      case 'leave-after-finish':
        OpsGame.leaveAfterFinish(data);
        break;
      
      case 'proctor':
        OpsGame.proctor(data);
        break;
      
      case 'bad-table':
        //socket.close();
        break;
    }

    return;
  };
},
500);

const OpsGame = {
  chat: function (data) {
    jQuery("#userchatdiv").append(data.message);
  },
  enter: function (data) {
    jQuery("#tablepot").html(table.pot);

    for (const cardPos in table.cards) {
      const card = table.cards[cardPos];
      jQuery("#card" + cardPos).html(card);
    }

    for (const p in data.players) {
      let plyr = data.players[p];

      jQuery('#player-' + plyr.seat + '-image').html( plyr.avatar );
      jQuery('#player-' + plyr.seat + '-info').html( plyr.info );
      jQuery('#player-' + plyr.seat + '-bet').html( plyr.bet );
      jQuery('#player-' + plyr.seat + '-cards').html( plyr.cards.join("") );

      if (plyr.name !== '' && plyr.name === player)
        seat = parseInt(plyr.seat);
    }

    jQuery("#checkboxes").show();
    jQuery("#buttons").hide();

    if (table.interplay)
    {
      if (table.move === seat)
        socketSend({type: 'get-my-turn'}, _SELF);
      else
        startSeatTimer(table.move, _MOVETIME, table.timeleft, 'no');
    }
    else {
      if (table.dealer === seat)
      {
        runByHand(table.hand);
      }
    }
    loadStartButton();
  },
  join: function (data) {
    leaveAfter = 0;
    jQuery('#player-' + data.player.seat + '-image').html( data.player.avatar );
    jQuery('#player-' + data.player.seat + '-info').html( data.player.info );

    if (data.player.name === player)
      seat = parseInt(data.player.seat);

    loadStartButton();
    jQuery("#checkboxes").show();
  },
  start: function (data) {
    loadStartButton();

    if (table.dealer !== seat)
      return;
    
    setTimeout(function(){
      socketSend({type: 'restart'}, _EVERYONE);
    }, 1000);
  },
  restart: function (data) {
    push_action('show-my-cards');
    jQuery("#checkboxes").show();
    jQuery("#buttons").hide();

    for (let j = 1; j < 6; j++) {
      jQuery("#card" + j).html(table.cards[j]);
    }
    
    for (const i in data.players)
    {
      const player = data.players[i];

      jQuery("#player-" + player.seat + "-image").html(player.avatar);
      jQuery("#player-" + player.seat + "-info").html(player.info);
      jQuery("#player-" + player.seat + "-cards").html(player.cards);
      jQuery("#player-" + player.seat + "-bet").html(player.bet);
    }

    if (table.dealer === seat)
    {
      setTimeout(function(){
        socketSend({type: 'assign-dealer'}, _EVERYONE);
      }, 1000);
    }

    playSound('shuffle');
  },
  assignDealer: function (data) {
    jQuery(".player-dealer").hide();
    jQuery("#player-" + table.dealer + "-dealer").show();
    jQuery("#tablepot").text(table.pot);

    if (leaveAfter === 2) {
      socketSend({type: "leave"});
    }

    if (table.dealer !== seat)
      return;

    setTimeout(function(){
      socketSend({type: 'assign-chipleader'}, _EVERYONE);
    }, 1000);
  },
  assignChipleader: function (data) {
    if (table.dealer !== seat)
      return;

    setTimeout(function(){
      socketSend({type: 'post-small-blinds'}, _EVERYONE);
    }, 1000);
  },
  postSmallBlinds: function (data) {
    jQuery("#tablepot").html(table.pot);
    jQuery("#player-" + data.player.seat + "-info").html(data.player.info);
    jQuery("#player-" + data.player.seat + "-bet").html(data.player.bet);
    playSound('chips');

    if (table.dealer === seat)
    {
      setTimeout(function() {
        socketSend({type: 'post-big-blinds'}, _EVERYONE);
      }, 1000);
    }
  },
  postBigBlinds: function (data) {
    jQuery("#tablepot").html(table.pot);
    jQuery("#player-" + data.player.seat + "-info").html(data.player.info);
    jQuery("#player-" + data.player.seat + "-bet").html(data.player.bet);
    playSound('chips');

    if (table.dealer === seat)
    {
      setTimeout(function(){
        socketSend({type: 'deal-cards'}, _EVERYONE);
      }, 1000);
    }

    setTimeout(function(){
      socketSend({type: 'get-my-cards'}, _SELF);
    }, 3000);
  },
  dealCards: function (data) {},
  getMyCards: function (data) {
    for (let i = 0; i < 11; i++)
    {
      if ( !jQuery("#player-" + i + "-info").find(".poker__user-name").length || i == seat )
        continue;
      
      jQuery("#player-" + i + "-cards").html(data.othercards);
    }

    jQuery("#player-" + seat + "-cards").html(data.player.cards.join(""));
    playSound('deal');

    if (table.move === seat) {
      socketSend({type: 'get-my-turn'}, _SELF);
    } else if (table.botgod === seat && bots.includes(table.move)) {
      startSeatTimer(table.move, _MOVETIME, _MOVETIME);

      setTimeout(function(){
        push_action('bot');
      }, 3000);
    }
  },
  getMyTurn: function (data) {
    jQuery("#checkboxes").hide();
    jQuery("#buttons").html(data.buttons).css('display', 'flex');
    socketSend({type: 'start-timer'}, _EVERYONE);
  },
  startTimer: function (data) {
    if (table.move !== seat)
    {
      jQuery("#checkboxes").show();
      jQuery("#buttons").hide();
    }

    for (let i = 1; i < 11; i++) {
      stopSeatTimer(i);
    }

    if (data.timeleft < 1) {
      push_action('auto');
      return;
    }
    startSeatTimer(table.move, _MOVETIME, data.timeleft, 'no');

    if (table.move === seat) {
      let checkedAction = getCheckedAction();

      if (checkedAction) {
        push_action(checkedAction);
        return;
      }

      autoAction = setTimeout(function(){ return push_action('auto'); }, (data.timeleft * 1000));
    }
  },
  playerAction: function (data) {
    clearTimeout(autoAction);

    jQuery("#buttons").hide();
    jQuery("#checkboxes").show();

    for (let i = 0; i < 11; i++) {
      stopSeatTimer(i);
    }

    jQuery("#tablepot").html(table.pot);
    jQuery("#player-" + data.player.seat + "-info").html(data.player.info);
    jQuery("#player-" + data.player.seat + "-bet").html(data.player.bet);

    jQuery(".player-dealer").hide();
    jQuery("#player-" + table.dealer + "-dealer").show();

    switch (data.action) {
      case 'fold':
        playSound('fold');
        jQuery("#player-" + data.player.seat + "-cards").html("");
        break;
      
      case 'check':
        playSound('check');
        break;

      default:
        playSound('chips');
        break;
    }

    if (data.showdown && table.dealer === seat)
    {
      socketSend({type: 'showdown'}, _EVERYONE);
      return;
    }

    startSeatTimer(data.next.seat, _MOVETIME, _MOVETIME);

    if (table.botgod === seat && bots.includes(data.next.seat)) {
      setTimeout(function(){
        push_action('bot');
      }, 3000);
    } else if (data.next.seat === seat) {
      socketSend({type: 'get-my-turn'}, _SELF);
    }

    if (Object.keys(table.cards).length > 0)
    {
      setTimeout(function(){
        if (data.showdown)
          return;

        if (table.cardlog !== false)
          jQuery("#tablelog").html(table.cardlog);

        for (const pos in table.cards) {
          const card = table.cards[pos];
          jQuery("#card" + pos).html(card);
        }

        playSound(table.hand === 6 ? 'flop' : 'card');

        if (!data.showdown)
        {
          startSeatTimer(data.next.seat, _MOVETIME, _MOVETIME);

          if (data.next.seat === seat)
            socketSend({type: 'get-my-turn'}, _SELF);
        }
      }, 1000);
    }
  },
  showdown: function (data) {
    jQuery("#buttons").hide();
    jQuery("#checkboxes").show();

    for (let i = 1; i < 11; i++) {
      stopSeatTimer(i);
    }

    setTimeout(function() {
      socketSend({type: 'finish'}, _EVERYONE);
    }, 1000);

    for (let c in table.cards) {
      let card = table.cards[c];
      
      if (jQuery("#card" + c).find(".poker__table-card").html().trim() !== '')
        continue;

      jQuery("#card" + c).html(card);
    }

    for (const p in data.players) {
      const player = data.players[p];
      stopSeatTimer(player.seat);

      if (player.seat === seat)
        continue;
      
      jQuery("#player-" + player.seat + "-cards").html(player.cards.join(""));
    }
  },
  finish: function (data) {
    for (let i = 1; i < 11; i++) {
      stopSeatTimer(i);
    }

    if (table.dealer !== seat)
    {
      if (leaveAfter === 1) {
        socketSend({type: "leave"});
      }
      return;
    }
    
    if (leaveAfter === 1)
      leaveAfter = 2;
    
    setTimeout(function(){
      socketSend({type: 'restart'}, _EVERYONE);
    }, (_SHOWDOWN * 1000));
  },
  leave: function (data) {
    jQuery('#player-' + data.player.seat + '-bet').html( data.player.bet );
    jQuery('#player-' + data.player.seat + '-image').html( data.player.avatar );
    jQuery('#player-' + data.player.seat + '-info').html( data.player.info );
    jQuery('#player-' + data.player.seat + '-cards').html( data.player.cards.join("") );

    seat = 0;
    loadStartButton();
  },
  alone: function (data) {
    jQuery(".player-dealer").hide();

    for (const p in data.players) {
      let plyr = data.players[p];

      jQuery('#player-' + plyr.seat + '-image').html( plyr.avatar );
      jQuery('#player-' + plyr.seat + '-info').html( plyr.info );
      jQuery('#player-' + plyr.seat + '-bet').html( plyr.bet );
      jQuery('#player-' + plyr.seat + '-cards').html( plyr.cards.join("") );

      if (plyr.name !== '' && plyr.name === player)
        seat = parseInt(plyr.seat);
    }

    jQuery("#tablepot").html(table.pot);

    for (const cardPos in table.cards) {
      const card = table.cards[cardPos];
      jQuery("#card" + cardPos).html(card);
    }

    loadStartButton();
  },
  leaveAfterFinish: function (data) {
    if (data.player.seat !== seat)
      return;
    
    leaveAfter = 1;
    alert("You will be exited once this hand is over.");
  },
  proctor: function (data) {
    jQuery(".player-dealer").hide();
    jQuery("#player-" + table.dealer + "-dealer").show();

    if (table.dealer !== seat)
      return;

    if (data.dealerChanged) {
      runByHand(table.hand);
    }

    if (data.actOnBehalf) {
      push_action('behalf');
    }
  }
};

const resetProctor = function (data) {
  clearTimeout(proctorAction);

  if (seat < 1)
    return;

  proctorAction = setTimeout(function() {
    socketSend({type: "proctor"}, _EVERYONE);
  }, seat * 1000);
}

const runByHand = function (hand) {
  switch (hand) {
    case 0:
      setTimeout(function(){
        socketSend({type: 'restart'}, _EVERYONE);
      }, 1000);
      break;
    
    case 1:
      setTimeout(function(){
        socketSend({type: 'assign-chipleader'}, _EVERYONE);
      }, 1000);
      break;
    
    case 2:
      setTimeout(function(){
        socketSend({type: 'post-small-blinds'}, _EVERYONE);
      }, 1000);
      break;

    case 3:
      setTimeout(function(){
        socketSend({type: 'post-big-blinds'}, _EVERYONE);
      }, 1000);
      break;

    case 4:
      setTimeout(function(){
        socketSend({type: 'deal-cards'}, _EVERYONE);
      }, 1000);
      break;
    
    case 12:
    case 13:
    case 14:
    case 15:
      setTimeout(function(){
        socketSend({type: 'showdown'}, _EVERYONE);
      }, 1000);
      break;
  }
}

const isAddonActive = function (a) {
  return table.addons.includes(a);
};

function playSound(s) {
  let sl = document.getElementById("aud-" + s);
  if ( typeof(sl) === 'undefined' )
    return;

  sl.play();
}

function socketSend(json, to = false) {
  if ( !socket )
    return;
  
  lastSend = json;
  let data = { to: to ? to : _SELF, player: player, gamePass: "<?php echo $idr['gamepass']; ?>", ...json };

  if (_DEBUG)
    console.log('up', data.type, data);
  
  return socket.send(JSON.stringify(data));
}

function showLog(data) {
  let type = typeof(data);

  if (type === 'undefined')
    return;

  let logMsg = type === 'string' ? data : (typeof(data.log) !== 'undefined' ? data.log : '');

  if ( !logMsg.length )
    return;
  
  jQuery("#chatdiv").find(".mCustomScrollBox").remove();

  const logCount = jQuery("#chatdiv").find(".poker__chat-logs-i").length;
  if (logCount > 4) {
    jQuery("#chatdiv").find(".poker__chat-logs-i").eq(0).remove();
  }

  jQuery("#tablelog").html(logMsg);
  jQuery("#chatdiv").append(logMsg);
}

function loadStartButton()
{
  let names = getPlayerNames();

  if ( table.hand < 0 && names.length >= 2 && names.includes(player) )
    jQuery('#startButton').show();
  else
    jQuery('#startButton').hide();
}

function getPlayerNames() {
  let names = [];

  for (let i = 1; i < 11; i++)
  {
    let nl = jQuery('#player-' + i + '-info');
    let n  = nl.find('.poker__user-name').find('p').text().trim();

    if ( n == '' )
      continue;

    names.push(n);
  }

  return names;
}

jQuery(document).on("change", "#button-checkboxes input", function () {
  jQuery("#button-checkboxes input").not(this).prop("checked", false);
});

const getCheckedAction = function () {
  if (jQuery("#button-checkboxes input:checked").length !== 1 || jQuery("#buttons").css("display") === "none")
    return false;

  let checkedInp = jQuery("#button-checkboxes input:checked");
  let autoActions = checkedInp.attr("value").split("/");
  let firstAction = autoActions[0];
  let secondAction = autoActions[1];
  let firstButton = jQuery(".poker-btn[data-poker-btn=" + firstAction + "]");
  let secondButton = jQuery(".poker-btn[data-poker-btn=" + secondAction + "]");

  if (firstButton.length == 1) {
    checkedInp.prop("checked", false);
    return firstAction;
  }
  else if (secondButton.length == 1) {
    checkedInp.prop("checked", false);
    return secondAction;
  }

  return false;
};

const pushChat = function (e) {
  e.preventDefault();
  let form    = jQuery('.poker__chat-write');
  let input   = form.find('.write-input');
  let message = input.val().trim();

  if (message.length < 1)
      return false;

  input.val('');
  socketSend({type: "self-chat", message: message}, _SELF);
  socketSend({type: "chat", message: message}, _OTHERS);
}

let timerleftTimeout, numberInterval;

function startSeatTimer(seat, total, rem, playSound = 'no') {
    var $seat = $('#player-' + seat);
    var $seatTimer = $seat.find('.poker__user-photo-loader');

    if ($seatTimer.data('running') == 1)
        return false;

    $seatTimer.find('svg').data('total-time', total);
    $seatTimer.find('svg').data('current-time', rem);
    $seatTimer.find('.poker__user-photo-loader-timer').data('time', rem);
    $seatTimer.data('running', 1).show();

    // countdown number display
    var $loaderTimer = $seat.find('.poker__user-photo-loader-timer');
    var startNubmer = $loaderTimer.data('time');
    if ($loaderTimer.length) {
        $loaderTimer.html(startNubmer);
        clearInterval(numberInterval);
        numberInterval = setInterval(function () {
            if (parseInt($seatTimer.data('running')) == 0) {
                clearInterval(numberInterval);
                return;
            }
            startNubmer--;
            $loaderTimer.html(startNubmer);
            if (startNubmer < 0) {
                $seatTimer.data('running', 0).hide();
                clearInterval(numberInterval);
            }
        }, 1000);
    }

    var $loader_svg = $seat.find('.poker__user-photo-loader svg');
    if ($loader_svg.length) {
        var loader_svg_current_time = $loader_svg.data('current-time');
        var loader_svg_total_time = $loader_svg.data('total-time');
        var currentOffset = loader_svg_current_time * 100 / loader_svg_total_time;
        $loader_svg.css('strokeDashoffset', 125 - (currentOffset * 1.25));
        $loader_svg.animate({ 'stroke-dashoffset': 125 }, loader_svg_current_time * 1000, 'linear');
    }

    if (playSound == 'yes') {
        var $minusFive = rem - 5;
        timerleftTimeout = setTimeout(function () {
            document.getElementById("aud-timerleft").play();
        }, ($minusFive * 1000));
    }
}
function stopSeatTimer(seat) {
    var $seat = $('#player-' + seat);
    var $seatTimer = $seat.find('.poker__user-photo-loader');
    var $loader_svg = $seat.find('.poker__user-photo-loader svg');

    $seatTimer.find('svg').data('total-time', 0).data('current-time', 0);
    $seatTimer.data('running', 0).hide();
    clearInterval(numberInterval);
    $loader_svg.stop();
}

function dochatbox() {
  var chatbox = document.getElementById("chatdiv");
  chatbox.scrollTop = chatbox.scrollHeight;
}

function checkEnter(e) {
  var characterCode;

  if (e && e.which) {
    e = e;
    characterCode = e.which;
  } else {
    e = event;
    characterCode = e.keyCode;
  }

  if (characterCode == 13) {
    push_talk();
    return false;
  } else {
    return true;
  }
}

function push_poker() {
  var unixts = document.forms["checkmov"]["lastmove"].value;
  var Xhand = document.forms["checkmov"]["hand"].value;
  var Xmove = document.forms["checkmov"]["tomove"].value;
  var force = 0;
  var url = document.location.href;
  var xend = url.lastIndexOf("/") + 1;
  var base_url = url.substring(0, xend);

  thisurl = base_url + "includes/push_poker.php?ts=" + unixts + "&h=" + Xhand + "&m=" + Xmove + "&f=" + force;
  checkloadfile(thisurl, "js");

  thisurl = base_url + "includes/auto_move.php";
  checkloadfile(thisurl, "js");

  thisurl = base_url + "includes/auto_chat.php";
  checkloadfile(thisurl, "js");

  dochatbox();
}

jQuery(document).on("click", "#leaveButton", function(e) {
  e.preventDefault();
  socketSend({type: "leave"}, _EVERYONE);
});

function push_action(action)
{
  if (usewebsockets)
  {
    clearTimeout(autoAction);
    stopSeatTimer(seat);

    switch (action)
    {
      case 'start':
        table.dealer = player;
        return socketSend({type: 'start'}, _EVERYONE);
        break;

      case 'behalf':
        return socketSend({type: 'player-action', action: 'behalf'}, _EVERYONE);
        break;

      case 'bot':
        return socketSend({type: 'player-action', action: 'bot'}, _EVERYONE);
        break;

      case 'auto':
        jQuery("#buttons").hide();
        jQuery("#checkboxes").show();
        return socketSend({type: 'player-action', action: 'auto'}, _EVERYONE);
        break;

      case 'fold':
        jQuery("#buttons").hide();
        jQuery("#checkboxes").show();
        return socketSend({type: 'player-action', action: 'fold'}, _EVERYONE);
        break;

      case 'check':
        jQuery("#buttons").hide();
        jQuery("#checkboxes").show();
        return socketSend({type: 'player-action', action: 'check'}, _EVERYONE);
        break;

      case 'call':
        jQuery("#buttons").hide();
        jQuery("#checkboxes").show();
        return socketSend({type: 'player-action', action: 'call'}, _EVERYONE);
        break;

      case 'allin':
        jQuery("#buttons").hide();
        jQuery("#checkboxes").show();
        return socketSend({type: 'player-action', action: 'allin'}, _EVERYONE);
        break;

      case 'hide-my-cards':
        jQuery(".poker__hidecards-btn")
          .removeClass("poker__hidecards-btn")
          .addClass("poker__showcards-btn")
          .text("<?php echo __('Show Cards', 'core'); ?>");
        return socketSend({type: 'player-action', action: 'hide-my-cards'}, _SELF);
        break;

      case 'show-my-cards':
        jQuery(".poker__showcards-btn")
          .removeClass("poker__showcards-btn")
          .addClass("poker__hidecards-btn")
          .text("<?php echo __('Hide Cards', 'core'); ?>");
        return socketSend({type: 'player-action', action: 'show-my-cards'}, _SELF);
        break;

      default:
        if (Number.isInteger(action))
        {
          jQuery("#buttons").hide();
          jQuery("#checkboxes").show();
          return socketSend({type: 'player-action', action: 'raise', amount: action}, _EVERYONE);
        }
        return;
        break;
    }
  }

  var url = document.location.href;
  var xend = url.lastIndexOf("/") + 1;
  var base_url = url.substring(0, xend);
  thisurl = base_url + "includes/player_move.php?action=" + action;
  checkloadfile(thisurl, "js");
}
///////////////

function push_talk() {
  var msg = document.talk.talk.value;
  var url = document.location.href;
  var xend = url.lastIndexOf("/") + 1;
  var base_url = url.substring(0, xend);
  thisurl = base_url + "includes/push_chat.php?msg=" + msg;
  checkloadfile(thisurl, "js");
  document.talk.talk.value = "";
}

function sit_down(pos) {
    if ( !usewebsockets )
      return;

    if (isAddonActive('buyin')) {
      buyin_popup(parseInt(pos));
      return;
    }

    socketSend({type: 'join', seat: parseInt(pos)}, _EVERYONE);
    return;
}

function checkloadfile(filename, filetype) {
  if (filesadded.indexOf("[" + filename + "]") == -1) {
    loadfile(filename, filetype);
    filesadded += "[" + filename + "]";
  } else {
    replacefile(filename, filename, filetype);
  }
}

function loadfile(filename, filetype) {
  if (filetype == "js") {
    var fileref = document.createElement("script");
    fileref.setAttribute("type", "text/javascript");
    fileref.setAttribute("src", filename);
  } else if (filetype == "css") {
    var fileref = document.createElement("link");
    fileref.setAttribute("rel", "stylesheet");
    fileref.setAttribute("type", "text/css");
    fileref.setAttribute("href", filename);
  }

  if (typeof fileref != "undefined")
    document.getElementsByTagName("head")[0].appendChild(fileref);
}

function createfile(filename, filetype) {
  if (filetype == "js") {
    var fileref = document.createElement("script");
    fileref.setAttribute("type", "text/javascript");
    fileref.setAttribute("src", filename);
  }

  return fileref;
}

function replacefile(oldfilename, newfilename, filetype) {
  var targetelement =
    filetype == "js" ? "script" : filetype == "css" ? "link" : "none";
  var targetattr =
    filetype == "js" ? "src" : filetype == "css" ? "href" : "none";
  var allsuspects = document.getElementsByTagName(targetelement);

  for (var i = allsuspects.length; i >= 0; i--) {
    if (
      allsuspects[i] &&
      allsuspects[i].getAttribute(targetattr) != null &&
      allsuspects[i].getAttribute(targetattr).indexOf(oldfilename) != -1
    ) {
      var newelement = createfile(newfilename, filetype);
      allsuspects[i].parentNode.replaceChild(newelement, allsuspects[i]);
    }
  }
}
