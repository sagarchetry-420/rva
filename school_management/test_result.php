<?php
require 'app/Core/App.php';
App::boot();

$pdo = new PDO("mysql:host=localhost;dbname=RVA;charset=utf8mb4", "root", "");

$stmt = $pdo->prepare("SELECT * FROM student_academics WHERE student_id = 22");
$stmt->execute();
$sa = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($sa);

$stmt = $pdo->prepare("SELECT * FROM results WHERE student_id = 22");
$stmt->execute();
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
