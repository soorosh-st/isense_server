<?php


class scenario
{
    private $key;
    private $name;
    private $isActive;
    public $src;
    public $delay;
    private $conn;

    public function __construct($id, $key, $name, $src, $delay)
    {
        $this->id = $id;
        $this->delay = $delay;
        $this->src = $src;
        $this->key = $key;
        $this->name = $name;
        $this->isActive = false;
    }

    function readAll($house_id, $token, $conn)
    {

        $house = new house(NULL, $conn, $house_id);
        if (!$house->isUserInHouse($token)) {
            return false;
        }
        return $house->getHouseScenarios();
    }
    function setKey($house_id, $key, $conn)
    {

        $scenario_code = null;


        $stmt = $conn->prepare("SELECT scenario_code FROM scenario WHERE scenario_id = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();


        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $scenario_code = $row['scenario_code'];
        }
        $stmt->close();

        if (empty($scenario_code)) {
            return false;
        }

        $stmt = $conn->prepare("UPDATE house SET scenario = ? WHERE house_id = ?");
        $stmt->bind_param("ss", $scenario_code, $house_id);
        $update_success = $stmt->execute();
        $stmt->close();

        return $update_success;
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