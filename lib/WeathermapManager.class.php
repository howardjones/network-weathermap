<?php

class WeathermapManagedMap
{
    public $sortorder;
    public $group_id;
    public $active;
    public $configfile;
    public $imagefile;
    public $htmlfile;
    public $titlecache;
    public $filehash;
    public $warncount;
    public $config;
    public $thumb_width;
    public $thumb_height;
    public $schedule;
    public $archiving;
}

class WeathermapManager
{

    var $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getMap($id)
    {
        $statement = $this->pdo->prepare("select * from weathermap_maps where id=?");
        $statement->execute(array($id));
        $map = $statement->fetch(PDO::FETCH_OBJ);

        return $map;
    }

    private function make_set($data, $allowed)
    {
        $values = array();
        $set = "";
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $set .= "`" . str_replace("`", "``", $field) . "`" . "=:$field, ";
                $values[$field] = $data[$field];
            }
        }
        $set = substr($set, 0, -2);

        return $set;
    }

    public function updateMap($id, $data)
    {
        // $data = ['name' => 'foo','submit' => 'submit']; // data for insert
        $allowed = ["active", "sortorder", "group_id"]; // allowed fields
        $set = $this->make_set($data, $allowed);

        $values['id'] = $id;

        $stmt = $this->pdo->prepare("UPDATE weathermap_maps SET $set where id=:id");
        $stmt->execute($values);
    }

    public function deleteMap($id)
    {
        $this->pdo->prepare("delete from weathermap_maps where id=?")->execute(array($id));
        $this->pdo->prepare("delete from weathermap_auth where mapid=?")->execute(array($id));
        $this->pdo->prepare("delete from weathermap_settings where mapid=?")->execute(array($id));
    }

    public function addPermission($map_id, $user_id)
    {
        $this->pdo->prepare("insert into weathermap_auth (mapid,userid) values(?,?)")->execute(array($map_id, $user_id));
    }
    public function removePermission($map_id, $user_id)
    {
        $this->pdo->prepare("delete from weathermap_auth where mapid=? and userid=?")->execute(array($map_id, $user_id));
    }
}
