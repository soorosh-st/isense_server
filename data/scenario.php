<?php


class scenario
{
    private $key;
    private $name;
    private $isActive;
    public $src;
    public $delay;
    private $conn;

    public function __construct($key, $name, $src, $delay)
    {
        $this->delay = $delay;
        $this->src = $src;
        $this->key = $key;
        $this->name = $name;
        $this->isActive = false;
    }

    function readAll($house_name, $user_id, $conn)
    {

        $house = new house($house_name, $conn, NULL);
        if (!$house->isUserInHouse($user_id)) {
            return false;
        }
        return $house->getHouseScenarios();
    }
    function setKey($house_name, $key, $conn)
    {
        $stmt = $conn->prepare("UPDATE house SET scenario = ? WHERE house_name = ?");
        $stmt->bind_param("ss", $key, $house_name);
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        }
        $stmt->close();
        return false;
    }
    public function getKey()
    {
        return $this->key;
    }
    public function getName()
    {
        return $this->name;
    }
    public function isActive()
    {
        return $this->isActive;
    }
}


?>