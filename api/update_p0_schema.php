<?php
require_once "config.php";

$statements = [
    "ALTER TABLE questions ADD COLUMN source_name VARCHAR(100) DEFAULT NULL;",
    "ALTER TABLE questions ADD COLUMN source_year INT DEFAULT NULL;",
    "ALTER TABLE questions ADD COLUMN source_reference VARCHAR(100) DEFAULT NULL;",
    "ALTER TABLE questions ADD COLUMN domain VARCHAR(100) DEFAULT NULL;",
    "ALTER TABLE questions ADD COLUMN sub_materi VARCHAR(100) DEFAULT NULL;",
    "ALTER TABLE questions ADD COLUMN version VARCHAR(20) DEFAULT 'v1';",

    "CREATE TABLE IF NOT EXISTS diagnostic_results (
        id VARCHAR(36) PRIMARY KEY,
        student_id VARCHAR(36) NOT NULL,
        total_score FLOAT DEFAULT 0,
        gap_domain VARCHAR(100) DEFAULT NULL,
        gap_sub_materi VARCHAR(100) DEFAULT NULL,
        gap_score FLOAT DEFAULT 0,
        details JSON DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS learning_paths (
        id VARCHAR(36) PRIMARY KEY,
        student_id VARCHAR(36) NOT NULL,
        diagnostic_result_id VARCHAR(36) DEFAULT NULL,
        gap_focus VARCHAR(100) NOT NULL,
        recommended_materials JSON DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (diagnostic_result_id) REFERENCES diagnostic_results(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($statements as $sql) {
    try {
        $pdo->exec($sql);
        echo "Executed: " . substr($sql, 0, 50) . "...\n";
    } catch (PDOException $e) {
        echo "Error or already exists: " . $e->getMessage() . "\n";
    }
}
echo "Schema updated successfully.\n";

