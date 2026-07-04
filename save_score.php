<?php
session_start();
include "config.php";
header('Content-Type: application/json');

if (!isset($_SESSION["user"])) {
    echo json_encode(["ok" => false, "error" => "No autenticado"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$earned = isset($data["points"]) ? (int) $data["points"] : 0;
$earned = max(0, min($earned, 1000));

$username = $_SESSION["user"];
$today = date("Y-m-d");
$yesterday = date("Y-m-d", strtotime("-1 day"));

$stmt = $conn->prepare("SELECT id, points, streak, last_played, games_played FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$userId = $row["id"];
$newPoints = ($row["points"] ?? 0) + $earned;
$newLevel = intdiv($newPoints, 100) + 1;
$newGamesPlayed = ($row["games_played"] ?? 0) + 1;

$lastPlayed = $row["last_played"];
$streak = $row["streak"] ?? 0;

if ($lastPlayed === $today) {
    // ya jugó hoy
} elseif ($lastPlayed === $yesterday) {
    $streak++;
} else {
    $streak = 1;
}

$update = $conn->prepare("UPDATE users SET points = ?, level = ?, streak = ?, last_played = ?, games_played = ? WHERE id = ?");
$update->bind_param("iiisii", $newPoints, $newLevel, $streak, $today, $newGamesPlayed, $userId);
$update->execute();

/* ===== Verificar logros nuevos ===== */
$newBadges = [];
$stats = [
    "games" => $newGamesPlayed,
    "streak" => $streak,
    "points" => $newPoints,
    "level" => $newLevel
];

$allAchievements = $conn->query("SELECT * FROM achievements");
while ($ach = $allAchievements->fetch_assoc()) {
    if ($stats[$ach["type"]] >= $ach["threshold"]) {
        $check = $conn->prepare("SELECT id FROM user_achievements WHERE user_id = ? AND achievement_code = ?");
        $check->bind_param("is", $userId, $ach["code"]);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            $ins = $conn->prepare("INSERT INTO user_achievements (user_id, achievement_code) VALUES (?, ?)");
            $ins->bind_param("is", $userId, $ach["code"]);
            $ins->execute();
            $newBadges[] = ["name" => $ach["name"], "icon" => $ach["icon"]];
        }
    }
}

echo json_encode([
    "ok" => true,
    "points" => $newPoints,
    "level" => $newLevel,
    "streak" => $streak,
    "pointsInLevel" => $newPoints % 100,
    "newBadges" => $newBadges
]);