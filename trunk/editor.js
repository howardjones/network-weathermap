// global variable for subwindow reference

var newWindow;

// seed the help text. Done in a big lump here, so we could make a foreign language version someday.

var helptexts = {
    "link_target": 'Where should Weathermap get data for this link? This can either be an RRD file, or an HTML with special comments in it (normally from MRTG).',
    "link_width": 'How wide the link arrow will be drawn, in pixels.',
    "link_infourl":
        'If you are using the \'overlib\' HTML style then this is the URL that will be opened when you click on the link',
    "link_hover":
        'If you are using the \'overlib\' HTML style then this is the URL of the image that will be shown when you hover over the link',
    "link_bandwidth_in": "The bandwidth from the first node to the second node",
    "link_bandwidth_out": "The bandwidth from the second node to the first node (if that is different)",
    "link_commentin": "The text that will appear alongside the link",
    "link_commentout": "The text that will appear alongside the link",

    "node_infourl":
        'If you are using the \'overlib\' HTML style then this is the URL that will be opened when you click on the node',
    "node_hover":
        'If you are using the \'overlib\' HTML style then this is the URL of the image that will be shown when you hover over the node',
    "node_x": "How far from the left to position the node, in pixels",
    "node_y": "How far from the top to position the node, in pixels",
    "node_label": "The text that appears on the node",
    "node_new_name": "The name used for this node when defining links",

    "tb_newfile": 'Change to a different file, or start creating a new one.',
    "tb_addnode": 'Add a new node to the map',
    "tb_addlink": 'Add a new link to the map, by joining two nodes together.',


    "hover_tb_newfile": 'Select a different map to edit, or start a new one.',

// These are the default text - what appears when nothing more interesting
// is happening. One for each dialog/location.
    "link_default": 'This is where help appears for links',
    "map_default": 'This is where help appears for maps',
    "node_default": 'This is where help appears for nodes',
    "tb_default": 'or click a Node or Link to edit it\'s properties'
};

jQuery(document).ready(initJS);
jQuery(document).unload(cleanupJS);

function initJS()
    {
    // check if DOM is available, if not, we'll stop here, leaving the warning showing
    if (!document.getElementById || !document.createTextNode || !document.getElementsByTagName)
        {
        // I'm pretty sure this is actually impossible now.
        return;
        }

        // check if there is a "No JavaScript" message
        jQuery("#nojs").hide();

    // so that's the warning hidden, now let's show the content

    // check if there is a "with JavaScript" div
    jQuery("#withjs").show();

    // if the xycapture element is there, then we are in the main edit screen
    if (document.getElementById('xycapture'))
        {
        attach_click_events();
        attach_help_events();
        show_context_help('node_label', 'node_help');

        // set the mapmode, so we know where we stand.
        mapmode('existing');
        }
    }

function cleanupJS()
    {

    // This should be cleaning up all the handlers we added in initJS, to avoid killing
    // IE/Win and Safari (at least) over a period of time with memory leaks.

    }



function attach_click_events()
    {  

    jQuery("area[id^=LINK:]").attr("href","#").click(click_handler);
    jQuery("area[id^=NODE:]").attr("href","#").click(click_handler);
    jQuery("area[id^=TIMES]").attr("href","#").click(position_timestamp);
    jQuery("area[id^=LEGEN]").attr("href","#").click(position_legend);
    
    if(fromplug===1)
    {
        jQuery("#tb_newfile").click( function() {window.location = "weathermap-cacti-plugin-mgmt.php";} )
    }
    else
    {
        jQuery("#tb_newfile").click(new_file);
    }

    jQuery("#tb_addnode").click(add_node);
    jQuery("#tb_mapprops").click(map_properties);
    jQuery("#tb_mapstyle").click(map_style);

    jQuery("#tb_addlink").click(add_link);
    jQuery("#tb_poslegend").click(position_first_legend);
    jQuery("#tb_postime").click(position_timestamp);
    jQuery("#tb_colours").click(manage_colours);

    jQuery("#tb_manageimages").click(manage_images);
    jQuery("#tb_prefs").click(prefs);

    jQuery("#node_move").click(move_node);
    jQuery("#node_delete").click(delete_node);
    jQuery("#node_clone").click(clone_node);
    jQuery("#node_edit").click(edit_node);

    jQuery("#link_delete").click(delete_link);
    jQuery("#link_edit").click(edit_link);

    jQuery("#link_vert").click(align_link_v);
    jQuery("#link_horiz").click(align_link_h);
    jQuery("#link_via").click(via_link);
        
    jQuery('.wm_submit').click(do_submit);
    jQuery('.wm_cancel').click(cancel_op);
    
    jQuery('#link_cactipick').click(cactipicker).attr("href","#");
    jQuery('#node_cactipick').click(nodecactipicker).attr("href","#");
    
    jQuery('#xycapture').mouseover(function(event) {coord_capture(event);});
    jQuery('#xycapture').mousemove(function(event) {coord_update(event);});
    jQuery('#xycapture').mouseout(function(event) {coord_release(event);});
        
    }

