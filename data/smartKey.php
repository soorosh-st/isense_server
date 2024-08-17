<?php

class smartKey implements JsonSerializable
{
    private $key_id;
    private $key_name;
    private $key_status;
    private $active_color;
    private $deactive_color;
    private $firmware_version;
    private $key_model;
    private $newCommand;

    private $conn;

    public function __construct($key_id, $key_name, $key_status, $active_color, $deactive_color, $firmware_version, $key_model, $newCommand)
    {
        $this->key_id = $key_id;
        $this->key_name = $key_name;
        $this->key_status = $key_status;
        $this->active_color = $active_color;
        $this->deactive_color = $deactive_color;
        $this->firmware_version = $firmware_version;
        $this->key_model = $key_model;
        $this->newCommand = $newCommand;
    }



    function readAll($house_id, $token, $conn)
    {
        $house = new house(NULL, $conn, $house_id);
        if (!$house->isUserInHouse($token)) {
            return false;
        }
        return $house->getHouseSmartKey();
    }
    public function updateKey($conn, $poles)
    {



        $stmt = $conn->prepare("UPDATE smartkey SET active_color = ?, deactive_color = ?, hasColor= ? WHERE key_id = ?");
        $hasColor = 1;
        $stmt->bind_param("ssii", $this->active_color, $this->deactive_color, $hasColor, $this->key_id);
        $stmt->execute();

        // Check if the update was successful


        // Update poles information
        foreach ($poles as $pole) {
            $pole_id = $pole->pole_id;
            $pole_img = $pole->pole_img;
            $pole_name = $pole->pole_displayname;

            $stmt_pole = $conn->prepare("UPDATE keypole SET pole_img = ?, pole_displayname = ? WHERE pole_id = ? AND key_id = ?");
            $stmt_pole->bind_param("ssii", $pole_img, $pole_name, $pole_id, $this->key_id);
            $stmt_pole->execute();
            //$stmt_pole->store_result();

            // Check if the update was successful for each pole

        }

        return true;
    }

    function setKey($house_id, $key, $conn, $pole_id)
    {
        $stmt = $conn->prepare("SELECT key_id,key_status FROM smartkey WHERE key_id = ?");
        $stmt->bind_param("s", $this->key_id);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $stmt->close();
            // key does not exist
            return false;
        }
        $row = $result->fetch_assoc();
        $status = $row['key_status'];
        $stmt = $conn->prepare("SELECT house_id FROM house WHERE house_id = ?");
        $stmt->bind_param("s", $house_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            // house does not exist
            return false;
        }

        $stmt = $conn->prepare("SELECT pole_name FROM keypole WHERE pole_id = ?");
        $stmt->bind_param("s", $pole_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $pole_number = (int) $row['pole_name'];
        // echo $this->key_status;
        $status[$pole_number - 1] = $this->key_status;
        $stmt = $conn->prepare("UPDATE smartkey SET  key_status = ?, newCommand = 1 WHERE key_id = ?");

        $stmt->bind_param("ss", $status, $this->key_id);

        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
        $stmt->close();


        $stmt_pole = $conn->prepare("UPDATE keypole SET pole_status=? WHERE key_id=? AND pole_id=?");
        $stmt_pole->bind_param("iii", $this->key_status, $this->key_id, $pole_id);
        $stmt_pole->execute();
        $stmt_pole->close();


        $stmt = $conn->prepare("UPDATE house SET keyChange = 1 WHERE house_id = ?");
        $stmt->bind_param("s", $house_id);

        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        return true;

    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    public function getKeyId()
    {
        return $this->key_id;
    }

    public function getKeyName()
    {
        return $this->key_name;
    }

    public function getKeyStatus()
    {
        return $this->key_status;
    }

    public function getActiveColor()
    {
        return $this->active_color;
    }

    public function getDeactiveColor()
    {
        return $this->deactive_color;
    }

    public function getFirmwareVersion()
    {
        return $this->firmware_version;
    }

    public function getKeyModel()
    {
        return $this->key_model;
    }

    public function isNewCommand()
    {
        return $this->newCommand;
    }

}












?>