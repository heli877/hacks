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

$codeUnlocked = $points >= 300;
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
        CodeQuestion

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
      <div class="mode-card <?php echo $codeUnlocked ? '' : 'mode-locked'; ?>" onclick="openGame('code')">
        <div class="icon"><?php echo $codeUnlocked ? '🐱' : '🔒'; ?></div>
        <h3>Programa al Gato</h3>
        <p><?php echo $codeUnlocked ? 'Bloques de comandos' : 'Se desbloquea con 300 pts'; ?></p>
      </div>
      <div class="mode-card <?php echo $codeUnlocked ? '' : 'mode-locked'; ?>" onclick="openGame('codeadv')">
        <div class="icon"><?php echo $codeUnlocked ? '💻' : '🔒'; ?></div>
        <h3>Programa al Gato: Avanzado</h3>
        <p><?php echo $codeUnlocked ? 'Escribe tu propio código' : 'Se desbloquea con 300 pts'; ?></p>
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

    <!-- PROGRAMA AL GATO -->
    <div class="game-panel" id="panel-code">
      <button class="back-btn" onclick="backToMenu()">← Volver</button>
      <div id="codeArea"></div>
    </div>

    <!-- PROGRAMA AL GATO: AVANZADO -->
    <div class="game-panel" id="panel-codeadv">
      <button class="back-btn" onclick="backToMenu()">← Volver</button>
      <div id="codeAdvArea"></div>
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

/* ===================== SEGURIDAD: escapar HTML ===================== */
// Evita que texto con <, >, &, etc. (p.ej. "<!--") rompa el HTML
// al insertarse con innerHTML, o inyecte etiquetas no deseadas.
function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = String(str);
  return div.innerHTML;
}

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

/* ===================== PROGRAMA AL GATO ===================== */
const codeUnlocked = <?php echo $codeUnlocked ? 'true' : 'false'; ?>;

// Celdas: '.' camino, '#' muro, '*' estrella (coleccionable), 'G' meta.
// La posición inicial del gato se define aparte en "start".
const catBoards = [
  {
    name: "Tablero 1 · Línea recta",
    grid: [
      "......",
      ".####.",
      ".#..#.",
      ".#.*#.",
      ".#..G.",
      "......",
    ],
    start: { r: 2, c: 2 },
    basePoints: 20,
  },
  {
    name: "Tablero 2 · Esquinas",
    grid: [
      "S....#",
      ".####.",
      ".#..*.",
      ".#.##.",
      ".*..#.",
      "####G.",
    ],
    start: { r: 0, c: 0 },
    basePoints: 30,
  },
  {
    name: "Tablero 3 · El laberinto",
    grid: [
      "S.#....",
      ".#.###.",
      ".#.*..#",
      ".#.#.#.",
      ".#.#.#.",
      "...#.*.",
      "###.#G.",
    ],
    start: { r: 0, c: 0 },
    basePoints: 40,
  },
];

/* ===================== PROGRAMA AL GATO: AVANZADO (código) ===================== */

// Diccionario del mini-lenguaje: mover("arriba"|"abajo"|"izquierda"|"derecha")
// y repetir(n, "direccion"). Se usa tanto en el tutorial como en el editor real.
const codeDirWords = { arriba: 'up', abajo: 'down', izquierda: 'left', derecha: 'right' };
const codeDirIcons = { up: '⬆️', down: '⬇️', left: '⬅️', right: '➡️' };