// used by the cancel button on each of the properties dialogs
function cancel_op()
    {
    hide_all_dialogs();
    jQuery("#action").val("");
    }

function help_handler(e)
    {

       var objectid = jQuery(this).attr('id');
       var section = objectid.slice(0, objectid.indexOf('_'));
       var target = section + '_help';
       var helptext = "undefined";

       if (helptexts[objectid]) {       
            helptext = helptexts[objectid];
       }

    if ((e.type == 'blur') || (e.type == 'mouseout')) {
                        
        helptext = helptexts[section + '_default'];

        if (helptext == 'undefined') {
            alert('OID is: ' + objectid + ' and target is:' + target + ' and section is: ' + section);
            }
            
        }

        if(helptext != "undefined") {
            jQuery("#" + target).text(helptext);
        }

    }

// Any clicks in the imagemap end up here.
function click_handler(e)
    {
    
    var alt, objectname, objecttype, objectid;
    
    // alt = el.getAttribute('alt');
	alt = jQuery(this).attr("id");

    objecttype = alt.slice(0, 4);
    objectname = alt.slice(5, alt.length);
    objectid = objectname.slice(0,objectname.length-2);

    // if we're not in a mode yet...
    if (document.frmMain.action.value === '')
        {

        // if we're waiting for a node specifically (e.g. "make link") then ignore links here
        if (objecttype == 'NODE')
            {
                // chop off the suffix
                // objectid = objectname.slice(0,objectname.length-2);
                objectname = NodeIDs[objectid];
                show_node(objectname);
            }

        if (objecttype == 'LINK')
            {
                // chop off the suffix
                // objectid = objectname.slice(0,objectname.length-2);
                objectname = LinkIDs[objectid];
                show_link(objectname);
            }
        }

    // we've got a command queued, so do the appropriate thing
    else
        {
        if (objecttype == 'NODE' && document.getElementById('action').value == 'add_link')
            {
            document.getElementById('param').value = NodeIDs[objectid];
            document.frmMain.submit();
            }

        else if (objecttype == 'NODE' && document.getElementById('action').value == 'add_link2')
            {
            document.getElementById('param').value = NodeIDs[objectid];
            document.frmMain.submit();
            }

        else
            {
            // Halfway through one operation, the user has done something unexpected.
            // reset back to standard state, and see if we can oblige them
            //		alert('A bit confused');
            document.frmMain.action.value = '';
            hide_all_dialogs()
            click_handler(e);
            }
        }
    }

// used by the Submit button on each of the properties dialogs
function do_submit()
    {
    document.frmMain.submit();
    }

function cactipicker()
    {
    // make sure it isn't already opened
    if (!newWindow || newWindow.closed)
        {
        newWindow = window.open("", "cactipicker", "scrollbars=1,status=1,height=400,width=400,resizable=1");
        }

    else if (newWindow.focus)
        {
        // window is already open and focusable, so bring it to the front
        newWindow.focus();
        }

    // newWindow.location = "cacti-pick.php?command=link_step1";
    newWindow.location = "cacti-pick.php?command=link_step1";
    }


