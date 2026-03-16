<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=biocmms', 'root', '');
    $db->exec("ALTER TABLE assets ADD COLUMN next_maintenance_date DATE AFTER frecuencia_mp_meses");
    echo "COLUMN next_maintenance_date ADDED SUCCESSFULLY.\n";
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
