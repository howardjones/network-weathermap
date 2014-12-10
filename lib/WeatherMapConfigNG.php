<?php

/*
 * This is the beginnings of a schema-based config parser with validation based on types
 */


class WeatherMapConfigReaderNG
{

    private $lineCount;
    private $currentObject;
    private $currentSource;
    private $mapObject;
    private $objectLineCount;


    function readConfig($lines)
    {
        $config = new WeatherMapConfig();
        $schema = new WeatherMapConfigSchema();
        $schema->loadSchema();

        $current_scope = null;
        $current_schema = null;

        $this->lineCount = 0;
        $this->objectLineCount = 0;

        foreach ($lines as $buffer) {
            $this->lineCount++;
            $buffer = trim($buffer);
            $lineMatched = false;

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
                            $lineMatched = true;
                            break;
                        case 'NODE':
                            $config->nodes[$matches[2]] = new WeatherMapConfigScope();
                            $config->nodes[$matches[2]]->parent = $config->nodes['DEFAULT'];
                            $current_scope = $config->nodes[$matches[2]];
                            $current_schema = $schema->getScopeSchema('NODE');
                            $lineMatched = true;
                            break;
                    }
                }

                if ($lineMatched === false) {
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
        $fileHandle = fopen($filename, "r");

        if ($fileHandle) {
            while (!feof($fileHandle)) {
                $buffer = fgets($fileHandle, 16384);
                // strip out any Windows line-endings that have gotten in here
                $buffer = str_replace("\r", "", $buffer);
                $lines[] = $buffer;
            }
            fclose($fileHandle);
        }

        $this->currentSource = $filename;
        $result = $this->readConfig($lines);

        return $result;
    }
}