function nodecactipicker()
    {
        // make sure it isn't already opened
    if (!newWindow || newWindow.closed)
        {
		newWindow = window.open("", "cactipicker", "scrollbars=1,status=1,height=400,width=400,resizable=1");
        }

    else if (newWindow.focus)
        {
        // window is already open and focusable, so bring it to the front
        newWindow.focus();
        }

        newWindow.location = "cacti-pick.php?command=node_step1";
    }

function show_context_help(itemid, targetid)
    {
    //    var itemid = item.id;
    var helpbox, helpboxtext, message;
    //    var ct = document.getElementById(targetid);
    //    if(ct)
    //  {
    message = "We'd show helptext for " + itemid + " in the'" + targetid + "' div";
    // }
    helpbox = document.getElementById(targetid);
    helpboxtext = helpbox.firstChild;
    helpboxtext.nodeValue = message;
    }

function manage_colours()
    {
    mapmode('existing');

    hide_all_dialogs();
    document.getElementById('action').value = "set_map_colours";
    show_dialog('dlgColours');
    }

function manage_images()
    {
    mapmode('existing');

    hide_all_dialogs();
    document.getElementById('action').value = "set_image";
    show_dialog('dlgImages');
    }

function prefs()
    {
        hide_all_dialogs();
        document.getElementById('action').value = "editor_settings";
        show_dialog('dlgEditorSettings');
    }

function default_toolbar()
    {
    }

function working_toolbar()
    {
    }

function new_file()
    {
    self.location = "?action=newfile";
    }

function mapmode(m)
    {
    if (m == 'xy')
        {
        document.getElementById('debug').value = "xy";
        document.getElementById('xycapture').style.display = 'inline';
        document.getElementById('existingdata').style.display = 'none';
        }

    else if (m == 'existing')
        {
        document.getElementById('debug').value = "existing";
        document.getElementById('xycapture').style.display = 'none';
        document.getElementById('existingdata').style.display = 'inline';
        }

    else
        {
        alert('invalid mode');
        }
    }

function add_node()
    {
    document.getElementById('tb_help').innerText = 'Click on the map where you would like to add a new node.';
    document.getElementById('action').value = "add_node";
    mapmode('xy');
    }

function delete_node()
    {
    if (confirm("This node (and any links it is part of) will be deleted permanently."))
        {
        document.getElementById('action').value = "delete_node";
        document.frmMain.submit();
        }
    }

function clone_node()
    {
        document.getElementById('action').value = "clone_node";
        document.frmMain.submit();
    }

function edit_node()
{
        document.getElementById('action').value = "edit_node";
        show_itemtext('node',document.frmMain.node_name.value);
        // document.frmMain.submit();
   }

function edit_link()
{
        document.getElementById('action').value = "edit_link";
        show_itemtext('link',document.frmMain.link_name.value);
        // document.frmMain.submit();
}

function move_node()
    {
    hide_dialog('dlgNodeProperties');
    document.getElementById('tb_help').innerText = 'Click on the map where you would like to move the node to.';
    document.getElementById('action').value = "move_node";
    mapmode('xy');
    }

function via_link()
 {
    hide_dialog('dlgLinkProperties');
    document.getElementById('tb_help').innerText = 'Click on the map via which point you whant to redirect link.';
    document.getElementById('action').value = "via_link";
    mapmode('xy');
} 
	
function add_link()
    {
    document.getElementById('tb_help').innerText = 'Click on the first node for one end of the link.';
    document.getElementById('action').value = "add_link";
    mapmode('existing');
    }

function delete_link()
    {
    if (confirm("This link will be deleted permanently."))
        {
        document.getElementById('action').value = "delete_link";
        document.frmMain.submit();
        }
    }

function map_properties()
    {
    mapmode('existing');

    hide_all_dialogs();
    document.getElementById('action').value = "set_map_properties";
    show_dialog('dlgMapProperties');
    document.getElementById('map_title').focus();
    }

function map_style()
    {
    mapmode('existing');

    hide_all_dialogs();
    document.getElementById('action').value = "set_map_style";
    show_dialog('dlgMapStyle');
    document.getElementById('mapstyle_linklabels').focus();
    }

