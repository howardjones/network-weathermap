_.templateSettings.variable = "rc";

var wmEditor = {
    start: function() {
        // check if there is a "No JavaScript" message
        jQuery("#nojs").hide();

        // hide all the dialog boxes for now
        jQuery(".dlgProperties").hide();
        // but show the 'start' dialog, if there is one... (only on the start page)
        jQuery("#dlgStart").show();

        console.debug(this);
        wmEditor.startClickListeners();
    },

    startClickListeners: function() {
        jQuery('area[id^="LINK:"]').attr("href", "#").click(wmEditor.handleMapClick);
        jQuery('area[id^="NODE:"]').attr("href", "#").click(wmEditor.handleMapClick);

        jQuery('area[id^="LINK:"]').attr("href", "#").on("linkClicked", wmEditor.handleLinkClick);
        jQuery('area[id^="NODE:"]').attr("href", "#").on("nodeClicked", wmEditor.handleNodeClick);

        var click_listeners = {
            "#tb_mapprops": wmEditor.handleMapProperties,
            "#tb_addnode": wmEditor.handleAddNode,
            "#tb_addlink": wmEditor.handleAddLink
        };

        jQuery.each(click_listeners, function (selector, handler) {
            jQuery(selector).click(handler);
            console.log("Added click handler for " + selector);
        });

        // jQuery('area[id^="TIMES"]').attr("href", "#").click(position_timestamp);
        // jQuery('area[id^="LEGEN"]').attr("href", "#").click(position_legend);
    },

    // Translate a click event into a nodeClicked or linkClicked event
    // special handlers for those events do the real work. This will allow us to have multiple methods
    // of selecting nodes/links, like a pick-list.
    handleMapClick: function(e) {
        var element_id, objectname, objecttype, objectid;

        console.log("Click handler!");
        console.debug(this);

        element_id = jQuery(this).attr("id");

        objecttype = element_id.slice(0, 4);
        objectname = element_id.slice(5, element_id.length);
        objectid = objectname.slice(0, objectname.length - 2);

        if (objecttype == 'NODE') {
            objectname = NodeIDs[objectid];
            console.log("custom nodeClicked event for " + objectname);
            jQuery(this).trigger("nodeClicked", [objecttype, objectname]);
        }

        if (objecttype == 'LINK') {
            objectname = LinkIDs[objectid];
            console.log("custom linkClicked event for " + objectname);
            jQuery(this).trigger("linkClicked", [objecttype, objectname]);
        }
    },

    handleLinkClick: function(e, type, name) {
        console.log("Showing link properties for " + name);
        wmEditor.showLinkProperties(name);
    },

    handleNodeClick: function(e, type, name) {
        // TODO - check if we're in the middle of adding a node
        // don't show the properties then.

        var element_id, objectname, objecttype, objectid;

        // alt = el.getAttribute('alt');
        element_id = jQuery(this).attr("id");

        objectname = element_id.slice(5, element_id.length);
        objectid = objectname.slice(0, objectname.length - 2);

        if (jQuery("#action").val() == 'add_link') {
            jQuery("#param").val(NodeIDs[objectid]);
            document.frmMain.submit();
        } else if (jQuery("#action").val() == 'add_link2') {
            jQuery("#param").val(NodeIDs[objectid]);
            document.frmMain.submit();
        } else {
            console.log("Showing node properties for " + name);
            wmEditor.showNodeProperties(name);
        }
    },

    showLinkProperties: function(name) {
        jQuery("#action").val("edit_link");
        wmEditor.setMapMode('existing');
        wmEditor.hideAllDialogs();

        var template = _.template($("script#tpl-dialog-link-properties" ).html());

        $("#mainarea").after(template( {name: name, data: Links[name]} ));
        $("#param").val(name);
        $("#dlgLinkProperties").show();
        $("#dlgLinkProperties").draggable({handle:"h3"});
        $("#link_bandwidth_in").focus();

        $('#link_delete').click(wmEditor.handleDeleteLink);
        $('#link_tidy').click(wmEditor.handleTidyLink);
    },

    showNodeProperties: function(name) {
        jQuery("#action").val("edit_node");
        wmEditor.setMapMode('existing');
        wmEditor.hideAllDialogs();

        var template = _.template($("script#tpl-dialog-node-properties" ).html());

        $("#mainarea").after(template(Nodes[name]));
        $("#param").val(name);
        $("#dlgNodeProperties").show();
        $("#dlgNodeProperties").draggable({handle:"h3"});
        $("#node_new_name").focus();

        $('#node_delete').click(wmEditor.handleDeleteNode);
        $('#node_clone').click(wmEditor.handleCloneNode);
        $('#node_move').click(wmEditor.handleMoveNode);

    },

    hideAllDialogs: function() {
        jQuery(".dlgProperties").remove();
    },

    setMapMode: function(new_mode) {
        if (new_mode == 'xy') {
            jQuery("#debug").val("xy");
            jQuery("#xycapture").show();
            jQuery("#existingdata").hide();
        } else if (new_mode == 'existing') {
            jQuery("#debug").val("existing");
            jQuery("#existingdata").show();
            jQuery("#xycapture").hide();
        } else {
            alert('invalid mode');
        }
    },
    handleAddNode:   function(event, type, name) {
        console.log("in handleAddNode");
        wmEditor.setMapMode('xy');
        jQuery("#action").val("add_node");
    },
    handleAddLink:   function(event, type, name) {
        console.log("in handleAddLink");
        jQuery("#action").val("add_link");
        wmEditor.setMapMode('existing');
    },
    handleDeleteNode: function(event, type, name) {
        jQuery('#action').val('delete_node');
        document.frmMain.submit();
    },
    handleDeleteLink: function(event, type, name) {
        jQuery('#action').val('delete_link');
        document.frmMain.submit();
    },
    handleTidyLink: function(event, type, name) {
        jQuery('#action').val('link_tidy');
        document.frmMain.submit();
    },
    handleCloneNode: function(event, type, name) {
        jQuery('#action').val('clone_node');
        document.frmMain.submit();
    },
    handleMoveNode: function(event, type, name) {
        console.log("move node - setting xy mode");
        jQuery('#action').val('move_node');
        wmEditor.hideAllDialogs();
        wmEditor.setMapMode("xy");
    },
    handleMapProperties:   function(event, type, name) {

        console.log("in handleMapProperties");
        wmEditor.hideAllDialogs();

        var template = _.template($("script#tpl-dialog-map-properties" ).html());
        $("#mainarea").after(template(Map));
        $("#dlgMapProperties").show();
        $("#dlgMapProperties").draggable({handle:"h3"});
    },
    setMapMode: function(mode) {
        if (mode == 'xy') {
            jQuery('#debug').value='xy';
            jQuery('#existingdata').hide();
            jQuery('#xycapture').show();
        } else if (mode == 'existing') {
            jQuery('#debug').value='existing';
            jQuery('#xycapture').hide();
            jQuery('#existingdata').show();
        } else {
            alert('invalid mode');
        }
    }
};

jQuery(document).ready(wmEditor.start);
