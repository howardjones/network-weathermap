// global variable for subwindow reference

var newWindow;

var helptexts = new Object;

// seed the help text. Done in a big lump here, so we could make a foreign language version someday.

helptexts.link_target
    = 'Where should Weathermap get data for this link? This can either be an RRD file, or an HTML with special comments in it (normally from MRTG).';
helptexts.link_width = 'How wide the link arrow will be drawn, in pixels.';
helptexts.link_infourl
    = 'If you are using the \'overlib\' HTML style then this is the URL that will be opened when you click on the link';
helptexts.link_hover
    = 'If you are using the \'overlib\' HTML style then this is the URL of the image that will be shown when you hover over the link';

helptexts.tb_newfile = 'Change to a different file, or start creating a new one.';
helptexts.tb_addnode = 'Add a new node to the map';
helptexts.tb_addlink = 'Add a new link to the map, by joining two nodes together.';

helptexts.hover_tb_newfile = 'Select a different map to edit, or start a new one.';

helptexts.link_default = 'This is where help appears for links';
helptexts.map_default = 'This is where help appears for maps';
helptexts.node_default = 'This is where help appears for nodes';
helptexts.tb_defaults = 'or click a Node or Link to edit it\'s properties';

addEvent(window, 'load', initJS);
addEvent(window, 'unload', cleanupJS);

function addEvent(obj, evType, fn)
    {
    if (obj.addEventListener)
        {
        obj.addEventListener(evType, fn, false);
        return true;
        }

    else if (obj.attachEvent)
        {
        var r = obj.attachEvent("on" + evType, fn);
        return r;
        }

    else
        {
        return false;
        }
    }

