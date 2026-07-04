<?php
session_start();
include "config.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION["user"];
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$userId = $stmt->get_result()->fetch_assoc()["id"];

$earnedStmt = $conn->prepare("SELECT achievement_code, earned_at FROM user_achievements WHERE user_id = ?");
$earnedStmt->bind_param("i", $userId);
$earnedStmt->execute();
$earnedResult = $earnedStmt->get_result();

$earned = [];
while ($row = $earnedResult->fetch_assoc()) {
    $earned[$row["achievement_code"]] = $row["earned_at"];
}

$all = $conn->query("SELECT * FROM achievements ORDER BY type, threshold");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis Logros</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

  <div class="brand">
    <div class="brand-icon">🏅</div>
    Mis Logros
  </div>

  <div class="hub-wrap" style="max-width:600px;">
    <nav class="top-actions" aria-label="Navegación secundaria">
      <a href="game.php">← Volver al juego</a>
      <a href="dashboard.php">Panel</a>
    </nav>

    <p style="color:rgba(255,255,255,0.7); font-size:13px; margin-bottom:16px;">
      Has desbloqueado <?php echo count($earned); ?> de <?php echo $all->num_rows; ?> logros
    </p>

    <div class="badges-grid">
      <?php while ($ach = $all->fetch_assoc()):
        $isEarned = isset($earned[$ach["code"]]);
      ?>
        <div class="badge-card <?php echo $isEarned ? '' : 'locked'; ?>">
          <div class="badge-icon"><?php echo $ach["icon"]; ?></div>
          <div class="badge-name"><?php echo htmlspecialchars($ach["name"]); ?></div>
          <div class="badge-desc"><?php echo htmlspecialchars($ach["description"]); ?></div>
          <?php if ($isEarned): ?>
            <div class="badge-date"><?php echo date("d/m/Y", strtotime($earned[$ach["code"]])); ?></div>
          <?php endif; ?>
        </div>
      <?php endwhile; ?>
    </div>
  </div>

</body>
</html>