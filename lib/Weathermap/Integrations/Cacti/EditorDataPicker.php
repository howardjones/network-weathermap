<?php


namespace Weathermap\Integrations\Cacti;

use Weathermap\UI\UIBase;
use Weathermap\UI\SimpleTemplate;
use PDO;

/**
 * Data/Graph picker for the Editor, looking inside the Cacti database
 *
 * @package Weathermap\Integrations\Cacti
 *
 */
class EditorDataPicker extends UIBase
{
    public $pdo;

    public $commands = array(
        "link_step1" => array(
            "args" => array(
                array("host_id", "int", true),
                array("aggregate", "int", true),
                array("overlib", "int", true),
                array("target", "string", true)
            ),
            "handler" => "handleLinkStep1"
        ),
        "link_step2" => array(
            "args" => array(
                array("host_id", "int", true),
                array("ds_stats", "int", true),
                array("dataid", "int")
            ),
            "handler" => "handleLinkStep2"
        ),
        "node_step1" => array(
            "args" => array(
                array("host_id", "int", true),
                array("aggregate", "int", true),
                array("overlib", "int", true),
                array("target", "string", true)
            ),
            "handler" => "handleNodeStep1"
        )

    );

    public function main($request, $fromPlugin = false)
    {
        $action = "";

        if (isset($request['action'])) {
            $action = strtolower(trim($request['action']));
        }

        if ($this->validateRequest($action, $request)) {
            $result = $this->dispatchRequest($action, $request, null);
        }
    }

    public function handleNodeStep1($request, $context = null)
    {
        global $config; // Cacti config object

        $hostId = -1;

        $overlib = 0;
        $aggregate = 0;

        if (array_key_exists("overlib", $request) && $request['overlib'] == 1) {
            $overlib = 1;
        }

        if (array_key_exists("aggregate", $request) && $request['aggregate'] == 1) {
            $aggregate = 1;
        }

        // $pdo = weathermap_get_pdo();

        if (isset($request['host_id'])) {
            $hostId = intval($request['host_id']);
        }

        if ($hostId >= 0) {
            $picklistSQL = "SELECT graph_templates_graph.id, graph_local.host_id, graph_templates_graph.local_graph_id, graph_templates_graph.height, graph_templates_graph.width, graph_templates_graph.title_cache AS description, graph_templates.name, graph_local.host_id	FROM (graph_local,graph_templates_graph) LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id) WHERE graph_local.id=graph_templates_graph.local_graph_id ";
            $picklistSQL .= " and graph_local.host_id=? ";
            $picklistSQL .= " order by title_cache";
            $statement = $this->pdo->prepare($picklistSQL);
            $statement->execute(array($hostId));
        } else {
            $picklistSQL = "SELECT graph_templates_graph.id, graph_local.host_id, graph_templates_graph.local_graph_id, graph_templates_graph.height, graph_templates_graph.width, graph_templates_graph.title_cache AS description, graph_templates.name, graph_local.host_id	FROM (graph_local,graph_templates_graph) LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id) WHERE graph_local.id=graph_templates_graph.local_graph_id ";
            $picklistSQL .= " order by title_cache";
            $statement = $this->pdo->prepare($picklistSQL);
            $statement->execute();
        }

        $sources = $statement->fetchAll(PDO::FETCH_ASSOC);

        uasort($sources, array($this, "usortNaturalDescriptions"));

        $hosts = $this->cactiHostList($this->pdo);

        $target = "";
        if (array_key_exists('target', $request) && isset($request['target'])) {
            $target = htmlspecialchars($request['target'], ENT_QUOTES, 'UTF-8');
        }

        $tpl = new SimpleTemplate();
        $tpl->set("title", "Pick a graph");
        $tpl->set("target", $target);
        $tpl->set("hosts", $hosts);
        $tpl->set("sources", $sources);
        $tpl->set("overlib", ($overlib ? 1 : 0));
        $tpl->set("selected_host", $hostId);
        $tpl->set("aggregate", ($aggregate ? 1 : 0));
        $tpl->set("base_url", json_encode(isset($config['base_url']) ? $config['base_url'] : ''));
        $tpl->set("rra_path", json_encode($config['rra_path']));

        echo $tpl->fetch("editor-resources/templates/picker-graph.php");
    }

    private function unpackBoolean($request, $name, $default)
    {
        if (!isset($request[$name])) {
            return $default;
        }
        if ($request[$name] == 0) {
            return false;
        }
        return true;
    }

