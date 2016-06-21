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
        $statement = $this->pdo->prepare("SELECT * FROM weathermap_maps WHERE id=?");
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

        return array($set, $values);
    }

    public function updateMap($id, $data)
    {
        // $data = ['name' => 'foo','submit' => 'submit']; // data for insert
        $allowed = ["active", "sortorder", "group_id"]; // allowed fields
        list($set, $values) = $this->make_set($data, $allowed);

        $values['id'] = $id;

        $stmt = $this->pdo->prepare("UPDATE weathermap_maps SET $set where id=:id");
        $stmt->execute($values);
    }

    public function deleteMap($id)
    {
        $this->pdo->prepare("DELETE FROM weathermap_maps WHERE id=?")->execute(array($id));
        $this->pdo->prepare("DELETE FROM weathermap_auth WHERE mapid=?")->execute(array($id));
        $this->pdo->prepare("DELETE FROM weathermap_settings WHERE mapid=?")->execute(array($id));
    }

    public function addPermission($map_id, $user_id)
    {
        $this->pdo->prepare("INSERT INTO weathermap_auth (mapid,userid) VALUES(?,?)")->execute(array($map_id, $user_id));
    }

    public function removePermission($map_id, $user_id)
    {
        $this->pdo->prepare("DELETE FROM weathermap_auth WHERE mapid=? AND userid=?")->execute(array($map_id, $user_id));
    }

    // Repair the sort order column (for when something is deleted or inserted, or moved between groups)
    // our primary concern is to make the sort order consistent, rather than any special 'correctness'
    public function resortMaps()
    {
        $stmt = $this->pdo->query('SELECT * FROM weathermap_maps ORDER BY group_id,sortorder;');

        $newMapOrder = array();

        $i = 1;
        $lastGroupSeen = -1020.5;
        foreach ($stmt as $map) {
            if ($lastGroupSeen != $map['group_id']) {
                $lastGroupSeen = $map['group_id'];
                $i = 1;
            }
            $newMapOrder[$map['id']] = $i;
            $i++;
        }

        $statement = $this->pdo - prepare("UPDATE weathermap_maps SET sortorder=? WHERE id=?");

        if (!empty($newMapOrder)) {
            foreach ($newMapOrder as $mapId => $sortOrder) {
                $result = $statement->execute(array($sortOrder, $mapId));
            }
        }

    }

    public function moveMap($mapId, $direction)
    {
        $source = $this->pdo->query('SELECT * FROM weathermap_maps WHERE id=?;')->execute(array($mapId));

//        $source = db_fetch_assoc("select * from weathermap_maps where id=$mapId");
        $oldOrder = $source[0]['sortorder'];
        $group = $source[0]['group_id'];

        $newOrder = $oldOrder + $direction;
        $target = $this->pdo->query("SELECT * FROM weathermap_maps WHERE group_id=? AND sortorder =?")->execute(array($group, $newOrder));
//        $target = db_fetch_assoc("select * from weathermap_maps where group_id=$group and sortorder = $newOrder");

        if (!empty($target[0]['id'])) {
            $otherId = $target[0]['id'];
            // move $mapid in direction $direction
            $this->pdo->prepare("UPDATE weathermap_maps SET sortorder =? WHERE id=?")->execute(array($newOrder, $mapId));
//            $sql[] = "update weathermap_maps set sortorder = $newOrder where id=$mapId";
            // then find the other one with the same sortorder and move that in the opposite direction
            $this->pdo->prepare("UPDATE weathermap_maps SET sortorder =? WHERE id=?")->execute(array($oldOrder, $otherId));
//            $sql[] = "update weathermap_maps set sortorder = $oldOrder where id=$otherId";
        }

    }

    public function moveGroup($groupId, $direction)
    {

    }

    public function resortGroups()
    {
        $stmt = $this->pdo->query('SELECT * FROM weathermap_groups ORDER BY sortorder;');

        $newGroupOrder = array();

        $i = 1;
        foreach ($stmt as $map) {
            $newGroupOrder[$map['id']] = $i;
            $i++;
        }
        $statement = $this->pdo - prepare("UPDATE weathermap_groups SET sortorder=? WHERE id=?");

        if (!empty($newGroupOrder)) {
            foreach ($newGroupOrder as $mapId => $sortOrder) {
                $result = $statement->execute(array($sortOrder, $mapId));
            }
        }

    }

}
