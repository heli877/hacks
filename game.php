<?php
session_start();
include "config.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION["user"];
$stmt = $conn->prepare("SELECT points, level, streak FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$points = $stats["points"] ?? 0;
$level  = $stats["level"] ?? 1;
$streak = $stats["streak"] ?? 0;

$pointsInLevel = $points % 100;
$progressPct = $pointsInLevel;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aprende Programación - Jugar</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

  <div class="brand">
    <div class="brand-icon">🔒</div>
    MiPlataforma
  </div>

  <div class="hub-wrap">

    <nav class="top-nav" aria-label="Navegación principal">
      <span class="nav-greeting">Hola, <?php echo htmlspecialchars($username); ?> 👋</span>
      <button class="hamburger" id="navToggle" type="button" aria-expanded="false" aria-controls="navLinks" aria-label="Abrir menú de navegación">
        <span></span><span></span><span></span>
      </button>
      <ul class="nav-links" id="navLinks">
        <li><a href="leaderboard.php">🏆 Ranking</a></li>
        <li><a href="achievements.php">🏅 Logros</a></li>
        <li><a href="dashboard.php">Panel</a></li>
        <li><a href="logout.php">Salir</a></li>
      </ul>
    </nav>

    <div class="stats-bar">
      <div class="stat-pill"><div class="num" id="statPoints"><?php echo $points; ?></div><div class="label">Puntos</div></div>
      <div class="stat-pill"><div class="num" id="statLevel"><?php echo $level; ?></div><div class="label">Nivel</div></div>
      <div class="stat-pill"><div class="num" id="statStreak">🔥 <?php echo $streak; ?></div><div class="label">Racha</div></div>
    </div>

    <div class="level-progress">
      <div class="top-row">
        <span>Nivel <?php echo $level; ?></span>
        <span id="progressLabel"><?php echo $pointsInLevel; ?>/100 pts</span>
      </div>
      <div class="progress-track">
        <div class="progress-fill" id="progressFill" style="width: <?php echo $progressPct; ?>%;"></div>
      </div>
    </div>

    <!-- MENU -->
    <div class="mode-grid" id="menuView">
      <div class="mode-card" onclick="openGame('quiz')">
        <div class="icon">🧠</div>
        <h3>Quiz</h3>
        <p>Opción múltiple</p>
      </div>
      <div class="mode-card" onclick="openGame('flash')">
        <div class="icon">🗂️</div>
        <h3>Flashcards</h3>
        <p>Memoriza conceptos</p>
      </div>
      <div class="mode-card" onclick="openGame('match')">
        <div class="icon">🔗</div>
        <h3>Emparejar</h3>
        <p>Arrastra y conecta</p>
      </div>
      <div class="mode-card" onclick="openGame('timed')">
        <div class="icon">⚡</div>
        <h3>Contrarreloj</h3>
        <p>Responde rápido</p>
      </div>
    </div>

    <!-- QUIZ -->
    <div class="game-panel" id="panel-quiz">
      <button class="back-btn" onclick="backToMenu()">← Volver</button>
      <div id="quizArea"></div>
    </div>

    <!-- FLASHCARDS -->
    <div class="game-panel" id="panel-flash">
      <button class="back-btn" onclick="backToMenu()">← Volver</button>
      <div id="flashArea"></div>
    </div>

    <!-- MATCHING -->
    <div class="game-panel" id="panel-match">
      <button class="back-btn" onclick="backToMenu()">← Volver</button>
      <div id="matchArea"></div>
    </div>

    <!-- TIMED -->
    <div class="game-panel" id="panel-timed">
      <button class="back-btn" onclick="backToMenu()">← Volver</button>
      <div id="timedArea"></div>
    </div>

  </div>

  <div class="toast-wrap" id="toastWrap"></div>

<script>
/* ===================== NAV MÓVIL ===================== */
const navToggle = document.getElementById('navToggle');
const navLinks = document.getElementById('navLinks');

navToggle.addEventListener('click', () => {
  const isOpen = navLinks.classList.toggle('open');
  navToggle.classList.toggle('open', isOpen);
  navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
});

document.addEventListener('click', (e) => {
  if (!navLinks.classList.contains('open')) return;
  if (!navLinks.contains(e.target) && !navToggle.contains(e.target)) {
    navLinks.classList.remove('open');
    navToggle.classList.remove('open');
    navToggle.setAttribute('aria-expanded', 'false');
  }
});

/* ===================== DATA ===================== */
const quizQuestions = [
  { q: "¿Qué palabra clave se usa en PHP para declarar una función?", options: ["function", "def", "func", "method"], correct: 0,
    explain: "En PHP, toda función empieza con la palabra clave 'function', seguida del nombre y paréntesis: function saludar() { ... }" },
  { q: "¿Qué símbolo se usa para comentarios de una línea en JavaScript?", options: ["#", "//", "<!--", "%%"], correct: 1,
    explain: "'//' comenta el resto de la línea en JavaScript. Para comentarios de varias líneas se usa /* ... */." },
  { q: "¿Qué estructura repite un bloque de código mientras una condición sea verdadera?", options: ["if", "switch", "while", "function"], correct: 2,
    explain: "'while' ejecuta un bloque repetidamente mientras su condición evalúe a verdadero. 'if' solo ejecuta una vez." },
  { q: "¿Cuál de estos es un tipo de dato booleano?", options: ["'texto'", "42", "true", "3.14"], correct: 2,
    explain: "Un booleano solo puede tener dos valores: true o false. Los demás son string, entero y decimal." },
  { q: "¿Qué operador se usa para comparar igualdad estricta en JavaScript?", options: ["=", "==", "===", "!="], correct: 2,
    explain: "'===' compara valor Y tipo de dato. '==' solo compara el valor, convirtiendo tipos automáticamente." },
  { q: "¿Qué palabra clave crea un arreglo asociativo en PHP?", options: ["array()", "list()", "set()", "map()"], correct: 0,
    explain: "array() (o la sintaxis [ ]) crea arreglos en PHP, que pueden ser indexados o asociativos con claves de texto." },
  { q: "¿Qué significa 'SQL' en el contexto de bases de datos?", options: ["Structured Query Language", "Simple Query List", "System Query Logic", "Sequential Query Line"], correct: 0,
    explain: "SQL (Structured Query Language) es el lenguaje estándar para consultar y manipular bases de datos relacionales." },
  { q: "¿Qué método HTTP se usa normalmente para enviar datos de un formulario?", options: ["GET", "POST", "DELETE", "HEAD"], correct: 1,
    explain: "POST envía datos en el cuerpo de la petición, ideal para formularios. GET los envía visibles en la URL." },
];

const flashcards = [
  { front: "Variable", back: "Espacio de memoria que almacena un valor que puede cambiar." },
  { front: "Función", back: "Bloque de código reutilizable que realiza una tarea específica." },
  { front: "Bucle (loop)", back: "Estructura que repite un bloque de código varias veces." },
  { front: "Condicional", back: "Estructura que ejecuta código según si una condición es verdadera o falsa." },
  { front: "Array", back: "Colección ordenada de valores almacenados en una sola variable." },
  { front: "API", back: "Conjunto de reglas que permite que dos programas se comuniquen entre sí." },
  { front: "Base de datos", back: "Sistema organizado para almacenar y consultar información." },
  { front: "Debugging", back: "Proceso de encontrar y corregir errores en el código." },
];

const matchPairs = [
  { term: "HTML", def: "Estructura de una página web" },
  { term: "CSS", def: "Estilos y diseño visual" },
  { term: "PHP", def: "Lógica del lado del servidor" },
  { term: "MySQL", def: "Almacena datos en tablas" },
  { term: "JavaScript", def: "Interactividad en el navegador" },
  { term: "Git", def: "Control de versiones del código" },
];

const timedQuestions = [
  { q: "¿'echo' se usa en...?", options: ["PHP", "CSS", "SQL"], correct: 0 },
  { q: "¿Qué etiqueta define un párrafo en HTML?", options: ["<div>", "<p>", "<span>"], correct: 1 },
  { q: "¿Qué símbolo inicia una variable en PHP?", options: ["$", "@", "#"], correct: 0 },
  { q: "¿'SELECT' pertenece a...?", options: ["JavaScript", "SQL", "CSS"], correct: 1 },
  { q: "¿Qué es 'const' en JS?", options: ["Variable constante", "Función", "Bucle"], correct: 0 },
  { q: "¿Qué extensión tiene un archivo PHP?", options: [".js", ".php", ".html"], correct: 1 },
  { q: "¿Qué hace 'ALTER TABLE'?", options: ["Borra la BD", "Modifica una tabla", "Crea un usuario"], correct: 1 },
  { q: "¿Qué es un 'array'?", options: ["Una lista de valores", "Un solo número", "Un color"], correct: 0 },
];

/* ===================== NAVIGATION ===================== */
function openGame(mode) {
  document.getElementById('menuView').style.display = 'none';
  document.querySelectorAll('.game-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('panel-' + mode).classList.add('active');
  if (mode === 'quiz') startQuiz();
  if (mode === 'flash') startFlash();
  if (mode === 'match') startMatch();
  if (mode === 'timed') startTimed();
}

function backToMenu() {
  document.querySelectorAll('.game-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('menuView').style.display = 'grid';
  clearInterval(window.timedInterval);
}

async function saveScore(points) {
  try {
    const res = await fetch('save_score.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ points })
    });

    if (!res.ok) {
      showErrorToast('El servidor no respondió correctamente. Tus puntos no se guardaron.');
      return false;
    }

    const data = await res.json();

    if (data.ok) {
      document.getElementById('statPoints').textContent = data.points;
      document.getElementById('statLevel').textContent = data.level;
      document.getElementById('statStreak').textContent = '🔥 ' + data.streak;
      document.getElementById('progressLabel').textContent = data.pointsInLevel + '/100 pts';
      document.getElementById('progressFill').style.width = data.pointsInLevel + '%';

      if (data.newBadges && data.newBadges.length > 0) {
        data.newBadges.forEach((badge, idx) => {
          setTimeout(() => showToast(badge), idx * 300);
        });
      }
      return true;
    } else {
      showErrorToast(data.error || 'No se pudo guardar tu puntaje.');
      return false;
    }
  } catch (err) {
    showErrorToast('Sin conexión a internet. Tus puntos no se guardaron, inténtalo de nuevo.');
    return false;
  }
}

function showToast(badge) {
  const wrap = document.getElementById('toastWrap');
  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.innerHTML = `
    <div class="t-icon">${badge.icon}</div>
    <div class="t-text">
      <div class="t-title">¡Nuevo logro desbloqueado!</div>
      <div class="t-name">${badge.name}</div>
    </div>`;
  wrap.appendChild(toast);
  setTimeout(() => toast.remove(), 4000);
}

function showErrorToast(message) {
  const wrap = document.getElementById('toastWrap');
  const toast = document.createElement('div');
  toast.className = 'toast toast-error';
  toast.innerHTML = `
    <div class="t-icon">⚠️</div>
    <div class="t-text">
      <div class="t-title">Ups, algo salió mal</div>
      <div class="t-name">${message}</div>
    </div>`;
  wrap.appendChild(toast);
  setTimeout(() => toast.remove(), 5000);
}

function resultScreen(container, earned, onDone) {
  container.innerHTML = `
    <div class="result-box">
      <div class="big-score">+${earned} pts</div>
      <div class="msg">¡Buen trabajo!</div>
      <button class="btn-primary" id="doneBtn">Guardar y volver</button>
    </div>`;

  document.getElementById('doneBtn').onclick = async (e) => {
    const btn = e.currentTarget;
    if (btn.classList.contains('btn-loading')) return; // evita doble clic mientras guarda
    btn.classList.add('btn-loading');
    btn.disabled = true;

    const success = await saveScore(earned);

    if (success) {
      backToMenu();
      if (onDone) onDone();
    } else {
      btn.classList.remove('btn-loading');
      btn.disabled = false;
    }
  };
}

/* ===================== QUIZ ===================== */
function startQuiz() {
  let current = 0, score = 0;
  const area = document.getElementById('quizArea');

  function render() {
    if (current >= quizQuestions.length) {
      resultScreen(area, score);
      return;
    }
    const item = quizQuestions[current];
    area.innerHTML = `
      <div class="quiz-progress">Pregunta ${current + 1} de ${quizQuestions.length}</div>
      <div class="quiz-question">${item.q}</div>
      <div class="quiz-options">
        ${item.options.map((opt, i) => `<div class="quiz-option" data-i="${i}">${opt}</div>`).join('')}
      </div>
      <div class="explanation" id="expBox"></div>
    `;

    area.querySelectorAll('.quiz-option').forEach(el => {
      el.onclick = () => {
        const i = parseInt(el.dataset.i);
        const isCorrect = i === item.correct;
        area.querySelectorAll('.quiz-option').forEach(o => o.classList.add('disabled'));

        if (isCorrect) {
          el.classList.add('correct');
          score += 10;
        } else {
          el.classList.add('incorrect');
          area.querySelector(`[data-i="${item.correct}"]`).classList.add('correct');
        }

        const expBox = document.getElementById('expBox');
        expBox.className = 'explanation show ' + (isCorrect ? 'exp-correct' : 'exp-incorrect');
        expBox.innerHTML = `
          <strong>${isCorrect ? '✅ ¡Correcto!' : '❌ Incorrecto'}</strong>
          ${item.explain}
        `;

        const nextBtn = document.createElement('button');
        nextBtn.className = 'next-btn';
        nextBtn.textContent = current + 1 < quizQuestions.length ? 'Siguiente →' : 'Ver resultado →';
        nextBtn.onclick = () => { current++; render(); };
        area.appendChild(nextBtn);
      };
    });
  }
  render();
}

/* ===================== FLASHCARDS ===================== */
function startFlash() {
  let current = 0, known = 0;
  const area = document.getElementById('flashArea');

  function render() {
    if (current >= flashcards.length) {
      resultScreen(area, known * 10);
      return;
    }
    const card = flashcards[current];
    area.innerHTML = `
      <div class="flash-progress">Tarjeta ${current + 1} de ${flashcards.length}</div>
      <div class="flashcard-scene">
        <div class="flashcard" id="fc">
          <div class="flash-face flash-front">${card.front}</div>
          <div class="flash-face flash-back">${card.back}</div>
        </div>
      </div>
      <div class="flash-actions">
        <button class="btn-review" id="btnReview">Repasar</button>
        <button class="btn-know" id="btnKnow">Ya lo sé</button>
      </div>`;

    document.getElementById('fc').onclick = () => document.getElementById('fc').classList.toggle('flipped');
    document.getElementById('btnKnow').onclick = () => { known++; current++; render(); };
    document.getElementById('btnReview').onclick = () => { current++; render(); };
  }
  render();
}

/* ===================== MATCHING ===================== */
function startMatch() {
  const area = document.getElementById('matchArea');
  let matched = 0;
  const shuffledDefs = [...matchPairs].sort(() => Math.random() - 0.5);

  area.innerHTML = `
    <div class="match-progress">Arrastra cada término a su definición correcta</div>
    <div class="match-container">
      <div id="terms">
        ${matchPairs.map(p => `<div class="match-item" draggable="true" data-term="${p.term}">${p.term}</div>`).join('')}
      </div>
      <div id="defs">
        ${shuffledDefs.map(p => `<div class="match-dropzone" data-answer="${p.term}">${p.def}</div>`).join('')}
      </div>
    </div>`;

  area.querySelectorAll('.match-item').forEach(item => {
    item.addEventListener('dragstart', e => {
      e.dataTransfer.setData('text/plain', item.dataset.term);
    });
  });

  area.querySelectorAll('.match-dropzone').forEach(zone => {
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('over'));
    zone.addEventListener('drop', e => {
      e.preventDefault();
      zone.classList.remove('over');
      if (zone.classList.contains('filled')) return;
      const term = e.dataTransfer.getData('text/plain');
      if (term === zone.dataset.answer) {
        zone.classList.add('filled');
        zone.textContent = '✅ ' + term + ' → ' + zone.textContent;
        const draggedEl = area.querySelector(`.match-item[data-term="${term}"]`);
        draggedEl.classList.add('matched');
        draggedEl.setAttribute('draggable', 'false');
        matched++;
        if (matched === matchPairs.length) {
          setTimeout(() => resultScreen(area, matched * 15), 700);
        }
      }
    });
  });
}

/* ===================== TIMED ===================== */
function startTimed() {
  let current = 0, score = 0, timeLeft = 100;
  const area = document.getElementById('timedArea');

  function render() {
    clearInterval(window.timedInterval);
    if (current >= timedQuestions.length) {
      resultScreen(area, score);
      return;
    }
    const item = timedQuestions[current];
    timeLeft = 100;
    area.innerHTML = `
      <div class="timed-score">Puntos: ${score} · Pregunta ${current + 1}/${timedQuestions.length}</div>
      <div class="timer-track"><div class="timer-fill" id="timerFill" style="width:100%"></div></div>
      <div class="quiz-question">${item.q}</div>
      <div class="quiz-options">
        ${item.options.map((opt, i) => `<div class="quiz-option" data-i="${i}">${opt}</div>`).join('')}
      </div>`;

    window.timedInterval = setInterval(() => {
      timeLeft -= 2;
      document.getElementById('timerFill').style.width = timeLeft + '%';
      if (timeLeft <= 0) {
        clearInterval(window.timedInterval);
        current++;
        render();
      }
    }, 100);

    area.querySelectorAll('.quiz-option').forEach(el => {
      el.onclick = () => {
        clearInterval(window.timedInterval);
        const i = parseInt(el.dataset.i);
        if (i === item.correct) { el.classList.add('correct'); score += 15; }
        else { el.classList.add('incorrect'); area.querySelector(`[data-i="${item.correct}"]`).classList.add('correct'); }
        area.querySelectorAll('.quiz-option').forEach(o => o.classList.add('disabled'));
        setTimeout(() => { current++; render(); }, 600);
      };
    });
  }
  render();
}
</script>

</body>
</html>