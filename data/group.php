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
        $stmt = $this->conn->prepare("INSERT INTO room (title, img_src, house_id, database_status) VALUES (?, ?, ?, ?)");
        if (!$stmt) {

            return false;
        }

        $database_status = "Available";  // Example default value
        $stmt->bind_param("ssss", $this->title, $this->img_src, $this->house_id, $database_status);

        if ($stmt->execute()) {

            return true;
        }


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
        return $rooms;
    }
    public function readSingle()
    {
        $stmt = $this->conn->prepare("SELECT sk.key_id, sk.key_name, sk.key_status, sk.active_color, sk.deactive_color, sk.firmware_version, sk.key_model, sk.newCommand
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
        return $smartkeys;
    }
}