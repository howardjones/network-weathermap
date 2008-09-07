/*global $  */


// * pixel offsets all over the place
// * via cleanup
// * popup dialogs
// * scales

/*extern $, console */

// the map data structure - updated via JSON from the server
var map = { valid: 0 };

var mapfile = '';
var lastserial = 0;

var dragstate = {
			linkmdown: false,
			dragmdown: false,
			addedVia: false,
			dragstart: {x: -1, y: -1},
			dragstop: {x: -1, y: -1},
			dragitem: '',
			dragoffset: {x: 0, y: 0}
				};

// var linkmdown = false;
// var dragmdown = false;
// var addedVia = false;
// var dragstart = {x: -1, y: -1};
// var dragstop = {x: -1, y: -1};
// var dragitem = '';
// var dragoffset = {x: 0, y: 0};

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

function getMousePosition(event)
{
	var x,y;

	x = event.pageX || (event.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft)) || 0;
	y = event.pageY || (event.clientY + (document.documentElement.scrollTop || document.body.scrollTop)) || 0;
    
	return {x:x, y:y};
}


function reapplyLinkEvents()
{
		// unbind, then re-apply all events for links.
		// is this actually necessary?
		
		$('area[@id^=LINK:]').unbind();

		$('area[@id^=LINK:]').mousedown( function(ev) { 
			var pos;
			
			console.log('LINK mousedown'); 
			dragstate.linkmdown = true;
			// retrieve positioning properties
				pos    = getMousePosition(ev);
				dragstate.dragstart.x = pos.x;
				dragstate.dragstart.y = pos.y;
				dragstate.dragitem = $(this).attr('id');
			return(false);
		});

	$('area[@id^=LINK:]').mousemove( function() { 
			if(dragstate.linkmdown)
			{
				return(false);
			}
			return(true);
		});
  
  	$('area[@id^=LINK:]').mouseup( function(ev) {
		var pos, dx, dy;
		// verify if we've moved much since the down - if we did, it's not a click	
		pos    = getMousePosition(ev);
		dragstate.dragstop.x = pos.x;
		dragstate.dragstop.y = pos.y;

		dx = Math.abs(dragstate.dragstop.x - dragstate.dragstart.x);
		dy = Math.abs(dragstate.dragstop.y - dragstate.dragstart.y);

		if ((dx+dy) < 4)
		{
			// treat this is a click - we're still inside the link, and we didn't move far
			alert('click on ' + dragstate.dragitem);
		}
		else
		{
			dragstate.dragitem = '';
			dragstate.dragstart = {x: -1, y: -1};
			dragstate.dragstop = {x: -1, y: -1};
			if(dragstate.addedVia) { $('#newvia').remove(); dragstate.addedVia=false; }
		}
	
		console.log('LINK mouseup');
		dragstate.linkmdown = false;
		
	} );
}


function reapplyDraggableEvents()
{
    var origin_x, origin_y;

	$('.draggable').unbind();
		
	$('.draggable').mousedown( function(ev) { 
		var pos, w, h, t, l;
	
		dragstate.dragmdown=true; 
		console.log('mousedown');
		pos    = getMousePosition(ev);
		dragstate.dragstart.x = pos.x;
		dragstate.dragstart.y = pos.y;
		dragstate.dragitem = $(this).attr('id');
        
		w = $(this).width(); 
		h = $(this).height();
		t = parseInt($(this).css('top'),10);
		l = parseInt($(this).css('left'),10);
                
		dragstate.dragoffset.x = -(w/2);
		dragstate.dragoffset.y = -(h/2);

		return(false);
		
	 });
	$('.draggable').mousemove( function(ev) {  } );
	$('.draggable').mouseup( function(ev) { 
		var pos ,dx, dy;
		
		console.log('mouseup');	
		dragstate.dragmdown = false; 
		// verify if we've moved much since the down - if we did, it's not a click	
		pos    = getMousePosition(ev);
		dragstate.dragstop.x = pos.x;
		dragstate.dragstop.y = pos.y;

		dx = Math.abs(dragstate.dragstop.x - dragstate.dragstart.x);
		dy = Math.abs(dragstate.dragstop.y - dragstate.dragstart.y);

		if((dx+dy)<4)
		{
			// treat this is a click - we're still inside the link, and we didn't move far
			alert('click on ' + dragstate.dragitem);
		}
		else
		{
			if(dragstate.dragitem.slice(0,8) === 'mapnode_')
			{
				origin_x = parseInt($('#existingdata').css('left'),10);
				origin_y = parseInt($('#existingdata').css('top'),10);
				dragstate.dragitem = dragstate.dragitem.slice(8,dragstate.dragitem.length);
				$.getJSON('editor-backend.php',{ map: mapfile, cmd: "move_node", x: pos.x-origin_x, y: pos.y-origin_y, nodename: dragstate.dragitem  },
						  function() {map_refresh(); });
			
			}
			
			if(dragstate.dragitem.slice(0,7) === 'mapvia_')
			{
				origin_x = parseInt($('#existingdata').css('left'),10);
				origin_y = parseInt($('#existingdata').css('top'),10);
				dragstate.dragitem = dragstate.dragitem.slice(7,dragstate.dragitem.length);
				$.getJSON('editor-backend.php',{ map: mapfile, cmd: "move_via", x: pos.x-origin_x, y: pos.y-origin_y, vianame: dragstate.dragitem  },
						  function() {map_refresh(); });
			}
                        
			dragstate.dragitem = '';
			dragstate.dragstart = {x: -1, y: -1};
			dragstate.dragstop = {x: -1, y: -1};
			dragstate.addedVia = false;
		}
		return(false);
	} );

}

