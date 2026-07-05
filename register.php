<?php
include "config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $error = "El usuario debe tener 3-20 caracteres (letras, números, guion bajo)";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $hashed);

        if ($stmt->execute()) {
            header("Location: login.php?registered=1");
            exit;
        } else {
            $error = "Ese usuario ya existe";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear cuenta</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

  <div class="brand">
    <div class="brand-icon">🔒</div>
        CodeQuestion

  </div>

  <div class="card">
    <h2>Crea tu cuenta</h2>
    <p class="subtitle">Regístrate para empezar</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="field">
        <label>Usuario</label>
        <input type="text" name="username" placeholder="Elige un usuario" required autofocus>
      </div>

      <div class="field">
        <label>Contraseña</label>
        <input type="password" name="password" id="password" class="pw-input" placeholder="Mínimo 6 caracteres" required>
        <span class="toggle-pw" onclick="togglePassword()" id="toggleIcon">👁️</span>
      </div>

      <button type="submit" class="submit-btn">Crear cuenta</button>
    </form>

    <div class="switch-link">
      ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
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