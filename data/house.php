<?php
date_default_timezone_set('Asia/Tehran');
class house
{
    private $house_name;
    private $house_id;
    private $scenarios;
    private $smartkeys;
    private $users;
    private $scenarioChange;
    private $keyChange;
    private $key_firmware_version;
    private $conn;
    public function __construct($house_name, $conn, $house_id)
    {
        $this->house_id = $house_id;
        $this->house_name = $house_name;
        $this->conn = $conn;
    }

    public function addscenario($arrayOfscenario)
    {
        $lastInsertedIds = [];
        foreach ($arrayOfscenario as $scenario) {
            $scenario_id = NULL;
            $id = $scenario->getKey();
            $stmt_check = $this->conn->prepare("SELECT scenario_id  FROM scenario WHERE scenario_code = ?");
            $stmt_check->bind_param("s", $id);
            $stmt_check->execute();
            $stmt_check->store_result();
            $stmt_check->bind_result($scenario_id);
            if ($stmt_check->num_rows == 0) {
                $stmt = $this->conn->prepare("INSERT INTO scenario (scenario_name,scenario_code,isActive) VALUES (?, ?, ?)");

                $keyId = $scenario->getKey();
                $keyName = $scenario->getName();
                $isActive = 0;
                $stmt->bind_param(
                    "ssi",
                    $keyName,
                    $keyId,
                    $isActive
                );
                $stmt->execute();
                $lastInsertedIds[] = $this->conn->insert_id;
                $stmt->close();
            } else {

                $stmt_check->fetch();
                $lastInsertedIds[] = $scenario_id;
            }

        }







        $Json = json_encode($lastInsertedIds);

        $stmt = $this->conn->prepare("UPDATE house SET scenarios = ? WHERE house_id = ?");
        $stmt->bind_param("ss", $Json, $this->house_id);
        $stmt->execute();
        $stmt->close();
        $this->conn->close();
    }
    public function isUserInHouse($user_id)
    {
        $stmt = $this->conn->prepare("SELECT users FROM house WHERE house_name = ?");
        $stmt->bind_param("s", $this->house_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_ids_json = $row['users'];

            $user_ids = json_decode($user_ids_json, true);

            if (in_array($user_id, $user_ids)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getHouseScenarios()
    {
        $stmt = $this->conn->prepare("SELECT scenarios FROM house WHERE house_name = ?");
        $stmt->bind_param("s", $this->house_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $scenario_ids_json = $row['scenarios'];

            $scenario_ids = json_decode($scenario_ids_json, true);

            $scenario_list = array();
            foreach ($scenario_ids as $scenario_id) {

                $scenario_stmt = $this->conn->prepare("SELECT * FROM scenario WHERE scenario_id = ?");
                $scenario_stmt->bind_param("i", $scenario_id);
                $scenario_stmt->execute();
                $scenario_result = $scenario_stmt->get_result();

                if ($scenario_result->num_rows > 0) {

                    $scenario = $scenario_result->fetch_assoc();
                    $scenario_list[] = $scenario;
                }
                $scenario_stmt->close();
            }

            return $scenario_list;
        } else {
            return array();
        }


    }

    public function getHouseSmartKey()
    {
        $stmt = $this->conn->prepare("SELECT smartkeys FROM house WHERE house_name = ?");
        $stmt->bind_param("s", $this->house_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $smartkeys_ids_json = $row['smartkeys'];

            $smartkeys_ids = json_decode($smartkeys_ids_json, true);

            $smartkeys_list = array();
            foreach ($smartkeys_ids as $smartkeys_id) {

                $smartkeys_stmt = $this->conn->prepare("SELECT * FROM smartkey WHERE key_id = ?");
                $smartkeys_stmt->bind_param("i", $smartkeys_id);
                $smartkeys_stmt->execute();
                $smartkeys_result = $smartkeys_stmt->get_result();

                if ($smartkeys_result->num_rows > 0) {

                    $smartkeys = $smartkeys_result->fetch_assoc();
                    $smartkeys_list[] = $smartkeys;
                }
                $smartkeys_stmt->close();
            }

            return $smartkeys_list;
        } else {
            return array();
        }


    }


    public function adduser($user_id)
    {
        $stmt = $this->conn->prepare("SELECT users FROM house WHERE house_id = ?");
        $stmt->bind_param("s", $this->house_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $existing_users = json_decode($row['users'], true);
            if ($existing_users == NULL)
                $existing_users = array();

            if (!in_array($user_id, $existing_users)) {
                $existing_users[] = $user_id;

                $updated_users = json_encode($existing_users);

                $update_stmt = $this->conn->prepare("UPDATE house SET users = ? WHERE house_id = ?");
                $update_stmt->bind_param("ss", $updated_users, $this->house_id);
                $update_success = $update_stmt->execute();
                $update_stmt->close();

                if ($update_success) {
                    return array("success" => true, "message" => "User added to the house successfully");
                } else {
                    return array("success" => false, "message" => "Failed to update users in the house");
                }
            } else {
                return array("success" => false, "message" => "User is already in the house");
            }
        } else {
            return array("success" => false, "message" => "House not found");
        }
    }
    public function removeUserFromHouse($user_id)
    {

        $stmt = $this->conn->prepare("SELECT users FROM house WHERE house_id = ?");
        $stmt->bind_param("s", $this->house_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $existing_users = json_decode($row['users'], true);


            $index = array_search($user_id, $existing_users);
            if ($index !== false) {

                unset($existing_users[$index]);


                $existing_users = array_values($existing_users);


                $updated_users = json_encode($existing_users);


                $update_stmt = $this->conn->prepare("UPDATE house SET users = ? WHERE house_id = ?");
                $update_stmt->bind_param("ss", $updated_users, $this->house_id);
                $update_success = $update_stmt->execute();
                $update_stmt->close();

                if ($update_success) {
                    return array("success" => true, "message" => "User removed to the house successfully");
                } else {
                    return array("success" => false, "message" => "Failed to update users in the house");
                }
            } else {
                return array("success" => false, "message" => "User is not in the house");
            }
        } else {
            return array("success" => false, "message" => "House not found");
        }


    }

    public function addKey($arrayOfSmartKeys)
    {
        foreach ($arrayOfSmartKeys as $smartKey) {
            $id = $smartKey->getKeyId();
            $stmt_check = $this->conn->prepare("SELECT key_id FROM smartkey WHERE key_id = ?");
            $stmt_check->bind_param("i", $id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows == 0) {
                $stmt = $this->conn->prepare("INSERT INTO smartkey (key_id, key_name, key_status, active_color, deactive_color, firmware_version, key_model, newCommand) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

                $keyId = $smartKey->getKeyId();
                $keyName = $smartKey->getKeyName();
                $keyStatus = $smartKey->getKeyStatus();
                $activeColor = $smartKey->getActiveColor();
                $deactiveColor = $smartKey->getDeactiveColor();
                $firmwareVersion = $smartKey->getFirmwareVersion();
                $keyModel = $smartKey->getKeyModel();
                $newCommand = $smartKey->isNewCommand() ? 1 : 0;

                $stmt->bind_param(
                    "issssssi",
                    $keyId,
                    $keyName,
                    $keyStatus,
                    $activeColor,
                    $deactiveColor,
                    $firmwareVersion,
                    $keyModel,
                    $newCommand
                );
                $stmt->execute();
                $stmt->close();
            }

        }




        $lastInsertedIds = [];
        foreach ($arrayOfSmartKeys as $smartKey) {
            $lastInsertedIds[] = $smartKey->getKeyId();
        }


        $smartKeysJson = json_encode($lastInsertedIds);

        $stmt = $this->conn->prepare("UPDATE house SET smartkeys = ? WHERE house_id = ?");
        $stmt->bind_param("ss", $smartKeysJson, $this->house_id);
        $stmt->execute();

        $stmt->close();
        $this->conn->close();

    }


    public function create($key_firmware_version)
    {
        $stmtCheck = $this->conn->prepare("SELECT * FROM house WHERE house_name=?");
        $stmtCheck->bind_param("s", $this->house_name);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        if (!$stmtCheck->num_rows == 0) {
            return false;
        }

        $stmt = $this->conn->prepare("INSERT INTO house (house_name, keyChange,scenarioChange,key_firmware_version) VALUES (?, ?,?,?)");

        $keyChange = false;
        $scenarioChange = false;
        $stmt->bind_param("siis", $this->house_name, $keyChange, $scenarioChange, $key_firmware_version);
        $success = $stmt->execute();
        $stmt->close();
        $this->conn->close();

        if ($success) {
            return true;
        } else {
            return false;
        }
    }

}
?>