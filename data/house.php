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

        foreach ($arrayOfScenario as $scenario) {

            $id = $scenario->getKey();
            $stmt_check = $this->conn->prepare("SELECT * FROM scenario WHERE scenario_code = ? AND house_id=?");
            $stmt_check->bind_param("ss", $id, $this->house_id);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            //echo $result->num_rows;
            if ($result->num_rows == 0) {
                //echo "here";
                $stmt = $this->conn->prepare("INSERT INTO scenario (scenario_name, scenario_code,  scenario_delay, house_id) VALUES (?, ?,  ?, ?)");

                $scenarioName = $scenario->getName();
                $scenarioCode = $scenario->getKey();
                $scenarioDelay = 40; // Assuming this is to be provided or can be set to a default value
                $houseId = $this->house_id;
                $stmt->bind_param(
                    "ssii",
                    $scenarioName,
                    $scenarioCode,
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
    public function isUserAdminInHouse($token)
    {
        // Check if user is an admin
        $stmt = $this->conn->prepare("SELECT isManager,user_id FROM user WHERE user_token = ?");
        $stmt->bind_param("i", $token);
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
        $admin_id = $row['user_id'];
        // Check if user is part of the house
        $stmt = $this->conn->prepare("SELECT 1 FROM join_user_house WHERE user_id = ? AND house_id = ?");
        $stmt->bind_param("ii", $admin_id, $this->house_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $admin_id;
        } else
            return false;
    }
    public function getHouseUsers($admin_id)
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
            if ($row['user_id'] != $admin_id) {
                $user_list[] = $row;
            }
        }
        $status = ($result->num_rows != 0);
        return ["status" => $status, "List" => $user_list];
    }


    public function setHouseScenarioFavorites($scenarios)
    {
        $stmt = $this->conn->prepare("UPDATE scenario SET favorite=0 WHERE house_id = ?");
        $stmt->bind_param("i", $this->house_id);
        if (!$stmt->execute())
            return false;

        foreach ($scenarios as $scenario) {
            $stmt = $this->conn->prepare("UPDATE scenario SET favorite=1 WHERE house_id = ? AND scenario_id = ?");
            $stmt->bind_param("ii", $this->house_id, $scenario->scenario_id);
            if (!$stmt->execute())
                return false;

        }
        $stmt->close();
        return true;
    }

    public function getHouseScenarios($favorite)
    {
        if ($favorite == 0) {
            $stmt = $this->conn->prepare("SELECT scenario_id, scenario_name, scenario_code, scenario_delay, isActive,favorite FROM scenario WHERE house_id = ?");
        } else {
            $stmt = $this->conn->prepare("SELECT scenario_id, scenario_name, scenario_code, scenario_delay, isActive,favorite FROM scenario WHERE house_id = ? AND favorite=1");
        }

        $stmt->bind_param("s", $this->house_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $scenarios = [];
        while ($row = $result->fetch_assoc()) {
            // Modify scenario_img based on theme
            // $row['scenario_img'] .= ".svg";
            $scenarios[] = $row;
        }
        $stmt->close();

        // If favorites were requested and none were found, fetch up to 4 random scenarios
        if ($favorite == 1 && empty($scenarios)) {
            $stmt = $this->conn->prepare("SELECT scenario_id, scenario_name, scenario_code, scenario_delay, isActive,favorite FROM scenario WHERE house_id = ? ORDER BY RAND() LIMIT 4");
            $stmt->bind_param("s", $this->house_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while (($row = $result->fetch_assoc())) {
                // Modify scenario_img based on theme
                // $row['scenario_img'] .= ".svg";
                $scenarios[] = $row;

            }
            $stmt->close();
        }

        return $scenarios;
    }

    public function getHouseSmartKey()
    {
        $stmt = $this->conn->prepare("SELECT key_id, key_uid, key_name,active_color, deactive_color, key_model FROM smartkey WHERE house_id = ?");
        $stmt->bind_param("s", $this->house_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $smartKeys = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $row['type'] = "Key";

                // Get poles for the current key
                $stmt_poles = $this->conn->prepare("SELECT pole_status , pole_img , pole_displayname , pole_id FROM keypole WHERE key_id = ?");
                $stmt_poles->bind_param("i", $row['key_id']);
                $stmt_poles->execute();
                $result_poles = $stmt_poles->get_result();

                $poles = [];
                while ($pole = $result_poles->fetch_assoc()) {
                    $poles[] = $pole;
                }
                $stmt_poles->close();

                $row['poles'] = $poles;
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


    public function adduser($user, $iv, $token)
    {
        $admin_id = $user->checkToken($token);

        $user_id = -1;
        if (!$user_id = $user->signup($iv)) {
            return array("success" => false, "message" => "Failed to create user");
        }

        $log = new Log($this->conn, "user with id: {$user_id} just created.", $admin_id, "LOW");
        $log->create();

        // Insert the relationship into join_user_house table
        $stmt = $this->conn->prepare("INSERT INTO join_user_house (user_id, house_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $this->house_id);



        if ($stmt->execute()) {
            $log = new Log($this->conn, "user with id: {$user_id} added to house", $admin_id, "LOW");
            $log->create();
            return array("success" => true, "message" => "User added to the house successfully");
        } else {
            return array("success" => false, "message" => "Failed to add user to the house");
        }
    }


    public function updateKey($arrayOfSmartKeys)
    {
        foreach ($arrayOfSmartKeys as $smartKey) {
            $id = $smartKey->getKeyId();

            // Check if the smart key exists
            $stmt_check = $this->conn->prepare("SELECT key_id FROM smartkey WHERE key_uid = ?");
            $stmt_check->bind_param("s", $id);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                // Update the smart key status
                $stmt = $this->conn->prepare("UPDATE smartkey SET key_status=?, newCommand=? WHERE key_uid = ?");
                $keyStatus = $smartKey->getKeyStatus();
                $newCommand = 0;

                $stmt->bind_param(
                    "sis",
                    $keyStatus,
                    $newCommand,
                    $id
                );
                $stmt->execute();
                $stmt->close();

                // Update the poles associated with this smart key
                // Assuming poles are associated with smart key by key_id in pole table
                for ($i = 0; $i < strlen($keyStatus); $i++) {
                    $poleStatus = $keyStatus[$i] == '1' ? 1 : 0; // Convert '1'/'0' to boolean 1/0
                    $poleIndex = $i + 1; // Pole index starts from 1

                    $stmt_pole = $this->conn->prepare("UPDATE keypole SET pole_status=? WHERE key_id=? AND pole_name=?");
                    $stmt_pole->bind_param("iis", $poleStatus, $id, $poleIndex);
                    echo $id . "  " . $poleStatus . "   " . $poleIndex;

                    $stmt_pole->execute();
                    $stmt_pole->close();
                }
            }

            $stmt_check->close();
        }
    }

    public function getupdates()
    {
        $response = [
            "scenario" => "none",
            "delay" => 0,
            "devices" => []
        ];
        $stmt = $this->conn->prepare("SELECT scenario FROM house WHERE house_id = ?");
        $stmt->bind_param("s", $this->house_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();


        if ($row['scenario'] !== null) {
            $response['scenario'] = $row['scenario'];

            $stmt = $this->conn->prepare("SELECT scenario_delay FROM scenario WHERE scenario_code = ?");
            $stmt->bind_param("s", $response['scenario']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $response['delay'] = $row['scenario_delay'];

            $stmt = $this->conn->prepare("UPDATE house SET scenario=NULL WHERE house_id = ?");
            $stmt->bind_param("s", $this->house_id);
            $stmt->execute();
        }
        $stmt = $this->conn->prepare("SELECT key_uid, key_status, active_color, deactive_color,hasColor FROM smartkey WHERE house_id = ? AND newCommand = 1");
        $stmt->bind_param("s", $this->house_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {

            $response['devices'][] = $row;
        }

        $stmt->close();





        return $response;
    }
    public function addKey($arrayOfSmartKeys)
    {
        foreach ($arrayOfSmartKeys as $smartKey) {
            $id = $smartKey->getKeyId();
            $stmt_check = $this->conn->prepare("SELECT key_id FROM smartkey WHERE key_uid = ?");
            $stmt_check->bind_param("s", $id);
            $stmt_check->execute();
            // $stmt_check->store_result();
            $result = $stmt_check->get_result();
            $row = $result->fetch_assoc();
            $key_id = $row['key_id'];

            if ($result->num_rows == 0) {
                $stmt = $this->conn->prepare("INSERT INTO smartkey (key_uid, house_id, key_name, key_status, active_color, deactive_color, key_model, newCommand,pole_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?)");

                $keyId = $smartKey->getKeyId();
                $houseId = $this->house_id; // Assign the house_id to the smart key
                $keyName = $smartKey->getKeyName();
                $keyStatus = $smartKey->getKeyStatus();
                $activeColor = $smartKey->getActiveColor();
                $deactiveColor = $smartKey->getDeactiveColor();
                $keyModel = $smartKey->getKeyModel();
                $newCommand = $smartKey->isNewCommand() ? 1 : 0;
                $poleNumber = strlen($keyStatus);
                $stmt->bind_param(
                    "sisssssii",
                    $keyId,
                    $houseId,
                    $keyName,
                    $keyStatus,
                    $activeColor,
                    $deactiveColor,
                    $keyModel,
                    $newCommand,
                    $poleNumber
                );
                $stmt->execute();
                $stmt->close();
                $keyIdInserted = $this->conn->insert_id;
                for ($i = 0; $i < $poleNumber; $i++) {
                    $poleStatus = (int) $keyStatus[$i]; // Static value for pole status at creation
                    $poleImg = "/public/devices/light-bulb.svg"; // Static value for pole image at creation
                    $poleName = ($i + 1); // Static value for pole name at creation

                    $stmt_pole = $this->conn->prepare("INSERT INTO keypole (pole_status, pole_img, pole_name, key_id,pole_displayname) VALUES (?, ?, ?, ?,?)");
                    $stmt_pole->bind_param("issis", $poleStatus, $poleImg, $poleName, $keyIdInserted, $poleName);
                    $stmt_pole->execute();
                    $stmt_pole->close();
                }
            } else {
                $stmt = $this->conn->prepare("UPDATE smartkey SET key_status=?,newCommand=? WHERE key_uid = ?");
                $keyStatus = $smartKey->getKeyStatus();
                $newCommand = 0;

                $stmt->bind_param(
                    "sis",
                    $keyStatus,
                    $newCommand,
                    $id
                );
                $stmt->execute();
                $stmt->close();

                for ($i = 0; $i < strlen($keyStatus); $i++) {
                    $poleStatus = (int) $keyStatus[$i]; // Get the status from keyStatus string
                    $stmt_pole = $this->conn->prepare("UPDATE keypole SET pole_status=? WHERE key_id=? AND pole_name=?");
                    $poleName = ($i + 1);

                    $stmt_pole->bind_param("iis", $poleStatus, $key_id, $poleName);
                    $stmt_pole->execute();
                    $stmt_pole->close();
                }

            }
        }
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