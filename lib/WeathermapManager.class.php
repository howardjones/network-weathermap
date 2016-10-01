<?php

class WeathermapManagedMap
{
    /**
     * Some day we'll migrate to using this for the managed maps, and the various mutators below will be
     * methods here instead.
     */
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
        $statement->execute(array(intval($mapId)));
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

    public function getMapWithAccess($userId, $mapId)
    {
        $statement = $this->pdo->prepare("SELECT weathermap_maps.* FROM weathermap_auth,weathermap_maps WHERE weathermap_maps.id=weathermap_auth.mapid AND active='on' AND (userid=? OR userid=0) AND weathermap_maps.id=?");
        $statement->execute(array($userId, $mapId));
        $maps = $statement->fetchAll(PDO::FETCH_OBJ);

        return $maps;
    }

    public function getMapsWithAccessAndGroups($userId)
    {
        $statement = $this->pdo->prepare("SELECT DISTINCT weathermap_maps.*,weathermap_groups.name, weathermap_groups.sortorder AS gsort FROM weathermap_groups,weathermap_auth,weathermap_maps WHERE weathermap_maps.group_id=weathermap_groups.id AND weathermap_maps.id=weathermap_auth.mapid AND active='on' AND (userid=? OR userid=0) ORDER BY gsort, sortorder");
        $statement->execute(array($userId));
        $maps = $statement->fetchAll(PDO::FETCH_OBJ);

        return $maps;
    }

    public function getMapAuth($mapId)
    {
        $statement = $this->pdo->prepare("SELECT * FROM weathermap_auth WHERE mapid=? ORDER BY userid");
        $statement->execute(array($mapId));

        return $statement->fetchAll(PDO::FETCH_OBJ);
    }

    public function getMapTotalCount()
    {
        $statement = $this->pdo->query("SELECT count(*) AS total FROM weathermap_maps");
        $statement->execute();
        $total_map_count = $statement->fetchColumn();

        return $total_map_count;
    }

    public function getMapsInGroup($groupId)
    {
        $statement = $this->pdo->prepare("SELECT * FROM weathermap_maps WHERE group_id=? ORDER BY sortorder");
        $statement->execute(array($groupId));
        $maps = $statement->fetchAll(PDO::FETCH_OBJ);

        return $maps;
    }

    public function getMapsWithGroups()
    {
        $statement = $this->pdo->query("SELECT weathermap_maps.*, weathermap_groups.name AS groupname FROM weathermap_maps, weathermap_groups WHERE weathermap_maps.group_id=weathermap_groups.id ORDER BY weathermap_groups.sortorder,sortorder");
        $statement->execute();
        $maps = $statement->fetchAll(PDO::FETCH_OBJ);

        return $maps;
    }

    public function getMapRunList()
    {
        $statement = $this->pdo->query("select m.*, g.name as groupname from weathermap_maps m,weathermap_groups g where m.group_id=g.id and active='on' order by sortorder,id");
        $statement->execute();
        $maps = $statement->fetchAll(PDO::FETCH_OBJ);

        return $maps;
    }

