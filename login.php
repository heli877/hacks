<?php
session_start();
include "config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);
        $_SESSION["user"] = $user["username"];

        if (empty($user["onboarded"])) {
            header("Location: onboarding.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

  <div class="brand">
    <div class="brand-icon">🔒</div>
    CodeQuestion
  </div>

  <div class="card">
    <h2>Bienvenido de nuevo</h2>
    <p class="subtitle">Ingresa tus datos para continuar</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET["registered"])): ?>
      <div class="alert alert-success">Cuenta creada. Ya puedes iniciar sesión.</div>
    <?php endif; ?>

    <form method="POST">
      <div class="field">
        <label>Usuario</label>
        <input type="text" name="username" placeholder="Tu usuario" required autofocus>
      </div>

      <div class="field">
        <label>Contraseña</label>
        <input type="password" name="password" id="password" class="pw-input" placeholder="Tu contraseña" required>
        <span class="toggle-pw" onclick="togglePassword()" id="toggleIcon">👁️</span>
      </div>

      <button type="submit" class="submit-btn">Iniciar sesión</button>
    </form>

    <div class="switch-link">
      ¿No tienes cuenta? <a href="register.php">Regístrate</a>
    </div>
  </div>

  <script>
    function togglePassword() {
      const input = document.getElementById('password');
      const icon = document.getElementById('toggleIcon');
      if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '🙈';
      } else {
        input.type = 'password';
        icon.textContent = '👁️';
      }
    }
  </script>

</body>
</html>