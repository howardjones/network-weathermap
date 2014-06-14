<?php


class WeatherMapConfigSchemaItem {
    const WM_CONFIG_SCHEMA_STRING = 1;
    const WM_CONFIG_SCHEMA_POSITIVE_INT = 2;
    const WM_CONFIG_SCHEMA_INT = 3;
    const WM_CONFIG_SCHEMA_POSITIVE_FLOAT = 3;
    const WM_CONFIG_SCHEMA_FLOAT = 5;
    const WM_CONFIG_SCHEMA_BANDWIDTH = 6;
    const WM_CONFIG_SCHEMA_COLOUR = 7;
    const WM_CONFIG_SCHEMA_COLOUR_OR_NONE = 8;
    const WM_CONFIG_SCHEMA_COLOUR_OR_NONE_OR_CONTRAST = 9;
    const WM_CONFIG_SCHEMA_COLOUR_OR_CONTRAST = 10;
    const WM_CONFIG_SCHEMA_ENUM = 11;
    const WM_CONFIG_SCHEMA_BOOLEAN = 12;
    const WM_CONFIG_SCHEMA_PERCENTAGE = 13;
    const WM_CONFIG_SCHEMA_COORDINATE = 14;
    const WM_CONFIG_SCHEMA_NODENAME = 15;

    var $item_type;
    var $name;
    var $schema_type;
    var $enums = [];

    function WeatherMapConfigSchemaItem($name, $data_type, $enumvalues=array())
    {
        $this->name = $name;
        $this->schema_type = $data_type;
        $this->enums = $enumvalues;
    }
}

class WeatherMapConfigSchema {
    var $types = array();

    function WeatherMapConfigSchema()
    {
        $this->types['GLOBAL'] = array();
        $this->types['LINK'] = array();
        $this->types['NODE'] = array();
    }

    function loadSchema()
    {
        $this->types['NODE']['LABEL'] []= new WeatherMapConfigSchemaItem('LABEL',WeatherMapConfigSchemaItem::WM_CONFIG_SCHEMA_STRING);
        $this->types['NODE']['LABELBGCOLOR'] []= new WeatherMapConfigSchemaItem('LABELBGCOLOR',WeatherMapConfigSchemaItem::WM_CONFIG_SCHEMA_COLOUR_OR_NONE);
        $this->types['NODE']['LABELSTYLE'] []= new WeatherMapConfigSchemaItem('LABELSTYLE',WeatherMapConfigSchemaItem::WM_CONFIG_SCHEMA_ENUM,array('classic','angled'));
    }

    function getScopeSchema($scope)
    {
        return $this->types[$scope];
    }
}


class WeatherMapConfigItem {
    var $name;
    var $value;
    var $schema;
}

class WeatherMapConfigScope {
    var $parent;
    var $items = [];
    var $attached_object;
    var $children = [];
}

class WeatherMapConfig {
    var $global = null;
    var $nodes = array();
    var $links = array();

    function WeatherMapConfig()
    {
        $this->global = new WeatherMapConfigScope();

        $this->nodes['DEFAULT'] = new WeatherMapConfigScope();
        $this->links['DEFAULT'] = new WeatherMapConfigScope();
    }
}

class WeatherMapConfigReader {

    function readConfig($lines)
    {
        $config = new WeatherMapConfig();
        $schema = new WeatherMapConfigSchema();
        $schema->loadSchema();

        $current_scope = null;
        $current_schema = null;

        $linecount = 0;
        $objectlinecount = 0;

        foreach ($lines as $buffer) {
            $linecount++;
            $buffer = trim($buffer);
            $linematched = false;

            if ($buffer == '' || substr($buffer, 0, 1) == '#') {
                // this is a comment line, or a blank line
            } else {
                if (preg_match("/^(LINK|NODE)\s+(\S+)\s*$/i", $buffer, $matches)) {

                    switch ($matches[1]) {
                        case 'LINK':
                            $config->links[$matches[2]] = new WeatherMapConfigScope();
                            $config->links[$matches[2]]->parent = $config->links['DEFAULT'];
                            $current_scope = $config->links[$matches[2]];
                            $current_schema = $schema->getScopeSchema('LINK');
                            $linematched = true;
                            break;
                        case 'NODE':
                            $config->nodes[$matches[2]] = new WeatherMapConfigScope();
                            $config->nodes[$matches[2]]->parent = $config->nodes['DEFAULT'];
                            $current_scope = $config->nodes[$matches[2]];
                            $current_schema = $schema->getScopeSchema('NODE');
                            $linematched = true;
                            break;
                    }
                }

                print_r($schema);

                if ($linematched === false) {
                    // alternative for use later where quoted strings are more useful
                    $args = wmParseString($buffer);

                    if (true === isset($args[0])) {

                    }
                }
            }
        }
    }

    function readConfigFile($filename)
    {

        $fd = fopen($filename, "r");

        if ($fd) {
            while (!feof($fd)) {
                $buffer = fgets($fd, 16384);
                // strip out any Windows line-endings that have gotten in here
                $buffer = str_replace("\r", "", $buffer);
                $lines[] = $buffer;
            }
            fclose($fd);
        }

        $result = $this->readConfig($lines);

        return $result;
    }
}