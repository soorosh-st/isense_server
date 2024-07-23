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

    function setKey($house_id, $key, $conn)
    {
        $stmt = $conn->prepare("SELECT key_id FROM smartkey WHERE key_id = ?");
        $stmt->bind_param("s", $this->key_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            // key does not exist
            return false;
        }
        $stmt = $conn->prepare("SELECT house_id FROM house WHERE house_id = ?");
        $stmt->bind_param("s", $house_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            // key is not in this house
            return false;
        }

        $stmt = $conn->prepare("UPDATE smartkey SET  key_status = ?, newCommand = 1 WHERE key_id = ?");

        $stmt->bind_param("ss", $this->key_status, $this->key_id);

        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
        $stmt->close();

        for ($i = 0; $i < strlen($this->key_status); $i++) {
            $poleStatus = (int) $this->key_status[$i]; // Get the status from keyStatus string
            $stmt_pole = $conn->prepare("UPDATE keypole SET pole_status=? WHERE key_id=? AND pole_name=?");
            $poleName = "Pole " . ($i + 1);

            $stmt_pole->bind_param("iis", $poleStatus, $this->key_id, $poleName);
            $stmt_pole->execute();
            $stmt_pole->close();
        }



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