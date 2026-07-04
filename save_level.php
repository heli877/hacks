<?php
session_start();
include "config.php";
header('Content-Type: application/json');

if (!isset($_SESSION["user"])) {
    echo json_encode(["ok" => false]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$level = $data["level"] ?? "";

$allowed = ["basico", "intermedio", "avanzado"];
if (!in_array($level, $allowed)) {
    echo json_encode(["ok" => false, "error" => "Nivel inválido"]);
    exit;
}

$username = $_SESSION["user"];
$stmt = $conn->prepare("UPDATE users SET skill_level = ?, onboarded = 1 WHERE username = ?");
$stmt->bind_param("ss", $level, $username);
$stmt->execute();

echo json_encode(["ok" => true]);