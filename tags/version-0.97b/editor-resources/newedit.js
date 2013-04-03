// * pixel offsets all over the place
// * via cleanup
// * popup dialogs
// * scales

/*extern $, console */

// the map data structure - updated via JSON from the server
var map = {
    valid: 0
};

var mapfile = '';
var lastserial = 0;

var linkmdown = false;
var dragmdown = false;
var addedVia = false;
var dragstart = {
    x: -1,
    y: -1
};

var dragstop = {
    x: -1,
    y: -1
};

var dragitem = '';
var dragoffset = {
    x: 0,
    y: 0
};

var interactmode = '';

// we queue these up to make our one-at-a-time AJAX call work
//var AJAXRequest = {
//    params: {},
//    send: function()
//    {
//    }
//};

//function printfire() {
//   if (document.createEvent)
//   {
//      printfire.args =  arguments;
//      var ev = document.createEvent("Events");
//      ev.initEvent("printfire", false, true );
//      dispatchEvent(ev);
//   }
//}

function openmap(mapname)
{
    console.log('Opening map: ' + mapname);
    mapfile = mapname;
    $('#welcome').hide();
    $('#filepicker').hide();
    $('#toolbar').show();
    $('#themap').show();
    $('#busy').hide();

    $('img.mapnode').remove();
    $('img.mapvia').remove();

    $('#filename').html(mapname);

    console.log('Refreshing map: ' + mapname);

    map_refresh();
}

function showpicker()
{
    $('#welcome').hide();
    $('#filepicker').show();
    $('#toolbar').hide();
    $('#themap').hide();
    $('#busy').show();

    $('#filelist').empty();
    $('#filelist').append(
        '<li id="status"><em><img src="editor-resources/activity-indicator.gif">Fetching File List...</em></li>');

    $.getJSON("editor-backend.php", {
        map: '',
        cmd: "maplist"
    }, function(json)
    {
        if (json.status === 'OK') {
            var i = 0;
            var imax = json.files.length;
            $('#filelist').empty();

            for (i = 0; i < imax; i++) {
                var locked = '';

                if (json.files[i].locked === 1) {
                    locked
                        = '<img src="editor-resources/lock.png" alt="Read-Only file" title="Read-Only file" />';
                }
                $('#filelist').append('<li><a>' + locked + '<span>' + json.files[i].file
                    + '</span> <em>' + json.files[i].title + '</em></a></li>');
            }
            console.log("Built list");
            $('#filelist li a').click(function()
            {
                var filename = $(this).children("span").text();
                console.log('About to call openmap()');
                openmap(filename);
            });
            console.log("Added Actions");
        } else {
            console.log("list not OK - " + json.status);
        }
    });
}

function syncmap()
{
    $('#busy').show();

    var existing, newx, newy;

    var nodes = map.nodes;
    var links = map.links;

    // first, clear out the NODES that have disappeared...

    if (1 === 0) {
        // this doesn't flicker as much as you might expect
        $('img.mapnode').remove();
    } else {
        // this just seems to die for some reason.
        $('img.mapnode').each(function(i)
        {
            var myname = $(this).attr('id');
            myname = myname.replace('mapnode_', '');

            // alert(myname);
            if (map.nodes[myname])
            //if(1==0)
            {
            // it still exists, keep it around
            }
            else {
                $(this).remove();
            }
        });
    }

    var origin_x = parseInt($('#existingdata').css('left'), 10);
    var origin_y = parseInt($('#existingdata').css('top'), 10);

    // now go through the list and move around or add...
    for (var node in nodes) {
        if (map.nodes[node].name !== 'DEFAULT') {
            var nodeid = 'mapnode_' + map.nodes[node].name;
            existing = $('img#' + nodeid);

            if (existing.size() !== 0) {
            //alert('The node already exists called ' + nodeid);
            }
            else {
                $('#nodecontainer').append(
                    '<img class="mapnode draggable" src="editcache/'
                    + map.nodes[node].iconcachefile + '" id="' + nodeid + '"/>');
                existing = $('img#' + nodeid);
            }
            // one way or another, by here we have a node, I hope.

            newx = origin_x + map.nodes[node].x;
            newy = origin_y + map.nodes[node].y;

            existing.css({
                position: 'absolute',
                left: newx + "px",
                top: newy + "px",
                'z-index': 30
            });
        }
    }

    // something here needs to clear existing VIAs

    $('.deadvia').remove();

    for (var link in links) {
        if (map.links[link].name !== 'DEFAULT') {
            console.log("LINK " + link);

            if (map.links[link].via.length > 0) {
                console.log(link + ' has VIAs');
                var vs = map.links[link].via;

                for (var i = 0; i < vs.length; i++) {
                    console.log('VIA ' + vs[i][0] + ',' + vs[i][1]);
                    var via_id = 'mapvia_' + link + '_via_' + i;
                    existing = $('img#' + via_id);

                    if (existing.size() !== 0) { }
                    else {
                        $('#nodecontainer').append(
                            '<img class="mapvia draggable" src="editor-resources/via-marker.png" id="'
                            + via_id + '"/>');
                        existing = $('img#' + via_id);
                    }
                    newx = origin_x + vs[i][0] - 5;
                    newy = origin_y + vs[i][1] - 5;
                    existing.css({
                        position: 'absolute',
                        left: newx + "px",
                        top: newy + "px",
                        'z-index': 30
                    });

                    console.log("created " + via_id + ' at ' + newx + ',' + newy);
                }
            }
        }
    }

    reapplyDraggableEvents();

    $('#busy').hide();
}