function initJS()
    {
    // check if DOM is available, if not, we'll stop here, leaving the warning showing
    if (!document.getElementById || !document.createTextNode || !document.getElementsByTagName)
        {
        return;
        }

    // check if there is a "No JavaScript" message
    var nojsmsg = document.getElementById('nojs');

    if (nojsmsg)
        {
        nojsmsg.style.display = 'none';
        }

    // so that's the warning hidden, now let's show the content

    // check if there is a "with JavaScript" div
    var jsmsg = document.getElementById('withjs');

    if (jsmsg)
        {
        jsmsg.style.display = 'block';
        }

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

function attach_help_events()
    {
    var helps;

    // add an onblur/onfocus handler to all the visible <input> items  
    helps = document.getElementsByTagName('input');

    for (i = 0; i < helps.length; ++i)
        {
        if (helps[i].getAttribute('type') != 'hidden')
            {
            addEvent(helps[i], 'focus', help_handler);
            addEvent(helps[i], 'blur', help_handler);
            }
        }

    if (6 == 8)
        {
        // add an onmousein/onmouseout handler to the toolbar buttons
        helps = document.getElementsByTagName('li');

        for (i = 0; i < helps.length; ++i)
            {
            // alert(helps[i].id.slice(0,3));
            //alert(helps[i].className);
            if (helps[i].className == 'tb_active')
                { // alert(helps[i].id);
                addEvent(helps[i], 'mouseover', mouse_help_handler);
                addEvent(helps[i], 'mouseout', mouse_help_handler);
                //   alert('Attaching to ' + helps[i].className);
                }
            }
        }
    }

function attach_click_events()
    {
    var alt, i;
    var areas, type;

    areas = document.getElementsByTagName('area');

    for (i = 0; i < areas.length; ++i)
        {
        alt = areas[i].getAttribute('alt');
        type = alt.slice(0, 5);

        if (type == 'LINK:' || type == 'NODE:')
            {
            // we add the href so that the browser adds a 'hand' cursor
            areas[i].setAttribute('href', '#');
            // and the click_handler does the actual work
            addEvent(areas[i], 'click', click_handler);
            }

        if (type == 'TIMES')
            {
            areas[i].setAttribute('href', '#');
            addEvent(areas[i], 'click', position_timestamp);
            }

        if (type == 'LEGEN')
            {
            areas[i].setAttribute('href', '#');
            addEvent(areas[i], 'click', position_legend);
            }
        }

    if(fromplug===1)
    {
        addEvent(document.getElementById('tb_newfile'), 'click', function() { window.location = "weathermap-cacti-plugin-mgmt.php"; });
    }
    else
    {
        addEvent(document.getElementById('tb_newfile'), 'click', new_file);
    }
    
    addEvent(document.getElementById('tb_addnode'), 'click', add_node);
    addEvent(document.getElementById('tb_mapprops'), 'click', map_properties);
    addEvent(document.getElementById('tb_mapstyle'), 'click', map_style);

    addEvent(document.getElementById('tb_addlink'), 'click', add_link);
    addEvent(document.getElementById('tb_poslegend'), 'click', position_first_legend);
    addEvent(document.getElementById('tb_postime'), 'click', position_timestamp);
    addEvent(document.getElementById('tb_colours'), 'click', manage_colours);
    addEvent(document.getElementById('tb_manageimages'), 'click', manage_images);
    addEvent(document.getElementById('tb_prefs'), 'click', prefs);

    addEvent(document.getElementById('tb_node_cancel'), 'click', cancel_op);
    addEvent(document.getElementById('tb_node_submit'), 'click', do_submit);
    addEvent(document.getElementById('node_move'), 'click', move_node);
    addEvent(document.getElementById('node_delete'), 'click', delete_node);
    addEvent(document.getElementById('node_clone'), 'click', clone_node);
    addEvent(document.getElementById('node_edit'), 'click', edit_node);
        
    addEvent(document.getElementById('tb_textedit_cancel'), 'click', cancel_op);
    addEvent(document.getElementById('tb_textedit_submit'), 'click', do_submit);


    addEvent(document.getElementById('tb_link_cancel'), 'click', cancel_op);
    addEvent(document.getElementById('tb_link_submit'), 'click', do_submit);
    addEvent(document.getElementById('link_delete'), 'click', delete_link);
    addEvent(document.getElementById('link_edit'), 'click', edit_link);

    addEvent(document.getElementById('tb_map_cancel'), 'click', cancel_op);
    addEvent(document.getElementById('tb_map_submit'), 'click', do_submit);

    addEvent(document.getElementById('tb_mapstyle_cancel'), 'click', cancel_op);
    addEvent(document.getElementById('tb_mapstyle_submit'), 'click', do_submit);

    addEvent(document.getElementById('tb_colours_cancel'), 'click', cancel_op);
    addEvent(document.getElementById('tb_colours_submit'), 'click', do_submit);

    addEvent(document.getElementById('tb_images_cancel'), 'click', cancel_op);
    addEvent(document.getElementById('tb_images_submit'), 'click', do_submit);

    var cp = document.getElementById('link_cactipick');
    addEvent(cp, 'click', cactipicker);
    cp.setAttribute('href', '#');
    
    cp = document.getElementById('node_cactipick');
    addEvent(cp, 'click', nodecactipicker);
    cp.setAttribute('href', '#');

    $('#xycapture').mouseover(function(event) {coord_capture(event);});
    $('#xycapture').mousemove(function(event) {coord_update(event);});
    $('#xycapture').mouseout(function(event) {coord_release(event);});
    
    
    }

// used by the cancel button on each of the properties dialogs
function cancel_op()
    {
    hide_all_dialogs();
    document.frmMain.action.value = '';
    }

function help_handler(e)
    {
    var el;

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

    var objectid = el.id;

    if ((e.type == 'focus') || (e.type == 'mouseover'))
        {
        if (helptexts[objectid])
            {
            var help = helptexts[objectid];
            var target = objectid.slice(0, objectid.indexOf('_')) + '_help';

            document.getElementById(target).firstChild.nodeValue = help;

            // alert(target);
            }
        }

    if ((e.type == 'blur') || (e.type == 'mouseout'))
        {
        // alert('blurred - figure out default, and apply it.');

        var section = objectid.slice(0, objectid.indexOf('_'));
        var target = section + '_help';
        var help = helptexts[section + '_default'];

        if (help == 'undefined')
            {
            alert('OID is: ' + objectid + ' and target is:' + target + ' and section is: ' + section);
            }

        document.getElementById(target).firstChild.nodeValue = help;
        }
    }

function mouse_help_handler(e)
    {
    var el;

    //  alert('ddd');

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

    var objectid = el.firstChild.id;

    if ((e.type == 'focus') || (e.type == 'mouseover'))
        {
        if (helptexts[objectid])
            {
            var help = helptexts[objectid];
            var target = objectid.slice(0, objectid.indexOf('_')) + '_help';

            document.getElementById(target).firstChild.nodeValue = help;

            // alert(target);
            }
        }

    if ((e.type == 'blur') || (e.type == 'mouseout'))
        {
        // alert('blurred - figure out default, and apply it.');

        var section = objectid.slice(0, objectid.indexOf('_'));
        var target = section + '_help';
        var help = helptexts[section + '_default'];

        if (help == 'undefined')
            {
            alert('OID is: ' + objectid + ' and target is:' + target + ' and section is: ' + section);
            }

        document.getElementById(target).firstChild.nodeValue = help;
        }
    }

// Any clicks in the imagemap end up here.
function click_handler(e)
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

    alt = el.getAttribute('alt');

    objecttype = alt.slice(0, 4);
    objectname = alt.slice(5, alt.length);

    // if we're not in a mode yet...
    if (document.frmMain.action.value === '')
        {

        // if we're waiting for a node specifically (e.g. "make link") then ignore links here
        if (objecttype == 'NODE')
            {
            show_node(objectname);
            }

        if (objecttype == 'LINK')
            {
            show_link(objectname);
            }
        }

    // we've got a command queued, so do the appropriate thing
    else
        {
        if (objecttype == 'NODE' && document.getElementById('action').value == 'add_link')
            {
            document.getElementById('param').value = objectname;
            document.frmMain.submit();
            }

        else if (objecttype == 'NODE' && document.getElementById('action').value == 'add_link2')
            {
            document.getElementById('param').value = objectname;
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
        newWindow = window.open("", "cactipicker", "status,scrollbars=yes,height=400,width=400");
        }

    else if (newWindow.focus)
        {
        // window is already open and focusable, so bring it to the front
        newWindow.focus();
        }

    newWindow.location = "cacti-pick.php?command=link_step1";
    }


function nodecactipicker()
    {
        // make sure it isn't already opened
    if (!newWindow || newWindow.closed)
        {
        newWindow = window.open("", "cactipicker", "status,scrollbars=yes,height=400,width=400");
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
    alert("Not Yet");
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
        alt = el.getAttribute('alt');

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
    
    $('textarea#item_configtext').val('');
    
    if(itemtype==='node')
    {           
        $('#action').val('set_node_config');
    }
    
    if(itemtype==='link')
    {           
        $('#action').val('set_link_config');
    }
    show_dialog('dlgTextEdit');
    
//    $('#item_configtext').block();
    
    $.ajax( { type: "GET",
                url: 'editor.php',
                data: {action: 'fetch_config',
                        item_type: itemtype,
                        item_name: name,
                        mapname: document.frmMain.mapname.value},
                success: function(text) {
                        $('#item_configtext').val(text);
                        document.getElementById('item_configtext').focus();
                      //  $('#dlgTextEdit').unblock();
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
            document.frmMain.node_iconfilename.value = mynode.iconfile;
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
        
    $('#tb_coords').html('Position<br />'+ cursorx + ', ' + cursory);
}

function coord_release(event)
{
    $('#tb_coords').html('Position<br />---, ---');
}