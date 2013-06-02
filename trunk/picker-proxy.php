<?php

	require_once "lib/JSON.php";

	$json = new Services_JSON();
	$input = file_get_contents('editor-collections.cfg', 1000000);
	$config = $json->decode($input);
	
		
    $action = isset($_REQUEST['action']) ? trim(strtolower($_REQUEST['action'])) : "";
    $auth = isset($_REQUEST['auth']) ? trim(strtolower($_REQUEST['auth'])) : "";
		
	$action = "list_collections";
			
	header("Content-type: application/json");
	
    switch ($action)
    {
        case 'list_collections':
			$return['collections'] = array();
            foreach ($config->sourcecollections as $details) {
				$return['collections'] []= $details->name;				
			}
			$return['result'] = "ok";
			print $json->encode($return);
            break;

		case "get_level_choices":
			$collection = isset($_REQUEST['col']) ? trim(strtolower($_REQUEST['col'])) : "";
			$level1key = isset($_REQUEST['l1']) ? trim(strtolower($_REQUEST['l1'])) : "";
			$level2key = isset($_REQUEST['l2']) ? trim(strtolower($_REQUEST['l2'])) : "";
			$level3key = isset($_REQUEST['l3']) ? trim(strtolower($_REQUEST['l3'])) : "";
			
        default:
			$return["result"] = "unknown";
			print $json->encode($return);
            # print "{result: 0}";
    }
?>
