<?php

namespace Weathermap\Integrations;

class ManagementWebAPI
{
    /** @var MapManager $manager */
    private $manager;

    function __construct($manager)
    {
        $this->manager = $manager;
    }

    public function mapList()
    {
        header('Content-type: application/json');

        $groups = $this->manager->getGroups();
        $group_assoc = array();

        foreach ($groups as $group) {
            $group_assoc[$group->id] = $group;
        }

        $data = array(
            'maps' => $this->manager->getMaps(),
            'groups' => $group_assoc
        );

        print json_encode($data);
    }

    public function mapEnable($id)
    {
        header('Content-type: application/json');
    }

    public function mapDisable($id)
    {
        header('Content-type: application/json');
    }

    public function mapDelete($id)
    {
        $data = array("result" => "OK");

        if ($this->manager->mapExists($id)) {
            $this->manager->deleteMap($id);
        } else {
            $data = array("result" => "error", "message" => "No such map");
        }

        header('Content-type: application/json');
        return json_encode($data);
    }

    public function groupCreate($name)
    {
        $group_id = $this->manager->createGroup($name);

        $data = array("result" => "OK", "id" => $group_id);

        header('Content-type: application/json');
        return json_encode($data);
    }

    public function groupDelete($id)
    {
        $data = array("result" => "OK");

        if ($this->manager->groupExists($id)) {
            $this->manager->deleteMap($id);
        } else {
            $data = array("result" => "error", "message" => "No such group");
        }

        header('Content-type: application/json');
        return json_encode($data);
    }
}
