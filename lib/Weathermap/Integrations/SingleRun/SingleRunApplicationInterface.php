<?php

namespace Weathermap\Integrations\SingleRun;

use Weathermap\Integrations\ApplicationInterface;

/**
 * The CLI implementation of ApplicationInterface
 *
 * @package Weathermap\Integrations\SingleRun
 */
class SingleRunApplicationInterface extends ApplicationInterface
{
    public function getLocale()
    {
        throw new \Exception("Getting app locale");
        return "en_US";
    }

    public function getAppVersion()
    {
        return "single-run-cli";
    }


    public function getAppSetting($name, $defaultValue = "")
    {
        throw new \Exception("Getting app setting");

        $statement = $this->pdo->prepare("SELECT value FROM settings WHERE name=?");
        $statement->execute(array($name));
        $result = $statement->fetchColumn();

        if ($result === false) {
            return $defaultValue;
        }

        return $result;
    }

    public function getMapURL($mapConfig)
    {
        return sprintf("%s.html", $mapConfig->id);
    }

    public function getMapImageURL($mapConfig)
    {
        return sprintf("%s.png", $mapConfig->id);
    }

}