const tutorialSteps = [
  {
    title: "1. El comando mover()",
    body: `Para mover al gato una casilla usamos la función <code>mover()</code>, indicando la dirección entre comillas:`,
    code: `mover("arriba")\nmover("derecha")`,
    explain: "Cada línea es una instrucción independiente. El gato las ejecuta en orden, de arriba hacia abajo, tal como leerías el código.",
  },
  {
    title: "2. Las 4 direcciones",
    body: `Puedes usar cualquiera de estas cuatro palabras dentro de <code>mover()</code>:`,
    code: `mover("arriba")\nmover("abajo")\nmover("izquierda")\nmover("derecha")`,
    explain: "Si el gato choca contra un muro o sale del tablero, el programa se detiene y tendrás que corregirlo.",
  },
  {
    title: "3. Repetir código con repetir()",
    body: `Cuando necesitas el mismo movimiento varias veces seguidas, en vez de repetir la línea puedes usar un bucle con <code>repetir()</code>:`,
    code: `repetir(3, "derecha")\n\n// Es lo mismo que escribir:\nmover("derecha")\nmover("derecha")\nmover("derecha")`,
    explain: "El primer valor es cuántas veces se repite, y el segundo es la dirección. Esto es exactamente lo mismo que un bucle 'for' o 'while' en un lenguaje real: menos código, mismo resultado.",
  },
  {
    title: "4. Comentarios",
    body: `Puedes escribir notas para ti mismo con <code>//</code>. El gato las ignora por completo:`,
    code: `// Bajo hasta la fila del tesoro\nmover("abajo")\nmover("abajo")`,
    explain: "Comentar tu código es una buena práctica: te ayuda a recordar por qué escribiste cada parte.",
  },
  {
    title: "5. ¡Tu turno!",
    body: `Ya conoces todo lo necesario: <code>mover()</code>, <code>repetir()</code> y comentarios con <code>//</code>.`,
    code: `// Escribe aquí tu propio programa\nmover("abajo")\nrepetir(2, "derecha")`,
    explain: "Ahora vas a resolver tableros reales escribiendo el código tú mismo. Si te atoras, hay un botón de Ayuda 💡 que te da una pista sin resolverte todo el problema.",
  },
];

const codeBoardsAdvanced = [
  {
    name: "Reto 1 · El giro",
    grid: [
      "S....",
      ".###.",
      ".#*..",
      "...#G",
    ],
    start: { r: 0, c: 0 },
    basePoints: 35,
    solution: ["down", "down", "down", "right", "right", "up", "right", "right", "down"],
  },
  {
    name: "Reto 2 · El desvío",
    grid: [
      "S.#..",
      "..#..",
      "..#.*",
      "....#",
      "##..G",
    ],
    start: { r: 0, c: 0 },
    basePoints: 45,
    solution: ["down", "down", "down", "right", "right", "right", "down", "right"],
  },
];

