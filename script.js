/*
  PERSONALIZA AQUÍ:
  - Escribe la hora exacta en EVENT_TIME_TEXT.
  - Escribe tu número con lada de país en WHATSAPP_NUMBER, sin + ni espacios.
    Ejemplo México: 524491234567
*/
const EVENT = {
  title: "Fiesta en el Balneario Valladolid",
  dateISO: "2026-08-08",
  dateLabel: "Sábado 8 de agosto de 2026",
  timeText: "9:30 AM",
  startHour: 9, // se usa al crear el archivo de calendario; cámbiala al confirmar la hora
  startMinute: 30,
  durationHours: 7,
  whatsappNumber: "4495820032",
  mapsUrl: "https://maps.app.goo.gl/wWDXqC5cG3LxbQJt6",
  place: "Balneario Valladolid, AGS 18, Col. Jardines del Valle, 20250 Jesús Gómez Portugal, Ags."
};

const PHRASES = [
  "Prohibido llegar de malas.",
  "Traje de baño obligatorio.",
  "El que llegue tarde pone de más.",
  "Si vienes sin ganas de cotorrear, aquí te dan ganas."
];

const FRAMES = [
  "assets/03_corriendo.png",
  "assets/09_saludo.png",
  "assets/01_vengan.png",
  "assets/05_cotorreo.png",
  "assets/06_tu.png",
  "assets/04_gti_pulgares.png",
  "assets/10_brindis_gti.png",
  "assets/07_inflable.png"
];

const $ = (selector) => document.querySelector(selector);
const gate = $("#gate");
const invite = $("#invite");
const hostFrame = $("#hostFrame");
const stage = $(".stage");
const soundToggle = $("#soundToggle");
const bgMusic = $("#bgMusic");
bgMusic.volume = 0.45;
let soundEnabled = true;
let frameTimer = null;

function setGuestName() {
  const params = new URLSearchParams(location.search);
  const guest = (params.get("para") || "").trim().slice(0, 36);
  if (guest) $("#guestName").textContent = `${guest}, ¡estás invitado!`;
}

function buildTicker() {
  const track = $("#tickerTrack");
  const doubled = [...PHRASES, ...PHRASES];
  track.innerHTML = doubled.map(text => `<span class="ticker__item">${text}</span>`).join("");
}

function makeBubbles() {
  const field = $("#bubbleField");
  for (let i = 0; i < 26; i++) {
    const bubble = document.createElement("span");
    bubble.className = "bubble";
    const size = 12 + Math.random() * 62;
    bubble.style.width = `${size}px`;
    bubble.style.height = `${size}px`;
    bubble.style.left = `${Math.random() * 100}%`;
    bubble.style.setProperty("--duration", `${8 + Math.random() * 11}s`);
    bubble.style.setProperty("--delay", `${-Math.random() * 16}s`);
    field.appendChild(bubble);
  }
}

function animateHost() {
  clearInterval(frameTimer);
  let index = 0;
  hostFrame.src = FRAMES[index];
  frameTimer = setInterval(() => {
    hostFrame.classList.remove("host--active");
    setTimeout(() => {
      index = (index + 1) % FRAMES.length;
      hostFrame.src = FRAMES[index];
      hostFrame.alt = [
        "Anfitrión corriendo hacia la fiesta",
        "Anfitrión saludando",
        "Anfitrión haciendo la seña de venir",
        "Anfitrión listo para cotorrear",
        "Anfitrión señalando al invitado",
        "Anfitrión con pulgares arriba frente al GTI",
        "Anfitrión brindando frente al GTI",
        "Anfitrión descansando en un inflable"
      ][index];
      hostFrame.classList.add("host--active");
      stage.classList.remove("is-splashing");
      void stage.offsetWidth;
      stage.classList.add("is-splashing");
    }, 330);
  }, 2650);
}

function openInvite() {
  gate.classList.add("is-open");
  invite.classList.add("is-visible");
  invite.setAttribute("aria-hidden", "false");
  document.body.style.overflow = "auto";
  if (soundEnabled) {
  bgMusic.currentTime = 0;
  bgMusic.play().catch(() => {
    showToast("Presiona el botón de sonido para iniciar la música.");
  });
}
  animateHost();
  launchConfetti(160);
}

function updateCountdown() {
  const now = new Date();
  const target = new Date(`${EVENT.dateISO}T00:00:00-06:00`);
  let diff = target - now;

  if (diff <= 0) {
    const eventEnd = new Date(`${EVENT.dateISO}T23:59:59-06:00`);
    if (now <= eventEnd) {
      $("#countdown-title").textContent = "¡Hoy es el día! Nos vemos en el agua";
      ["days", "hours", "minutes", "seconds"].forEach(id => $(`#${id}`).textContent = "00");
      return;
    }
    $("#countdown-title").textContent = "Esta fiesta ya pasó… pero el cotorreo queda";
    diff = 0;
  }

  const days = Math.floor(diff / 86400000);
  const hours = Math.floor((diff % 86400000) / 3600000);
  const minutes = Math.floor((diff % 3600000) / 60000);
  const seconds = Math.floor((diff % 60000) / 1000);
  $("#days").textContent = String(days).padStart(2, "0");
  $("#hours").textContent = String(hours).padStart(2, "0");
  $("#minutes").textContent = String(minutes).padStart(2, "0");
  $("#seconds").textContent = String(seconds).padStart(2, "0");
}

