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
        $stmt = $this->conn->prepare("INSERT INTO room (title,  house_id, database_status,clicks) VALUES (?, ?, ?, ?)");
        if (!$stmt) {

            return false;
        }
        $randomNumber = rand(0, 7);
        $database_status = "Available";
        $stmt->bind_param("sssi", $this->title, $this->house_id, $database_status, $randomNumber);

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        } else
            return -1;


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



        // remove devices that are already in room
        $stmt_delete = $this->conn->prepare("DELETE FROM `join_room_smartkey` WHERE room_id = ? ");
        $stmt_delete->bind_param("i", $this->room_id);
        if (!$stmt_delete->execute())
            return false;

        // rename the room
        $stmt_delete = $this->conn->prepare("UPDATE room SET title = ?  WHERE room_id = ? ");
        $stmt_delete->bind_param("si", $this->title, $this->room_id);
        if (!$stmt_delete->execute())
            return false;

        foreach ($devices as $device) {
            $key_id = $device->id;



            // Prepare statement for inserting device into the room
            if ($device->type == "Key") {
                $stmt = $this->conn->prepare("SELECT key_id FROM smartkey WHERE key_uid = ? ");
                $stmt->bind_param("i", $key_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $keyID = $row['key_id'];

                $stmt = $this->conn->prepare("INSERT INTO join_room_smartkey (room_id, key_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $this->room_id, $keyID);
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

            // Increment count for successfully added devices
            $stmt->close();
        }

        // Update the room's count column
        $size = sizeof($devices);

        $stmt_update = $this->conn->prepare("UPDATE room SET count =  ? WHERE room_id = ?");
        $stmt_update->bind_param("ii", $size, $this->room_id);
        $stmt_update->execute();
        $stmt_update->close();

        return true; // Return true indicating successful import
    }
    public function readTop()
    {
        $query = "SELECT room_id, title, count, clicks FROM room WHERE house_id = ? ORDER BY clicks DESC LIMIT 3";

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
        $query = "SELECT room_id, title,count FROM room WHERE house_id = ?";

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


        return $rooms;
    }
    public function readAllPanel()
    {
        // SQL query to select all rooms and associated smart keys for a specific house
        $query = "
        SELECT 
            r.room_id, 
            r.title, 
            r.count,
            sk.key_id, 
            sk.key_uid
            
        FROM 
            room r
        LEFT JOIN 
        join_room_smartkey rs ON r.room_id = rs.room_id 
        LEFT JOIN 
            smartkey sk ON rs.key_id = sk.key_id
        WHERE 
            r.house_id = ?
    ";

        // Prepare the query
        $stmt = $this->conn->prepare($query);

        // Bind the house_id parameter
        $stmt->bind_param('i', $this->house_id);

        // Execute the query
        $stmt->execute();
        $result = $stmt->get_result();

        // Initialize an array to hold the rooms and their smart keys
        $rooms = [];

        // Process the results
        while ($row = $result->fetch_assoc()) {
            $roomId = $row['room_id'];

            // If this room has not been added to the $rooms array yet, add it
            if (!isset($rooms[$roomId])) {
                $rooms[$roomId] = [
                    'room_id' => $row['room_id'],
                    'title' => $row['title'],
                    'count' => $row['count'],
                    'devices' => []
                ];
            }

            // Add smart key information to the current room
            if ($row['key_id']) {
                $rooms[$roomId]['devices'][] = [

                    'key_uid' => $row['key_uid'],

                ];
            }
        }

        $stmt->close();

        // Return the rooms as an indexed array (to remove numeric keys)
        return array_values($rooms);
    }




    public function readSingle()
    {
        $stmt = $this->conn->prepare("SELECT sk.key_id, sk.key_name, sk.active_color, sk.deactive_color ,sk.key_model , sk.pole_number,key_uid
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
                $smartkeys[] = $row;
            }
        }


        $stmt->close();
        $stmt = $this->conn->prepare("SELECT clicks, title  FROM room WHERE room_id = ?  ");
        $stmt->bind_param("i", $this->room_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $title = $row['title'];
        $clicks = $row['clicks'];
        $stmt->close();

        $clicks += 1;
        $stmt = $this->conn->prepare("UPDATE room SET clicks=? WHERE  room_id = ?");
        $stmt->bind_param("ii", $clicks, $this->room_id);
        $stmt->execute();
        $stmt->close();


        return ["Group" => $title, "Smartkeys" => $smartkeys];
    }
}