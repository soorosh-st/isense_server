<?php

class Log
{
    private $user_id;
    private $action;
    private $conn;
    private $secutrity;

    public function __construct($conn, $action, $user_id, $secutrity)
    {
        $this->action = $action;
        $this->secutrity = $secutrity;
        $this->conn = $conn;
        $this->user_id = $user_id;
    }
    public function create()
    {
        $stmt = $this->conn->prepare("INSERT INTO log (user_id,  action, date,security) VALUES (?, ?, now(),?)");
        $stmt->bind_param("iss", $this->user_id, $this->action, $this->secutrity);
        $stmt->execute();
    }
}