function showToast(message) {
  const toast = $("#toast");
  toast.textContent = message;
  toast.classList.add("is-visible");
  clearTimeout(showToast.timer);
  showToast.timer = setTimeout(() => toast.classList.remove("is-visible"), 2400);
}

function confirmWhatsApp() {
  const params = new URLSearchParams(location.search);
  const guest = (params.get("para") || "").trim();
  const who = guest ? `Soy ${guest}. ` : "";
  const text = `${who}¡Confirmo que sí me lanzo a la fiesta en el Balneario Valladolid el sábado 8 de agosto! 🏊‍♂️🚗💦`;
  const base = EVENT.whatsappNumber
    ? `https://wa.me/${EVENT.whatsappNumber}`
    : "https://wa.me/";
  window.open(`${base}?text=${encodeURIComponent(text)}`, "_blank", "noopener,noreferrer");
}

async function shareInvite() {
  const data = {
    title: EVENT.title,
    text: "¡Nos vamos al Balneario Valladolid! Abre la invitación y confirma si te lanzas.",
    url: location.href
  };
  try {
    if (navigator.share) await navigator.share(data);
    else {
      await navigator.clipboard.writeText(location.href);
      showToast("Enlace copiado");
    }
  } catch (error) {
    if (error.name !== "AbortError") showToast("No se pudo compartir; copia el enlace del navegador.");
  }
}

function downloadCalendar() {
  const pad = n => String(n).padStart(2, "0");
  const start = new Date(2026, 7, 8, EVENT.startHour, EVENT.startMinute);
  const end = new Date(start.getTime() + EVENT.durationHours * 3600000);
  const localStamp = date => `${date.getFullYear()}${pad(date.getMonth()+1)}${pad(date.getDate())}T${pad(date.getHours())}${pad(date.getMinutes())}00`;
  const ics = [
    "BEGIN:VCALENDAR",
    "VERSION:2.0",
    "PRODID:-//Invitacion Balneario//ES",
    "CALSCALE:GREGORIAN",
    "BEGIN:VEVENT",
    `DTSTART:${localStamp(start)}`,
    `DTEND:${localStamp(end)}`,
    `SUMMARY:${EVENT.title}`,
    `LOCATION:${EVENT.place}`,
    `DESCRIPTION:Alberca, cotorreo y buena compañía. Ubicación: ${EVENT.mapsUrl}`,
    "END:VEVENT",
    "END:VCALENDAR"
  ].join("\r\n");
  const blob = new Blob([ics], {type: "text/calendar;charset=utf-8"});
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = "fiesta-balneario-valladolid.ics";
  a.click();
  URL.revokeObjectURL(url);
  showToast("Evento preparado para tu calendario");
}

function launchConfetti(amount = 120) {
  const canvas = $("#confetti");
  const ctx = canvas.getContext("2d");
  const ratio = Math.min(window.devicePixelRatio || 1, 2);
  canvas.width = innerWidth * ratio;
  canvas.height = innerHeight * ratio;
  canvas.style.width = `${innerWidth}px`;
  canvas.style.height = `${innerHeight}px`;
  ctx.scale(ratio, ratio);

  const pieces = Array.from({length: amount}, () => ({
    x: Math.random() * innerWidth,
    y: -20 - Math.random() * innerHeight * .25,
    w: 5 + Math.random() * 9,
    h: 8 + Math.random() * 13,
    speed: 2.4 + Math.random() * 5.5,
    sway: (Math.random() - .5) * 2.5,
    rot: Math.random() * Math.PI,
    spin: (Math.random() - .5) * .22,
    color: ["#ffd85e", "#20e3d3", "#ff6f61", "#ffffff", "#1888ff"][Math.floor(Math.random()*5)]
  }));

  let frame = 0;
  function draw() {
    ctx.clearRect(0, 0, innerWidth, innerHeight);
    pieces.forEach(p => {
      p.y += p.speed;
      p.x += Math.sin(p.y / 35) + p.sway;
      p.rot += p.spin;
      ctx.save();
      ctx.translate(p.x, p.y);
      ctx.rotate(p.rot);
      ctx.fillStyle = p.color;
      ctx.fillRect(-p.w/2, -p.h/2, p.w, p.h);
      ctx.restore();
    });
    frame++;
    if (frame < 190) requestAnimationFrame(draw);
    else ctx.clearRect(0, 0, innerWidth, innerHeight);
  }
  draw();
}

$("#eventTime").textContent = EVENT.timeText;
$("#openInvite").addEventListener("click", openInvite);
$("#whatsappBtn").addEventListener("click", confirmWhatsApp);
$("#shareBtn").addEventListener("click", shareInvite);
$("#calendarBtn").addEventListener("click", downloadCalendar);
$("#replayBtn").addEventListener("click", () => {
  scrollTo({top: 0, behavior: "smooth"});
  if (soundEnabled) {
  bgMusic.currentTime = 0;
  bgMusic.play().catch(() => {});
}
  launchConfetti(100);
  animateHost();
});

soundToggle.addEventListener("click", () => {
  soundEnabled = !soundEnabled;
  soundToggle.setAttribute("aria-pressed", String(soundEnabled));
  soundToggle.textContent = soundEnabled ? "🔊 Música" : "🔇 Sin música";

  if (soundEnabled) {
    bgMusic.play().catch(() => {
      showToast("No se pudo iniciar la música.");
    });
  } else {
    bgMusic.pause();
  }
});

setGuestName();
buildTicker();
makeBubbles();
updateCountdown();
setInterval(updateCountdown, 1000);
