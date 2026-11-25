const formulario = document.querySelector("#FormularioCreaCuenta");
const inputName = document.querySelector("#NombreUsuario");
const inputMail = document.querySelector("#Correo");
const inputPassword = document.querySelector("#Contraseña");
const confirmPasswword = document.querySelector("#ConfirmarContraseña");

formulario.addEventListener("submit", function (event) {
    let valid = true;

    if (inputName.value.trim() === "" || inputMail.value.trim() === "" || inputPassword.value.trim() === "") {
        alert("Por favor completa todos los campos");
        valid = false;
    } else if (confirmPasswword.value.trim() === "") {
        alert("Por favor confirma tu contraseña");
        valid = false;
    } else if (inputPassword.value !== confirmPasswword.value) {
        alert("Las contraseñas no coinciden");
        valid = false;
    }

    if (!valid) {
        event.preventDefault();
    }
});

// mostrar contraseña
const lockBtn = document.querySelectorAll(".lock");
lockBtn.forEach(Lock => {
    Lock.addEventListener("click", (event) => {
        event.preventDefault();
        Lock.classList.toggle("lock");
        if (!Lock.classList.contains("lock")) {
            lockBtn.forEach(Lockk => {
                Lockk.style.opacity = "0.5";
            });
            inputPassword.type = "text";
            confirmPasswword.type = "text";
        } else {
            lockBtn.forEach(Lockk => {
                Lockk.style.opacity = "1";
            });
            inputPassword.type = "password";
            confirmPasswword.type = "password";
        }
    });
});