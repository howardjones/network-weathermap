<?php

namespace Weathermap\Integrations;

use PDOException;
use PDO;

class MapManager
{

    /** @var PDO $pdo */
    private $pdo;

    private $configDirectory;

    /** @var ApplicationInterface $application */
    public $application;

    public function __construct($pdo, $configDirectory, $applicationInterface = null)
    {
        $this->configDirectory = $configDirectory;
        $this->pdo = $pdo;
        $this->application = $applicationInterface;
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
        $totalMapCount = $statement->fetchColumn();

        return $totalMapCount;
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
            // $statement = $this->pdo->prepare("SELECT DISTINCT weathermap_maps.* FROM weathermap_auth,weathermap_maps WHERE weathermap_maps.id=weathermap_auth.mapid AND active='on' AND  (userid=? OR userid=0) ORDER BY sortorder, id");
            $statement = $this->pdo->prepare("SELECT DISTINCT group_id, titlecache, filehash, thumb_width, thumb_height, sortorder FROM weathermap_auth,weathermap_maps WHERE weathermap_maps.id=weathermap_auth.mapid AND active='on' AND  (userid=? OR userid=0) ORDER BY group_id, sortorder");
            $statement->execute(array($userId));
        } else {
            $statement = $this->pdo->prepare("SELECT DISTINCT group_id, titlecache, filehash, thumb_width, thumb_height, sortorder FROM weathermap_auth,weathermap_maps WHERE weathermap_maps.id=weathermap_auth.mapid AND active='on' AND  weathermap_maps.group_id=? AND  (userid=? OR userid=0) ORDER BY group_id, sortorder");
            //$statement = $this->pdo->prepare("SELECT DISTINCT weathermap_maps.* FROM weathermap_auth,weathermap_maps WHERE weathermap_maps.id=weathermap_auth.mapid AND active='on' AND  weathermap_maps.group_id=? AND  (userid=? OR userid=0) ORDER BY sortorder, id");
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

    private function buildSet($data, $allowed)
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
        $allowed = array(
            "active",
            "sortorder",
            "warncount",
            "runtime",
            "group_id",
            "thumb_width",
            "thumb_height",
            "titlecache"
        ); // allowed fields
        list($set, $values) = $this->buildSet($data, $allowed);

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
            $this->pdo->prepare("UPDATE weathermap_maps SET sortorder =? WHERE id=?")->execute(
                array(
                    $newOrder,
                    $mapId
                )
            );
            // then find the other one with the same sortorder and move that in the opposite direction
            $this->pdo->prepare("UPDATE weathermap_maps SET sortorder =? WHERE id=?")->execute(
                array(
                    $oldOrder,
                    $otherId
                )
            );
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
            $this->pdo->prepare("UPDATE weathermap_groups SET sortorder = ? WHERE id=?")->execute(
                array(
                    $newOrder,
                    $groupId
                )
            );
            // then find the other one with the same sortorder and move that in the opposite direction
            $this->pdo->prepare("UPDATE weathermap_groups SET sortorder = ? WHERE id=?")->execute(
                array(
                    $oldOrder,
                    $otherId
                )
            );
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
        list($set, $values) = $this->buildSet($data, $allowed);

        $values['id'] = $settingId;

        $stmt = $this->pdo->prepare("UPDATE weathermap_settings SET $set where id=:id");
        $stmt->execute($values);
    }

