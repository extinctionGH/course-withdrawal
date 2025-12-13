<?php
class Notification {
    private $conn;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    public function create($userID, $title, $message, $type = 'info') {
        $stmt = $this->conn->prepare("
            INSERT INTO notification (UserID, Title, Message, Type) 
            VALUES (:userID, :title, :message, :type)
        ");
        return $stmt->execute([
            ':userID' => $userID,
            ':title' => $title,
            ':message' => $message,
            ':type' => $type
        ]);
    }
    
    public function getUnreadCount($userID) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM notification 
            WHERE UserID = :userID AND IsRead = FALSE
        ");
        $stmt->execute([':userID' => $userID]);
        return $stmt->fetch()['count'];
    }
    
    public function getAll($userID, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT * FROM notification 
            WHERE UserID = :userID 
            ORDER BY CreatedAt DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':userID', $userID, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function markAsRead($notificationID, $userID) {
        $stmt = $this->conn->prepare("
            UPDATE notification 
            SET IsRead = TRUE 
            WHERE NotificationID = :id AND UserID = :userID
        ");
        return $stmt->execute([
            ':id' => $notificationID,
            ':userID' => $userID
        ]);
    }
    
    public function markAllAsRead($userID) {
        $stmt = $this->conn->prepare("
            UPDATE notification 
            SET IsRead = TRUE 
            WHERE UserID = :userID
        ");
        return $stmt->execute([':userID' => $userID]);
    }
    
    public function delete($notificationID, $userID) {
        $stmt = $this->conn->prepare("
            DELETE FROM notification 
            WHERE NotificationID = :id AND UserID = :userID
        ");
        return $stmt->execute([
            ':id' => $notificationID,
            ':userID' => $userID
        ]);
    }
}
?>