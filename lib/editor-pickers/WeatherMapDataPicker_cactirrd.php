<?php

class WeatherMapDataPicker_cactirrd extends WeatherMapDataPicker
{
    function getInfo()
    {
        return(array("nlevels"=>4, levels=>array("host","ds_template","instance","ds_names")));
    }

    function initialise($config)
    {

    }

    var $config;
    var $db_link = null;
    function CactiDataPicker($cfg) {
        $this->config = $cfg;
    }
    function db_connect() {
        if (is_null ( $this->db_link )) {
            $this->db_link = mysql_connect ( $this->config ['db_hostname'], $this->config ['db_username'], $this->config ['db_password'] ) or die ( 'Could not connect: ' . mysql_error () );
            mysql_selectdb ( $this->config ['db_database'], $this->db_link ) or die ( 'Could not select database: ' . mysql_error () );
        }
    }
    function get_metadata() {
        return array (
            "name" => "Cacti Data Sources",
            "levels" => 3,
            "final_ds" => 1,
            "headings" => array (
                "Host",
                "Data Template",
                "Datasource",
                "DS Names"
            )
        );
    }
    function get_list($keys) {
        $this->db_connect ();

        $result_list = array ();

        // first level - pick a host
        if (! isset ( $keys ) || count ( $keys ) == 0) {
            $sql = "select id, description, hostname from host order by description,hostname";

            $result = mysql_query ( $sql );

            $result_list = array ();

            while ( $host = mysql_fetch_assoc ( $result ) ) {
                $result_list [] = array (
                    $host ['id'],
                    $host ['description'],
                    $host ['hostname']
                );
            }
            mysql_free_result ( $result );
        }

        // second level - show data templates for that host
        if (isset ( $keys ) && count ( $keys ) == 1) {
            $result_list [] = array (
                "X",
                "Data Template Name",
                "description"
            );
        }		// third level - show data sources for that host and template
        elseif (isset ( $keys ) && count ( $keys ) == 2) {
            $result_list [] = array (
                "X",
                "Data Source",
                "description"
            );
        } else {
            $result_list [] = "CONFUSED";
        }

        return $result_list;
    }
}