    public function handleLinkStep1($request, $context = null)
    {
        global $config;

//        $pdo = weathermap_get_pdo();

        $overlib = $this->unpackBoolean($request, 'overlib', true);
        $aggregate = $this->unpackBoolean($request, 'aggregate', false);

        if (isset($request['host_id']) && intval($request['host_id']) >= 0) {
            $hostID = intval($request['host_id']);
            $statement = $this->pdo->prepare("SELECT data_local.host_id, data_template_data.local_data_id, data_template_data.name_cache AS description, data_template_data.active, data_template_data.data_source_path FROM data_local,data_template_data,data_input,data_template WHERE data_local.id=data_template_data.local_data_id AND data_input.id=data_template_data.data_input_id AND data_local.data_template_id=data_template.id  AND data_local.host_id=?  ORDER BY name_cache;");
            $statement->execute(array(intval($request['host_id'])));
        } else {
            $statement = $this->pdo->prepare("SELECT data_local.host_id, data_template_data.local_data_id, data_template_data.name_cache AS description, data_template_data.active, data_template_data.data_source_path FROM data_local,data_template_data,data_input,data_template WHERE data_local.id=data_template_data.local_data_id AND data_input.id=data_template_data.data_input_id AND data_local.data_template_id=data_template.id  ORDER BY name_cache;");
            $statement->execute();
            $hostID = -1;
        }

        $sources = $statement->fetchAll(PDO::FETCH_ASSOC);
        uasort($sources, array($this, "usortNaturalDescriptions"));

        $hosts = $this->cactiHostList($this->pdo);

        $target = "";
        if (array_key_exists('target', $request) && isset($request['target'])) {
            $target = htmlspecialchars($request['target'], ENT_QUOTES, 'UTF-8');
        }

        $tpl = new SimpleTemplate();
        $tpl->set("title", "Pick a data source");
        $tpl->set("target", $target);
        $tpl->set("selected_host", $hostID);
        $tpl->set("hosts", $hosts);
        $tpl->set("recents", self::getRecentHosts());
        $tpl->set("sources", $sources);
        $tpl->set("overlib", ($overlib ? 1 : 0));
        $tpl->set("aggregate", ($aggregate ? 1 : 0));
        $tpl->set("base_url", json_encode(isset($config['base_url']) ? $config['base_url'] : ''));
        $tpl->set("rra_path", json_encode($config['rra_path']));

        echo $tpl->fetch("editor-resources/templates/picker-data.php");
    }


    public function handleLinkStep2($request, $context = null)
    {
        global $config;

        $dataId = intval($request['dataid']);
        $hostId = $request['host_id'];

        list($graphId, $name) = self::getCactiGraphForDataSource($dataId);

        self::updateRecentHosts($hostId, $name);

        $tpl = new SimpleTemplate();
        $tpl->set("graphId", $graphId);
        $tpl->set("base_url", json_encode(isset($config['base_url']) ? $config['base_url'] : ''));

        echo $tpl->fetch("editor-resources/templates/picker-update.php");
    }


    public function updateRecentHosts($hostId, $name)
    {
        if ($hostId > 0 && !in_array($hostId, $_SESSION['cacti']['weathermap']['last_used_host_id'])) {
            $_SESSION['cacti']['weathermap']['last_used_host_id'][] = $hostId;
            $_SESSION['cacti']['weathermap']['last_used_host_name'][] = $name; // $line['title_cache']

            $_SESSION['cacti']['weathermap']['last_used_host_id'] = array_slice(
                $_SESSION['cacti']['weathermap']['last_used_host_id'],
                -5
            );
            $_SESSION['cacti']['weathermap']['last_used_host_name'] = array_slice(
                $_SESSION['cacti']['weathermap']['last_used_host_name'],
                -5
            );
        }
    }

    public function getRecentHosts()
    {
        $recents = array();
        $last = array();
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
//        $pdo = weathermap_get_pdo();

        $statement = $this->pdo->prepare("SELECT graph_templates_item.local_graph_id, title_cache FROM graph_templates_item,graph_templates_graph,data_template_rrd WHERE graph_templates_graph.local_graph_id = graph_templates_item.local_graph_id  AND task_item_id=data_template_rrd.id AND local_data_id=? LIMIT 1;");
        $statement->execute(array($dataId));
        $line = $statement->fetch(PDO::FETCH_ASSOC);

        return array($line['local_graph_id'], $line['title_cache']);
    }

    /**
     * @param $pdo
     * @return mixed
     */
    protected function cactiHostList($pdo)
    {
        $statement = $pdo->prepare("SELECT id,CONCAT_WS('',description,' (',hostname,')') AS name FROM host ORDER BY description,hostname");
        $statement->execute();

        $hosts = $statement->fetchAll(PDO::FETCH_ASSOC);
        uasort($hosts, array($this, "usortNaturalNames"));

        return $hosts;
    }

    /** usortNaturalNames - sorts two values naturally (ie. ab1, ab2, ab7, ab10, ab20)
     * @arg $a - the first string to compare
     * @arg $b - the second string to compare
     * @returns int - '1' if $a is greater than $b, '-1' if $a is less than $b, or '0' if
     * $b is equal to $b */
    public function usortNaturalNames($a, $b)
    {
        return strnatcasecmp($a['name'], $b['name']);
    }

    /** usortNaturalDescriptions - sorts two values naturally (ie. ab1, ab2, ab7, ab10, ab20)
     * @arg $a - the first string to compare
     * @arg $b - the second string to compare
     * @returns int - '1' if $a is greater than $b, '-1' if $a is less than $b, or '0' if
     * $b is equal to $b */
    public function usortNaturalDescriptions($a, $b)
    {
        return strnatcasecmp($a['description'], $b['description']);
    }
}
