-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50), 
    message TEXT,
    link VARCHAR(255),
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY user_idx (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Badges
CREATE TABLE IF NOT EXISTS badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    description TEXT,
    icon VARCHAR(50),
    criteria_type VARCHAR(50), 
    criteria_value INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Badges
CREATE TABLE IF NOT EXISTS user_badges (
    user_id INT,
    badge_id INT,
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, badge_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Weekly Summaries
CREATE TABLE IF NOT EXISTS weekly_summaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    week_start DATE,
    stats_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_week (user_id, week_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Basic Badges
INSERT IGNORE INTO badges (name, description, icon, criteria_type, criteria_value) VALUES 
('First Step', 'Created your first diary entry', 'fa-pen-nib', 'entry_count', 1),
('Dedicated Writer', 'Created 10 diary entries', 'fa-book-open', 'entry_count', 10),
('Social Butterfly', 'Received your first autograph request', 'fa-envelope-open-text', 'autograph_received', 1),
('Weekly Reflector', 'Viewed your first weekly summary', 'fa-chart-line', 'summary_view', 1);
