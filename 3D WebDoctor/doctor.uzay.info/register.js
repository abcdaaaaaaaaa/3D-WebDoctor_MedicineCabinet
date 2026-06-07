const regForm = document.getElementById('registerForm');
if (regForm) {
    regForm.addEventListener('submit', function(event) {
        const info = document.getElementById('information');
        if (info) {
            let infoText = info.value;
            infoText = infoText.replace(/\b[Bb]en astım'ım\b/g, (m) => m.charAt(0) === 'B' ? 'Hasta astım' : 'hasta astım').replace(/\b[Bb]en astımım\b/g, (m) => m.charAt(0) === 'B' ? 'Hasta astım' : 'hasta astım').replace(/\b[Aa]stım'ım\b/g, (m) => m.charAt(0) === 'A' ? "Astım'ı" : "astım'ı").replace(/\b[Aa]stımım\b/g, (m) => m.charAt(0) === 'A' ? "Astım'ı" : "astım'ı").replace(/\b[Bb]ende\b/g, (m) => m.charAt(0) === 'B' ? 'Hastada' : 'hastada').replace(/\b[Bb]en\b/g, (m) => m.charAt(0) === 'B' ? 'Hasta' : 'hasta').replace(/\b[Bb]iz\b/g, (m) => m.charAt(0) === 'B' ? 'Hasta' : 'hasta');
            const tokens = {};
            let tokenIndex = 0;
            infoText = infoText.replace(/\b([Aa]stım'ı|[Aa]stımı|[Aa]stım)\b/g, (match) => { const token = `__ASTIM_TOKEN_${tokenIndex}__`; tokens[token] = match; tokenIndex++; return token; });
            infoText = infoText.replace(/([a-zçğıöşü]+?)yorum\b/gi, '$1yor').replace(/([a-zçğıöşü]+?)dım\b/gi, '$1dı').replace(/([a-zçğıöşü]+?)dim\b/gi, '$1di').replace(/([a-zçğıöşü]+?)dum\b/gi, '$1du').replace(/([a-zçğıöşü]+?)düm\b/gi, '$1dü').replace(/([a-zçğıöşü]+?)tım\b/gi, '$1tı').replace(/([a-zçğıöşü]+?)tim\b/gi, '$1ti').replace(/([a-zçğıöşü]+?)tum\b/gi, '$1tu').replace(/([a-zçğıöşü]+?)tüm\b/gi, '$1tü').replace(/([a-zçğıöşü]+?)arım\b/gi, '$1ar').replace(/([a-zçğıöşü]+?)erim\b/gi, '$1er').replace(/([a-zçğıöşü]+?)irim\b/gi, '$1ir').replace(/([a-zçğıöşü]+?)ürüm\b/gi, '$1ür').replace(/([a-zçğıöşü]+?)urum\b/gi, '$1ur');
            Object.keys(tokens).forEach(token => { const original = tokens[token]; const regex = new RegExp(token, 'g'); infoText = infoText.replace(regex, original); });
            info.value = infoText.charAt(0).toUpperCase() + infoText.slice(1);
            let sentences = infoText.split(/(?<=\.)\s*/);
            info.value = sentences.map(s => s.trim()).join('\n');
            const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
            if (checkboxes.length > 0) {
                let additionalInfo = '';
                checkboxes.forEach((checkbox, index) => { additionalInfo += checkbox.value; if (index < checkboxes.length - 1) additionalInfo += '\n'; });
                info.value += '\n' + additionalInfo;
            }
        }
    });
}

const tcknoInput = document.getElementById('TCKNO');
if (tcknoInput) {
    tcknoInput.addEventListener('input', function (e){ this.value = this.value.replace(/[^0-9]/g, ''); });
}

function checkPasswordStrength(input) {
    const v = input.value;
    const note = document.getElementById("passwordNote");
    const segs = document.querySelectorAll("#passwordBarContainer .seg");
    if(!note || segs.length === 0) return;
    segs.forEach(s => { s.style.background = "#ddd"; });
    if (!v) { note.textContent = ""; input.setCustomValidity(''); return; }
    const r = zxcvbn(v);
    const score = r.score;
    let color = "";
    if (score === 1) color = "#e74c3c";
    else if (score === 2) color = "#f39c12";
    else if (score === 3) color = "#f1c40f";
    else if (score === 4) color = "#27ae60";
    for (let i = 0; i < score; i++) { if (segs[i]) segs[i].style.background = color; }
    note.textContent = score === 1 ? "Çok zayıf" : score === 2 ? "Zayıf" : score === 3 ? "Orta" : score === 4 ? "Güçlü" : "";
    note.style.color = color;
    if (score < 3) input.setCustomValidity("Parola en az orta seviye olmalıdır.");
    else if (input.value.length > 100) input.setCustomValidity("Parola en fazla 100 karakter olabilir.");
    else input.setCustomValidity('');
}

const togglePassword = document.getElementById("togglePassword");
const passwordInput = document.getElementById("password");
if (togglePassword && passwordInput) {
    togglePassword.addEventListener("click", () => {
        const icon = togglePassword.querySelector("i");
        if (passwordInput.type === "password" || passwordInput.style.webkitTextSecurity === "disc") {
            passwordInput.style.webkitTextSecurity = "none";
            passwordInput.type = "text";
            icon.className = "fa-solid fa-eye-slash";
        } else {
            passwordInput.style.webkitTextSecurity = "disc";
            passwordInput.type = "password";
            icon.className = "fa-solid fa-eye";
        }
    });
}

const toggleTCKNO = document.getElementById("toggleTCKNO");
if (toggleTCKNO && tcknoInput) {
    toggleTCKNO.addEventListener("click", () => {
        const icon = toggleTCKNO.querySelector("i");
        if (tcknoInput.type === "password" || tcknoInput.style.webkitTextSecurity === "disc") {
            tcknoInput.style.webkitTextSecurity = "none";
            tcknoInput.type = "text";
            icon.className = "fa-solid fa-eye-slash";
        } else {
            tcknoInput.style.webkitTextSecurity = "disc";
            tcknoInput.type = "password";
            icon.className = "fa-solid fa-eye";
        }
    });
}

let val = ""; let t = 0; let alpha = 1; let started = false; let timer = null; let captchaOk = false;
const cv = document.getElementById("cv");
const ctx = cv ? cv.getContext("2d") : null;
const cb = document.getElementById("cb");
const sp = document.getElementById("sp");
const off = document.createElement("canvas");
off.width = 180; off.height = 60;
const offctx = off.getContext("2d");
let chars = [];

function gen() {
    val = ''; let arr = [];
    for (let i = 0; i < 3; i++) { arr.push(String.fromCharCode(65 + Math.floor(Math.random() * 26))); }
    for (let i = 0; i < 2; i++) { arr.push(Math.floor(Math.random() * 10)); }
    val = arr.sort(() => Math.random() - 0.5).join('');
    fetch("captcha_set.php", { method: "POST", body: new URLSearchParams({ v: val }) });
    alpha = 1; t = 0; chars = [];
    for (let i = 0; i < val.length; i++) { chars.push({ x: 20 + i * 28, y: 42, sx: Math.random() * 10, sy: Math.random() * 10, w: Math.random() * 6 + 4 }); }
    if(document.getElementById("inp")) document.getElementById("inp").value = "";
}

function draw() {
    if (!offctx || !ctx) return;
    offctx.clearRect(0, 0, 180, 60);
    offctx.font = "34px Arial";
    offctx.fillStyle = "#333";
    for (let i = 0; i < val.length; i++) {
        let c = val[i]; let o = chars[i];
        let dx = Math.sin(t / o.sx) * o.w; let dy = Math.cos(t / o.sy) * o.w;
        offctx.fillText(c, o.x + dx, o.y + dy);
    }
    ctx.clearRect(0, 0, 180, 60);
    ctx.globalAlpha = alpha;
    for (let y = 0; y < 60; y++) {
        let warp = Math.sin((y + t) / 6) * 5;
        ctx.drawImage(off, 0, y, 180, 1, warp, y, 180, 1);
    }
    for (let i = 0; i < 30; i++) { ctx.fillRect(Math.random() * 180, Math.random() * 60, 2, 2); }
}

window.startCaptcha = function() {
    if (started || !sp || !cb) return;
    started = true;
    sp.style.display = "block";
    cb.style.display = "none";
    document.getElementById("ch").style.display = "block";
    gen();
    timer = setInterval(() => { t++; alpha -= 0.001; if (alpha <= 0) { gen(); } draw(); }, 60);
}

const inp = document.getElementById("inp");
if (inp) {
    inp.addEventListener("input", e => {
        if (e.target.value.length !== val.length) return;
        fetch("captcha_check.php", { method: "POST", body: new URLSearchParams({ v: e.target.value }) })
            .then(r => r.text())
            .then(res => {
                if (res === "OK") {
                    captchaOk = true;
                    clearInterval(timer);
                    sp.style.display = "none";
                    cb.style.display = "flex";
                    cb.classList.add("ok");
                    document.getElementById("ch").style.display = "none";
                }
            });
    });
}

window.checkCaptcha = function() {
    if (!captchaOk) { alert("Lütfen Ben robot değilim doğrulamasını yapınız."); return false; }
    return true;
}