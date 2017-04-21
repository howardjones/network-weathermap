<?php

require_once dirname(__FILE__) . '/SimpleTemplate.class.php';
require_once dirname(__FILE__) . '/WeatherMapEditorUI.class.php';

class EditorDataPicker extends WeatherMapUIBase
{

    var $commands = array(
        "link_step1" => array(
            "args" => array(
                array("host_id", "int", true),
                array("aggregate", "int", true),
                array("overlib", "int", true)
            ),
            "handler" => "handleLinkStep1"
        ),
        "link_step2" => array(
            "args" => array(
                array("host_id", "int", true),
                array("dataid", "int")
            ),
            "handler" => "handleLinkStep2"
        ),
        "node_step1" => array(
            "args" => array(
                array("host_id", "int", true),
                array("aggregate", "int", true),
                array("overlib", "int", true)
            ),
            "handler" => "handleNodeStep1"
        )

    );

    function main($request, $from_plugin = false)
    {
        $action = "";

        if (isset($request['action'])) {
            $action = strtolower(trim($request['action']));
        }

        if ($this->validateRequest($action, $request)) {
            $result = $this->dispatchRequest($action, $request, null);
        } else {
            echo "POOP";
        }
    }

    public function handleLinkStep1($request, $context=null)
    {
        global $config;

        $pdo = weathermap_get_pdo();

        $host_id = -1;

        $overlib = true;
        $aggregate = false;

        if (isset($request['aggregate'])) {
            $aggregate = ($request['aggregate'] == 0 ? false : true);
        }

        if (isset($request['overlib'])) {
            $overlib = ($request['overlib'] == 0 ? false : true);
        }

        if (isset($request['host_id']) && intval($request['host_id']) >= 0) {
            $host_id = intval($request['host_id']);
            $statement = $pdo->prepare("SELECT data_local.host_id, data_template_data.local_data_id, data_template_data.name_cache as description, data_template_data.active, data_template_data.data_source_path FROM data_local,data_template_data,data_input,data_template WHERE data_local.id=data_template_data.local_data_id AND data_input.id=data_template_data.data_input_id AND data_local.data_template_id=data_template.id  AND data_local.host_id=?  ORDER BY name_cache;");
            $statement->execute(array(intval($request['host_id'])));
        } else {
            $statement = $pdo->prepare("SELECT data_local.host_id, data_template_data.local_data_id, data_template_data.name_cache as description, data_template_data.active, data_template_data.data_source_path FROM data_local,data_template_data,data_input,data_template WHERE data_local.id=data_template_data.local_data_id AND data_input.id=data_template_data.data_input_id AND data_local.data_template_id=data_template.id  ORDER BY name_cache;");
            $statement->execute();
        }

        $sources = $statement->fetchAll(PDO::FETCH_ASSOC);
        uasort($sources, "usortNaturalDescriptions");

        $hosts_stmt = $pdo->prepare("SELECT id,CONCAT_WS('',description,' (',hostname,')') AS name FROM host ORDER BY description,hostname");
        $hosts_stmt->execute();
        $hosts = $hosts_stmt->fetchAll(PDO::FETCH_ASSOC);
        uasort($hosts, "usortNaturalNames");

        $tpl = new SimpleTemplate();
        $tpl->set("title", "Pick a data source");
        $tpl->set("selected_host", $host_id);
        $tpl->set("hosts", $hosts);
        $tpl->set("recents", self::getRecentHosts());
        $tpl->set("sources", $sources);
        $tpl->set("overlib", ($overlib ? 1 : 0));
        $tpl->set("aggregate", ($aggregate ? 1 : 0));
        $tpl->set("base_url", jsEscape(isset($config['base_url']) ? $config['base_url'] : ''));
        $tpl->set("rra_path", jsEscape($config['rra_path']));

        echo $tpl->fetch("editor-resources/templates/picker-data.php");

    }


    public function handleLinkStep2($request, $context=null)
    {
        $dataId = intval($_REQUEST['dataid']);
        $hostId = $_REQUEST['host_id'];

        list($graphId, $name) = self::getCactiGraphForDataSource($dataId);

        self::updateRecentHosts($hostId, $name);

        $tpl = new SimpleTemplate();
        $tpl->set("graphId", $graphId);
        $tpl->set("base_url", isset($config['base_url']) ? $config['base_url'] : '');

        echo $tpl->fetch("editor-resources/templates/picker-update.php");

    }


        public function updateRecentHosts($hostId, $name)
    {
        if ($hostId > 0 && !in_array($hostId, $_SESSION['cacti']['weathermap']['last_used_host_id'])) {
            $_SESSION['cacti']['weathermap']['last_used_host_id'][] = $hostId;
            $_SESSION['cacti']['weathermap']['last_used_host_name'][] = $name; // $line['title_cache']

            $_SESSION['cacti']['weathermap']['last_used_host_id'] = array_slice($_SESSION['cacti']['weathermap']['last_used_host_id'],
                -5);
            $_SESSION['cacti']['weathermap']['last_used_host_name'] = array_slice($_SESSION['cacti']['weathermap']['last_used_host_name'],
                -5);
        }
    }

