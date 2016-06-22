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
    var $configDirectory;

    public function __construct($pdo, $configDirectory)
    {
        $this->configDirectory = $configDirectory;
        $this->pdo = $pdo;
    }

    public function getMap($mapId)
    {
        $statement = $this->pdo->prepare("SELECT * FROM weathermap_maps WHERE id=?");
        $statement->execute(array($mapId));
        $map = $statement->fetch(PDO::FETCH_OBJ);

        return $map;
    }

    public function getGroup($groupId)
    {
        $statement = $this->pdo->prepare("SELECT * FROM weathermap_groups WHERE id=?");
        $statement->execute(array($groupId));
        $group = $statement->fetch(PDO::FETCH_OBJ);

        return $group;
    }

    public function getMaps()
    {
        $statement = $this->pdo->query("SELECT * FROM weathermap_maps ORDER BY group_id, sortorder");
        $statement->execute();
        $maps = $statement->fetchAll(PDO::FETCH_OBJ);

        return $maps;
    }

    public function getGroups()
    {
        $statement = $this->pdo->query("SELECT * FROM weathermap_groups ORDER BY sortorder");
        $statement->execute();
        $groups = $statement->fetchAll(PDO::FETCH_OBJ);

        return $groups;
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

    public function updateMap($mapId, $data)
    {
        // $data = ['name' => 'foo','submit' => 'submit']; // data for insert
        $allowed = ["active", "sortorder", "group_id"]; // allowed fields
        list($set, $values) = $this->make_set($data, $allowed);

        $values['id'] = $mapId;

        $stmt = $this->pdo->prepare("UPDATE weathermap_maps SET $set where id=:id");
        $stmt->execute($values);
    }

    public function activateMap($mapId)
    {
        $this->updateMap($mapId, array('active' => 'on'));
    }

    public function disableMap($mapId)
    {
        $this->updateMap($mapId, array('active' => 'off'));
    }

    public function setMapGroup($mapId, $groupId)
    {
        $this->updateMap($mapId, array('group_id' => $groupId));
        $this->resortMaps();
    }

    public function deleteMap($id)
    {
        $this->pdo->prepare("DELETE FROM weathermap_maps WHERE id=?")->execute(array($id));
        $this->pdo->prepare("DELETE FROM weathermap_auth WHERE mapid=?")->execute(array($id));
        $this->pdo->prepare("DELETE FROM weathermap_settings WHERE mapid=?")->execute(array($id));

        $this->resortMaps();
    }

    public function addPermission($mapId, $userId)
    {
        $this->pdo->prepare("INSERT INTO weathermap_auth (mapid,userid) VALUES(?,?)")->execute(array($mapId, $userId));
    }

    public function removePermission($mapId, $userId)
    {
        $this->pdo->prepare("DELETE FROM weathermap_auth WHERE mapid=? AND userid=?")->execute(array($mapId, $userId));
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

        $statement = $this->pdo->prepare("UPDATE weathermap_maps SET sortorder=? WHERE id=?");

        if (!empty($newMapOrder)) {
            foreach ($newMapOrder as $mapId => $sortOrder) {
                $statement->execute(array($sortOrder, $mapId));
            }
        }

    }

    public function moveMap($mapId, $direction)
    {
        $source = $this->getMap($mapId);
        $oldOrder = intval($source->sortorder);
        $group = $source->group_id;

        $newOrder = $oldOrder + $direction;

        $statement = $this->pdo->prepare("SELECT * FROM weathermap_maps WHERE group_id=? AND sortorder =? LIMIT 1");
        $statement->execute(array($group, $newOrder));
        $target = $statement->fetch(PDO::FETCH_OBJ);

        if (!empty($target->id)) {
            $otherId = $target->id;
            // move $mapid in direction $direction
            $this->pdo->prepare("UPDATE weathermap_maps SET sortorder =? WHERE id=?")->execute(array(
                $newOrder,
                $mapId
            ));
            // then find the other one with the same sortorder and move that in the opposite direction
            $this->pdo->prepare("UPDATE weathermap_maps SET sortorder =? WHERE id=?")->execute(array(
                $oldOrder,
                $otherId
            ));
        }
    }

    public function moveGroup($groupId, $direction)
    {
        $source = $this->getMap($groupId);

        $oldOrder = intval($source->sortorder);
        $newOrder = $oldOrder + $direction;

        $statement = $this->pdo->prepare("SELECT * FROM weathermap_groups WHERE sortorder =? LIMIT 1");
        $statement->execute(array($newOrder));
        $target = $statement->fetch(PDO::FETCH_OBJ);

        if (!empty($target->id)) {
            $otherId = $target->id;
            // move $mapid in direction $direction
            $this->pdo->prepare("UPDATE weathermap_groups SET sortorder = ? WHERE id=?")->execute(array(
                $newOrder,
                $groupId
            ));
            // then find the other one with the same sortorder and move that in the opposite direction
            $this->pdo->prepare("UPDATE weathermap_groups SET sortorder = ? WHERE id=?")->execute(array(
                $oldOrder,
                $otherId
            ));
        }
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
        $statement = $this->pdo->prepare("UPDATE weathermap_groups SET sortorder=? WHERE id=?");

        if (!empty($newGroupOrder)) {
            foreach ($newGroupOrder as $mapId => $sortOrder) {
                $statement->execute(array($sortOrder, $mapId));
            }
        }
    }

    public function saveMapSetting($mapId, $name, $value)
    {
        if ($mapId > 0) {
            // map setting
            $data = array("id" => $mapId, "name" => $name, "value" => $value);
            $statement = $this->pdo->prepare("REPLACE INTO weathermap_settings (mapid, optname, optvalue) VALUES (:id, :name, :value)");
            $statement->execute($data);
        } elseif ($mapId < 0) {
            // group setting
            $data = array("groupid" => -$mapId, "name" => $name, "value" => $value);
            $statement = $this->pdo->prepare("REPLACE INTO weathermap_settings (mapid, groupid, optname, optvalue) VALUES (0, :groupid,  :name, :value)");
            $statement->execute($data);
        } else {
            // Global setting
            $data = array("name" => $name, "value" => $value);
            $statement = $this->pdo->prepare("REPLACE INTO weathermap_settings (mapid, groupid, optname, optvalue) VALUES (0, 0,  :name, :value)");
            $statement->execute($data);
        }
    }

    public function updateMapSetting($settingId, $name, $value)
    {
        $data = array("optname" => $name, "optvalue" => $value);

        $allowed = ["optname", "optvalue"]; // allowed fields
        list($set, $values) = $this->make_set($data, $allowed);

        $values['id'] = $settingId;

        $stmt = $this->pdo->prepare("UPDATE weathermap_settings SET $set where id=:id");
        $stmt->execute($values);
    }

    public function deleteMapSetting($mapId, $settingId)
    {
        $this->pdo->prepare("DELETE FROM weathermap_settings WHERE id=? AND mapid=?")->execute(array(
            $settingId,
            $mapId
        ));
    }

    public function createGroup($groupName)
    {
        $sortOrder = $this->pdo->query("SELECT max(sortorder)+1 AS next_id FROM weathermap_groups")->fetchColumn();
        $this->pdo->prepare("INSERT INTO weathermap_groups(name, sortorder) VALUES(?,?)")->execute(array(
            $groupName,
            $sortOrder
        ));
    }

    public function deleteGroup($groupId)
    {
        $statement = $this->pdo->prepare("SELECT MIN(id) AS first_group FROM weathermap_groups WHERE id <> ?");
        $statement->execute(array($groupId));
        $newId = $statement->fetchColumn();

        # move any maps out of this group into a still-existing one
        $this->pdo->prepare("UPDATE weathermap_maps SET group_id=? WHERE group_id=?")->execute(array($newId, $groupId));

        # then delete the group
        $this->pdo->prepare("DELETE FROM weathermap_groups WHERE id=?")->execute(array($groupId));

        # Finally, resort, just in case
        $this->resortGroups();
        $this->resortMaps();
    }

    public function renameGroup($groupId, $newName)
    {
        $this->pdo->prepare("UPDATE weathermap_groups SET name=? WHERE id=?")->execute(array($newName, $groupId));
    }

    function extractMapTitle($filename)
    {
        $title = "(no title)";
        $fd = fopen($filename, "r");
        while (!feof($fd)) {
            $buffer = fgets($fd, 4096);
            if (preg_match('/^\s*TITLE\s+(.*)/i', $buffer, $matches)) {
                $title = $matches[1];
            }
            // this regexp is tweaked from the ReadConfig version, to only match TITLEPOS lines *with* a title appended
            if (preg_match('/^\s*TITLEPOS\s+\d+\s+\d+\s+(.+)/i', $buffer, $matches)) {
                $title = $matches[1];
            }
            // strip out any DOS line endings that got through
            $title = str_replace("\r", "", $title);
        }
        fclose($fd);

        return $title;
    }

    public function addMap($mapFilename)
    {
        chdir($this->configDirectory);

        $pathParts = pathinfo($mapFilename);
        $fileDirectory = realpath($pathParts['dirname']);

        // TODO - this still takes user data and puts it in the database uncleansed

        if ($fileDirectory != $this->configDirectory) {
            // someone is trying to read arbitrary files?
            throw new Exception("Path mismatch");
        } else {
            $realfile = $this->configDirectory . DIRECTORY_SEPARATOR . $mapFilename;
            $title = $this->extractMapTitle($realfile);

            $statement = $this->pdo->prepare("INSERT INTO weathermap_maps (configfile,titlecache,active,imagefile,htmlfile,filehash,config) VALUES (?,?,'on','','','','')");
            $statement->execute(array($mapFilename, $title));
            $newMapId = $this->pdo->lastInsertId();

            // add auth for 'current user'
            $myuid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);

            $statement = $this->pdo->prepare("insert into weathermap_auth (mapid,userid) VALUES (?,?)");
            $statement->execute(array($newMapId, $myuid));

            // now we've got an ID, fill in the filehash
            $statement = $this->pdo->prepare("update weathermap_maps set filehash=LEFT(MD5(concat(id,configfile,rand())),20) where id=?");
            $statement->execute(array($newMapId));

            $this->resortMaps();
        }
    }

}
