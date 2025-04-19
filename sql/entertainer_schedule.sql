CREATE TABLE IF NOT EXISTS entertainer_schedule (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    schedule_date DATE,
    weekday INT,
    schedule_type ENUM('bulk', 'custom') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer_account(customer_id) ON DELETE CASCADE,
    CHECK ((schedule_type = 'bulk' AND schedule_date IS NOT NULL AND weekday IS NULL) OR 
           (schedule_type = 'custom' AND weekday IS NOT NULL AND schedule_date IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
