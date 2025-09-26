<?php
require_once 'inc/config.php';

$free = disk_free_space($uploadDir); 
$total = disk_total_space($uploadDir);

header("Content-Type: application/json");
echo json_encode([
    "free"  => $free ?: 0,
    "total" => $total ?: 0
]);