function map_refresh()
{
    console.log('Fetching JSON.');

    $.getJSON("editor-backend.php", {
        map: mapfile,
        "cmd": "dump_map",
        "serial": lastserial
    }, function(json)
    {
        console.log('Inside JSON function');

        if (json.valid === 1) {
            map = json;
            console.log('Loaded JSON for ' + mapfile + ' - about to syncmap()');

            syncmap();
            console.log('Map synced. Fetching bg image data.');
            $('#existingdata').attr('src', json.map.mapcache + "?s=" + json.serial);
            lastserial = json.serial;
            // $('#existingdata').attr('src',json.map.mapcache);
            $.get('editor-backend.php', {
                map: mapfile,
                cmd: "imagemap"
            }, function(cont)
            {
                $('map#weathermap_imap').empty();
// $('map#weathermap_imap').html(cont);
// Getting medieval here - jQuery doesn't seem to be interested in Imagemaps??
                var oldskool = document.getElementById('weathermap_imap');
                oldskool.innerHTML = cont;

                reapplyLinkEvents();
            });
        } else {
            console.log('Failed to get good JSON');
            alert('Got invalid JSON map data');
        }
        console.log('Leaving JSON function');
    });
    console.log('Done with map_refresh()');
}

function getMousePosition(event)
{
    var x = event.pageX || (event.clientX
        + (document.documentElement.scrollLeft || document.body.scrollLeft)) || 0;
    var y = event.pageY || (event.clientY
        + (document.documentElement.scrollTop || document.body.scrollTop)) || 0;
    return {
        x: x,
        y: y
    };
}

function reapplyLinkEvents()
{
    // unbind, then re-apply all events for links.
    // is this actually necessary?

    $('area[@id^=LINK:]').unbind();

    $('area[@id^=LINK:]').mousedown(function(ev)
    {
        console.log('LINK mousedown');
        linkmdown = true;
        // retrieve positioning properties
        var pos = getMousePosition(ev);
        dragstart.x = pos.x;
        dragstart.y = pos.y;
        dragitem = $(this).attr('id');
        return(false);
    });

    $('area[@id^=LINK:]').mousemove(function()
    {
        if (linkmdown) {
            return(false);
        }
        return(true);
    });

    $('area[@id^=LINK:]').mouseup(function(ev)
    {

        // verify if we've moved much since the down - if we did, it's not a click
        var pos = getMousePosition(ev);
        dragstop.x = pos.x;
        dragstop.y = pos.y;

        var dx = Math.abs(dragstop.x - dragstart.x);
        var dy = Math.abs(dragstop.y - dragstart.y);

        if ((dx + dy) < 4) {
            // treat this is a click - we're still inside the link, and we didn't move far
            alert('click on ' + dragitem);
        } else {
            dragitem = '';
            dragstart = {
                x: -1,
                y: -1
            };

            dragstop = {
                x: -1,
                y: -1
            };

            if (addedVia) {
                $('#newvia').remove();
                addedVia = false;
            }
        }

        console.log('LINK mouseup');
        linkmdown = false;
    });
}

