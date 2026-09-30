<?php
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    echo "MySQL connected.\n";
    $stmt = $pdo->query('SHOW DATABASES');
    $dbs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Databases: " . implode(", ", $dbs) . "\n";
    if (in_array('edupath_db', $dbs)) {
        echo "edupath_db exists!\n";
    } else {
        echo "edupath_db NOT FOUND.\n";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
