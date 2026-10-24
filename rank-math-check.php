<?php

require_once __DIR__ . '/wp-load.php';

global $wpdb;

$table = $wpdb->prefix . 'rank_math_backup_20260816';

$rows = $wpdb->get_results(
    "SELECT option_id, option_name, option_value
     FROM {$table}
     WHERE option_id IN (1303, 1304)
     ORDER BY option_id"
);

header('Content-Type: text/plain; charset=UTF-8');

echo "Rank Math serialization check\n";
echo "=============================\n\n";

if (!$rows) {
    echo "ERROR: backup table or records not found.\n";
    exit;
}

foreach ($rows as $row) {
    echo "option_id: {$row->option_id}\n";
    echo "option_name: {$row->option_name}\n";
    echo "length: " . strlen($row->option_value) . "\n";

    $value = @unserialize($row->option_value);

    if ($value === false && $row->option_value !== 'b:0;') {
        echo "RESULT: ERROR - unserialize() failed\n";
    } elseif (is_array($value)) {
        echo "RESULT: OK - unserialize() returned array\n";
        echo "array elements: " . count($value) . "\n";
    } else {
        echo "RESULT: OK - unserialize() returned " . gettype($value) . "\n";
    }

    echo "\n-----------------------------\n\n";
}