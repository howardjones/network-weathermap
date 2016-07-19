<?php

// NOTE: this is included in setup.php, so stuff in here pollutes the Cacti namespace

function weathermap_get_pdo()
{
    // This is the Cacti standard settings
    global $database_type, $database_default, $database_hostname, $database_username, $database_password;
    global $config;

    $cacti_version = $config["cacti_version"];
    
    // TODO: Do clever stuff in here to get us the host application's PDO session, if possible.

    $host = $database_hostname;
    $dbname = $database_default;
    $user = $database_username;
    $pass = $database_password;

    try {
        # MySQL with PDO_MYSQL
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
        $pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

    }
    catch(PDOException $e) {
        echo $e->getMessage();
    }

    return $pdo;
}


function weathermap_get_table_list($pdo)
{

    $statement = $pdo->query("show tables");
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);

    $tables = array();
    $sql = array();

    foreach ($result as $index => $arr) {
        foreach ($arr as $t) {
            $tables[] = $t;
        }
    }

    return $tables;
}