function reapplyDraggableEvents()
{
    var origin_x, origin_y;

    $('.draggable').unbind();

    $('.draggable').mousedown(function(ev)
    {
        dragmdown = true;
        console.log('mousedown');
        var pos = getMousePosition(ev);
        dragstart.x = pos.x;
        dragstart.y = pos.y;
        dragitem = $(this).attr('id');
        var w = $(this).width();
        var h = $(this).height();
        var t = parseInt($(this).css('top'), 10);
        var l = parseInt($(this).css('left'), 10);

        dragoffset.x = -(w / 2);
        dragoffset.y = -(h / 2);

        return(false);
    });
    $('.draggable').mousemove(function(ev) { });
    $('.draggable').mouseup(function(ev)
    {
        console.log('mouseup');
        dragmdown = false;
        // verify if we've moved much since the down - if we did, it's not a click
        var pos = getMousePosition(ev);
        dragstop.x = pos.x;
        dragstop.y = pos.y;

        var dx = Math.abs(dragstop.x - dragstart.x);
        var dy = Math.abs(dragstop.y - dragstart.y);

        if ((dx + dy) < 4) {
            // treat this is a click - we're still inside the link, and we didn't move far
            alert('click on ' + dragitem);
        } else {
            if (dragitem.slice(0, 8) === 'mapnode_') {
                origin_x = parseInt($('#existingdata').css('left'), 10);
                origin_y = parseInt($('#existingdata').css('top'), 10);
                dragitem = dragitem.slice(8, dragitem.length);
                $.getJSON('editor-backend.php', {
                    map: mapfile,
                    cmd: "move_node",
                    x: pos.x - origin_x,
                    y: pos.y - origin_y,
                    nodename: dragitem
                }, function() { map_refresh(); });
            }

            if (dragitem.slice(0, 7) === 'mapvia_') {
                origin_x = parseInt($('#existingdata').css('left'), 10);
                origin_y = parseInt($('#existingdata').css('top'), 10);
                dragitem = dragitem.slice(7, dragitem.length);
                $.getJSON('editor-backend.php', {
                    map: mapfile,
                    cmd: "move_via",
                    x: pos.x - origin_x,
                    y: pos.y - origin_y,
                    vianame: dragitem
                }, function() { map_refresh(); });
            }

            dragitem = '';
            dragstart = {
                x: -1,
                y: -1
            };

            dragstop = {
                x: -1,
                y: -1
            };

            addedvia = false;
        }
        return(false);
    });
}

function nodeadd()
{
    console.log('Node Add - Initial');
    interactmode = 'nodeadd';
    $('#existingdata').click(function(ev)
    {
        var x = ev.pageX - parseInt($('#existingdata').css('left'), 10);
        var y = ev.pageY - parseInt($('#existingdata').css('top'), 10);
        console.log('Ready to add a new node at ' + x + ',' + y);
        return(false);
    });
}

function linkadd() { console.log('Link Add - Initial'); }

$(document).ready(function()
{
    $("body").ajaxStart(function() { $(this).css({
        border: 'red 2px solid'
    }); });

    $("body").ajaxStop(function() { $(this).css({
        border: 'none'
    }); });

    $('#welcome').show();
    $('#filepicker').hide();
    $('#toolbar').hide();
    $('#themap').hide();
    $('#busy').hide();

    $('#welcome').click(function() {

    showpicker(); });

    // handle the release, which may not be over the original object anymore
    $(document).mouseup(function(ev)
    {
        if (linkmdown === true) {
            console.log("LINK mouseup");
            // retrieve positioning properties
            var pos = getMousePosition(ev);
            dragstop.x = pos.x;
            dragstop.y = pos.y;
            linkmdown = false;

            //	$('#log').append('That was a drag. ');
            if (addedVia) {
                // give the temporary VIA a better name
                var now = new Date();
                $('#newvia').attr('class', 'deadvia');
                $('#newvia').attr('id', 'via_' + now.getTime());
                addedVia = false;
                console.log("Solidified ephemeral VIA");

                reapplyDraggableEvents();
                reapplyLinkEvents();
                // XXX - do something to tell the editor serverside
                var origin_x = parseInt($('#existingdata').css('left'), 10);
                var origin_y = parseInt($('#existingdata').css('top'), 10);
                var linkname = dragitem.slice(5, dragitem.length);
                $.getJSON('editor-backend.php', {
                    map: mapfile,
                    cmd: "add_via",
                    x: pos.x - origin_x,
                    y: pos.y - origin_y,
                    linkname: linkname,
                    startx: dragstart.x,
                    starty: dragstart.y
                }, function() { map_refresh(); });
            }
        }
    });

    $(document).mousemove(function(ev)
    {
        var theItem;

        if (linkmdown || dragmdown) {
            var pos = getMousePosition(ev);

            if (dragmdown) {
                theItem = $('#' + dragitem);
            }

            if (linkmdown) {
                theItem = $('#newvia');

                if (!addedVia) {
                    console.log("Created ephemeral VIA");
                    // we just left the reservation, and we're still dragging.
                    // - time to create a little marker
                    $('#nodecontainer').append(
                        '<img src="editor-resources/via-marker.png" id="newvia" class="viamarker draggable">');
                    theItem = $('#newvia');
                    dragoffset.x = -theItem.width() / 2;
                    dragoffset.y = -theItem.height() / 2;
                    addedVia = true;
                }
            }
            var x = pos.x + dragoffset.x;
            var y = pos.y + dragoffset.y;
            theItem.css({
                left: x,
                top: y
            });
        }
    });

    $('#btn_refresh').click(function() { map_refresh(); });
    $('#btn_selectfile').click(function() { showpicker(); });

    $('#btn_addnode').click(function() { nodeadd(); });
    $('#btn_addlink').click(function() { linkadd(); });

    reapplyDraggableEvents();
    reapplyLinkEvents();
});