<?php
date_default_timezone_set('Asia/Tehran');
class user
{
    private $username;
    private $password;
    private $token;
    private $isManager;
    private $timeout;
    private $toekn_timeout;
    private $conn;



    public function __construct($conn, $username, $password, $isManager, $timeout, $token)
    {
        $this->token = $token;
        $this->username = $username;
        $this->password = $password;
        $this->conn = $conn;
        $this->isManager = $isManager;
        $this->timeout = $timeout;
    }




    public function signin($iv)
    {

        if (!$this->hasUser($this->username)) {

            return false;
        }

        $this->password = $this->decryptAES($this->password, $iv);

        $stmt = $this->conn->prepare("SELECT isManager,user_name, user_password,user_id FROM user WHERE user_name = ?");
        $stmt->bind_param("s", $this->username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            $hashedPasswordFromDB = $row['user_password'];

            if (password_verify($this->password, $hashedPasswordFromDB)) {
                $this->username = $row['user_name'];
                $this->isManager = $row['isManager'];
                $id = $row['user_id'];

                $this->createToken();
                $houses = $this->getHousesByUserId($id);

                return array("token" => $this->token, "username" => $this->username, "isManager" => $this->isManager, "id" => $id, "houses" => $houses);
            }
        }
        $stmt->close();
        $this->conn->close();
        return false;
    }


    private function getHousesByUserId($user_id)
    {
        $stmt = $this->conn->prepare("
            SELECT h.house_id, h.house_name,h.database_status
            FROM house h
            INNER JOIN join_user_house juh ON h.house_id = juh.house_id
            WHERE juh.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $houses = array();
        while ($row = $result->fetch_assoc()) {
            $houses[] = $row;
        }

        $stmt->close();
        return $houses;
    }

    private function decryptAES($data, $iv)
    {
        $key = "feUGSmdz4ih/vxOxOZg506eOnfOgSUP1AHmrCqT8ayg=";
        $decodedKey = base64_decode($key);
        $decodedIV = base64_decode($iv);
        $decodedEncryptedData = base64_decode($data);
        return openssl_decrypt($decodedEncryptedData, 'aes-256-cbc', $decodedKey, OPENSSL_RAW_DATA, $decodedIV);
    }

    private function encryptAES($data, $iv)
    {
        $key = "feUGSmdz4ih/vxOxOZg506eOnfOgSUP1AHmrCqT8ayg=";
        $decodedKey = base64_decode($key);
        $decodedIV = base64_decode($iv);


        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $decodedKey, OPENSSL_RAW_DATA, $decodedIV);


        return base64_encode($encryptedData);
    }



    public function signup()
    {
        $success = true;
        $accessTimeout = NULL;
        $noTimeLimit = false;

        if ($this->hasUser($this->username)) {
            return false;
        }

        $hashedPassword = password_hash($this->password, PASSWORD_BCRYPT, ['cost' => 11]);

        if ($this->timeout == -1) {
            $accessTimeout = null;
            $noTimeLimit = true;
        } else if (!$this->isManager) {
            $accessTimeout = date('Y-m-d H:i:s', strtotime('+' . $this->timeout . ' days'));
        }

        $stmt = $this->conn->prepare("INSERT INTO user (user_name, user_password, isManager, access_timeout, noTimeLimit) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $this->username, $hashedPassword, $this->isManager, $accessTimeout, $noTimeLimit);

        if ($stmt->execute() === TRUE) {
            return $this->conn->insert_id;
        } else {
            return false;
        }
    }



    public function checkAccess()
    {
        $success = true;
        $stmt = $this->conn->prepare("SELECT noTimeLimit,isManager, access_timeout, token_timeout FROM user WHERE user_token = ?");
        $stmt->bind_param("ss", $this->username, $this->token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $isManager = $row['isManager'];
            $accessTimeout = strtotime($row['access_timeout']);
            $tokenTimeout = strtotime($row['token_timeout']);
            $noTimeLimit = $row['noTimeLimit'];
            $currentTime = time();
            if (!$isManager && $accessTimeout < $currentTime && !$noTimeLimit) {
                $success = false;
            }
            if ($tokenTimeout < $currentTime) {
                $success = false;
            }
        } else {
            $success = false;
        }

        $stmt->close();
        $stmt = $this->conn->prepare("UPDATE user SET lastLogin = now() WHERE user_name = ?");
        $stmt->bind_param("s", $this->username);

        $stmt->execute();
        return $success;
    }
    private function hasUser($username)
    {
        $sql = "SELECT * FROM user WHERE user_name = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return true;
        } else
            return false;
    }
    private function createToken()
    {
        $token = bin2hex(random_bytes(16));
        $tokenTimeout = date('Y-m-d H:i:s', strtotime('+2 months'));
        $updateStmt = $this->conn->prepare("UPDATE user SET user_token = ?, token_timeout = ? WHERE user_name = ?");
        $updateStmt->bind_param("sss", $token, $tokenTimeout, $this->username);
        $updateStmt->execute();
        $updateStmt->close();
        $this->token = $token;

    }

    private function getThis()
    {
        return array(
            'username' => $this->username,
            'isManager' => $this->isManager,
            'timeout' => $this->timeout,
        );
    }
}






?>