function position_timestamp()
    {
    document.getElementById('tb_help').innerText = 'Click on the map where you would like to put the timestamp.';
    document.getElementById('action').value = "place_stamp";
    mapmode('xy');
    }

// called from clicking the toolbar
function position_first_legend()
{
    real_position_legend('DEFAULT');
}

// called from clicking on the existing legends
function position_legend(e)
    {
        var el;
        var alt, objectname, objecttype;
    
        if (window.event && window.event.srcElement)
            {
            el = window.event.srcElement;
            }
    
        if (e && e.target)
            {
            el = e.target;
            }
    
        if (!el)
            {
            return;
            }
        
        // we need to figure out WHICH legend, nowadays
        //alt = el.getAttribute('alt');
		alt = el.id;

    // objecttype = alt.slice(0, 5);
    objectname = alt.slice(7, alt.length);
      
    real_position_legend(objectname);
            
    //document.getElementById('tb_help').innerText = 'Click on the map where you would like to put the legend.';
    //document.getElementById('action').value = "place_legend";
    //document.getElementById('param').value = objectname;
    //mapmode('xy');
    }
    
function real_position_legend(scalename)
{
    document.getElementById('tb_help').innerText = 'Click on the map where you would like to put the legend.';
    document.getElementById('action').value = "place_legend";
    document.getElementById('param').value = scalename;
    mapmode('xy');
}

function show_itemtext(itemtype,name)
    {
    var found = -1;
    mapmode('existing');

    hide_all_dialogs();

    // $('#dlgNodeProperties').block();
    
  //  $.blockUI.defaults.elementMessage = 'Please Wait';
    
    jQuery('textarea#item_configtext').val('');
    
    if(itemtype==='node')
    {           
        jQuery('#action').val('set_node_config');
    }
    
    if(itemtype==='link')
    {           
        jQuery('#action').val('set_link_config');
    }
    show_dialog('dlgTextEdit');
    
//    $('#item_configtext').block();
    
    jQuery.ajax( { type: "GET",
                url: 'editor.php',
                data: {action: 'fetch_config',
                        item_type: itemtype,
                        item_name: name,
                        mapname: document.frmMain.mapname.value},
                success: function(text) {
                        jQuery('#item_configtext').val(text);
                        document.getElementById('item_configtext').focus();
                      //  jQuery('#dlgTextEdit').unblock();
                   }
           } );
}

function show_node(name)
    {
    var found = -1;
    mapmode('existing');

    hide_all_dialogs();

    var mynode = Nodes[name];

    if (mynode)
        {
        document.frmMain.action.value = "set_node_properties";
        document.frmMain.node_name.value = name;
        document.frmMain.node_new_name.value = name;

        document.frmMain.node_x.value = mynode.x;
        document.frmMain.node_y.value = mynode.y;

        document.frmMain.node_name.value = mynode.name;
        document.frmMain.node_new_name.value = mynode.name;
        document.frmMain.node_label.value = mynode.label;
        document.frmMain.node_infourl.value = mynode.infourl;
        document.frmMain.node_hover.value = mynode.overliburl;
        
        if(mynode.iconfile != '')
        {
			// console.log(mynode.iconfile.substring(0,2));
			if(mynode.iconfile.substring(0,2)=='::')
			{
				document.frmMain.node_iconfilename.value = '--AICON--';
			}
			else
			{
				document.frmMain.node_iconfilename.value = mynode.iconfile;
			}
        }
        else
        {
            document.frmMain.node_iconfilename.value = '--NONE--';
        }  

        // save this here, just in case they choose delete_node or move_node
        document.getElementById('param').value = mynode.name;

        show_dialog('dlgNodeProperties');
        document.getElementById('node_new_name').focus();
        }
    }

