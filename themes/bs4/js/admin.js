var filesadded = "";
var state1 = '#000000';
var state2 = '#FF0000';

function avatar(ava) {
    for (var i = 1; i < 17; i++) {
        var thiscell = document.getElementById('avatar' + i);
        if (ava == i) {
            createplyr.av.value = i;
            thiscell.style.backgroundColor = state2;
        } else {
            thiscell.style.backgroundColor = state1;
        }
    }
}

function checkthis(msg) {
    var answer = confirm(msg);
    if (answer) {
        return true;
    } else {
        return false;
    }
}

function games() {
    var url = document.location.href;
    var xend = url.lastIndexOf("/") + 1;
    var base_url = url.substring(0, xend);
    thisurl = base_url + 'live_games.php';
    checkloadfile(thisurl, "js");
    setTimeout("games()", 3000);
}

function selectgame(url) {
    window.location.href = url;
}

function changeview(url) {
    window.location.href = url;
}

function newavatar(av) {
    var url = 'myplayer.php?newavatar=' + av;
    window.location.href = url;
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
        var fileref = document.createElement('script');
        fileref.setAttribute("type", "text/javascript");
        fileref.setAttribute("src", filename);
    } else if (filetype == "css") {
        var fileref = document.createElement("link");
        fileref.setAttribute("rel", "stylesheet");
        fileref.setAttribute("type", "text/css");
        fileref.setAttribute("href", filename);
    }
    if (typeof fileref != "undefined") document.getElementsByTagName("head")[0].appendChild(fileref);
}

function createfile(filename, filetype) {
    if (filetype == "js") {
        var fileref = document.createElement('script');
        fileref.setAttribute("type", "text/javascript");
        fileref.setAttribute("src", filename);
    }
    return fileref;
}

function replacefile(oldfilename, newfilename, filetype) {
    var targetelement = (filetype == "js") ? "script" : (filetype == "css") ? "link" : "none";
    var targetattr = (filetype == "js") ? "src" : (filetype == "css") ? "href" : "none";
    var allsuspects = document.getElementsByTagName(targetelement);
    for (var i = allsuspects.length; i >= 0; i--) {
        if (allsuspects[i] && allsuspects[i].getAttribute(targetattr) != null && allsuspects[i].getAttribute(targetattr).indexOf(oldfilename) != -1) {
            var newelement = createfile(newfilename, filetype);
            allsuspects[i].parentNode.replaceChild(newelement, allsuspects[i]);
        }
    }
}