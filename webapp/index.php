<?php
//header('Pragma: no-cache');
//header('Expires: Fri, 30 Oct 1998 14:19:41 GMT');
//header('Cache-Control: no-cache, must-revalidate');

if (function_exists("date_default_timezone_set")) {
  @date_default_timezone_set('America/Chicago');
}

function myErrorHandler($errno, $errstr, $errfile, $errline)
{
  $errnoStr = "$errno";
  switch ($errno) {
  case E_ERROR:   $errnoStr = 'E_ERROR'; break;
  case E_WARNING: $errnoStr = 'E_WARNING'; break;
  case E_NOTICE:  $errnoStr = 'E_NOTICE'; break;
  default:        $errnoStr = "E_$errno"; break;
  }
  if ('' != $errnoStr) {
    //$tmp['typ'] = $errnoStr;
    //$tmp['msg'] = $errstr;
    //$tmp['loc'] = "$errfile @ $errline";
    //$GLOBALS["errHandler"][] = $tmp;
    log("$errnoStr / $errstr / $errfile @ $errline");
  }
  /* Don't execute PHP internal error handler */
  return true;
}
set_error_handler("myErrorHandler");
error_reporting(E_ALL);
ini_set('display_errors', '1');



global $PHP_SELF;
if (!$PHP_SELF) {
    $PHP_SELF    = $_SERVER['PHP_SELF'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="keywords" content="" />
    <meta name="description" content="" />
    <title>Pointwise Web App Demo</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <link rel='stylesheet' type='text/css' href='pw.css' />
    <!--
    <script src='path/to/script.js' type='text/javascript'></script>
    -->
</head>
<body>
<div id='app'>
    <ul id='menu'>
        <li>Shape <select id='SHAPE' class='evtChange'>
          <option value='square'>Square</option>
          <option value='circle'>Circle</option>
          <option value='triangle'>Triangle</option>
          <option value='cube'>Cube</option>
        </select></li>
        <li>View <select id='SET_VIEW' class='evtChange'>
          <option value='iso'>Isometric</option>
          <option value='+X'>+X</option>
          <option value='-X'>-X</option>
          <option value='+Y'>+Y</option>
          <option value='-Y'>-Y</option>
          <option value='+Z'>+Z</option>
          <option value='-Z'>-Z</option>
        </select></li>
        <li id='GET_VIEW' class='evtClick'>Get View</li>
        <li id='GET_VERSION' class='evtClick'>Get Pointwise Version</li>
        <li id='IMAGE_FORMATS' class='evtClick'>Get Image Formats</li>
        <li id='REFRESH' class='evtClick'>Refresh</li>
    </ul>
    <div id='display_panel'>
        <img id='DISPLAY' src='display.png' class='evtClick evtMouseMove evtMouseOut' />
        <div id='status_bar'>
            <div id='mouse_xy'>X=<span id='mouse_x'>x</span> Y=<span id='mouse_y'>y</span></div>
        </div>
    </div>
    <div id='log_panel'>
        <pre id='log'></pre>
        <div class='buttonBar'>
            <button id='CLEAR_MSGS' class='evtClick'>Clear Messages</button>
            <input type='checkbox' id='DEBUG_GLYPH'><label> Debug Glyph Client</label>
        </div>
    </div>
</div>


<script type="text/javascript">
var prevMtime = 0;
var mie = (navigator.appName == "Microsoft Internet Explorer");

$.ajaxSetup({
    async: false  // make all ajax/post calls synchronous
});

// add DOM event handlers
$(document).ready(function(){

    $('.evtClick').click(function(domEvt){
        domEvt.preventDefault();
        dispatchEvent(makeEvent($(this).attr('id'), domEvt));
        return false;
    });

    $('.evtMouseMove').mousemove(function(domEvt){
        dispatchEvent(makeEvent($(this).attr('id'), domEvt));
        return false;
    });

    $('.evtMouseOut').mouseout(function(domEvt){
        dispatchEvent(makeEvent($(this).attr('id'), domEvt));
        return false;
    });

    $('.evtChange').change(function(domEvt){
        domEvt.preventDefault();
        evt = makeEvent($(this).attr('id'), domEvt);
        evt.optSelected = [];
        if ('multiple' == $('#' + evt.id).attr('multiple')) {
            evt.optIsMultiple = true;
            $('#' + evt.id + ' option:selected').each(function() {
                evt.optSelected.push($( this ).text());
            });
        }
        else {
            evt.optIsMultiple = false;
            evt.optSelected = $('#' + evt.id + ' option:selected').attr('value');
        }
        dispatchEvent(evt);
        return false;
    });

    // init widget status
    $('#DISPLAY').mouseout();
    $('#SHAPE').change();
    $('#SET_VIEW').change();
});


function makeEvent(id, domEvt) {
    //log(inFunction() + ' id=' + id + ' domEvt=' + JSON.stringify(domEvt));
    // handle "id:subId" construct
    idToks = id.split(':');
    evt = {
        'type':         domEvt.type,
        'id':           idToks.shift(), // first component is always id
        'debugGlyph':   ($('#DEBUG_GLYPH').prop('checked') ? 1 : 0)
    }
    // if any toks remaining, set subId.
    if (idToks.length != 0) {
        evt.subId = idToks.join(':');
    }
    // grab mouse location
    if (!mie) {
        evt.x = domEvt.pageX;
        evt.y = domEvt.pageY;
    }
    else {
        evt.x = event.clientX + document.body.scrollLeft;
        evt.y = event.clientY + document.body.scrollTop;
    }
    //evt.mie = mie;
    //evt.typeofEvent = typeof event;
    //evt.typeofE = typeof domEvt;

    //log(inFunction() + ' evt=' + JSON.stringify(evt));
    return evt;
}


function dispatchEvent(evt)
{
    //log(inFunction() + ' evt=' + JSON.stringify(evt));
    if (callEventHandler(evt, evt.id + '_' + evt.type)) {
        // event handled by WIDGETID_evtType(), stop processing
    }
    else if (callEventHandler(evt, evt.id + '_$')) {
        // event handled by WIDGETID_$(), stop processing
    }
    else if (callEventHandler(evt, '$_' + evt.type)) {
        // event handled by $_evtType(), stop processing
    }
    else if (callEventHandler(evt, '$_$')) {
        // event handled by $_$(), stop processing
    }
    else {
        log(inFunction() + ' UNHANDLED evt=' + JSON.stringify(evt));
    }
}


function callEventHandler(evt, handler) {
    evt.handler = handler;
    //log(inFunction() + ' evt=' + JSON.stringify(evt));
    if (typeof window[handler] == 'function') {
        //log(inFunction() + ' CALL handler={' + handler + '}');
        // Call event handler. Return true to stop further dispatch
        return true === window[handler](evt);
    }
    // not handled
    evt.handler = null;
    //log(inFunction() + ' NOP evt=' + JSON.stringify(evt));
    return false;
}


// Generic click event handler
function $_click(evt) {
    //log(inFunction() + ' evt=' + JSON.stringify(evt));
    data = sendCmd(evt);
    return true; // stop dispatch
}


function SHAPE_change(evt) {
    //log(inFunction() + ' evt=' + JSON.stringify(evt));
    data = sendCmd(evt);
    return true; // stop dispatch
}


function SET_VIEW_change(evt) {
    //log(inFunction() + ' evt=' + JSON.stringify(evt));
    data = sendCmd(evt);
    return true; // stop dispatch
}


function CLEAR_MSGS_click(evt) {
    $('#log').empty();
    //log(inFunction() + ' evt=' + JSON.stringify(evt));
    return true; // stop dispatch
}


function DISPLAY_mousemove(evt) {
    //log(inFunction() + ' evt=' + JSON.stringify(evt));
    $('#mouse_x').text('' + evt.x);
    $('#mouse_y').text('' + evt.y);
    $('#mouse_xy').show();
    return true; // stop dispatch
}


function DISPLAY_mouseout(evt) {
    $('#mouse_xy').hide();
    return true; // stop dispatch
}


var sendCmdData = null;

function sendCmd(cmd) {
    //log(inFunction() + ' cmd=' + JSON.stringify(cmd));
    $.post(
        'event.php', cmd
    )
    .done(function(data, stat, jqXHR) {
        sendCmdData = JSON.parse(data);
        //log("DONE ret=" + sendCmdData['ret']);
        sendCmdData['glyph'].forEach(function(msg){
            log('g> ' + msg);
        });
        checkDisplay(sendCmdData);
    })
    .fail(function(jqXHR, stat, errorThrown) {
        log('FAIL:' + stat + ':' + jqXHR.responseText);
        sendCmdData = null;
    })
    //.always(function(jqXHRorData, stat, errThrownOrJqXHR) {
    //    //alert("finished");
    //});
    //log(inFunction() + ' sendCmdData=' + JSON.stringify(sendCmdData));
    if (true && (null !== sendCmdData) && (null !== sendCmdData.ret)) {
        log(cmd.id + ' ret={' + sendCmdData.ret + '}');
    }
    return sendCmdData;
}


function checkDisplay(data) {
    // Should probably make this a timer and delay the img update
    //log(inFunction() + ' BEFORE data=' + JSON.stringify(data) + ' prevMtime(' + prevMtime + ' != ' + data.mtime + ')data.mtime');
    if (prevMtime != data.mtime) {
        $('#DISPLAY').attr('src', 'display.png?ts=' + data.mtime);
        prevMtime = data.mtime;
    }
    //log(inFunction() + ' AFTER data=' + JSON.stringify(data) + ' prevMtime(' + prevMtime + ' != ' + data.mtime + ')data.mtime');
}


//setInterval(
//    function TIMER(){
//        //log(inFunction() + "\n");
//        onWidgetClick('REFRESH');
//    }
//    , 2000);


function log(msg, newline=true) {
    $('#log').append(msg + (newline ? "\n" : ''));
}


function inFunction() {
    return /function (.*?)\(/.exec(inFunction.caller.toString())[1];
}

</script>
</body>
</html>
