const confInput = document.getElementById("confirmation_code");
const toggleConf = document.getElementById("toggleConfirmationCode");
if (toggleConf && confInput) {
    toggleConf.addEventListener("click", () => {
        const icon = toggleConf.querySelector("i");
        if (confInput.style.webkitTextSecurity === "disc" || confInput.type === "password") {
            confInput.style.webkitTextSecurity = "none";
            confInput.type = "text";
            icon.className = "fa-solid fa-eye-slash";
        } else {
            confInput.style.webkitTextSecurity = "disc";
            confInput.type = "password";
            icon.className = "fa-solid fa-eye";
        }
    });
}

const cupInput = document.getElementById("cupboard_code");
const toggleCup = document.getElementById("toggleCupboardCode");
if (toggleCup && cupInput) {
    toggleCup.addEventListener("click", () => {
        const icon = toggleCup.querySelector("i");
        if (cupInput.style.webkitTextSecurity === "disc" || cupInput.type === "password") {
            cupInput.style.webkitTextSecurity = "none";
            cupInput.type = "text";
            icon.className = "fa-solid fa-eye-slash";
        } else {
            cupInput.style.webkitTextSecurity = "disc";
            cupInput.type = "password";
            icon.className = "fa-solid fa-eye";
        }
    });
}

const _confInput = document.getElementById("new_confirmation_code");
const _toggleConf = document.getElementById("toggleNewConfirmationCode");
if (_toggleConf && _confInput) {
    _toggleConf.addEventListener("click", () => {
        const icon = _toggleConf.querySelector("i");
        if (_confInput.style.webkitTextSecurity === "disc" || _confInput.type === "password") {
            _confInput.style.webkitTextSecurity = "none";
            _confInput.type = "text";
            icon.className = "fa-solid fa-eye-slash";
        } else {
            _confInput.style.webkitTextSecurity = "disc";
            _confInput.type = "password";
            icon.className = "fa-solid fa-eye";
        }
    });
}