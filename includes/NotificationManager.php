<?php
// includes/NotificationManager.php

class NotificationManager {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    /**
     * Create a new notification
     *
     * @param int $userId Target user ID
     * @param string $type Type of notification (e.g., 'system', 'autograph', 'badge', 'reminder')
     * @param string $message The notification text
     * @param string|null $link Optional URL to redirect to
     * @return bool Success status
     */
    public function create($userId, $type, $message, $link = null) {
        $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $type, $message, $link);
        return $stmt->execute();
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnread($userId, $limit = 5) {
        $stmt = $this->conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Get unread count
     */
    public function getUnreadCount($userId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['total'];
    }

    /**
     * Get all notifications (paginated)
     */
    public function getAll($userId, $limit = 20, $offset = 0) {
        $stmt = $this->conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("iii", $userId, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id, $userId) {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $userId);
        return $stmt->execute();
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead($userId) {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }
}
?>
