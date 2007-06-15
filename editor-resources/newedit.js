

// the map data structure - updated via JSON from the server
var map = { valid: 0 };

var mapfile = '';
var lastserial = 0;

// we queue these up to make our one-at-a-time AJAX call work
var AJAXRequest = {
    params: {},
    send: function()
    {
    }
};

function printfire() {
   if (document.createEvent)
   {
      printfire.args =  arguments;
      var ev = document.createEvent("Events");
      ev.initEvent("printfire", false, true );
      dispatchEvent(ev);
   }
}

function openmap(mapname)
{
    mapfile = mapname;
    $('#welcome').hide();
    $('#filepicker').hide();
    $('#toolbar').show();
    $('#themap').show();
    $('#busy').hide();

    $('img.draggablenode').remove();

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
          function(json){
            
            if(json.status=='OK')  
            {
                var i=0;
                var imax = json.files.length;
                $('#filelist').empty();
                for(i=0; i<imax; i++)
                {
                    locked = '';
                    if(json.files[i].locked == 1)
                    {
                        locked = '<img src="editor-resources/lock.png" alt="Read-Only file" title="Read-Only file" />';
                    }
                    $('#filelist').append('<li><a>'+locked+'<span>' + json.files[i].file + '</span> <em>' + json.files[i].title + '</em></a></li>');
                    
                }
                $('#filelist li a').click(function() { openmap($(this).children("span").text()); });
            }           
        }
    );
}

function syncmap()
{
    $('#busy').show();

    var nodes = map.nodes;
  
    // first, clear out the NODES that have disappeared...
    
    if(1==0)
    {
        // this doesn't flicker as much as you might expect
        $('img.draggablenode').remove();
    }
    else
    {
        // this just seems to die for some reason.
        $('img.draggablenode').each( function(i) {
            var myname = $(this).attr('id');
            myname = myname.replace('mapnode_','');
           // alert(myname);
            if( map.nodes[myname] )
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
    
    var origin_x = parseInt($('#existingdata').css('left'));
    var origin_y = parseInt($('#existingdata').css('top'));
    
    // now go through the list and move around or add...
    for(var node in nodes)
    {
        if(map.nodes[node].name != 'DEFAULT')
        {
            var nodeid = 'mapnode_'+map.nodes[node].name;
            var existing = $('img#'+nodeid);
            
            if(existing.size() != 0)
            {               
                //alert('The node already exists called ' + nodeid);    
            }
            else
            {                   
                $('#nodecontainer').append('<img class="draggablenode" src="editcache/'+map.nodes[node].iconcachefile+'" id="'+nodeid+'"/>');
                existing = $('img#'+nodeid);
            }
            // one way or another, by here we have a node, I hope.
            
            var newx = origin_x + map.nodes[node].x;
            var newy = origin_y + map.nodes[node].y;
            
            existing.css({position: 'absolute', left: newx + "px", top: newy + "px", 'z-index': 3, opacity: '0.6'});
        }
    }
        
    $('#busy').hide();
}

function map_refresh()
{
    $.getJSON("editor-backend.php",
        { map: mapfile, cmd: "dump_map", serial: lastserial },
          function(json){
            if(json.valid==1)
            {
                map = json;
                syncmap();
                $('#existingdata').attr('src',json.map.mapcache + "?s=" + json.serial);
                //alert(lastserial);
                //alert(json.serial);
                lastserial = json.serial;
                // $('#existingdata').attr('src',json.map.mapcache);
            }
            else
            {
                alert('Got invalid JSON map data');
            }
        }
    );
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
    $('#toolbar').show();
    $('#themap').hide();
    $('#busy').hide();
    
    $('#welcome').click( function() {
            // $('#welcome').hide('slow');
            // $('#filepicker').show('slow');
            // $('#toolbar').show('slow');
            
            showpicker();
            
            } );
   // $('#filepicker').fadeIn('slow');
   // $('#themap').fadeIn('slow');   

    $('#btn_refresh').click( function() { map_refresh(); } );
    $('#btn_selectfile').click( function() { showpicker(); } );   
    
    }
);
