/*global $  */


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

var dragstate = {
    linkmdown: false,
    dragmdown: false,
    addedVia: false,
    dragstart: {
        x: -1,
        y: -1
    },
    dragstop: {
        x: -1,
        y: -1
    },
    dragitem: '',
    dragoffset: {
        x: 0,
        y: 0
    }
};

var interactmode = '';

function getMousePosition(event)
{
    var x, y;

    x = event.pageX || (event.clientX
        + (document.documentElement.scrollLeft || document.body.scrollLeft)) || 0;
    y = event.pageY || (event.clientY
        + (document.documentElement.scrollTop || document.body.scrollTop)) || 0;

    return {
        x: x,
        y: y
    };
}

var WMEditor = {
    map: {
        valid: 0
    },             // the downloaded JSON data
    mapfile: '',   // which file we're working with
    lastserial: 0, // last serial number for data synchronisation
    node1: '',     // selected node for link creation
    node2: '',     // second node for link creation
    act_node_add: function() { },
    act_node_add_click: function() { },
    act_link_add: function() { },
    act_link_add_click1: function() { },
    act_link_add_click2: function() { },
    syncnodes: function()
    {
        var node, nodeid, existing, origin_x, origin_y, newx, newy;

        origin_x = parseInt(jQuery('#existingdata').css('left'), 10);
        origin_y = parseInt(jQuery('#existingdata').css('top'), 10);

        // Destroy dead nodes as appropriate
        jQuery('img.mapnode').each(function(i)
        {
            var myname = jQuery(this).attr('id');
            myname = myname.replace(/^mapnode_/, '');

            if (map.nodes[myname]) {
            // it still exists, keep it around
            }
            else {
                jQuery(this).remove();
            }
        });

        // now go through the list and move around or add new ones...
        for (node in WMEditor.map.nodes) {
            if (WMEditor.map.nodes[node].name !== 'DEFAULT') {
                nodeid = 'mapnode_' + WMEditor.map.nodes[node].name;
                existing = jQuery('img#' + nodeid);

                if (existing.size() === 0) {
                    jQuery('#nodecontainer').append(
                        '<img class="mapnode draggable" src="editcache/'
                        + WMEditor.map.nodes[node].iconcachefile + '" id="' + nodeid
                            + '"/>');
                    existing = jQuery('img#' + nodeid);
                }
                // one way or another, by here we have a node, I hope.

                newx = origin_x + WMEditor.map.nodes[node].x;
                newy = origin_y + WMEditor.map.nodes[node].y;

                existing.css({
                    position: 'absolute',
                    left: newx + "px",
                    top: newy + "px",
                    'z-index': 30
                });
            }
        }
    },
    syncvias: function()
    {
        var link, vs, newx, newy, origin_x, origin_y, existing, via_id, i;

        // something here needs to clear existing VIAs

        origin_x = parseInt(jQuery('#existingdata').css('left'), 10);
        origin_y = parseInt(jQuery('#existingdata').css('top'), 10);

        jQuery('.deadvia').remove();

        for (link in WMEditor.map.links) {
            if (WMEditor.map.links[link].name !== 'DEFAULT') {
                console.log("LINK " + link);

                if (WMEditor.map.links[link].via.length > 0) {
                    console.log(link + ' has VIAs');
                    vs = WMEditor.map.links[link].via;

                    for (i = 0; i < vs.length; i += 1) {
                        console.log('VIA ' + vs[i][0] + ',' + vs[i][1]);
                        via_id = 'mapvia_' + link + '_via_' + i;
                        existing = jQuery('img#' + via_id);

                        if (existing.size() === 0) {
                            jQuery('#nodecontainer').append(
                                '<img class="mapvia draggable" src="editor-resources/via-marker.png" id="'
                                + via_id + '"/>');
                            existing = jQuery('img#' + via_id);
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
    },

    // Synchronise the JSON data with the DOM representation
    syncmap: function()
    {
        // only do anything if the data is valid
        jQuery('#busy').show();

        if (WMEditor.map.valid === 1) {
            WMEditor.syncnodes();
            WMEditor.syncvias();
        }
        jQuery('#busy').hide();
    },

    // Fetch the JSON data from the server for this map
    loadmap: function()
    {
        console.log('Fetching JSON.');

        $.getJSON("editor-backend.php", {
            map: WMEditor.mapfile,
            "cmd": "dump_map",
            "serial": WMEditor.lastserial
        }, function(json)
        {
            console.log('Inside JSON function');

            if (json.valid === 1) {
                WMEditor.map = json;

                console.log('Fetching bg image data.');
                jQuery('#existingdata').attr('src', json.map.mapcache + "?s=" + json.serial);
                WMEditor.lastserial = json.serial;
                // jQuery('#existingdata').attr('src',json.map.mapcache);
                $.get('editor-backend.php', {
                    map: WMEditor.mapfile,
                    cmd: "imagemap"
                }, function(cont)
                {
                    jQuery('map#weathermap_imap').empty();
// jQuery('map#weathermap_imap').html(cont);
// Getting medieval here - jQuery doesn't seem to be interested in Imagemaps??
                    var oldskool = document.getElementById('weathermap_imap');
                    oldskool.innerHTML = cont;

                    reapplyLinkEvents();
                });
                console.log('about to syncmap()');
                WMEditor.syncmap();
            } else {
                console.log('Failed to get good JSON');
                alert('Got invalid JSON map data');
            }
            console.log('Leaving JSON function');
        });
        console.log('Done with map_refresh()');
    },

    // force a refresh of the map
    refresh: function()
    {
        WMEditor.loadmap();
        WMEditor.syncmap();
    }
};

function reapplyLinkEvents()
{
    // unbind, then re-apply all events for links.
    // is this actually necessary?

    jQuery('area[@id^=LINK:]').unbind();

    jQuery('area[@id^=LINK:]').mousedown(function(ev)
    {
        var pos;

        console.log('LINK mousedown');
        dragstate.linkmdown = true;
        // retrieve positioning properties
        pos = getMousePosition(ev);
        dragstate.dragstart.x = pos.x;
        dragstate.dragstart.y = pos.y;
        dragstate.dragitem = jQuery(this).attr('id');
        return(false);
    });

    jQuery('area[@id^=LINK:]').mousemove(function()
    {
        if (dragstate.linkmdown) {
            return(false);
        }
        return(true);
    });

    jQuery('area[@id^=LINK:]').mouseup(function(ev)
    {
        var pos, dx, dy;
        // verify if we've moved much since the down - if we did, it's not a click
        pos = getMousePosition(ev);
        dragstate.dragstop.x = pos.x;
        dragstate.dragstop.y = pos.y;

        dx = Math.abs(dragstate.dragstop.x - dragstate.dragstart.x);
        dy = Math.abs(dragstate.dragstop.y - dragstate.dragstart.y);

        if ((dx + dy) < 4) {
            // treat this is a click - we're still inside the link, and we didn't move far
            alert('click on ' + dragstate.dragitem);
        } else {
            dragstate.dragitem = '';
            dragstate.dragstart = {
                x: -1,
                y: -1
            };

            dragstate.dragstop = {
                x: -1,
                y: -1
            };

            if (dragstate.addedVia) {
                jQuery('#newvia').remove();
                dragstate.addedVia = false;
            }
        }

        console.log('LINK mouseup');
        dragstate.linkmdown = false;
    });
}

function reapplyDraggableEvents()
{
    var origin_x, origin_y;

    jQuery('.draggable').unbind();

    jQuery('.draggable').mousedown(function(ev)
    {
        var pos, w, h, t, l;

        dragstate.dragmdown = true;
        console.log('mousedown');
        pos = getMousePosition(ev);
        dragstate.dragstart.x = pos.x;
        dragstate.dragstart.y = pos.y;
        dragstate.dragitem = jQuery(this).attr('id');

        w = jQuery(this).width();
        h = jQuery(this).height();
        t = parseInt(jQuery(this).css('top'), 10);
        l = parseInt(jQuery(this).css('left'), 10);

        dragstate.dragoffset.x = -(w / 2);
        dragstate.dragoffset.y = -(h / 2);

        return(false);
    });
    jQuery('.draggable').mousemove(function(ev) { });
    jQuery('.draggable').mouseup(function(ev)
    {
        var pos, dx, dy;

        console.log('mouseup');
        dragstate.dragmdown = false;
        // verify if we've moved much since the down - if we did, it's not a click
        pos = getMousePosition(ev);
        dragstate.dragstop.x = pos.x;
        dragstate.dragstop.y = pos.y;

        dx = Math.abs(dragstate.dragstop.x - dragstate.dragstart.x);
        dy = Math.abs(dragstate.dragstop.y - dragstate.dragstart.y);

        if ((dx + dy) < 4) {
            // treat this is a click - we're still inside the link, and we didn't move far
            alert('click on ' + dragstate.dragitem);
        } else {
            origin_x = parseInt(jQuery('#existingdata').css('left'), 10);
            origin_y = parseInt(jQuery('#existingdata').css('top'), 10);

            if (dragstate.dragitem.slice(0, 8) === 'mapnode_') {
                dragstate.dragitem = dragstate.dragitem.slice(8,
                    dragstate.dragitem.length);
                $.getJSON('editor-backend.php', {
                    map: mapfile,
                    cmd: "move_node",
                    x: (pos.x - origin_x),
                    y: (pos.y - origin_y),
                    nodename: dragstate.dragitem
                }, function() { map_refresh(); });
            }

            if (dragstate.dragitem.slice(0, 7) === 'mapvia_') {
                dragstate.dragitem = dragstate.dragitem.slice(7,
                    dragstate.dragitem.length);
                $.getJSON('editor-backend.php', {
                    map: mapfile,
                    cmd: "move_via",
                    x: (pos.x - origin_x),
                    y: (pos.y - origin_y),
                    vianame: dragstate.dragitem
                }, function() { map_refresh(); });
            }

            dragstate.dragitem = '';
            dragstate.dragstart = {
                x: -1,
                y: -1
            };

            dragstate.dragstop = {
                x: -1,
                y: -1
            };

            dragstate.addedVia = false;
        }
        return(false);
    });
}

function syncmap()
{
    var i, existing, newx, newy, node, link, origin_x, origin_y, nodeid, via_id, vs;

    jQuery('#busy').show();

    // first, clear out the NODES that have disappeared...

    if (1 === 0) {
        // this doesn't flicker as much as you might expect
        jQuery('img.mapnode').remove();
    } else {
        // this just seems to die for some reason.
        jQuery('img.mapnode').each(function(i)
        {
            var myname = jQuery(this).attr('id');
            myname = myname.replace('mapnode_', '');

            // alert(myname);
            if (map.nodes[myname])
            //if(1==0)
            {
            // it still exists, keep it around
            }
            else {
                jQuery(this).remove();
            }
        });
    }

    origin_x = parseInt(jQuery('#existingdata').css('left'), 10);
    origin_y = parseInt(jQuery('#existingdata').css('top'), 10);

    // now go through the list and move around or add...
    for (node in map.nodes) {
        if (map.nodes[node].name !== 'DEFAULT') {
            nodeid = 'mapnode_' + map.nodes[node].name;
            existing = jQuery('img#' + nodeid);

            if (existing.size() !== 0) {
            //alert('The node already exists called ' + nodeid);
            }
            else {
                jQuery('#nodecontainer').append(
                    '<img class="mapnode draggable" src="editcache/'
                    + map.nodes[node].iconcachefile + '" id="' + nodeid + '"/>');
                existing = jQuery('img#' + nodeid);
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

    jQuery('.deadvia').remove();

    for (link in map.links) {
        if (map.links[link].name !== 'DEFAULT') {
            console.log("LINK " + link);

            if (map.links[link].via.length > 0) {
                console.log(link + ' has VIAs');
                vs = map.links[link].via;

                for (i = 0; i < vs.length; i += 1) {
                    console.log('VIA ' + vs[i][0] + ',' + vs[i][1]);
                    via_id = 'mapvia_' + link + '_via_' + i;
                    existing = jQuery('img#' + via_id);

                    if (existing.size() !== 0) { }
                    else {
                        jQuery('#nodecontainer').append(
                            '<img class="mapvia draggable" src="editor-resources/via-marker.png" id="'
                            + via_id + '"/>');
                        existing = jQuery('img#' + via_id);
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

    jQuery('#busy').hide();
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

        if (parseInt(json.valid, 10) === 1) {
            map = json;

            console.log('Fetching bg image data.');
            jQuery('#existingdata').attr('src', json.map.mapcache + "?s=" + json.serial);
            lastserial = json.serial;
            // jQuery('#existingdata').attr('src',json.map.mapcache);
            $.get('editor-backend.php', {
                map: mapfile,
                cmd: "imagemap"
            }, function(cont)
            {
                jQuery('map#weathermap_imap').empty();
// jQuery('map#weathermap_imap').html(cont);
// Getting medieval here - jQuery doesn't seem to be interested in Imagemaps??
                var oldskool = document.getElementById('weathermap_imap');
                oldskool.innerHTML = cont;

                reapplyLinkEvents();
            });
            console.log('about to syncmap()');
            syncmap();
        } else {
            console.log('Failed to get good JSON');
            alert('Got invalid JSON map data');
        }
        console.log('Leaving JSON function');
    });
    console.log('Done with map_refresh()');
}

function openmap(mapname)
{
    console.log('Opening map: ' + mapname);
    WMEditor.mapfile = mapname;
    mapfile = mapname;

    jQuery('#welcome').hide();
    jQuery('#filepicker').hide();
    jQuery('#toolbar').show();
    jQuery('#themap').show();
    jQuery('#busy').hide();

    jQuery('img.mapnode').remove();
    jQuery('img.mapvia').remove();

    jQuery('#filename').html(mapname);

    console.log('Refreshing map: ' + mapname);

    map_refresh();
// WMEditor.loadmap();
}

function showpicker()
{
    jQuery('#welcome').hide();
    jQuery('#filepicker').show();
    jQuery('#toolbar').hide();
    jQuery('#themap').hide();
    jQuery('#busy').show();

    jQuery('#filelist').empty();
    jQuery('#filelist').append(
        '<li id="status"><em><img src="editor-resources/activity-indicator.gif">Fetching File List...</em></li>');

    $.getJSON("editor-backend.php", {
        map: '',
        cmd: "maplist"
    }, function(json)
    {
        var locked, i, imax;

        if (json.status === 'OK') {
            i = 0;
            imax = json.files.length;

            jQuery('#filelist').empty();

            for (i = 0; i < imax; i += 1) {
                locked = '';

                if (json.files[i].locked === 1) {
                    locked
                        = '<img src="editor-resources/lock.png" alt="Read-Only file" title="Read-Only file" />';
                }
                jQuery('#filelist').append('<li><a>' + locked + '<span>' + json.files[i].file
                    + '</span> <em>' + json.files[i].title + '</em></a></li>');
            }
            console.log("Built list");
            jQuery('#filelist li a').click(function()
            {
                var filename = jQuery(this).children("span").text();
                console.log('About to call openmap()');
                openmap(filename);
            });
            console.log("Added Actions");
        } else {
            console.log("list not OK - " + json.status);
        }
    });
}

function nodeadd()
{
    console.log('Node Add - Initial');
    interactmode = 'nodeadd';
    jQuery('#existingdata').click(function(ev)
    {
        var x, y;

        x = ev.pageX - parseInt(jQuery('#existingdata').css('left'), 10);
        y = ev.pageY - parseInt(jQuery('#existingdata').css('top'), 10);
        console.log('Ready to add a new node at ' + x + ',' + y);
        return(false);
    });
}

function linkadd() { console.log('Link Add - Initial'); }

jQuery(document).ready(function()
{
    jQuery("body").ajaxStart(function() { jQuery(this).css({
        border: 'red 2px solid'
    }); });

    jQuery("body").ajaxStop(function() { jQuery(this).css({
        border: 'none'
    }); });

    jQuery('#welcome').show();
    jQuery('#filepicker').hide();
    jQuery('#toolbar').hide();
    jQuery('#themap').hide();
    jQuery('#busy').hide();

    jQuery('#welcome').click(function() {

    showpicker(); });

    // handle the release, which may not be over the original object anymore
    jQuery(document).mouseup(function(ev)
    {
        var pos, now, origin_x, origin_y, linkname;

        if (dragstate.linkmdown === true) {
            console.log("LINK mouseup");
            // retrieve positioning properties
            pos = getMousePosition(ev);
            dragstate.dragstop.x = pos.x;
            dragstate.dragstop.y = pos.y;
            dragstate.linkmdown = false;

            //	jQuery('#log').append('That was a drag. ');
            if (dragstate.addedVia) {
                // give the temporary VIA a better name
                now = new Date();
                jQuery('#newvia').attr('class', 'deadvia');
                jQuery('#newvia').attr('id', 'via_' + now.getTime());
                dragstate.addedVia = false;
                console.log("Solidified ephemeral VIA");

                reapplyDraggableEvents();
                reapplyLinkEvents();
                // XXX - do something to tell the editor serverside
                origin_x = parseInt(jQuery('#existingdata').css('left'), 10);
                origin_y = parseInt(jQuery('#existingdata').css('top'), 10);
                linkname = dragstate.dragitem.slice(5, dragstate.dragitem.length);
                $.getJSON('editor-backend.php', {
                    map: mapfile,
                    cmd: "add_via",
                    x: pos.x - origin_x,
                    y: pos.y - origin_y,
                    linkname: linkname,
                    startx: dragstate.dragstart.x,
                    starty: dragstate.dragstart.y
                }, function() { map_refresh(); });
            }
        }
    });

    jQuery(document).mousemove(function(ev)
    {
        var x, y, theItem, pos;

        if (dragstate.linkmdown || dragstate.dragmdown) {
            pos = getMousePosition(ev);

            if (dragstate.dragmdown) {
                theItem = jQuery('#' + dragstate.dragitem);
            }

            if (dragstate.linkmdown) {
                theItem = jQuery('#newvia');

                if (!dragstate.addedVia) {
                    console.log("Created ephemeral VIA");
                    // we just left the reservation, and we're still dragging.
                    // - time to create a little marker
                    jQuery('#nodecontainer').append(
                        '<img src="editor-resources/via-marker.png" id="newvia" class="viamarker draggable">');
                    theItem = jQuery('#newvia');
                    dragstate.dragoffset.x = -theItem.width() / 2;
                    dragstate.dragoffset.y = -theItem.height() / 2;
                    dragstate.addedVia = true;
                }
            }
            x = pos.x + dragstate.dragoffset.x;
            y = pos.y + dragstate.dragoffset.y;
            theItem.css({
                left: x,
                top: y
            });
        }
    });

    jQuery('#btn_refresh').click(function() { map_refresh(); });
    jQuery('#btn_selectfile').click(function() { showpicker(); });

    jQuery('#btn_addnode').click(function() { nodeadd(); });
    jQuery('#btn_addlink').click(function() { linkadd(); });

    reapplyDraggableEvents();
    reapplyLinkEvents();
});