    public function getRecentHosts()
    {
        $recents = array();
        if (isset($_SESSION['cacti']['weathermap']['last_used_host_id'][0])) {
            $last['id'] = array_reverse($_SESSION['cacti']['weathermap']['last_used_host_id']);
            $last['name'] = array_reverse($_SESSION['cacti']['weathermap']['last_used_host_name']);

            foreach ($last['id'] as $key => $id) {
                list($name) = explode(" - ", $last['name'][$key], 2);
                $recents[$id] = $name;
            }
        }
        return $recents;
    }

    public function getCactiGraphForDataSource($dataId)
    {
        $pdo = weathermap_get_pdo();

        $statement = $pdo->prepare("SELECT graph_templates_item.local_graph_id, title_cache FROM graph_templates_item,graph_templates_graph,data_template_rrd WHERE graph_templates_graph.local_graph_id = graph_templates_item.local_graph_id  AND task_item_id=data_template_rrd.id AND local_data_id=? LIMIT 1;");
        $statement->execute(array($dataId));
        $line = $statement->fetch(PDO::FETCH_ASSOC);

        return array($line['local_graph_id'], $line['title_cache']);
    }

}

function jsEscape($str)
{
    $str = str_replace('\\', '\\\\', $str);
    $str = str_replace("'", "\\'", $str);

    $str = "'" . $str . "'";

    return $str;
}

/** usortNaturalNames - sorts two values naturally (ie. ab1, ab2, ab7, ab10, ab20)
 * @arg $a - the first string to compare
 * @arg $b - the second string to compare
 * @returns - '1' if $a is greater than $b, '-1' if $a is less than $b, or '0' if
 * $b is equal to $b */
function usortNaturalNames($a, $b)
{
    return strnatcasecmp($a['name'], $b['name']);
}

/** usortNaturalDescriptions - sorts two values naturally (ie. ab1, ab2, ab7, ab10, ab20)
 * @arg $a - the first string to compare
 * @arg $b - the second string to compare
 * @returns - '1' if $a is greater than $b, '-1' if $a is less than $b, or '0' if
 * $b is equal to $b */
function usortNaturalDescriptions($a, $b)
{
    return strnatcasecmp($a['description'], $b['description']);
}

class MasterCactiPicker
{

    function pickerDispatch($request)
    {
        if (isset($request['command'])) {
            if ($request["command"] == 'link_step1') {
                link_pick_step1($request);
            }

            if ($request["command"] == 'link_step2') {
                link_pick_step2(intval($request['dataid']));
            }

            if ($request["command"] == 'node_step1') {
                node_pick_step1($request);
            }
        }
    }

    function last_used()
    {
        if (isset($_SESSION['cacti']['weathermap']['last_used_host_id'][0])) {
            print "<b>Last Host Selected:</b><br>";
            $last['id'] = array_reverse($_SESSION['cacti']['weathermap']['last_used_host_id']);
            $last['name'] = array_reverse($_SESSION['cacti']['weathermap']['last_used_host_name']);

            foreach ($last['id'] as $key => $id) {
                list($name) = explode(" - ", $last['name'][$key], 2);
                print "<a href=cacti-pick.php?host_id=" . $id . "&command=link_step1&overlib=1&aggregate=0>[" . $name . "]</a><br>";
            }
        }
    }

    function link_pick_step1($request)
    {
        global $config; // Cacti config object

        $host_id = -1;

        $overlib = true;
        $aggregate = false;

        if (isset($request['aggregate'])) {
            $aggregate = ($request['aggregate'] == 0 ? false : true);
        }
        if (isset($request['overlib'])) {
            $overlib = ($request['overlib'] == 0 ? false : true);
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

    function cactiHostList()
    {
        $pdo = weathermap_get_pdo();

        $hosts = db_fetch_assoc("select id,CONCAT_WS('',description,' (',hostname,')') as name from host order by description,hostname");
        uasort($hosts, "usortNaturalHosts");

        return $hosts;
    }

    function cactiGraphFromDSID($local_data_id)
    {

        $pdo = weathermap_get_pdo();

        $statement = $pdo->prepare("SELECT graph_templates_item.local_graph_id, title_cache FROM graph_templates_item,graph_templates_graph,data_template_rrd WHERE graph_templates_graph.local_graph_id = graph_templates_item.local_graph_id  AND task_item_id=data_template_rrd.id AND local_data_id=? LIMIT 1;");
        $statement->execute(array($local_data_id));
        $line = $statement->fetch(PDO::FETCH_ASSOC);

//    $SQL_graphid = sprintf("select graph_templates_item.local_graph_id, title_cache FROM graph_templates_item,graph_templates_graph,data_template_rrd where graph_templates_graph.local_graph_id = graph_templates_item.local_graph_id  and task_item_id=data_template_rrd.id and local_data_id=%d LIMIT 1;", $dataid);

//    $result = mysql_query($SQL_graphid) or die('Query failed: ' . mysql_error());
//    $line = mysql_fetch_array($result, MYSQL_ASSOC);
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

        // TODO: this should be a 3rd template.
        ?>
        <html>
        <head>
            <script type="text/javascript" src="vendor/jquery/dist/jquery.min.js"></script>
            <script type="text/javascript" src="editor-resources/cacti-pick.js"></script>
            <script type="text/javascript">
                var base_url = <?php echo isset($config['base_url']) ? $config['base_url'] : ''; ?>;
                window.onload = update_source_link_step2(<?php echo $graph_id ?>);
            </script>
        </head>
        <body>This window should disappear in a moment.</body>
        </html>
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

        $pdo = weathermap_get_pdo();

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

}
