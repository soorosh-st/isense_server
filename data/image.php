<?php


class image
{
    private $src;

    private $conn;


    public function __construct($conn)
    {
        $this->conn = $conn;
    }
    public function readAll()
    {
        // SQL query to select all rooms for a specific house
        $query = "SELECT src,image_id FROM image";

        // Prepare the query
        $stmt = $this->conn->prepare($query);

        // Execute the query
        $stmt->execute();
        $result = $stmt->get_result();
        // Fetch all results
        $img = [];
        if ($result->num_rows > 0) {

            while ($row = $result->fetch_assoc()) {

                $img[] = $row;
            }
        }


        return $img;
    }
}