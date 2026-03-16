<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=biocmms', 'root', '');
    $stmt = $db->query('DESCRIBE assets');
    echo "COLUMNS IN ASSETS TABLE:\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
