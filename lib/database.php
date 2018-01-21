<?php

/**
 * @return null|PDO
 */
function weathermap_get_pdo()
{
    // This is the Cacti standard settings
    global $database_type, $database_default, $database_hostname, $database_username, $database_password;
    global $config;

    $cacti_version = $config["cacti_version"];

    $host = $database_hostname;
    $dbname = $database_default;
    $user = $database_username;
    $pass = $database_password;

    $pdo = null;

    try {
        # MySQL with PDO_MYSQL
        $pdo = new \PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

    return $pdo;
}
