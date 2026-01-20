<?php
// includes/BadgeManager.php

class BadgeManager {
    private $conn;
    private $nm;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        // Instantiate NotificationManager to alert user
        require_once dirname(__DIR__) . '/includes/NotificationManager.php';
        $this->nm = new NotificationManager($dbConnection);
    }

    /**
     * Check and Award Badges based on action
     * 
     * @param int $userId
     * @param string $actionType (e.g. 'entry_count', 'autograph_received')
     * @param int $currentValue The current count of the action (optional calculation inside)
     */
    public function checkAndAward($userId, $actionType) {
        // Calculate current value if not provided?
        // For simplicity, we'll recalculate specifically supported types
        $value = 0;

        if ($actionType == 'entry_count') {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM diary_entries WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $value = $stmt->get_result()->fetch_assoc()['total'];
        } elseif ($actionType == 'autograph_received') {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM autographs WHERE owner_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $value = $stmt->get_result()->fetch_assoc()['total'];
        } elseif ($actionType == 'summary_view') {
            // Logic handled by caller incrementing
            // For now, let's assume value is passed or just check existence
            // Simplification: We only support count-based for now.
             $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM weekly_summaries WHERE user_id = ?");
             $stmt->bind_param("i", $userId);
             $stmt->execute();
             $value = $stmt->get_result()->fetch_assoc()['total'];
             // Actually summary_view implies checking if they viewed ONE. 
             // If we want "First Summary", value >= 1.
        }

        // Fetch potential badges for this criteria
        $stmt = $this->conn->prepare("SELECT * FROM badges WHERE criteria_type = ? AND criteria_value <= ?");
        $stmt->bind_param("si", $actionType, $value);
        $stmt->execute();
        $potential_badges = $stmt->get_result();

        while ($badge = $potential_badges->fetch_assoc()) {
            // Check if already awarded
            $check = $this->conn->prepare("SELECT 1 FROM user_badges WHERE user_id = ? AND badge_id = ?");
            $check->bind_param("ii", $userId, $badge['id']);
            $check->execute();
            if ($check->get_result()->num_rows == 0) {
                // Award Badge
                $ins = $this->conn->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)");
                $ins->bind_param("ii", $userId, $badge['id']);
                $ins->execute();

                // Notify User
                $this->nm->create(
                    $userId, 
                    'badge', 
                    "Badge Unlocked: " . $badge['name'] . "!", 
                    "dashboard.php" // Or badges page
                );
            }
        }
    }

    /**
     * Get Earned Badges
     */
    public function getEarnedBadges($userId) {
        $sql = "SELECT b.*, ub.awarded_at FROM badges b JOIN user_badges ub ON b.id = ub.badge_id WHERE ub.user_id = ? ORDER BY ub.awarded_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result();
    }
}
?>
