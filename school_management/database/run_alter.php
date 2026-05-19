<?php
require_once dirname(__DIR__) . '/config/database.php';
$query = "ALTER TABLE attendance ADD COLUMN application_document VARCHAR(255) DEFAULT NULL";
if (mysqli_query($conn, $query)) {
    echo "Column added successfully.";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
