<?php




class category
{
    private $conn;
    private $house_id;

    public function __construct($conn, $house_id)
    {
        $this->conn = $conn;
        $this->house_id = $house_id;
    }
    public function readAll()
    {

        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM smartkey WHERE house_id = ?");
        $stmt->bind_param("s", $this->house_id);

        if (!$stmt->execute()) {
            $response['message'] = "failed to retrive data";
            return $response;
        }
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $response['message'] = "Success";
        $response['counts']['SmartKey'] = $row['count'];

        $response['counts']['SmartRelay'] = 0;

        $response['counts']['Thermostate'] = 0;

        $response['counts']['SmartDoor'] = 0;


        return $response;
    }




}