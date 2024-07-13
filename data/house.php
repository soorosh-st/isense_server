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

    public function addScenario($arrayOfScenario)
    {
        $lastInsertedIds = [];
        foreach ($arrayOfScenario as $scenario) {
            $scenarioId = NULL;
            $id = $scenario->getKey();
            $stmt_check = $this->conn->prepare("SELECT scenario_id FROM scenario WHERE scenario_code = ?");
            $stmt_check->bind_param("s", $id);
            $stmt_check->execute();
            $stmt_check->store_result();
            $stmt_check->bind_result($scenarioId);

            if ($stmt_check->num_rows == 0) {
                $stmt = $this->conn->prepare("INSERT INTO scenario (scenario_name, scenario_code, scenario_img, scenario_delay, house_id) VALUES (?, ?, ?, ?, ?)");

                $scenarioName = $scenario->getName();
                $scenarioCode = $scenario->getKey();
                $scenarioImg = $scenario->src; // Assuming this is to be provided or can be set to a default value
                $scenarioDelay = $scenario->delay; // Assuming this is to be provided or can be set to a default value
                $houseId = $this->house_id;


                $stmt->bind_param(
                    "sssis",
                    $scenarioName,
                    $scenarioCode,
                    $scenarioImg,
                    $scenarioDelay,
                    $houseId
                );
                $stmt->execute();
                $lastInsertedIds[] = $this->conn->insert_id;
                $stmt->close();
            }

            $stmt_check->close();
        }

        $this->conn->close();
    }
    public function isUserInHouse($user_token)
    {
        // Step 1: Verify the user based on the provided user_token
        $stmt_user = $this->conn->prepare("SELECT user_id, token_timeout FROM user WHERE user_token = ?");
        $stmt_user->bind_param("s", $user_token);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();

        if ($result_user->num_rows > 0) {
            $user_data = $result_user->fetch_assoc();
            $user_id = $user_data['user_id'];
            $token_timeout = $user_data['token_timeout'];

            // Step 2: Check if the token is valid
            if (time() < strtotime($token_timeout)) {
                // Step 3: Check if the user is part of the house
                $stmt_house = $this->conn->prepare("SELECT * FROM join_user_house WHERE user_id = ? AND house_id = ?");
                $stmt_house->bind_param("ii", $user_id, $this->house_id);
                $stmt_house->execute();
                $result_house = $stmt_house->get_result();

                if ($result_house->num_rows > 0) {
                    return array("success" => true, "message" => "This user is in this house", "code" => 200);
                } else {
                    return array("success" => false, "message" => "This user is has no access to the house", "code" => 403);
                }
            } else {
                // Token is expired
                return array("success" => false, "message" => "User token expired", "code" => 401);
            }
        } else {
            // User not found
            return array("success" => false, "message" => "Specified user did not found", "code" => 404);
        }
    }
    public function isUserAdminInHouse($user_id)
    {
        // Check if user is an admin
        $stmt = $this->conn->prepare("SELECT isManager FROM user WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $isManager = $row['isManager'];
            if ($isManager != 1) {
                return false;
            }
        } else {
            return false;
        }

        // Check if user is part of the house
        $stmt = $this->conn->prepare("SELECT 1 FROM join_user_house WHERE user_id = ? AND house_id = ?");
        $stmt->bind_param("ii", $user_id, $this->house_id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }
    public function getHouseUsers($requested_user)
    {
        $stmt = $this->conn->prepare("SELECT user.user_id, user.user_name, user.access_timeout, user.lastLogin 
                                  FROM user 
                                  JOIN join_user_house ON user.user_id = join_user_house.user_id 
                                  WHERE join_user_house.house_id = ?");
        $stmt->bind_param("i", $this->house_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $user_list = array();
        while ($row = $result->fetch_assoc()) {
            if ($row['user_id'] != $requested_user) {
                $user_list[] = $row;
            }
        }
        return $user_list;
    }

    public function getHouseScenarios($theme)
    {
        $stmt = $this->conn->prepare("SELECT scenario_id, scenario_name, scenario_code, scenario_img, scenario_delay, isActive FROM scenario WHERE house_id = ?");
        $stmt->bind_param("s", $this->house_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $scenarios = [];
        while ($row = $result->fetch_assoc()) {
            // Modify scenario_img based on theme
            if ($theme == "blue") {
                $row['scenario_img'] .= "_blue.svg";
            } elseif ($theme == "red") {
                $row['scenario_img'] .= ".svg";
            }

            $scenarios[] = $row;
        }

        $stmt->close();
        return $scenarios;
    }

    public function getHouseSmartKey()
    {
        $stmt = $this->conn->prepare("SELECT key_id ,key_uid,key_name,key_status,active_color,deactive_color,key_model FROM smartkey WHERE house_id = ?");
        $stmt->bind_param("s", $this->house_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $smartKeys = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['type'] = "Key";
                $smartKeys[] = $row;
            }
        }

        $stmt->close();
        return $smartKeys;
    }
    public function getHouseRelays()
    {
        $stmt = $this->conn->prepare("SELECT smartRelays FROM house WHERE house_name = ?");
        $stmt->bind_param("s", $this->house_name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $smartRelays_ids_json = $row['smartRelays'];

            $smartRelays_ids = json_decode($smartRelays_ids_json, true);

            $smartkeys_list = array();
            foreach ($smartRelays_ids as $smartRelay_id) {

                $smartrelay_stmt = $this->conn->prepare("SELECT * FROM smartrelay WHERE smart_relay_id = ?");
                $smartrelay_stmt->bind_param("i", $smartRelay_id);
                $smartrelay_stmt->execute();
                $smartrelays_result = $smartrelay_stmt->get_result();

                if ($smartrelays_result->num_rows > 0) {

                    $smartRelays = $smartrelays_result->fetch_assoc();
                    $smartrelays_list[] = $smartRelays;
                }
                $smartrelay_stmt->close();
            }

            return $smartrelays_list;
        } else {
            return array();
        }
    }

    public function readAllUser($user_id)
    {
        if (!$this->isUserAdminInHouse($user_id)) {
            return false;
        }
        return $this->getHouseUsers($user_id);
    }
    public function adduser($user)
    {

        $user_id = -1;
        if (!$user_id = $user->signup()) {
            return array("success" => false, "message" => "Failed to create user");
        }

        // Insert the relationship into join_user_house table
        $stmt = $this->conn->prepare("INSERT INTO join_user_house (user_id, house_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $this->house_id);

        if ($stmt->execute()) {
            return array("success" => true, "message" => "User added to the house successfully");
        } else {
            return array("success" => false, "message" => "Failed to add user to the house");
        }
    }
    public function removeUserFromHouse($user_id, $admin_user_id)
    {
        // Check if admin_user_id is an admin
        $stmt = $this->conn->prepare("SELECT isManager FROM user WHERE user_id = ?");
        $stmt->bind_param("i", $admin_user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['isManager'] != 1) {
                return array("success" => false, "message" => "Admin user is not an admin", "code" => 401);
            }
        } else {
            return array("success" => false, "message" => "Admin user not found", "code" => 404);
        }

        // Check if admin_user_id is part of the house
        $stmt = $this->conn->prepare("SELECT 1 FROM join_user_house WHERE user_id = ? AND house_id = ?");
        $stmt->bind_param("ii", $admin_user_id, $this->house_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            return array("success" => false, "message" => "Admin user is not part of this house", "code" => 401);
        }

        // Check if user_id is part of the house
        $stmt = $this->conn->prepare("SELECT 1 FROM join_user_house WHERE user_id = ? AND house_id = ?");
        $stmt->bind_param("ii", $user_id, $this->house_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            return array("success" => false, "message" => "User is not part of this house", "code" => 404);
        }

        // Remove user_id from the house
        $stmt = $this->conn->prepare("DELETE FROM join_user_house WHERE user_id = ? AND house_id = ?");
        $stmt->bind_param("ii", $user_id, $this->house_id);

        if ($stmt->execute()) {
            // Optionally delete the user from the user table if needed
            $delete_user_stmt = $this->conn->prepare("DELETE FROM user WHERE user_id = ?");
            $delete_user_stmt->bind_param("i", $user_id);
            $delete_user_stmt->execute();

            return array("success" => true, "message" => "User removed from the house successfully", "code" => 200);
        } else {
            return array("success" => false, "message" => "Failed to remove user from the house", "code" => 500);
        }
    }


    public function addKey($arrayOfSmartKeys)
    {
        foreach ($arrayOfSmartKeys as $smartKey) {
            $id = $smartKey->getKeyId();
            $stmt_check = $this->conn->prepare("SELECT key_id FROM smartkey WHERE key_uid = ?");
            $stmt_check->bind_param("s", $id);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows == 0) {
                $stmt = $this->conn->prepare("INSERT INTO smartkey (key_uid, house_id, key_name, key_status, active_color, deactive_color, key_model, newCommand) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

                $keyId = $smartKey->getKeyId();
                $houseId = $this->house_id; // Assign the house_id to the smart key
                $keyName = $smartKey->getKeyName();
                $keyStatus = $smartKey->getKeyStatus();
                $activeColor = $smartKey->getActiveColor();
                $deactiveColor = $smartKey->getDeactiveColor();
                $keyModel = $smartKey->getKeyModel();
                $newCommand = $smartKey->isNewCommand() ? 1 : 0;

                $stmt->bind_param(
                    "sisssssi",
                    $keyId,
                    $houseId,
                    $keyName,
                    $keyStatus,
                    $activeColor,
                    $deactiveColor,
                    $keyModel,
                    $newCommand
                );
                $stmt->execute();
                $stmt->close();
            }
        }

        $this->conn->close();
    }
    public function addRelay($arrayofRelays)
    {
        foreach ($arrayofRelays as $smartRelay) {
            $id = $smartRelay->getSmartRelayID();
            $stmt_check = $this->conn->prepare("SELECT smart_relay_id  FROM smartrelay WHERE smart_relay_id  = ?");
            $stmt_check->bind_param("i", $id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows == 0) {
                $stmt = $this->conn->prepare("INSERT INTO smartrelay (smart_relay_id , smart_relay_status, smart_relay_count, firmware_version) VALUES (?, ?, ?, ?)");

                $relayId = $smartRelay->getSmartRelayID();
                $relayStatus = $smartRelay->getSmartRelayStatus();
                $relayCount = $smartRelay->getSmartRelayCount();
                $relayFirmware = $smartRelay->getFirmwareVersion();


                $stmt->bind_param(
                    "ssis",
                    $relayId,
                    $relayStatus,
                    $relayCount,
                    $relayFirmware,

                );
                $stmt->execute();
                $stmt->close();
            }

        }




        $lastInsertedIds = [];
        foreach ($arrayofRelays as $smartRelay) {
            $lastInsertedIds[] = $smartRelay->getSmartRelayID();
        }


        $smartKeysJson = json_encode($lastInsertedIds);

        $stmt = $this->conn->prepare("UPDATE house SET smartRelays = ? WHERE house_id = ?");
        $stmt->bind_param("ss", $smartKeysJson, $this->house_id);
        $stmt->execute();

        $stmt->close();
        $this->conn->close();
    }

    public function create()
    {
        $stmtCheck = $this->conn->prepare("SELECT * FROM house WHERE house_name=?");
        $stmtCheck->bind_param("s", $this->house_name);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        if (!$stmtCheck->num_rows == 0) {
            return false;
        }
        $database_status = "Available";
        $stmt = $this->conn->prepare("INSERT INTO house (house_name,key_firmware_version,hardware_revision,database_status) VALUES (?,?,?,?)");
        $stmt->bind_param("ss", $this->house_name, $database_status);
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