    public function deleteMapSetting($mapId, $settingId)
    {
        $this->pdo->prepare("DELETE FROM weathermap_settings WHERE id=? AND mapid=?")->execute(
            array(
                $settingId,
                $mapId
            )
        );
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

        $out = new \stdClass();
        foreach ($result as $k => $v) {
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

        if ($setting === false) {
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
        $this->pdo->prepare("INSERT INTO weathermap_groups(name, sortorder) VALUES(?,?)")->execute(
            array(
                $groupName,
                $sortOrder
            )
        );
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

    public function getMapTitle($mapid)
    {
        $statement = $this->pdo->prepare("SELECT titlecache FROM weathermap_maps WHERE ID=?");
        $statement->execute(array(intval($mapid)));
        $title = $statement->fetchColumn();

        return $title;
    }

    public function getMapTitleByHash($mapid)
    {
        $statement = $this->pdo->prepare("SELECT titlecache FROM weathermap_maps WHERE filehash=?");
        $statement->execute(array($mapid));
        $title = $statement->fetchColumn();

        return $title;
    }

    public function extractMapTitle($filename)
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
            throw new \Exception("Path mismatch - $fileDirectory != " . $this->configDirectory);
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

    public function translateFileHash($idOrFilename)
    {
        $statement = $this->pdo->prepare("SELECT id FROM weathermap_maps WHERE configfile=? OR filehash=?");
        $statement->execute(array($idOrFilename, $idOrFilename));

        $result = $statement->fetchColumn();

        return $result;
    }

    private function camelize($input, $separator = '_')
    {
        return str_replace($separator, '', ucwords($input, $separator));
    }

    /**
     * get a list of all tables in the database
     *
     * @return array
     */
    public function getTableList()
    {
        $statement = $this->pdo->query('show tables');
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        $tables = array();
        foreach ($result as $arr) {
            foreach ($arr as $t) {
                $tables[] = $t;
            }
        }
        return $tables;
    }

    public function getColumnList($tableName)
    {
        $columns = array();
        try {
            $rs = $this->pdo->query('SELECT * FROM ' . $tableName . ' LIMIT 0');
            for ($i = 0; $i < $rs->columnCount(); $i++) {
                $col = $rs->getColumnMeta($i);
                $columns[] = $col['name'];
            }
        } catch (PDOException $Exception) {
            // we want to allow for the table being completely missing
        }

        return $columns;
    }

    private function createMissingTables()
    {
        $tables = $this->getTableList();

        $databaseUpdates = array();

        if (!in_array('weathermap_maps', $tables)) {
            $databaseUpdates[] = "CREATE TABLE weathermap_maps (
                                id INT(11) NOT NULL AUTO_INCREMENT,
                                sortorder INT(11) NOT NULL DEFAULT 0,
                                group_id INT(11) NOT NULL DEFAULT 1,
                                active SET('on','off') NOT NULL DEFAULT 'on',
                                configfile TEXT NOT NULL,
                                imagefile TEXT NOT NULL,
                                htmlfile TEXT NOT NULL,
                                titlecache TEXT NOT NULL,
                                filehash VARCHAR (40) NOT NULL DEFAULT '',
                                warncount INT(11) NOT NULL DEFAULT 0,
                                debug set('on','off','once') NOT NULL default 'off',
                                runtime double NOT NULL default 0,
                                lastrun datetime,
                                config TEXT NOT NULL,
                                thumb_width INT(11) NOT NULL DEFAULT 0,
                                thumb_height INT(11) NOT NULL DEFAULT 0,
                                schedule VARCHAR(32) NOT NULL DEFAULT '*',
                                archiving SET('on','off') NOT NULL DEFAULT 'off',
                                PRIMARY KEY  (id)
                        );";
        }

        if (!in_array('weathermap_auth', $tables)) {
            $databaseUpdates[] = "CREATE TABLE weathermap_auth (
                                userid MEDIUMINT(9) NOT NULL DEFAULT '0',
                                mapid INT(11) NOT NULL DEFAULT '0'
                        );";
        }

        if (!in_array('weathermap_groups', $tables)) {
            $databaseUpdates[] = "CREATE TABLE  weathermap_groups (
                                `id` INT(11) NOT NULL AUTO_INCREMENT,
                                `name` VARCHAR( 128 ) NOT NULL DEFAULT '',
                                `sortorder` INT(11) NOT NULL DEFAULT 0,
                                PRIMARY KEY (id)
                                );";
            $databaseUpdates[] = "INSERT INTO weathermap_groups (id,name,sortorder) VALUES (1,'Weathermaps',1)";
        }

        if (!in_array('weathermap_settings', $tables)) {
            $databaseUpdates[] = "CREATE TABLE weathermap_settings (
                                id INT(11) NOT NULL AUTO_INCREMENT,
                                mapid INT(11) NOT NULL DEFAULT '0',
                                groupid INT(11) NOT NULL DEFAULT '0',
                                optname VARCHAR(128) NOT NULL DEFAULT '',
                                optvalue VARCHAR(128) NOT NULL DEFAULT '',
                                PRIMARY KEY  (id)
                        );";
        }

