<?php

namespace Weathermap\Integrations\Cacti;

use \PDO;
use Weathermap\Integrations\ApplicationInterface;

class CactiApplicationInterface extends ApplicationInterface
{
    public function getAppSetting($name, $defaultValue = "")
    {
        $statement = $this->pdo->prepare("SELECT value FROM settings WHERE name=?");
        $statement->execute(array($name));
        $result = $statement->fetchColumn();

        if ($result === false) {
            return $defaultValue;
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

    public function getUserList($includeAnyone = false)
    {
        $statement = $this->pdo->query("SELECT id, username, full_name, enabled FROM user_auth");
        $statement->execute();
        $userlist = $statement->fetchAll(PDO::FETCH_OBJ);

        $users = array();

        foreach ($userlist as $user) {
            $users[$user->id] = $user;
        }

        if ($includeAnyone) {
            $users[0] = new \stdClass();
            $users[0]->id = 0;
            $users[0]->username = "Anyone";
            $users[0]->full_name = "Anyone";
            $users[0]->enabled = true;
        }

        return $users;
    }

    public function checkUserAccess($userId, $realmId)
    {
        $statement = $this->pdo->prepare("SELECT user_auth_realm.realm_id FROM user_auth_realm WHERE user_auth_realm.user_id=? AND user_auth_realm.realm_id=?");
        $statement->execute(array($userId, $realmId));
        $userlist = $statement->fetchAll(PDO::FETCH_OBJ);

        if (count($userlist) > 0) {
            return true;
        }

        return false;
    }

    public function getCurrentUserId()
    {
        return isset($_SESSION['sess_user_id']) ? intval($_SESSION['sess_user_id']) : 1;
    }
}
