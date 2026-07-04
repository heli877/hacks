<?php
session_start();
include "config.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION["user"];
$initial = strtoupper(substr($username, 0, 1));

$stmt = $conn->prepare("SELECT points, level, streak FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$points = $stats["points"] ?? 0;
$level  = $stats["level"] ?? 1;
$streak = $stats["streak"] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

  <div class="brand">
    <div class="brand-icon">🔒</div>
    MiPlataforma
  </div>

  <div class="card dashboard-card">
    <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
    <h2>Hola, <?php echo htmlspecialchars($username); ?> 👋</h2>
    <p class="subtitle">Has iniciado sesión correctamente</p>

    <div class="stats-bar" style="margin-bottom: 20px;">
      <div class="stat-pill"><div class="num"><?php echo $points; ?></div><div class="label">Puntos</div></div>
      <div class="stat-pill"><div class="num"><?php echo $level; ?></div><div class="label">Nivel</div></div>
      <div class="stat-pill"><div class="num">🔥 <?php echo $streak; ?></div><div class="label">Racha</div></div>
    </div>

    <a href="game.php" class="btn-primary" style="display:inline-block; text-decoration:none; margin-bottom: 10px; width: 100%; box-sizing: border-box; text-align:center;">🎮 Jugar y aprender</a>
    <br>
    <a href="leaderboard.php" style="display:inline-block; color: rgba(255,255,255,0.75); font-size: 13px; margin-top: 6px; text-decoration: none;">🏆 Ver ranking</a>
    <br>
    <a href="logout.php" class="logout-link">Cerrar sesión</a>
  </div>

</body>
</html>