<?php
class database
{
    private $hostname = "localhost";
    private $username = "root";
    private $password = "";
    private $databasename = "isense";
    private $conn;

    public function get_connection()
    {
        $this->conn = null;
        try {
            $this->conn = new mysqli($this->hostname, $this->username, $this->password, $this->databasename);
            mysqli_set_charset($this->conn, "utf8");
        } catch (PDOException $e) {
            throw new Exception("connection error" . $e->getMessage(), $e->getCode());
        }
        return $this->conn;
    }
}
?>