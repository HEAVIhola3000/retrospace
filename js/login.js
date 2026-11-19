//verificacion iniciar sesion y redireccion temporal
const formularioI=document.querySelector("#FormularioInicioSesion");
const userL=document.querySelector("#Usuario");
const passL=document.querySelector("#Contraseña");
formularioI.addEventListener("submit",function(event){
let valid=true;
if(userL.value.trim()==="" || passL.value.trim()===""){
    event.preventDefault();
    alert("Por favor completa todos los campos");
    valid=false;
}
if(valid){
    event.preventDefault();
    window.location.href="perfil.html";
    return true;
} else{
    return false;
}
});
//mostrar contraseña
lockBtn=document.querySelectorAll('.lock');
lockBtn.forEach(Lock=> {
    Lock.addEventListener('click',()=>{
        event.preventDefault();
        Lock.classList.toggle('lock');
        if(!Lock.classList.contains('lock')){
            lockBtn.forEach(Lockk =>{
                Lockk.style.opacity='0.5';
            })
            passL.type='text';
        }else{
            lockBtn.forEach(Lockk =>{
                Lockk.style.opacity='1';
            })
            passL.type='password';
        }
    })
});