<?php

function pickerDispatch($request)
{
    if (isset($request['command'])) {

        if ($request["command"]=='link_step1') {
            link_pick_step1($request);
        }

        if ($request["command"]=='link_step2') {
            link_pick_step2(intval($request['dataid']));
        }

        if ($request["command"]=='node_step1') {
            node_pick_step1($request);
        }
    }
}

function jsEscape($str)
{
    $str = str_replace('\\', '\\\\', $str);
    $str = str_replace("'", "\\'", $str);

    $str = "'".$str."'";

    return($str);
}

/** usort_natural_hosts - sorts two values naturally (ie. ab1, ab2, ab7, ab10, ab20)
@arg $a - the first string to compare
@arg $b - the second string to compare
@returns - '1' if $a is greater than $b, '-1' if $a is less than $b, or '0' if
$b is equal to $b */
function usortNaturalHosts($a, $b)
{
    return strnatcasecmp($a['name'], $b['name']);
}

/** usort_natural_titles - sorts two values naturally (ie. ab1, ab2, ab7, ab10, ab20)
@arg $a - the first string to compare
@arg $b - the second string to compare
@returns - '1' if $a is greater than $b, '-1' if $a is less than $b, or '0' if
$b is equal to $b */
function usortNaturalDescriptions($a, $b)
{
    return strnatcasecmp($a['description'], $b['description']);
}

function link_pick_step1($request)
{
    global $config; // Cacti config object

    $host_id = -1;

    $overlib = true;
    $aggregate = false;

    if (isset($request['aggregate'])) {
        $aggregate = ( $request['aggregate']==0 ? false : true);
    }
    if (isset($request['overlib'])) {
        $overlib = ( $request['overlib']==0 ? false : true);
    }

    $SQL_picklist = "select data_local.host_id, data_template_data.local_data_id, data_template_data.name_cache as description, data_template_data.active, data_template_data.data_source_path from data_local,data_template_data,data_input,data_template where data_local.id=data_template_data.local_data_id and data_input.id=data_template_data.data_input_id and data_local.data_template_id=data_template.id ";

    if (isset($request['host_id'])) {
        $host_id = intval($request['host_id']);
        if ($host_id >= 0) {
            $SQL_picklist .= " and data_local.host_id=$host_id ";
        }
    }

    $SQL_picklist .= " order by name_cache;";

    $sources = db_fetch_assoc($SQL_picklist);
    uasort($sources, "usortNaturalDescriptions");

    $hosts = cactiHostList();

    $tpl = new SimpleTemplate();
    $tpl->set("title", "Pick a data source");
    $tpl->set("selected_host", $host_id);
    $tpl->set("hosts", $hosts);
    $tpl->set("sources", $sources);
    $tpl->set("overlib", ($overlib ? 1 : 0));
    $tpl->set("aggregate", ($aggregate ? 1 : 0));
    $tpl->set("base_url", isset($config['base_url']) ? $config['base_url'] : '');
    $tpl->set("rra_path", jsEscape($config['rra_path']));

    echo $tpl->fetch("editor-resources/templates/picker-data.php");
}

function cactiDatabaseConnect()
{
    global $database_hostname, $database_username, $database_password, $database_default;

    $link = mysql_pconnect($database_hostname, $database_username, $database_password)
        or die('Could not connect: ' . mysql_error());
    mysql_selectdb($database_default, $link) or die('Could not select database: '.mysql_error());

}

function cactiHostList()
{
    cactiDatabaseConnect();

    $hosts = db_fetch_assoc("select id,CONCAT_WS('',description,' (',hostname,')') as name from host order by description,hostname");
    uasort($hosts, "usortNaturalHosts");

    return $hosts;
}

function cactiGraphFromDSID($local_data_id)
{
    cactiDatabaseConnect();

    $SQL_graphid = sprintf("select graph_templates_item.local_graph_id, title_cache FROM graph_templates_item,graph_templates_graph,data_template_rrd where graph_templates_graph.local_graph_id = graph_templates_item.local_graph_id  and task_item_id=data_template_rrd.id and local_data_id=%d LIMIT 1;", $dataid);

    $result = mysql_query($SQL_graphid) or die('Query failed: ' . mysql_error());
    $line = mysql_fetch_array($result, MYSQL_ASSOC);
    $graph_id = $line['local_graph_id'];

    return $graph_id;
}

/**
 * Take the data ID that was picked in step 1, and figure out the graph_id that is related to it.
 *
 * @param $local_data_id
 */
function link_pick_step2($local_data_id)
{
    global $config;

    $graph_id = cactiGraphFromDSID($local_data_id);

    ?>
    <html><head>
        <script type="text/javascript" src="vendor/jquery/dist/jquery.min.js" ></script>
        <script type="text/javascript" src="editor-resources/cacti-pick.js"></script>
        <script type="text/javascript">
            var base_url = <?php echo isset($config['base_url']) ? $config['base_url'] : ''; ?>;
            window.onload = update_source_link_step2(<?php echo $graph_id ?>);
        </script>
    </head><body>This window should disappear in a moment.</body></html>
    <?php
}

function node_pick_step1($request)
{
    global $config; // Cacti config object

    $host_id = -1;

    $overlib = false;
    $aggregate = false;

    $SQL_picklist = "SELECT graph_templates_graph.id, graph_local.host_id, graph_templates_graph.local_graph_id, graph_templates_graph.height, graph_templates_graph.width, graph_templates_graph.title_cache as description, graph_templates.name, graph_local.host_id	FROM (graph_local,graph_templates_graph) LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id) WHERE graph_local.id=graph_templates_graph.local_graph_id ";

    if (isset($request['host_id'])) {
        $host_id = intval($request['host_id']);
        if ($host_id >= 0) {
            $SQL_picklist .= " and graph_local.host_id=$host_id ";
        }
    }
    $SQL_picklist .= " order by title_cache";

    cactiDatabaseConnect();

    $sources = db_fetch_assoc($SQL_picklist);
    uasort($sources, "usortNaturalDescriptions");

    $hosts = cactiHostList();

    $tpl = new SimpleTemplate();
    $tpl->set("title", "Pick a graph");
    $tpl->set("hosts", $hosts);
    $tpl->set("sources", $sources);
    $tpl->set("overlib", ($overlib ? 1 : 0));
    $tpl->set("selected_host", $host_id);
    $tpl->set("aggregate", ($aggregate ? 1 : 0));
    $tpl->set("base_url", isset($config['base_url']) ? $config['base_url'] : '');
    $tpl->set("rra_path", jsEscape($config['rra_path']));

    echo $tpl->fetch("editor-resources/templates/picker-graph.php");
}

