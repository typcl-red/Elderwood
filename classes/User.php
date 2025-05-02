<?php
class User {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function login($username, $password) {
        try {
            $query = "SELECT id, password, role FROM users WHERE username = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    return [
                        'success' => true,
                        'user_id' => $user['id'],
                        'role' => $user['role']
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => "Password verification failed"
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => "User not found"
                ];
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => "Exception: " . $e->getMessage()
            ];
        }
    }
    
    public function getUserById($userId) {
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function create($lastname, $firstname, $email, $username, $password, $contactno, $address, $role, $employer_id = null) {
        try {
            $this->conn->beginTransaction();
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (lastname, firstname, email, username, password, contactno, address, role, employer_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $success = $stmt->execute([$lastname, $firstname, $email, $username, $hashedPassword, $contactno, $address, $role, $employer_id]);
            
            if ($success && $role === 'Seller') {
                // Generate and insert seller_unique_id for new sellers
                $userId = $this->conn->lastInsertId();
                $sellerUniqueId = 'S' . str_pad($userId, 5, '0', STR_PAD_LEFT);
                
                $sql = "INSERT INTO seller_ids (user_id, seller_unique_id) VALUES (?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $sellerUniqueId]);
            }
            
            $this->conn->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    public function emailExists($email) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }

    public function usernameExists($username) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn() > 0;
    }
}