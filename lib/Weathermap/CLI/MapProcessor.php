<?php

namespace Weathermap\CLI;

use GetOpt\GetOpt;
use GetOpt\Option;
use GetOpt\ArgumentException;
use Weathermap\Core\Map;

/**
 * A base class for various command-line tools that take in a map config file, process it, and spit out a new one.
 */
class MapProcessor
{
    /** @var GetOpt $getOpt */
    private $getOpt;

    /** @var Map $map */
    private $map;

    private $version = "Map Processor Base Class v1.0";

    private function addOptions()
    {
        // override this function in subclasses for new options
        // --version and --help are always added

        $this->getOpt->addOptions(
            array(
            Option::create(null, 'input', GetOpt::REQUIRED_ARGUMENT)
                ->setDescription('filename to read from. Default weathermap.conf')
                ->setArgumentName('input_filename')
                ->setDefaultValue('weathermap.conf'),
            Option::create(null, 'output', GetOpt::REQUIRED_ARGUMENT)
                ->setDescription('filename to write to . Default weathermap-new.conf')
                ->setArgumentName('output_filename')
                ->setDefaultValue('weathermap-new.conf'),
            )
        );
    }

    private function getOptions()
    {
        $this->getOpt = new GetOpt(null, [GetOpt::SETTING_STRICT_OPERANDS => true]);

        $this->addOptions();

        $this->getOpt->addOptions(
            array(
            Option::create(null, 'version', GetOpt::NO_ARGUMENT)
                ->setDescription('Show version info and quit'),
            Option::create('h', 'help', GetOpt::NO_ARGUMENT)
                ->setDescription('Show this help and quit'),
            )
        );

        // process arguments and catch user errors
        try {
            $this->getOpt->process();
        } catch (ArgumentException $exception) {
            file_put_contents('php://stderr', $exception->getMessage() . PHP_EOL);
            echo PHP_EOL . $this->getOpt->getHelpText();
            exit;
        }

        // show version and quit
        if ($this->getOpt->getOption('version')) {
            echo $this->version . "\n";
            exit;
        }

        // show help and quit
        if ($this->getOpt->getOption('help')) {
            echo $this->getOpt->getHelpText();
            exit;
        }
    }

    private function processMap()
    {
        // do magic in here
    }

    public function run()
    {
        $this->getOptions();

        $this->map = new Map;
//        $this->map->context = 'cacti';

        $this->map->ReadConfig($this->getOpt->getOption('input_filename'));
        $this->processMap();
        $this->map->WriteConfig($this->getOpt->getOption('output_filename'));
    }
}
