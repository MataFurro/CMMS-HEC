<?php
require_once __DIR__ . '/Backend/Providers/BulkProvider.php';

try {
    $db = \Backend\Core\DatabaseService::getInstance();
    $res = $db->query('SELECT id, name, status FROM assets WHERE en_uso = 0 OR status IN (\'PENDING_RETIREMENT\', \'RETIRED\') LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
    echo "Found assets:\n";
    print_r($res);

    if (count($res) > 0) {
        $id = $res[0]['id'];
        echo "Testing restore on ID $id\n";
        $restoreRes = bulkRestoreAssets([$id]);
        print_r($restoreRes);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
