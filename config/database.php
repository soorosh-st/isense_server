<?php
class database
{
    private $hostname = "isensedatabase";
    private $username = "root";
    private $password = "BLdrnljYG5OHRUPEBLo4EtlU";
    private $databasename = "isense";
    private $port = 3306;
    private $conn;

    public function get_connection()
    {
        $this->conn = null;
        try {
            $this->conn = new mysqli($this->hostname, $this->username, $this->password, $this->databasename, $this->port);
            mysqli_set_charset($this->conn, "utf8");
        } catch (PDOException $e) {
            throw new Exception("connection error" . $e->getMessage(), $e->getCode());
        }
        return $this->conn;
    }
}
?>