    public function getMapsForUser($userId, $groupId = null)
    {
        if (is_null($groupId)) {
            $statement = $this->pdo->prepare("SELECT DISTINCT weathermap_maps.* FROM weathermap_auth,weathermap_maps WHERE weathermap_maps.id=weathermap_auth.mapid AND active='on' AND  (userid=? OR userid=0) ORDER BY sortorder, id");
            $statement->execute(array($userId));
        } else {
            $statement = $this->pdo->prepare("SELECT DISTINCT weathermap_maps.* FROM weathermap_auth,weathermap_maps WHERE weathermap_maps.id=weathermap_auth.mapid AND active='on' AND  weathermap_maps.group_id=? AND  (userid=? OR userid=0) ORDER BY sortorder, id");
            $statement->execute(array($groupId, $userId));
        }
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

    public function getTabs($userId)
    {
        $statement = $this->pdo->prepare("SELECT DISTINCTROW weathermap_maps.group_id AS id, weathermap_groups.name AS group_name, weathermap_groups.sortorder FROM weathermap_auth,weathermap_maps, weathermap_groups WHERE weathermap_groups.id=weathermap_maps.group_id AND weathermap_maps.id=weathermap_auth.mapid AND active='on' AND (userid=? OR userid=0) ORDER BY weathermap_groups.sortorder");
        $statement->execute(array($userId));
        $maps = $statement->fetchAll(PDO::FETCH_ASSOC);

        $tabs = array();
        foreach ($maps as $map) {
            $tabs[$map['id']] = $map['group_name'];
        }

        return $tabs;
    }

    public function updateMap($mapId, $data)
    {
        $allowed = array("active", "sortorder", "warncount", "group_id", "thumb_width", "thumb_height", "titlecache"); // allowed fields
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

        if ($target !== false) {
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
        $source = $this->getGroup($groupId);

        $oldOrder = intval($source->sortorder);
        $newOrder = $oldOrder + $direction;

        $statement = $this->pdo->prepare("SELECT * FROM weathermap_groups WHERE sortorder =? LIMIT 1");
        $statement->execute(array($newOrder));
        $target = $statement->fetch(PDO::FETCH_OBJ);

        if ($target !== false) {
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
            $statement = $this->pdo->prepare("INSERT INTO weathermap_settings (mapid, optname, optvalue) VALUES (:id, :name, :value)");
            $statement->execute($data);
        } elseif ($mapId < 0) {
            // group setting
            $data = array("groupid" => -$mapId, "name" => $name, "value" => $value);
            $statement = $this->pdo->prepare("INSERT INTO weathermap_settings (mapid, groupid, optname, optvalue) VALUES (0, :groupid,  :name, :value)");
            $statement->execute($data);
        } else {
            // Global setting
            $data = array("name" => $name, "value" => $value);
            $statement = $this->pdo->prepare("INSERT INTO weathermap_settings (mapid, groupid, optname, optvalue) VALUES (0, 0,  :name, :value)");
            $statement->execute($data);
        }
    }

    public function updateMapSetting($settingId, $name, $value)
    {
        $data = array("optname" => $name, "optvalue" => $value);

        $allowed = array("optname", "optvalue"); // allowed fields
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

    // This isn't actually used anywhere...
    public function getAllMapSettings($mapId)
    {
        $map = $this->getMap($mapId);
        $result = array();
        $s1 = $this->getMapSettings(0);
        $s2 = $this->getMapSettings(-$map->group_id);
        $s3 = $this->getMapSettings($mapId);

        foreach (array($s1, $s2, $s3) as $s) {
            foreach ($s as $setting) {
                $result[$setting->optname] = $setting;
            }
        }

        $out = new stdClass();
        foreach ($result as $k=>$v) {
            $out->$k = $v;
        }

        return $out;
    }

    public function getMapSettings($mapId)
    {
        if ($mapId == 0) {
            $statement = $this->pdo->query("SELECT * FROM weathermap_settings WHERE mapid=0 AND groupid=0");
            $statement->execute();
        }
        if ($mapId < 0) {
            $statement = $this->pdo->prepare("SELECT * FROM weathermap_settings WHERE mapid=0 AND groupid=?");
            $statement->execute(array((-intval($mapId))));
        }
        if ($mapId > 0) {
            $statement = $this->pdo->prepare("SELECT * FROM weathermap_settings WHERE mapid=?");
            $statement->execute(array(intval($mapId)));
        }

        $settings = $statement->fetchAll(PDO::FETCH_OBJ);

        return $settings;
    }

    public function getMapSettingById($settingId)
    {
        $statement = $this->pdo->prepare("SELECT * FROM weathermap_settings WHERE id=?");
        $statement->execute(array($settingId));
        $setting = $statement->fetch(PDO::FETCH_OBJ);

        return $setting;
    }

    public function getMapSettingByName($mapId, $name, $defaultValue)
    {
        $statement = $this->pdo->prepare("SELECT * FROM weathermap_settings WHERE mapid=? AND optname=?");
        $statement->execute(array($mapId, $name));
        $setting = $statement->fetch(PDO::FETCH_OBJ);

        if ($setting !== false) {
            $setting = $defaultValue;
        } else {
            $setting = $setting->optvalue;
        }

        return $setting;
    }

    public function getMapSettingCount($mapId, $groupId = null)
    {
        if (is_null($groupId)) {
            $statement = $this->pdo->prepare("SELECT count(*) FROM weathermap_settings WHERE mapid=?");
            $statement->execute(array($mapId));
        } else {
            $statement = $this->pdo->prepare("SELECT count(*) FROM weathermap_settings WHERE mapid=? AND groupid=?");
            $statement->execute(array($mapId, $groupId));
        }
        $count = $statement->fetchColumn();

        return $count;
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
        $title = "(no file)";

        if (file_exists($filename)) {
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
        }

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
            throw new Exception("Path mismatch - $fileDirectory != " . $this->configDirectory);
        } else {
            $realfile = $this->configDirectory . DIRECTORY_SEPARATOR . $mapFilename;
            $title = $this->extractMapTitle($realfile);

            $statement = $this->pdo->prepare("INSERT INTO weathermap_maps (configfile,titlecache,active,imagefile,htmlfile,filehash,config) VALUES (?,?,'on','','','','')");
            $statement->execute(array($mapFilename, $title));
            $newMapId = $this->pdo->lastInsertId();

            // add auth for 'current user'
            $myuid = (isset($_SESSION["sess_user_id"]) ? intval($_SESSION["sess_user_id"]) : 1);

            $statement = $this->pdo->prepare("INSERT INTO weathermap_auth (mapid,userid) VALUES (?,?)");
            $statement->execute(array($newMapId, $myuid));

            // now we've got an ID, fill in the filehash
            $statement = $this->pdo->prepare("UPDATE weathermap_maps SET filehash=LEFT(MD5(concat(id,configfile,rand())),20) WHERE id=?");
            $statement->execute(array($newMapId));

            $this->resortMaps();
        }
    }

    public function getMapAuthUsers($mapId)
    {
        $statement = $this->pdo->prepare('SELECT * FROM weathermap_auth WHERE mapid=? ORDER BY userid');
        $statement->execute(array($mapId));
        $users = $statement->fetchAll(PDO::FETCH_OBJ);

        return $users;
    }

    public function translateFileHash($id_or_filename)
    {
        $statement = $this->pdo->prepare("SELECT id FROM weathermap_maps WHERE configfile=? OR filehash=?");
        $statement->execute(array($id_or_filename, $id_or_filename));

        $result = $statement->fetchColumn();

        return $result;
    }

    // Below here will migrate into a Cacti API class at some point soon
    // (to allow for other hosts to have equivalent functions)

    public function getAppSetting($name, $default_value = "")
    {
        $statement = $this->pdo->prepare("SELECT value FROM settings WHERE name=?");
        $statement->execute(array($name));
        $result = $statement->fetchColumn();

        if ($result === false) {
            return $default_value;
        }

        return $result;
    }

    public function setAppSetting($name, $value)
    {
        $statement = $this->pdo->prepare("REPLACE INTO settings (name, value) VALUES (?,?)");
        $statement->execute(array($name, $value));

    }

    public function deleteAppSetting($name)
    {
        $statement = $this->pdo->prepare("DELETE FROM settings WHERE name=?");
        $statement->execute(array($name));
    }

    public function getUserList($include_anyone = false)
    {
        $statement = $this->pdo->query("SELECT id, username, full_name, enabled FROM user_auth");
        $statement->execute();
        $userlist = $statement->fetchAll(PDO::FETCH_OBJ);

        $users = array();

        foreach ($userlist as $user) {
            $users[$user->id] = $user;
        }

        if ($include_anyone) {
            $users[0] = new stdClass();
            $users[0]->id = 0;
            $users[0]->username = "Anyone";
            $users[0]->full_name = "Anyone";
            $users[0]->enabled = true;
        }

        return $users;
    }

    public function checkUserForRealm($userId, $realmId)
    {
        $statement = $this->pdo->prepare("SELECT user_auth_realm.realm_id FROM user_auth_realm WHERE user_auth_realm.user_id=? AND user_auth_realm.realm_id=?");
        $statement->execute(array($userId, $realmId));
        $userlist = $statement->fetchAll(PDO::FETCH_OBJ);

        if (sizeof($userlist) > 0) {
            return true;
        }

        return false;
    }
}
