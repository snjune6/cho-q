<?php
require __DIR__ . '/../includes/bootstrap.php';

$pdo = db();
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (['reports', 'status_audit_log', 'guest_messages', 'driver_status', 'cars', 'users'] as $table) {
    $pdo->exec('TRUNCATE TABLE ' . $table);
    echo "TRUNCATE {$table}\n";
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
echo "Done.\n";
