<?php


class WeatherMapConfigItemSchema {
    const WM_CONFIG_SCHEMA_STRING = 1;
    const WM_CONFIG_SCHEMA_POSITIVE_INT = 1;
    const WM_CONFIG_SCHEMA_INT = 1;
    const WM_CONFIG_SCHEMA_POSITIVE_FLOAT = 1;
    const WM_CONFIG_SCHEMA_FLOAT = 1;
    const WM_CONFIG_SCHEMA_BANDWIDTH = 1;
    const WM_CONFIG_SCHEMA_COLOUR = 1;
    const WM_CONFIG_SCHEMA_COLOUR_OR_NONE = 1;
    const WM_CONFIG_SCHEMA_COLOUR_OR_NONE_OR_CONTRAST = 1;
    const WM_CONFIG_SCHEMA_COLOUR_OR_CONTRAST = 1;
    const WM_CONFIG_SCHEMA_ENUM = 1;
    const WM_CONFIG_SCHEMA_BOOLEAN = 1;
    const WM_CONFIG_SCHEMA_PERCENTAGE = 1;
    const WM_CONFIG_SCHEMA_COORDINATE = 1;

	var $item_type;
	var $name;
	var $schema_type;
	var $enums = [];
}

class WeatherMapConfigItem {
	var $name;
	var $value;
	var $schema;
}

class WeatherMapConfigScope {
	var $parent;
	var $items = [];
}