/* ===================== NAVIGATION ===================== */
function openGame(mode) {
  if ((mode === 'code' || mode === 'codeadv') && !codeUnlocked) {
    showLockedNotice();
    return;
  }
  document.getElementById('menuView').style.display = 'none';
  document.querySelectorAll('.game-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('panel-' + mode).classList.add('active');
  if (mode === 'quiz') startQuiz();
  if (mode === 'flash') startFlash();
  if (mode === 'match') startMatch();
  if (mode === 'timed') startTimed();
  if (mode === 'code') startCode();
  if (mode === 'codeadv') startCodeAdvanced();
}

function showLockedNotice() {
  const wrap = document.getElementById('toastWrap');
  const toast = document.createElement('div');
  toast.className = 'toast toast-error';
  toast.innerHTML = `
    <div class="t-icon">🔒</div>
    <div class="t-text">
      <div class="t-title">Modo bloqueado</div>
      <div class="t-name">Programa al Gato se desbloquea con 300 puntos</div>
    </div>`;
  wrap.appendChild(toast);
  setTimeout(() => toast.remove(), 4000);
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
    <div class="t-icon">${escapeHtml(badge.icon)}</div>
    <div class="t-text">
      <div class="t-title">¡Nuevo logro desbloqueado!</div>
      <div class="t-name">${escapeHtml(badge.name)}</div>
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
      <div class="t-name">${escapeHtml(message)}</div>
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
      <div class="quiz-question">${escapeHtml(item.q)}</div>
      <div class="quiz-options">
        ${item.options.map((opt, i) => `<div class="quiz-option" data-i="${i}">${escapeHtml(opt)}</div>`).join('')}
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
          ${escapeHtml(item.explain)}
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

  function renderIntro() {
    area.innerHTML = `
      <div class="flash-intro">
        <div class="flash-intro-icon">🗂️</div>
        <h3>Flashcards — Memoriza conceptos</h3>
        <p class="subtitle">Repasa conceptos de programación uno por uno, a tu propio ritmo.</p>
        <ol class="flash-intro-steps">
          <li>Verás una tarjeta con un concepto (por ejemplo, "Variable").</li>
          <li>Tócala para voltearla y descubrir su definición.</li>
          <li>Marca si <strong>ya lo sabías</strong> o si quieres <strong>repasarlo</strong>.</li>
        </ol>
        <p class="flash-intro-tip">💡 Sé honesto contigo mismo: la idea es identificar qué conceptos ya dominas y cuáles necesitas reforzar, no solo conseguir puntos rápido.</p>
        <button class="btn-primary" id="startFlashBtn">Comenzar</button>
      </div>`;
    document.getElementById('startFlashBtn').onclick = () => render();
  }

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
          <div class="flash-face flash-front">${escapeHtml(card.front)}</div>
          <div class="flash-face flash-back">${escapeHtml(card.back)}</div>
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
  renderIntro();
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
        ${matchPairs.map(p => `<div class="match-item" draggable="true" data-term="${p.term}">${escapeHtml(p.term)}</div>`).join('')}
      </div>
      <div id="defs">
        ${shuffledDefs.map(p => `<div class="match-dropzone" data-answer="${p.term}">${escapeHtml(p.def)}</div>`).join('')}
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
      <div class="quiz-question">${escapeHtml(item.q)}</div>
      <div class="quiz-options">
        ${item.options.map((opt, i) => `<div class="quiz-option" data-i="${i}">${escapeHtml(opt)}</div>`).join('')}
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

/* ===================== PROGRAMA AL GATO ===================== */
function startCode() {
  const area = document.getElementById('codeArea');
  let boardIndex = 0;
  let totalEarned = 0;

  function renderIntro() {
    area.innerHTML = `
      <div class="flash-intro">
        <div class="flash-intro-icon">🐱</div>
        <h3>Programa al Gato</h3>
        <p class="subtitle">Escribe un programa con bloques de comandos para llevar al gato hasta la meta 🐟.</p>
        <ol class="flash-intro-steps">
          <li>Arma una secuencia con los bloques ⬆️ ⬇️ ⬅️ ➡️.</li>
          <li>Presiona <strong>▶ Ejecutar</strong> para ver al gato seguir tus instrucciones paso a paso.</li>
          <li>Recoge las estrellas ⭐ en el camino y llega hasta la meta 🐟 sin chocar contra los muros 🧱.</li>
          <li>Usa <strong>🔁 Repetir</strong> para duplicar el último bloque y ahorrar pasos, como un bucle.</li>
        </ol>
        <p class="flash-intro-tip">💡 Si el gato choca con un muro, el programa se reinicia. ¡Piensa antes de ejecutar!</p>
        <button class="btn-primary" id="startCodeBtn">Comenzar</button>
      </div>`;
    document.getElementById('startCodeBtn').onclick = () => loadBoard();
  }

  function loadBoard() {
    if (boardIndex >= catBoards.length) {
      resultScreen(area, totalEarned);
      return;
    }

    const board = catBoards[boardIndex];
    const grid = board.grid.map(row => row.split(''));
    const rows = grid.length;
    const cols = grid[0].length;

    let cat = { r: board.start.r, c: board.start.c };
    let program = [];
    let running = false;
    let collectedThisBoard = 0;

    area.innerHTML = `
      <div class="code-header">
        <span>${escapeHtml(board.name)}</span>
        <span class="code-progress">Tablero ${boardIndex + 1} de ${catBoards.length}</span>
      </div>
      <div class="code-board" id="codeBoard" style="grid-template-columns: repeat(${cols}, 1fr);"></div>
      <div class="code-message" id="codeMessage">Arma tu programa y presiona Ejecutar ▶</div>

      <div class="code-program" id="codeProgram"></div>

      <div class="code-palette">
        <button class="code-block" data-dir="up">⬆️</button>
        <button class="code-block" data-dir="down">⬇️</button>
        <button class="code-block" data-dir="left">⬅️</button>
        <button class="code-block" data-dir="right">➡️</button>
        <button class="code-block code-repeat" id="repeatBtn">🔁 Repetir</button>
      </div>

      <div class="code-actions">
        <button class="btn-secondary" id="clearBtn">🗑️ Borrar todo</button>
        <button class="btn-primary" id="runBtn">▶ Ejecutar</button>
      </div>
    `;

    const boardEl = document.getElementById('codeBoard');
    const messageEl = document.getElementById('codeMessage');
    const programEl = document.getElementById('codeProgram');
    const runBtn = document.getElementById('runBtn');
    const clearBtn = document.getElementById('clearBtn');
    const repeatBtn = document.getElementById('repeatBtn');

    function cellType(r, c) {
      return grid[r][c];
    }

    function renderBoard() {
      boardEl.innerHTML = '';
      for (let r = 0; r < rows; r++) {
        for (let c = 0; c < cols; c++) {
          const type = cellType(r, c);
          const cell = document.createElement('div');
          cell.className = 'code-cell';
          if (type === '#') cell.classList.add('cell-wall');
          else if (type === 'G') cell.classList.add('cell-goal');
          else if (type === '*') cell.classList.add('cell-star');
          else cell.classList.add('cell-floor');

          if (r === cat.r && c === cat.c) {
            const catSpan = document.createElement('span');
            catSpan.className = 'cell-cat';
            catSpan.textContent = '🐱';
            cell.appendChild(catSpan);
          } else if (type === 'G') {
            cell.textContent = '🐟';
          } else if (type === '*') {
            cell.textContent = '⭐';
          }
          boardEl.appendChild(cell);
        }
      }
    }

    function renderProgram() {
      if (program.length === 0) {
        programEl.innerHTML = '<span class="code-empty">Tu programa está vacío. Agrega bloques abajo 👇</span>';
        return;
      }
      const icons = { up: '⬆️', down: '⬇️', left: '⬅️', right: '➡️' };
      programEl.innerHTML = program
        .map((dir, i) => `<span class="code-chip" data-idx="${i}">${icons[dir]}</span>`)
        .join('');
      programEl.querySelectorAll('.code-chip').forEach(chip => {
        chip.onclick = () => {
          if (running) return;
          const idx = parseInt(chip.dataset.idx);
          program.splice(idx, 1);
          renderProgram();
        };
      });
    }

    function addCommand(dir) {
      if (running) return;
      program.push(dir);
      renderProgram();
    }

    area.querySelectorAll('.code-block[data-dir]').forEach(btn => {
      btn.onclick = () => addCommand(btn.dataset.dir);
    });

    repeatBtn.onclick = () => {
      if (running || program.length === 0) return;
      program.push(program[program.length - 1]);
      renderProgram();
    };

    clearBtn.onclick = () => {
      if (running) return;
      program = [];
      renderProgram();
    };

    function setControlsEnabled(enabled) {
      area.querySelectorAll('.code-block, #clearBtn, #runBtn').forEach(el => {
        el.disabled = !enabled;
      });
    }

    function resetCat() {
      cat = { r: board.start.r, c: board.start.c };
    }

    async function runProgram() {
      if (running || program.length === 0) return;
      running = true;
      setControlsEnabled(false);
      messageEl.className = 'code-message';
      messageEl.textContent = '▶ Ejecutando programa...';

      const deltas = {
        up: { r: -1, c: 0 },
        down: { r: 1, c: 0 },
        left: { r: 0, c: -1 },
        right: { r: 0, c: 1 },
      };

      for (let i = 0; i < program.length; i++) {
        await new Promise(res => setTimeout(res, 450));
        const d = deltas[program[i]];
        const nr = cat.r + d.r;
        const nc = cat.c + d.c;

        const outOfBounds = nr < 0 || nr >= rows || nc < 0 || nc >= cols;
        const hitsWall = !outOfBounds && cellType(nr, nc) === '#';

        if (outOfBounds || hitsWall) {
          messageEl.className = 'code-message code-fail';
          messageEl.textContent = '💥 ¡El gato chocó! Ajusta el programa e inténtalo de nuevo.';
          await new Promise(res => setTimeout(res, 700));
          resetCat();
          collectedThisBoard = 0;
          renderBoard();
          running = false;
          setControlsEnabled(true);
          return;
        }

        cat = { r: nr, c: nc };

        if (cellType(nr, nc) === '*') {
          grid[nr][nc] = '.';
          collectedThisBoard += 5;
        }

        renderBoard();

        if (cellType(nr, nc) === 'G') {
          messageEl.className = 'code-message code-success';
          messageEl.textContent = '🎉 ¡Llegaste a la meta!';
          const earned = board.basePoints + collectedThisBoard;
          totalEarned += earned;
          await new Promise(res => setTimeout(res, 900));
          boardIndex++;
          loadBoard();
          return;
        }
      }

      messageEl.className = 'code-message code-fail';
      messageEl.textContent = '🙀 El gato se quedó sin instrucciones antes de llegar a la meta.';
      await new Promise(res => setTimeout(res, 700));
      resetCat();
      collectedThisBoard = 0;
      renderBoard();
      running = false;
      setControlsEnabled(true);
    }

    runBtn.onclick = runProgram;

    renderBoard();
    renderProgram();
  }

  renderIntro();
}

/* ===== Mini-lenguaje: mover("dir") / repetir(n, "dir") / comentarios // ===== */
function parseAdvancedCode(code) {
  const lines = code.split('\n');
  const commands = [];

  for (let i = 0; i < lines.length; i++) {
    let raw = lines[i];
    const commentIdx = raw.indexOf('//');
    if (commentIdx !== -1) raw = raw.slice(0, commentIdx);
    const line = raw.trim();
    if (line === '') continue;

    let m = line.match(/^mover\(\s*["'](arriba|abajo|izquierda|derecha)["']\s*\)$/);
    if (m) {
      commands.push(codeDirWords[m[1]]);
      continue;
    }

    m = line.match(/^repetir\(\s*(\d{1,2})\s*,\s*["'](arriba|abajo|izquierda|derecha)["']\s*\)$/);
    if (m) {
      const n = Math.min(parseInt(m[1], 10), 20);
      for (let k = 0; k < n; k++) commands.push(codeDirWords[m[2]]);
      continue;
    }

    return {
      commands: null,
      error: { line: i + 1, message: `Línea ${i + 1}: no reconozco "${escapeHtml(line)}". Usa mover("direccion") o repetir(n, "direccion").` },
    };
  }

  if (commands.length === 0) {
    return { commands: null, error: { line: 0, message: 'Tu programa está vacío. Escribe al menos un comando mover(...).' } };
  }
  if (commands.length > 60) {
    return { commands: null, error: { line: 0, message: 'Tu programa es demasiado largo (máx. 60 pasos). Prueba usar repetir(n, "direccion").' } };
  }
  return { commands, error: null };
}

/* ===================== PROGRAMA AL GATO: AVANZADO ===================== */
function startCodeAdvanced() {
  const area = document.getElementById('codeAdvArea');
  let tutorialIndex = 0;
  let boardIndex = 0;
  let totalEarned = 0;

  function renderTutorialStep() {
    const step = tutorialSteps[tutorialIndex];
    const isLast = tutorialIndex === tutorialSteps.length - 1;
    area.innerHTML = `
      <div class="tut-wrap">
        <div class="tut-progress">Tutorial ${tutorialIndex + 1}/${tutorialSteps.length}</div>
        <h3>${step.title}</h3>
        <p class="subtitle">${step.body}</p>
        <pre class="code-preview">${escapeHtml(step.code)}</pre>
        <p class="tut-explain">${escapeHtml(step.explain)}</p>
        <div class="code-actions">
          ${tutorialIndex > 0 ? '<button class="btn-secondary" id="tutPrev">← Anterior</button>' : '<span></span>'}
          <button class="btn-primary" id="tutNext">${isLast ? 'Comenzar retos →' : 'Siguiente →'}</button>
        </div>
      </div>`;

    if (tutorialIndex > 0) {
      document.getElementById('tutPrev').onclick = () => { tutorialIndex--; renderTutorialStep(); };
    }
    document.getElementById('tutNext').onclick = () => {
      if (isLast) loadChallenge();
      else { tutorialIndex++; renderTutorialStep(); }
    };
  }

  function loadChallenge() {
    if (boardIndex >= codeBoardsAdvanced.length) {
      resultScreen(area, totalEarned);
      return;
    }

    const board = codeBoardsAdvanced[boardIndex];
    const grid = board.grid.map(row => row.split(''));
    const rows = grid.length;
    const cols = grid[0].length;

    let cat = { r: board.start.r, c: board.start.c };
    let running = false;
    let hintsUsed = 0;
    let collectedThisBoard = 0;

    area.innerHTML = `
      <div class="code-header">
        <span>${escapeHtml(board.name)}</span>
        <span class="code-progress">Reto ${boardIndex + 1} de ${codeBoardsAdvanced.length}</span>
      </div>
      <div class="code-board" id="codeBoardAdv" style="grid-template-columns: repeat(${cols}, 1fr);"></div>
      <div class="code-message" id="codeMessageAdv">Escribe tu programa y presiona Ejecutar ▶</div>

      <textarea class="code-editor" id="codeEditor" spellcheck="false" placeholder='mover(&quot;arriba&quot;)
repetir(2, &quot;derecha&quot;)'></textarea>
      <div class="code-hint" id="codeHint" style="display:none;"></div>

      <div class="code-actions">
        <button class="btn-secondary" id="clearAdvBtn">🗑️ Borrar</button>
        <button class="btn-secondary" id="hintBtn">💡 Ayuda</button>
        <button class="btn-primary" id="runAdvBtn">▶ Ejecutar</button>
      </div>
    `;

    const boardEl = document.getElementById('codeBoardAdv');
    const messageEl = document.getElementById('codeMessageAdv');
    const editorEl = document.getElementById('codeEditor');
    const hintEl = document.getElementById('codeHint');
    const runBtn = document.getElementById('runAdvBtn');
    const clearBtn = document.getElementById('clearAdvBtn');
    const hintBtn = document.getElementById('hintBtn');

    function cellType(r, c) { return grid[r][c]; }

    function renderBoard() {
      boardEl.innerHTML = '';
      for (let r = 0; r < rows; r++) {
        for (let c = 0; c < cols; c++) {
          const type = cellType(r, c);
          const cell = document.createElement('div');
          cell.className = 'code-cell';
          if (type === '#') cell.classList.add('cell-wall');
          else if (type === 'G') cell.classList.add('cell-goal');
          else if (type === '*') cell.classList.add('cell-star');
          else cell.classList.add('cell-floor');

          if (r === cat.r && c === cat.c) {
            const catSpan = document.createElement('span');
            catSpan.className = 'cell-cat';
            catSpan.textContent = '🐱';
            cell.appendChild(catSpan);
          } else if (type === 'G') {
            cell.textContent = '🐟';
          } else if (type === '*') {
            cell.textContent = '⭐';
          }
          boardEl.appendChild(cell);
        }
      }
    }

    function resetCat() {
      cat = { r: board.start.r, c: board.start.c };
    }

    function setControlsEnabled(enabled) {
      editorEl.disabled = !enabled;
      runBtn.disabled = !enabled;
      clearBtn.disabled = !enabled;
      hintBtn.disabled = !enabled;
    }

    clearBtn.onclick = () => {
      if (running) return;
      editorEl.value = '';
      hintEl.style.display = 'none';
    };

    hintBtn.onclick = () => {
      if (running) return;
      const parsed = parseAdvancedCode(editorEl.value);
      const typed = parsed.commands || [];
      const solution = board.solution;

      let idx = 0;
      while (idx < typed.length && idx < solution.length && typed[idx] === solution[idx]) idx++;

      hintsUsed++;
      hintEl.style.display = 'block';
      if (idx >= solution.length) {
        hintEl.textContent = '💡 ¡Ya tienes una secuencia válida! Presiona Ejecutar para probarla.';
      } else {
        const dirWord = Object.keys(codeDirWords).find(k => codeDirWords[k] === solution[idx]);
        hintEl.textContent = `💡 Pista: el paso ${idx + 1} podría ser mover("${dirWord}"). (Usar ayuda reduce un poco los puntos del reto)`;
      }
    };

    async function runProgram() {
      if (running) return;
      hintEl.style.display = 'none';

      const parsed = parseAdvancedCode(editorEl.value);
      if (parsed.error) {
        messageEl.className = 'code-message code-fail';
        messageEl.textContent = '❌ ' + parsed.error.message;
        return;
      }

      const program = parsed.commands;
      running = true;
      setControlsEnabled(false);
      messageEl.className = 'code-message';
      messageEl.textContent = '▶ Ejecutando programa...';

      const deltas = {
        up: { r: -1, c: 0 },
        down: { r: 1, c: 0 },
        left: { r: 0, c: -1 },
        right: { r: 0, c: 1 },
      };

      for (let i = 0; i < program.length; i++) {
        await new Promise(res => setTimeout(res, 400));
        const d = deltas[program[i]];
        const nr = cat.r + d.r;
        const nc = cat.c + d.c;

        const outOfBounds = nr < 0 || nr >= rows || nc < 0 || nc >= cols;
        const hitsWall = !outOfBounds && cellType(nr, nc) === '#';

        if (outOfBounds || hitsWall) {
          messageEl.className = 'code-message code-fail';
          messageEl.textContent = '💥 ¡El gato chocó! Revisa tu código e inténtalo de nuevo.';
          await new Promise(res => setTimeout(res, 700));
          resetCat();
          collectedThisBoard = 0;
          renderBoard();
          running = false;
          setControlsEnabled(true);
          return;
        }

        cat = { r: nr, c: nc };

        if (cellType(nr, nc) === '*') {
          grid[nr][nc] = '.';
          collectedThisBoard += 5;
        }

        renderBoard();

        if (cellType(nr, nc) === 'G') {
          messageEl.className = 'code-message code-success';
          messageEl.textContent = '🎉 ¡Tu programa funcionó! Meta alcanzada.';
          const penalty = Math.min(hintsUsed * 5, board.basePoints - 10);
          const earned = Math.max(10, board.basePoints - penalty) + collectedThisBoard;
          totalEarned += earned;
          await new Promise(res => setTimeout(res, 900));
          boardIndex++;
          loadChallenge();
          return;
        }
      }

      messageEl.className = 'code-message code-fail';
      messageEl.textContent = '🙀 Tu programa terminó pero el gato no llegó a la meta.';
      await new Promise(res => setTimeout(res, 700));
      resetCat();
      collectedThisBoard = 0;
      renderBoard();
      running = false;
      setControlsEnabled(true);
    }

    runBtn.onclick = runProgram;

    renderBoard();
  }

  renderTutorialStep();
}
</script>

</body>
</html>