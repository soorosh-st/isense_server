<?php
date_default_timezone_set('Asia/Tehran');
class user
{
    private $username;
    private $user_id;
    private $password;
    private $token;
    private $isManager;
    private $timeout;
    private $toekn_timeout;
    private $conn;



    public function __construct($conn, $username, $password, $isManager, $timeout, $token, $user_id)
    {
        $this->token = $token;
        $this->username = $username;
        $this->password = $password;
        $this->conn = $conn;
        $this->isManager = $isManager;
        $this->timeout = $timeout;
        $this->user_id = $user_id;
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
    public function readAllUser($house_id)
    {

        $house = new house(NULL, $this->conn, $house_id);
        if ($admin_id = $house->isUserAdminInHouse($this->token)) {

            return $house->getHouseUsers($admin_id);
        } else
            return false;

    }
    public function removeUserFromHouse($user_id, $house_id)
    {
        // Check if admin_user_id is an admin

        $stmt = $this->conn->prepare("SELECT user_id , isManager FROM user WHERE user_token = ?");
        $stmt->bind_param("i", $this->token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['isManager'] != 1) {
                return array("success" => false, "message" => "Admin user is not an admin", "code" => 401);
            }
        } else {
            return array("success" => false, "message" => "Admin user not found", "code" => 404);
        }
        $admin_id = $row['user_id'];
        // Check if admin_user_id is part of the house
        $stmt = $this->conn->prepare("SELECT 1 FROM join_user_house WHERE user_id = ? AND house_id = ?");
        $stmt->bind_param("ii", $admin_id, $house_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            return array("success" => false, "message" => "Admin user is not part of this house", "code" => 401);
        }

        // Check if user_id is part of the house
        $stmt = $this->conn->prepare("SELECT 1 FROM join_user_house WHERE user_id = ? AND house_id = ?");
        $stmt->bind_param("ii", $user_id, $house_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            return array("success" => false, "message" => "User is not part of this house", "code" => 404);
        }

        // Remove user_id from the house
        $stmt = $this->conn->prepare("DELETE FROM join_user_house WHERE user_id = ? AND house_id = ?");
        $stmt->bind_param("ii", $user_id, $house_id);

        if ($stmt->execute()) {
            // Optionally delete the user from the user table if needed
            $delete_user_stmt = $this->conn->prepare("DELETE FROM user WHERE user_id = ?");
            $delete_user_stmt->bind_param("i", $user_id);
            $delete_user_stmt->execute();

            return array("success" => true, "message" => "User removed from the house successfully", "code" => 200);
        } else {
            return array("success" => false, "message" => "Failed to remove user from the house", "code" => 500);
        }
    }

    private function getHousesByUserId($user_id)
    {
        $stmt = $this->conn->prepare("
            SELECT h.house_id, h.house_name
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

    public function updateuser($iv)
    {


        if (!$this->hasUserToken($this->user_id)) {
            return array("success" => false, "message" => "user not found");
        }
        $accessTimeout = NULL;
        $noTimeLimit = false;

        if ($this->timeout == -1) {
            $accessTimeout = null;
            $noTimeLimit = true;
        } else {
            $accessTimeout = $this->timeout;
        }


        // Insert the relationship into join_user_house table
        if ($this->password == NULL) {
            $stmt = $this->conn->prepare("UPDATE user SET access_timeout = ? , noTimeLimit = ? WHERE user_id = ? ");
            $stmt->bind_param("sii", $accessTimeout, $noTimeLimit, $this->user_id);
        } else {
            $this->password = $this->decryptAES($this->password, $iv);

            $hashedPassword = password_hash($this->password, PASSWORD_BCRYPT, ['cost' => 11]);

            $stmt = $this->conn->prepare("UPDATE user SET access_timeout = ? , noTimeLimit = ? , user_password= ? WHERE user_id = ? ");
            $stmt->bind_param("sisi", $accessTimeout, $noTimeLimit, $hashedPassword, $this->user_id);

        }

        if ($stmt->execute()) {
            return array("success" => true, "message" => "User updated successfully");
        } else {
            return array("success" => false, "message" => "Failed to update user");
        }
    }

    public function signup($iv)
    {
        $success = true;
        $accessTimeout = NULL;
        $noTimeLimit = false;

        if ($this->hasUser($this->username)) {
            return false;
        }
        $this->password = $this->decryptAES($this->password, $iv);
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
        $stmt->bind_param("s", $this->token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // echo "hi";
            $row = $result->fetch_assoc();
            $isManager = $row['isManager'];
            $accessTimeout = strtotime($row['access_timeout']);
            $tokenTimeout = strtotime($row['token_timeout']);
            $noTimeLimit = $row['noTimeLimit'];
            $currentTime = time();
            if (!$isManager && $accessTimeout < $currentTime && !$noTimeLimit) {
                //echo "hi3";
                $success = false;
            }
            if ($tokenTimeout < $currentTime) {
                $success = false;
                //echo "hi2";
            }
        } else {
            //echo "hi4";
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
    private function hasUserToken($user_id)
    {
        $sql = "SELECT * FROM user WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $user_id);
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