        if (!in_array('weathermap_data', $tables)) {
            $databaseUpdates[] = "CREATE TABLE IF NOT EXISTS weathermap_data (id INT(11) NOT NULL AUTO_INCREMENT,
                                rrdfile VARCHAR(190) NOT NULL,data_source_name VARCHAR(19) NOT NULL,
                                  last_time INT(11) NOT NULL,last_value VARCHAR(190) NOT NULL,
                                last_calc VARCHAR(190) NOT NULL, sequence INT(11) NOT NULL, local_data_id INT(11) NOT NULL DEFAULT 0, PRIMARY KEY  (id), KEY rrdfile (rrdfile),
                                  KEY local_data_id (local_data_id), KEY data_source_name (data_source_name) )";
        }

        return $databaseUpdates;
    }

    /**
     * Create and/or update the weathermap tables in the host database.
     *
     * Pulled out of Cacti's poller setup_tables() hook - we can re-use this in other integrations.
     */
    public function initializeDatabase()
    {
        // only bother with all this if it's a new install, a new version, or we're in a development version
        // - saves a handful of db hits per request!

        $databaseUpdates = $this->createMissingTables();

        $columns = $this->getColumnList("weathermap_maps");
        if (count($columns) > 0) {
            # Check that all the table columns exist for weathermap_maps
            # There have been a number of changes over versions.

            $mapsFieldChanges = array(
                'sortorder' => array("alter table weathermap_maps add sortorder int(11) NOT NULL default 0 after id"),
                'filehash' => array("alter table weathermap_maps add filehash varchar(40) NOT NULL default '' after titlecache"),
                'warncount' => array("alter table weathermap_maps add warncount int(11) NOT NULL default 0 after filehash"),
                'config' => array("alter table weathermap_maps add config text NOT NULL after warncount"),
                'thumb_width' => array(
                    "alter table weathermap_maps add thumb_width int(11) NOT NULL default 0 after config",
                    "alter table weathermap_maps add thumb_height int(11) NOT NULL default 0 after thumb_width",
                    "alter table weathermap_maps add schedule varchar(32) NOT NULL default '*' after thumb_height",
                    "alter table weathermap_maps add archiving set('on','off') NOT NULL default 'off' after schedule"
                ),
                'group_id' => array(
                    "alter table weathermap_maps add group_id int(11) NOT NULL default 1 after sortorder",
                    "ALTER TABLE `weathermap_settings` ADD `groupid` INT NOT NULL DEFAULT '0' AFTER `mapid`"
                ),
                'debug' => array(
                    "alter table weathermap_maps add runtime double NOT NULL default 0 after warncount",
                    "alter table weathermap_maps add lastrun datetime after runtime",
                    "alter table weathermap_maps add debug set('on','off','once') NOT NULL default 'off' after warncount;"
                )
            );

            foreach ($mapsFieldChanges as $field => $changes) {
                if (!in_array($field, $columns)) {
                    foreach ($changes as $change) {
                        $databaseUpdates[] = $change;
                    }
                }
            }
        }

        $databaseUpdates[] = "UPDATE weathermap_maps SET filehash=LEFT(MD5(concat(id,configfile,rand())),20) WHERE filehash = ''";

        $columns = $this->getColumnList("weathermap_data");
        if (count($columns) > 0) {
            if (!in_array('local_data_id', $columns)) {
                $databaseUpdates[] = "ALTER TABLE weathermap_data ADD local_data_id INT(11) NOT NULL DEFAULT 0 AFTER sequence";
                $databaseUpdates[] = "ALTER TABLE weathermap_data ADD INDEX ( `local_data_id` )";
                # if there is existing data without a local_data_id, ditch it
                $databaseUpdates[] = "DELETE FROM weathermap_data";
            }
        }

        // patch up the sortorder for any maps that don't have one.
        $databaseUpdates[] = "UPDATE weathermap_maps SET sortorder=id WHERE sortorder IS NULL OR sortorder=0;";

        if (!empty($databaseUpdates)) {
            for ($a = 0; $a < count($databaseUpdates); $a++) {
                $this->pdo->query($databaseUpdates[$a]);
            }
        }
    }

    public function initializeAppSettings()
    {
        // create the settings entries, if necessary
        $defaults = array(
            "weathermap_pagestyle" => 0,
            "weathermap_cycle_refresh" => 0,
            "weathermap_render_period" => 0,
            "weathermap_quiet_logging" => 0,
            "weathermap_render_counter" => 0,
            "weathermap_output_format" => "png",
            "weathermap_thumbsize" => 250,
            "weathermap_map_selector" => 1,
            "weathermap_all_tab" => 0,
            "weathermap_debug_data_only" => 1
        );

        foreach ($defaults as $key => $defaultValue) {
            $current = $this->application->getAppSetting($key, '');
            if ($current == '') {
                $this->application->setAppSetting($key, $defaultValue);
            }
        }

        // update the version, so we can skip this next time
        // TODO: get the version from...
        $this->application->setAppSetting("weathermap_db_version", "1.0.0");
    }


}
