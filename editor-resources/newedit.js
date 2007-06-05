

// the map data structure - updated via JSON from the server
var map = { valid: 0 };

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

function syncmap()
{
    $('#busy').show();

    var nodes = map.nodes;
  //  alert("length: "+nodes.length);
    
    var a=3;
    
    for(var node in nodes)
    {
//        alert(node);

        if(map.nodes[node].name != 'DEFAULT')
        {
        if(1==1)
        {
            var nodeid = 'mapnode_'+map.nodes[node].name;
            var existing = $('#'+nodeid);
            
            if(existing.size() != 0)
            {               
                //alert('The node already exists called ' + nodeid);    
            }
            else
            {
              //  alert("Creating a node called " + nodeid);
                
                $('#nodecontainer').append('<img class="draggablenode" src="editcache/'+map.nodes[node].iconcachefile+'" id="'+nodeid+'"/>');
                existing = $('#'+nodeid);
                existing.css('position', 'absolute');
            }
            // one way or another, by here we have a node, I hope.
            existing.css("left",map.nodes[node].x);
            existing.css("top",map.nodes[node].y);
        }
        }
    }
    $('#busy').hide();
}

$(document).ready( function() {
    
    $('#welcome').click( function() {
            $('#welcome').hide('slow');
            $('#filepicker').show('slow');
            $('#toolbar').show('slow');
            } );
   // $('#filepicker').fadeIn('slow');
   // $('#themap').fadeIn('slow');
    
    
    $('#welcome').hide();
    $('#filepicker').hide();
    $('#toolbar').show();
    $('#themap').show();
    
    $('#busy').show();
    
    $.getJSON("ajax-callback.php",
        { map: " weathermap.conf", cmd: "dump_map" },
          function(json){
            $('#busy').hide();
            $('#existingdata').attr('src',json.map.mapcache);
            map = json;
            syncmap();
        }
    );
});
