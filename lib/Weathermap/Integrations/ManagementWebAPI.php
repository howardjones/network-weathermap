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
    }


}