function show_link(name)
    {
    var found = -1;
    mapmode('existing');

    hide_all_dialogs();

    var mylink = Links[name];

    if (mylink)
        {
        document.frmMain.link_name.value = mylink.name;
        document.frmMain.link_target.value = mylink.target;
        document.frmMain.link_width.value = mylink.width;

        document.frmMain.link_bandwidth_in.value = mylink.bw_in;

        if (mylink.bw_in == mylink.bw_out)
            {
            document.frmMain.link_bandwidth_out.value = '';
            document.frmMain.link_bandwidth_out_cb.checked = 1;
            }

        else
            {
            document.frmMain.link_bandwidth_out_cb.checked = 0;
            document.frmMain.link_bandwidth_out.value = mylink.bw_out;
            }

        document.frmMain.link_infourl.value = mylink.infourl;
        document.frmMain.link_hover.value = mylink.overliburl;

        document.frmMain.link_commentin.value = mylink.commentin;
        document.frmMain.link_commentout.value = mylink.commentout;
        document.frmMain.link_commentposin.value = mylink.commentposin;
        document.frmMain.link_commentposout.value = mylink.commentposout; 

        // if that didn't "stick", then we need to add the special value
        if( jQuery('#link_commentposout').val() != mylink.commentposout)
        {
            jQuery('#link_commentposout').prepend("<option selected value='" + mylink.commentposout + "'>" + mylink.commentposout + "%</option>");
        }

        if( jQuery('#link_commentposin').val() != mylink.commentposin)
        {
            jQuery('#link_commentposin').prepend("<option selected value='" + mylink.commentposin + "'>" + mylink.commentposin + "%</option>");
        }
        
        document.getElementById('link_nodename1').firstChild.nodeValue = mylink.a;
        document.getElementById('link_nodename1a').firstChild.nodeValue = mylink.a;
        document.getElementById('link_nodename1b').firstChild.nodeValue = mylink.a;

        document.getElementById('link_nodename2').firstChild.nodeValue = mylink.b;

        document.getElementById('param').value = mylink.name;

        document.frmMain.action.value = "set_link_properties";

        show_dialog('dlgLinkProperties');
        document.getElementById('link_bandwidth_in').focus();
        }
    }

function show_dialog(dlg)
    {
    document.getElementById(dlg).style.display = 'block';
    }

function hide_dialog(dlg)
    {
    document.getElementById(dlg).style.display = 'none';
    // reset the action. The use pressed Cancel, if this function was called
    // (that, or they're about to open a new Properties dialog, so the value is irrelevant)
    document.frmMain.action.value = '';
    //	alert('ACTION=' + document.frmMain.action.value);
    }

function hide_all_dialogs()
    {
    hide_dialog('dlgMapProperties');
    hide_dialog('dlgMapStyle');
    hide_dialog('dlgLinkProperties');
    hide_dialog('dlgTextEdit');
    hide_dialog('dlgNodeProperties');
    hide_dialog('dlgColours');
    hide_dialog('dlgImages');
    hide_dialog('dlgEditorSettings');
    }

function ElementPosition(param){
  var x=0, y=0;
  var obj = (typeof param == "string") ? document.getElementById(param) : param;
  if (obj) {
    x = obj.offsetLeft;
    y = obj.offsetTop;
    var body = document.getElementsByTagName('body')[0];
    while (obj.offsetParent && obj!=body){
      x += obj.offsetParent.offsetLeft;
      y += obj.offsetParent.offsetTop;
      obj = obj.offsetParent;
    }
  }
  this.x = x;
  this.y = y;
}

function coord_capture(event)
{
    // $('#tb_coords').html('+++');
}

function coord_update(event)
{
    var cursorx = event.pageX;
    var cursory = event.pageY;
 
    // Adjust for coords relative to the image, not the document
    var p = new ElementPosition('xycapture');
    cursorx -= p.x;
    cursory -= p.y;
    cursory++; // fudge to make coords match results from imagemap (not sure why this is needed)
        
    jQuery('#tb_coords').html('Position<br />'+ cursorx + ', ' + cursory);
}

function coord_release(event)
{
    jQuery('#tb_coords').html('Position<br />---, ---');
}

function align_link_h()
{
    document.getElementById('action').value = "link_align_horizontal";
    document.frmMain.submit();
}

function align_link_v()
{
    document.getElementById('action').value = "link_align_vertical";
    document.frmMain.submit();
}

function attach_help_events()
{
    // add an onblur/onfocus handler to all the visible <input> items

    jQuery("input").focus(help_handler).blur(help_handler);
}