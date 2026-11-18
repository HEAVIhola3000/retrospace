// Obtener el usuario de la URL
const parametros = new URLSearchParams(window.location.search);

const nombreUsuario = parametros.get("usuario");

// Seleccionar el espacio del nombre
const NombreAmigo = document.querySelector("#NombreAmigo");

// Mostrar el nombre del amigo
if(nombreUsuario){
    NombreAmigo.textContent = nombreUsuario;
} else {
    NombreAmigo.textContent = "Usuario no encontrado";
}