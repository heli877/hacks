<?php
session_start();
include "config.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION["user"];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Configura tu perfil</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>

  <div class="brand">
    <div class="brand-icon">🔒</div>
    CodeQuestion
  </div>

  <div class="card onboard-wrap" id="stepChoice">
    <h2>¡Hola, <?php echo htmlspecialchars($username); ?>! 👋</h2>
    <p class="subtitle">Antes de empezar, cuéntanos tu nivel de programación</p>

    <div class="choice-grid">
      <div class="choice-card" onclick="showManual()">
        <div class="icon">🎯</div>
        <h3>Elegir mi nivel</h3>
        <p>Sé cuál es mi nivel</p>
      </div>
      <div class="choice-card" onclick="startAssessment()">
        <div class="icon">🧪</div>
        <h3>Hacer evaluación</h3>
        <p>Averígualo con 8 preguntas</p>
      </div>
    </div>
  </div>

  <!-- MANUAL SELECTION -->
  <div class="card onboard-wrap" id="stepManual" style="display:none;">
    <h2>¿Cuál es tu nivel?</h2>
    <p class="subtitle">Podrás cambiarlo más adelante si quieres</p>
    <div class="level-options">
      <div class="level-option" onclick="saveLevel('basico', this)">
        <strong>🌱 Básico</strong>
        <span>Estoy empezando a programar</span>
      </div>
      <div class="level-option" onclick="saveLevel('intermedio', this)">
        <strong>🌿 Intermedio</strong>
        <span>Ya conozco lógica, funciones y estructuras</span>
      </div>
      <div class="level-option" onclick="saveLevel('avanzado', this)">
        <strong>🌳 Avanzado</strong>
        <span>Manejo varios lenguajes y conceptos complejos</span>
      </div>
    </div>
  </div>

  <!-- ASSESSMENT -->
  <div class="card onboard-wrap" id="stepAssess" style="display:none; text-align:left;">
    <div id="assessArea"></div>
  </div>

  <div class="toast-wrap" id="toastWrap"></div>

<script>
const stepChoice = document.getElementById('stepChoice');
const stepManual = document.getElementById('stepManual');
const stepAssess = document.getElementById('stepAssess');

function showManual() {
  stepChoice.style.display = 'none';
  stepManual.style.display = 'block';
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

let savingLevel = false;

async function saveLevel(level, el) {
  if (savingLevel) return; // evita doble envío
  savingLevel = true;
  if (el) el.classList.add('is-loading');

  try {
    const res = await fetch('save_level.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ level })
    });

    if (!res.ok) {
      showErrorToast('El servidor no respondió correctamente. Intenta de nuevo.');
      savingLevel = false;
      if (el) el.classList.remove('is-loading');
      return;
    }

    const data = await res.json();
    if (data.ok) {
      window.location.href = 'dashboard.php';
      return; // no quitamos el loading: ya estamos navegando fuera
    } else {
      showErrorToast(data.error || 'No se pudo guardar tu nivel.');
    }
  } catch (err) {
    showErrorToast('Sin conexión a internet. Tu nivel no se guardó, inténtalo de nuevo.');
  }

  savingLevel = false;
  if (el) el.classList.remove('is-loading');
}

/* ===== ASSESSMENT ===== */
const assessQuestions = [
  { q: "¿Qué es una variable?", options: ["Un espacio para guardar datos", "Un tipo de error", "Un archivo de imagen"], correct: 0, weight: 1 },
  { q: "¿Qué hace un bucle 'for'?", options: ["Guarda una contraseña", "Repite código un número de veces", "Elimina una tabla"], correct: 1, weight: 1 },
  { q: "¿Qué es una función?", options: ["Un bloque de código reutilizable", "Un color CSS", "Una base de datos"], correct: 0, weight: 1 },
  { q: "¿Qué diferencia hay entre '==' y '===' en JavaScript?", options: ["Ninguna", "'===' compara también el tipo de dato", "'==' es más rápido siempre"], correct: 1, weight: 2 },
  { q: "¿Qué es la recursividad?", options: ["Una función que se llama a sí misma", "Un tipo de base de datos", "Un error de sintaxis"], correct: 0, weight: 2 },
  { q: "¿Para qué sirve una API REST?", options: ["Diseñar interfaces gráficas", "Comunicar sistemas mediante peticiones HTTP", "Comprimir imágenes"], correct: 1, weight: 2 },
  { q: "¿Qué problema resuelve la indexación en bases de datos?", options: ["Acelerar las búsquedas", "Cambiar el diseño visual", "Crear contraseñas"], correct: 0, weight: 3 },
  { q: "¿Qué es la complejidad Big O?", options: ["Una forma de medir el rendimiento de un algoritmo", "Un lenguaje de programación", "Un tipo de variable"], correct: 0, weight: 3 },
];

function startAssessment() {
  stepChoice.style.display = 'none';
  stepAssess.style.display = 'block';

  let current = 0, score = 0, maxScore = assessQuestions.reduce((a, q) => a + q.weight, 0);
  const area = document.getElementById('assessArea');

  function render() {
    if (current >= assessQuestions.length) {
      finish();
      return;
    }
    const item = assessQuestions[current];
    area.innerHTML = `
      <div class="quiz-progress">Pregunta ${current + 1} de ${assessQuestions.length}</div>
      <div class="quiz-question">${item.q}</div>
      <div class="quiz-options">
        ${item.options.map((opt, i) => `<div class="quiz-option" data-i="${i}">${opt}</div>`).join('')}
      </div>`;

    area.querySelectorAll('.quiz-option').forEach(el => {
      el.onclick = () => {
        const i = parseInt(el.dataset.i);
        area.querySelectorAll('.quiz-option').forEach(o => o.classList.add('disabled'));
        if (i === item.correct) {
          el.classList.add('correct');
          score += item.weight;
        } else {
          el.classList.add('incorrect');
          area.querySelector(`[data-i="${item.correct}"]`).classList.add('correct');
        }
        setTimeout(() => { current++; render(); }, 700);
      };
    });
  }

  async function finish() {
    const pct = score / maxScore;
    let level = 'basico', label = 'Básico 🌱';
    if (pct >= 0.75) { level = 'avanzado'; label = 'Avanzado 🌳'; }
    else if (pct >= 0.4) { level = 'intermedio'; label = 'Intermedio 🌿'; }

    area.innerHTML = `
      <div class="assess-result">
        <p class="subtitle">Según tus respuestas, tu nivel es:</p>
        <div class="badge">${label}</div>
        <button class="btn-primary" id="continueBtn">Continuar</button>
      </div>`;

    document.getElementById('continueBtn').onclick = async (e) => {
      await saveLevel(level, e.currentTarget);
    };
  }

  render();
}
</script>

</body>
</html>