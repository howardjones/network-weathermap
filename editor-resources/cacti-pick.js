"use strict";
/*global jQuery:false */
/*global rra_path:false */
/*global base_url:false */
/*global overlib:false */
/*global aggregate:false */
/*global selected_host:false */

function applyDSFilterChange(objForm) {
    var strURL = '?host_id=' + objForm.host_id.value;
    strURL = strURL + '&command=link_step1';
    if (objForm.overlib.checked) {
        strURL = strURL + "&overlib=1";
    } else {
        strURL = strURL + "&overlib=0";
    }

    if (objForm.aggregate.checked) {
        strURL = strURL + "&aggregate=1";
    } else {
        strURL = strURL + "&aggregate=0";
    }
    document.location = strURL;
}

function update_source_link_step2(graphid) {
    var graph_url, info_url;

    if (typeof window.opener === "object") {

        graph_url = base_url + 'graph_image.php?local_graph_id=' + graphid + '&rra_id=0&graph_nolegend=true&graph_height=100&graph_width=300';
        info_url = base_url + 'graph.php?rra_id=all&local_graph_id=' + graphid;

        opener.document.forms.frmMain.link_infourl.value = info_url;
        opener.document.forms.frmMain.link_hover.value = graph_url;
    }
    window.close();
}

function dataSelected(event) {
    var newlocation;
    var data_id = $(this).data("source-id");
    var path = $(this).data("path");

    var fullpath = path.replace(/<path_rra>/, rra_path);

    console.log(fullpath);

    if (window.opener && typeof window.opener === "object") {
        if (document.forms.mini.aggregate.checked) {
            opener.document.forms.frmMain.link_target.value = opener.document.forms.frmMain.link_target.value + " " + fullpath;
        } else {
            opener.document.forms.frmMain.link_target.value = fullpath;
        }
    }

    // If it's just a TARGET update, we're done, otherwise go onto step 2 to find a matching graph
    if (document.forms.mini.overlib.checked) {
        newlocation = 'cacti-pick.php?command=link_step2&dataid=' + data_id;
        window.location = newlocation;
    } else {
        window.close();
    }
}

function nodeGraphSelected(event) {
    var graph_id = $(this).data("graph-id");
    // var width = $(this).data("width");
    // var height = $(this).data("height");

    var graph_url = base_url + 'graph_image.php?rra_id=0&graph_nolegend=true&graph_height=100&graph_width=300&local_graph_id=' + graph_id;
    var info_url = base_url + 'graph.php?rra_id=all&local_graph_id=' + graph_id;

    console.log(graph_url);

    if (window.opener && typeof window.opener === "object") {
        // only set the overlib URL unless the box is checked
        if (document.forms.mini.overlib.checked) {
            opener.document.forms.frmMain.node_infourl.value = info_url;
        }
        opener.document.forms.frmMain.node_hover.value = graph_url;
    }
    this.close();
}

$(document).ready(function () {
    $("#dslist li:odd").addClass("row0");
    $("#dslist li:even").addClass("row1");

    $('#host_id').change(function () {
        applyDSFilterChange(document.mini);
    });

    $("body.data-picker span.ds_includegraph").show();
    $("body.data-picker span.aggregate").show();
    $("body.data-picker span.ds_dsstats").show();

    if (aggregate) {
        // TODO: Check what this did before!
    }

    if (overlib) {
        // TODO: Check what this did before!
    }

    if (selected_host >= 0) {
        $("select#host_id").val(selected_host);
    }

    $('span.filter').show();
    $('#filterstring').fastLiveFilter("#dslist");

    $("body.data-picker #dslist li").click(dataSelected);
    $("body.graph-picker #dslist li").click(nodeGraphSelected);

});
