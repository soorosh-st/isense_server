<?php

class smartRelay
{

    private $smartRelayStatus;
    private $smartRelayCount;
    private $firmwareVersion;
    private $smartRelayID;


    public function __construct($smartRelayID, $smartRelayStatus, $smartRelayCount, $firmwareVersion)
    {
        $this->smartRelayID = $smartRelayID;
        $this->smartRelayStatus = $smartRelayStatus;
        $this->firmwareVersion = $firmwareVersion;
        $this->smartRelayCount = $smartRelayCount;

    }

    public function readAll($house_name, $user_id, $conn)
    {
        $house = new house($house_name, $conn, NULL);
        if (!$house->isUserInHouse($user_id)) {
            return false;
        }
        return $house->getHouseRelays();
    }
    public function setRelay($house_name, $smart_relay_status, $conn)
    {
        $statusParts = explode(":", $smart_relay_status);

        $relayNumber = $statusParts[0];
        $relayState = $statusParts[1];
        $stmt = $conn->prepare("SELECT smart_relay_status FROM smartrelay WHERE smart_relay_id = ?");
        $stmt->bind_param("s", $this->smartRelayID);
        $stmt->execute();

        // Get the result
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            $currentStatus = $row['smart_relay_status'];

            $statusArray = str_split($currentStatus);


            $statusArray[$relayNumber - 1] = $relayState;

            $updatedStatus = implode("", $statusArray);

            $updateStmt = $conn->prepare("UPDATE smartrelay SET smart_relay_status = ? WHERE smart_relay_id = ?");
            $updateStmt->bind_param("ss", $updatedStatus, $this->smartRelayID);

            if ($updateStmt->execute()) {
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }
    }
    public function getSmartRelayStatus()
    {
        return $this->smartRelayStatus;
    }

    public function setSmartRelayStatus($smartRelayStatus)
    {
        $this->smartRelayStatus = $smartRelayStatus;
    }

    public function getSmartRelayCount()
    {
        return $this->smartRelayCount;
    }

    public function setSmartRelayCount($smartRelayCount)
    {
        $this->smartRelayCount = $smartRelayCount;
    }

    public function getFirmwareVersion()
    {
        return $this->firmwareVersion;
    }

    public function setFirmwareVersion($firmwareVersion)
    {
        $this->firmwareVersion = $firmwareVersion;
    }

    public function getSmartRelayID()
    {
        return $this->smartRelayID;
    }

    public function setSmartRelayID($smartRelayID)
    {
        $this->smartRelayID = $smartRelayID;
    }


}



?>