
const formulario=document.querySelector("#FormularioCreaCuenta");
const inputName=document.querySelector("#NombreUsuario");
const inputMail=document.querySelector("#Correo");
const inputPassword=document.querySelector("#Contraseña");
const confirmPasswword=document.querySelector("#ConfirmarContraseña");
//verificacion de campos del formulario
formulario.addEventListener("submit",function(event){
    let valid=true;
    if(inputName.value.trim()==="" || inputMail.value.trim()==="" || inputPassword.value.trim()===""){
        event.preventDefault();
        alert("Por favor completa todos los campos");
        valid=false;
    }
    if(confirmPasswword.value.trim()===""){
        event.preventDefault();
        alert("Por favor confirma tu contraseña");
        valid=false;
    }
    if(inputPassword.value!==confirmPasswword.value){
        event.preventDefault();
        alert("Las contraseñas no coinciden");
        valid=false;
    }
    if(valid){
        event.preventDefault();
        alert("Gracias por registrarte");
        return true;
    } else{
        return false;
    }
});