function syncmap()
{
	var i, existing, newx,newy, nodes, links, node, link, origin_x, origin_y, nodeid, via_id, vs;

    $('#busy').show();   

    nodes = map.nodes;
    links = map.links;
  
    // first, clear out the NODES that have disappeared...
    
    if (1===0)
    {
        // this doesn't flicker as much as you might expect
        $('img.mapnode').remove();
    }
    else
    {
        // this just seems to die for some reason.
        $('img.mapnode').each( function(i) {
            var myname = $(this).attr('id');
            myname = myname.replace('mapnode_','');
           // alert(myname);
            if ( map.nodes[myname] )
            //if(1==0)
            {
                // it still exists, keep it around
            }
            else
            {
                $(this).remove();
            }
        });
    }
    
    origin_x = parseInt($('#existingdata').css('left'),10);
    origin_y = parseInt($('#existingdata').css('top'),10);
    
    // now go through the list and move around or add...
    for (node in nodes)
    {
        if (map.nodes[node].name !== 'DEFAULT')
        {
			nodeid = 'mapnode_'+map.nodes[node].name;
            existing = $('img#'+nodeid);
            
            if(existing.size() !== 0)
            {               
                //alert('The node already exists called ' + nodeid);    
            }
            else
            {                   
                $('#nodecontainer').append('<img class="mapnode draggable" src="editcache/'+map.nodes[node].iconcachefile+'" id="'+nodeid+'"/>');
                existing = $('img#'+nodeid);
            }
            // one way or another, by here we have a node, I hope.
            
             newx = origin_x + map.nodes[node].x;
             newy = origin_y + map.nodes[node].y;
            
            existing.css({position: 'absolute', left: newx + "px", top: newy + "px", 'z-index': 30});
        }
    }
    
    // something here needs to clear existing VIAs
    
    $('.deadvia').remove();
    
    for (link in links)
    {
        if (map.links[link].name !== 'DEFAULT')
        {
            console.log("LINK " + link);

            if (map.links[link].via.length >0)
            {
                console.log(link + ' has VIAs');
                vs = map.links[link].via;
                for (i=0; i<vs.length; i+=1)
                {
                    console.log('VIA ' + vs[i][0] + ',' + vs[i][1]);
                    via_id='mapvia_' + link+'_via_'+i;
                     existing = $('img#'+via_id);

                    if(existing.size() !== 0)
                    {               

                    }
                    else
                    {                   
                        $('#nodecontainer').append('<img class="mapvia draggable" src="editor-resources/via-marker.png" id="'+via_id+'"/>');
                        existing = $('img#'+via_id);
                    }
                     newx = origin_x + vs[i][0] - 5;
                     newy = origin_y + vs[i][1] - 5;
                    existing.css({position: 'absolute', left: newx + "px", top: newy + "px", 'z-index': 30});
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

    $.getJSON("editor-backend.php",
        { map: mapfile, "cmd": "dump_map", "serial": lastserial },
          function (json) {
            console.log('Inside JSON function');
            if (json.valid === 1)
            {
                map = json;
                    console.log('Loaded JSON for ' + mapfile + ' - about to syncmap()');

                syncmap();
                console.log('Map synced. Fetching bg image data.');
                $('#existingdata').attr('src',json.map.mapcache + "?s=" + json.serial);
                lastserial = json.serial;
                // $('#existingdata').attr('src',json.map.mapcache);
                $.get('editor-backend.php',{ map: mapfile, cmd: "imagemap" },
                      function(cont) {
                        $('map#weathermap_imap').empty();
                        // $('map#weathermap_imap').html(cont);
                        // Getting medieval here - jQuery doesn't seem to be interested in Imagemaps??
                        var oldskool = document.getElementById('weathermap_imap');
                        oldskool.innerHTML = cont;
                        
                        reapplyLinkEvents();
                      });
            }
            else
            {
                console.log('Failed to get good JSON');
                alert('Got invalid JSON map data');
            }
            console.log('Leaving JSON function');
        }
    );
    console.log('Done with map_refresh()');
}

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
    $('#filelist').append('<li id="status"><em><img src="editor-resources/activity-indicator.gif">Fetching File List...</em></li>');
    
    $.getJSON("editor-backend.php",
        { map: '', cmd: "maplist" },
          function (json) {
			var locked, i , imax;
		  
            if (json.status === 'OK')  
            {
                i = 0;
				imax = json.files.length;
				
                $('#filelist').empty();
                for (i = 0; i < imax; i += 1)
                {
                    locked = '';
					
                    if (json.files[i].locked === 1)
                    {
                        locked = '<img src="editor-resources/lock.png" alt="Read-Only file" title="Read-Only file" />';
                    }
                    $('#filelist').append('<li><a>' + locked + '<span>' + json.files[i].file + '</span> <em>' + json.files[i].title + '</em></a></li>');
                }
                console.log("Built list");
                $('#filelist li a').click(function () { var filename = $(this).children("span").text(); console.log('About to call openmap()'); openmap(filename); });
                console.log("Added Actions");
            }
            else
            {
                console.log("list not OK - " + json.status);
            }
        }
    );
}






function nodeadd()
{
    console.log('Node Add - Initial');
    interactmode='nodeadd';
    $('#existingdata').click( function(ev) {
		var x,y;
		
        x = ev.pageX - parseInt($('#existingdata').css('left'),10);
        y = ev.pageY - parseInt($('#existingdata').css('top'),10);
        console.log('Ready to add a new node at ' + x + ',' + y);
        return(false);
    });    
}

function linkadd()
{
    console.log('Link Add - Initial');
}



$(document).ready( function() {


    $("body").ajaxStart(function(){
      $(this).css({border: 'red 2px solid'});
    });
    
    $("body").ajaxStop(function(){
      $(this).css({border: 'none'});
    });
        
    $('#welcome').show();
    $('#filepicker').hide();
    $('#toolbar').hide();
    $('#themap').hide();
    $('#busy').hide();
    
    $('#welcome').click( function() {
        
        showpicker();
        
        } );

// handle the release, which may not be over the original object anymore
	$(document).mouseup( function (ev) { 
			var pos, now, origin_x, origin_y, linkname;
	
            if(dragstate.linkmdown === true) { 
                
                console.log("LINK mouseup");
                // retrieve positioning properties
                pos    = getMousePosition(ev);
                dragstate.dragstop.x = pos.x;
                dragstate.dragstop.y = pos.y;
                dragstate.linkmdown=false;
                //	$('#log').append('That was a drag. ');
                if(dragstate.addedVia)
                {
                    // give the temporary VIA a better name
                    now = new Date();
                    $('#newvia').attr('class','deadvia');
                    $('#newvia').attr('id','via_'+now.getTime());
                    dragstate.addedVia=false;
                    console.log("Solidified ephemeral VIA");

                    reapplyDraggableEvents();
                    reapplyLinkEvents();
                    // XXX - do something to tell the editor serverside
                    origin_x = parseInt($('#existingdata').css('left'),10);
                    origin_y = parseInt($('#existingdata').css('top'),10);
                    linkname = dragstate.dragitem.slice(5,dragstate.dragitem.length);
                     $.getJSON('editor-backend.php',{ map: mapfile, cmd: "add_via", x: pos.x-origin_x, y: pos.y-origin_y, linkname: linkname, startx: dragstate.dragstart.x, starty: dragstate.dragstart.y  },
                        function() {map_refresh(); });
                }
            }
	} );
	
	$(document).mousemove( function (ev) { 
		var x,y, theItem, pos;
		
		if(dragstate.linkmdown || dragstate.dragmdown)
		{
			pos = getMousePosition(ev);

			if(dragstate.dragmdown) { theItem = $('#'+dragstate.dragitem);}
			if(dragstate.linkmdown) { theItem = $('#newvia');
				if(!dragstate.addedVia)
				{
					console.log("Created ephemeral VIA");
					// we just left the reservation, and we're still dragging.
					// - time to create a little marker
					$('#nodecontainer').append('<img src="editor-resources/via-marker.png" id="newvia" class="viamarker draggable">');
					theItem = $('#newvia');
					dragstate.dragoffset.x = -theItem.width()/2;
					dragstate.dragoffset.y = -theItem.height()/2;
					dragstate.addedVia = true;
				}
			}
			x = pos.x + dragstate.dragoffset.x;
			y = pos.y + dragstate.dragoffset.y;
			theItem.css( { left: x, top: y});
        }
	} );

    $('#btn_refresh').click( function() { map_refresh(); } );
    $('#btn_selectfile').click( function() { showpicker(); } );
    
    $('#btn_addnode').click( function() { nodeadd(); } );
    $('#btn_addlink').click( function() { linkadd(); } );


    reapplyDraggableEvents();
    reapplyLinkEvents();
    
    }
);

