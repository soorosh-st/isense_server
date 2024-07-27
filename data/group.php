<?php


class group
{

    private $room_id;
    private $title;
    private $img_src;
    private $house_id;
    private $conn;

    public function __construct($room_id, $title, $img_src, $house_id, $conn)
    {
        $this->room_id = $room_id;
        $this->title = $title;
        $this->img_src = $img_src;
        $this->house_id = $house_id;
        $this->conn = $conn;

    }
    public function create()
    {
        $stmt = $this->conn->prepare("INSERT INTO room (title, img_src, house_id, database_status,clicks) VALUES (?, ?, ?, ?,?)");
        if (!$stmt) {

            return false;
        }
        $randomNumber = rand(0, 7);
        $database_status = "Available";  // Example default value
        $stmt->bind_param("ssssi", $this->title, $this->img_src, $this->house_id, $database_status, $randomNumber);

        if ($stmt->execute()) {

            return true;
        }


    }
    function getSmartKeyCount($conn, $house_id)
    {
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM smartkey WHERE house_id = ? ");
        $stmt->bind_param("s", $house_id);
        if (!$stmt) {
            return false;
        }
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $stmt->close();
            return false;
        }
        $row = $result->fetch_assoc();
        $count = $row['count'];
        $stmt->close();
        return $count;
    }
    public function import($devices)
    {
        $added_count = 0;
        $duplicate = false;
        foreach ($devices as $device) {
            $key_id = $device->id;

            // Check if device is already in the room
            $stmt_check = $this->conn->prepare("SELECT * FROM join_room_smartkey WHERE room_id = ? AND key_id = ?");
            $stmt_check->bind_param("ii", $this->room_id, $key_id);
            $stmt_check->execute();
            $result = $stmt_check->get_result();

            if ($result->num_rows > 0) {
                $duplicate = true;
                continue; // Skip adding this device if it already exists in the room
            }

            // Prepare statement for inserting device into the room
            if ($device->type == "Key") {
                $stmt = $this->conn->prepare("INSERT INTO join_room_smartkey (room_id, key_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $this->room_id, $key_id);
            } elseif ($device->type == "Relay") {
                // Add logic for Relay devices if needed
                continue; // Example: $stmt = $this->conn->prepare("INSERT INTO join_room_relay (room_id, relay_id) VALUES (?, ?)");
            } else {
                continue; // Handle other device types if necessary
            }

            if (!$stmt->execute()) {
                $stmt->close();
                return false; // Return false on execution failure
            }

            $added_count++; // Increment count for successfully added devices
            $stmt->close();
        }

        // Update the room's count column
        if ($added_count > 0) {
            $stmt_update = $this->conn->prepare("UPDATE room SET count = count + ? WHERE room_id = ?");
            $stmt_update->bind_param("ii", $added_count, $this->room_id);
            $stmt_update->execute();
            $stmt_update->close();
        }
        if ($duplicate)
            return false;
        return true; // Return true indicating successful import
    }
    public function readTop()
    {
        $query = "SELECT room_id, title, img_src, count, clicks FROM room WHERE house_id = ? ORDER BY clicks DESC LIMIT 3";

        // Prepare the query
        $stmt = $this->conn->prepare($query);

        // Bind the house_id parameter
        $stmt->bind_param('i', $this->house_id);

        // Execute the query
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch the top 3 results
        $rooms = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rooms[] = $row;
            }
        }

        $stmt->close();
        return $rooms;
    }
    public function readAll()
    {
        // SQL query to select all rooms for a specific house
        $query = "SELECT room_id, title, img_src,count FROM room WHERE house_id = ?";

        // Prepare the query
        $stmt = $this->conn->prepare($query);

        // Bind the house_id parameter
        $stmt->bind_param('i', $this->house_id);

        // Execute the query
        $stmt->execute();
        $result = $stmt->get_result();
        // Fetch all results
        $rooms = [];
        if ($result->num_rows > 0) {

            while ($row = $result->fetch_assoc()) {

                $rooms[] = $row;
            }
        }

        $stmt->close();
        $count = $this->getSmartKeyCount($this->conn, $this->house_id);

        return array("All_device_count" => $count, "groups" => $rooms);
    }





    public function readSingle()
    {
        $stmt = $this->conn->prepare("SELECT sk.key_id, sk.key_name, sk.key_status, sk.active_color, sk.deactive_color ,sk.key_model , sk.pole_number,key_uid
                        FROM smartkey sk
                        INNER JOIN join_room_smartkey jrsk ON sk.key_id = jrsk.key_id
                        WHERE jrsk.room_id = ?");
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to prepare statement"]);
            exit();
        }

        $stmt->bind_param("i", $this->room_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $smartkeys = [];
        while ($row = $result->fetch_assoc()) {
            $smartkeys[] = $row;
        }

        $stmt->close();
        $stmt = $this->conn->prepare("SELECT clicks FROM room WHERE room_id = ?  ");
        $stmt->bind_param("i", $this->room_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $clicks = $result->fetch_assoc()['clicks'];
        $stmt->close();

        $clicks += 1;
        $stmt = $this->conn->prepare("UPDATE room SET clicks=? WHERE  room_id = ?");
        $stmt->bind_param("ii", $clicks, $this->room_id);
        $stmt->execute();
        $stmt->close();


        return $smartkeys;
    }
}