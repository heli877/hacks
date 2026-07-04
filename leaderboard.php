<?php
session_start();
include "config.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$me = $_SESSION["user"];
$result = $conn->query("SELECT username, points, level FROM users ORDER BY points DESC LIMIT 20");
$rank = 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ranking</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

  <div class="brand">
    <div class="brand-icon">🏆</div>
    Ranking
  </div>

  <div class="hub-wrap" style="max-width:480px;">
    <nav class="top-actions" aria-label="Navegación secundaria">
      <a href="game.php">← Volver al juego</a>
      <a href="dashboard.php">Panel</a>
    </nav>

    <?php while ($u = $result->fetch_assoc()): $rank++; ?>
      <div class="lb-row <?php echo ($u['username'] === $me) ? 'me' : ''; ?>">
        <div class="lb-rank"><?php echo $rank; ?></div>
        <div class="lb-avatar"><?php echo strtoupper(substr($u['username'], 0, 1)); ?></div>
        <div class="lb-name"><?php echo htmlspecialchars($u['username']); ?> <span style="opacity:0.6;font-size:11px;">· Nv.<?php echo $u['level']; ?></span></div>
        <div class="lb-pts"><?php echo $u['points']; ?> pts</div>
      </div>
    <?php endwhile; ?>
  </div>

</body>
</html>