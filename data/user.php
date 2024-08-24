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

        $stmt = $this->conn->prepare("SELECT isManager,user_name, user_password,user_id , access_timeout,noTimeLimit FROM user WHERE user_name = ? AND database_status = 'Available'");
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

                $currentTime = time();
                if (!$row['isManager'] && $row['access_timeout'] < $currentTime && !$row['noTimeLimit']) {

                    return [
                        "code" => 403,
                        "result" => ["message" => "Username or password is invalid"]

                    ];
                }

                $this->createToken();


                $log = new Log($this->conn, "user with id: {$id} Logged in", $id, 'LOW');
                $log->create();
                $houses = $this->getHousesByUserId($id);

                return [
                    "code" => 200,
                    "result" => [
                        "message" => "Success",
                        "user" => [
                            "token" => $this->token,
                            "username" => $this->username,
                            "isManager" => $this->isManager,
                            //"id" => $id,
                            "house" => [
                                "house_id" => $houses[0]['house_id'],
                                "iv" => $houses[0]['iv']
                            ]
                        ],

                    ]

                ];
            }
        }
        $stmt->close();
        $this->conn->close();
        return [
            "code" => 404,
            "result" => ["message" => "Username or password is invalid"]

        ];
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
            $delete_user_stmt = $this->conn->prepare("UPDATE user SET database_status=?,user_token = NULL WHERE user_id = ?");
            $status = "Unavailable";
            $delete_user_stmt->bind_param("si", $status, $user_id);
            $delete_user_stmt->execute();

            $log = new Log($this->conn, "user with id: {$this->user_id} removed", $admin_id, 'HIGH');
            $log->create();

            return array("success" => true, "message" => "User removed from the house successfully", "code" => 200);
        } else {
            return array("success" => false, "message" => "Failed to remove user from the house", "code" => 500);
        }
    }

    public function getHouses()
    {
        $stmt = $this->conn->prepare("SELECT user_id , isManager FROM user WHERE user_token = ?");
        $stmt->bind_param("i", $this->token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['isManager'] != 1) {
                return [
                    "code" => 401,
                    "result" => [
                        "message" => "User is not an admin",
                    ]
                ];
            }
        } else {
            return [
                "code" => 404,
                "result" => [
                    "message" => "User not found",
                ]
            ];
        }

        $admin_id = $row['user_id'];
        $house = $this->getHousesByUserId($admin_id);


        return [
            "code" => 200,
            "result" => [
                "message" => "Success",
                "Houses" => $house,
            ]
        ];
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
            $iv = $this->generateIV();
            $house_id = $row['house_id'];
            $userCount = $this->getUserCountInTheHouse($house_id);
            $row['house_id'] = $this->encryptAES($house_id, $iv);
            $row['iv'] = $iv;
            $row['userCount'] = $userCount;
            $houses[] = $row;
        }

        $stmt->close();
        return $houses;
    }
    private function getUserCountInTheHouse($house_id)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS count FROM join_user_house WHERE house_id = ? ");
        $stmt->bind_param("s", $house_id);
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
    private function generateIV()
    {
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivLength);
        return base64_encode($iv);
    }
    private function encryptAES($data, $iv)
    {
        $key = "feUGSmdz4ih/vxOxOZg506eOnfOgSUP1AHmrCqT8ayg=";
        $decodedKey = base64_decode($key);
        $decodedIV = base64_decode($iv);
        $encryptedData = openssl_encrypt($data, 'aes-256-cbc', $decodedKey, OPENSSL_RAW_DATA, $decodedIV);
        return base64_encode($encryptedData);
    }
    private function decryptAES($data, $iv)
    {
        $key = "feUGSmdz4ih/vxOxOZg506eOnfOgSUP1AHmrCqT8ayg=";
        $decodedKey = base64_decode($key);
        $decodedIV = base64_decode($iv);
        $decodedEncryptedData = base64_decode($data);
        return openssl_decrypt($decodedEncryptedData, 'aes-256-cbc', $decodedKey, OPENSSL_RAW_DATA, $decodedIV);
    }



    public function checkToken($token)
    {
        $stmt = $this->conn->prepare("SELECT user_id FROM user WHERE user_token = ? AND database_status='Available' ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if (!$row) {  // Check if $row is null (no rows found)
            return false;
        }
        $admin_id = $row['user_id'];
        return $admin_id;
    }

    public function updateuser($iv, $admin_token)
    {
        $stmt = $this->conn->prepare("SELECT user_id FROM user WHERE user_token = ? AND database_status='Available' ");
        $stmt->bind_param("s", $admin_token);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if (!$row) {  // Check if $row is null (no rows found)
            return array("success" => false, "message" => "You don't have access to do it");
        }
        $admin_id = $row['user_id'];


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

            $log = new Log($this->conn, "Access of user with id: {$this->user_id} changed", $admin_id, 'HIGH');
            $log->create();

        } else {
            $this->password = $this->decryptAES($this->password, $iv);

            $hashedPassword = password_hash($this->password, PASSWORD_BCRYPT, ['cost' => 11]);

            $stmt = $this->conn->prepare("UPDATE user SET access_timeout = ? , noTimeLimit = ? , user_password= ? ,user_token= NULL , token_timeout= NULL WHERE user_id = ? ");
            $stmt->bind_param("sisi", $accessTimeout, $noTimeLimit, $hashedPassword, $this->user_id);

            $log = new Log($this->conn, "Password and access of user with id: {$this->user_id} changed", $admin_id, 'HIGH');
            $log->create();
        }

        if ($stmt->execute()) {
            return array("success" => true, "message" => "User updated successfully");
        } else {
            return array("success" => false, "message" => "Failed to update user");
        }
    }

    public function signup($iv)
    {

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
        } else {
            $accessTimeout = $this->timeout;
        }

        $stmt = $this->conn->prepare("INSERT INTO user (user_name, user_password, isManager, access_timeout, noTimeLimit,database_status) VALUES (?, ?, ?, ?, ?,?)");
        $status = "Available";
        $stmt->bind_param("ssisss", $this->username, $hashedPassword, $this->isManager, $accessTimeout, $noTimeLimit, $status);



        if ($stmt->execute() === TRUE) {
            return $this->conn->insert_id;
        } else {
            return false;
        }
    }



    public function checkAccess()
    {
        $success = true;
        $stmt = $this->conn->prepare("SELECT user_id,noTimeLimit,isManager, access_timeout, token_timeout FROM user WHERE user_token = ?");
        $stmt->bind_param("s", $this->token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $row = $result->fetch_assoc();
            $isManager = $row['isManager'];
            $accessTimeout = strtotime($row['access_timeout']);
            $tokenTimeout = strtotime($row['token_timeout']);
            $noTimeLimit = $row['noTimeLimit'];
            $id = $row['user_id'];
            $currentTime = time();
            if (!$isManager && $accessTimeout < $currentTime && !$noTimeLimit) {

                $success = false;
            }
            if ($tokenTimeout < $currentTime) {
                $success = false;

            }
            $stmt->close();
            $stmt = $this->conn->prepare("UPDATE user SET lastLogin = now() WHERE user_name = ?");
            $stmt->bind_param("s", $this->username);

            //$log = new Log($this->conn, "user with id: {$id} Logged in", $id, 'LOW');
            //$log->create();

            $stmt->execute();


        } else {

            $success = false;
        }



        return $success;
    }
    private function hasUser($username)
    {
        $sql = "SELECT * FROM user WHERE user_name = ? AND database_status = 'Available' ";
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