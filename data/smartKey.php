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



    function readAll($house_name, $user_id, $conn)
    {

        $house = new house($house_name, $conn, NULL);
        if (!$house->isUserInHouse($user_id)) {
            return false;
        }
        return $house->getHouseSmartKey();
    }

    function setKey($house_name, $key, $conn)
    {
        $stmt = $conn->prepare("UPDATE smartkey SET key_name = ?, key_status = ?, active_color = ?, deactive_color = ? ,newCommand=1 WHERE key_id = ?");

        $stmt->bind_param("sssss", $this->key_name, $this->key_status, $this->active_color, $this->deactive_color, $this->key_id);

        if ($stmt->execute()) {
            $stmt = $conn->prepare("UPDATE house SET keyChange = 1 WHERE house_name = ?");
            $stmt->bind_param("s", $house_name);

            if ($stmt->execute()) {

                $stmt->close();
                return true;
            }
        }
        $stmt->close();
        return false;
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