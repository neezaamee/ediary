-- Alter diary_entries for new features
ALTER TABLE diary_entries 
MODIFY COLUMN memory_type VARCHAR(50) DEFAULT 'General',
ADD COLUMN unlock_date DATE NULL,
ADD COLUMN chapter_id INT NULL,
ADD COLUMN collection_id INT NULL,
ADD COLUMN energy_level INT DEFAULT 5;

-- Chapters Table
CREATE TABLE IF NOT EXISTS chapters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    start_date DATE,
    end_date DATE,
    KEY user_idx (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Collections Table
CREATE TABLE IF NOT EXISTS collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    KEY user_idx (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
