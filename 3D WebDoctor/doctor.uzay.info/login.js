const toggleTCKNO = document.getElementById("toggleTCKNO");
const tcknoInput = document.getElementById("TCKNO");
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