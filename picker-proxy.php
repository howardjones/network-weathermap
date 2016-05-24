<?php

    $action = isset($_REQUEST['action']) ? trim(strtolower($_REQUEST['action'])) : "";
    $auth = isset($_REQUEST['auth']) ? trim(strtolower($_REQUEST['auth'])) : "";

    switch ($action)
    {
        case 'list_collections':
                        
            break;
       

        default:
            print "{result: 0}";
    }
