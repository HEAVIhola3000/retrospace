//elementos que utilizara el buscador
const FormularioBusqueda = document.querySelector("#FormularioBusqueda");
const BuscarAmigo = document.querySelector("#BuscarAmigo");
const ResultadosBusqueda = document.querySelector("#ResultadosBusqueda");

//Usuarios registrados de ejemploss
const UsuariosRegistrados = [
    "AmigoEjem1",
    "AmigoEjem2",
    "UsuarioLuna",
    "PixelBoy",
    "NekoStar",
    "RetroFan",
    "EstrellaAzul"
];

//pa q el formulario no recargue la paginaasss
FormularioBusqueda.addEventListener("submit", function(event){
    event.preventDefault();
    mostrarResultados();
})

//Buscar kbrones mientras se escribe la wea
BuscarAmigo.addEventListener("input", mostrarResultados);

//funcion para mostrar todxs los resultadozzz
function mostrarResultados(){
    const textoBusqueda = BuscarAmigo.value.toLowerCase().trim();
    //obtener los amigos actuales chiaa
    const AmigosActuales = document.querySelectorAll("#ContenedorMisAmigos .Amigo");

    const NombresAmigos = [];

    AmigosActuales.forEach(function (Amigo){
        const nombre = Amigo.querySelector("p").textContent.toLowerCase();
        NombresAmigos.push(nombre);
    });

    //limpiar basura de busquedaalv
    ResultadosBusqueda.innerHTML = "";
    //no mostrar ni una mrda si el buscador esta vacio y mostrar el usuario solo si coinciden 3 caracteres
    if(textoBusqueda.length <3){
        return;
    }

    //Buscar a la pipol q coincida pero q no sean amigos 
    const UsuariosEncontrados = UsuariosRegistrados.filter(function(usuario){
        const nombre = usuario.toLowerCase();
        return nombre.includes(textoBusqueda);
    });

    //Mostrar los usuarios encontrados
    if(UsuariosEncontrados.length ===0){
        ResultadosBusqueda.textContent = "No se encontraron personas.";
    } else {
        UsuariosEncontrados.forEach(function(usuario){
            const resultado = document.createElement("div");
            resultado.classList.add("resultadoUsuario");

            const nombreUsuario = document.createElement("p");
            nombreUsuario.textContent = usuario;

            resultado.appendChild(nombreUsuario);
            ResultadosBusqueda.appendChild(resultado);
        });
    }
}

//solicitudes PA
//elementos de las silicitudes chad
const Solicitudes = document.querySelectorAll(".solicitud");
const CantidadSolicitudes = document.querySelector("#CantidadSolicitudes");

//funcion para actualizar el holychit contadohsasadgda
function actualizarContador(){
    const solicitudesPendientes = document.querySelectorAll(".solicitud").length;

    CantidadSolicitudes.textContent = "Tienes "+ solicitudesPendientes + " solicitudes pendientes";
}

// correr todas las colicitudes 
Solicitudes.forEach(function(solicitud){
    const BotonAceptar = solicitud.querySelector(".AceptarSolicitud");
    const BotonRechazar = solicitud.querySelector(".RechazarSolicitud");

    //aceptar solicitud
    BotonAceptar.addEventListener("click", function(){
        const nombre = solicitud.querySelector("p").textContent;
        //crear nuevo amigui
        const nuevoAmigo = document.createElement("div");
        nuevoAmigo.classList.add("Amigo");

        const foto = document.createElement("img");
        foto.src = solicitud.querySelector("img").src;
        foto.alt = "Foto de usuario";

        const nombreAmigo = document.createElement("p");
        nombreAmigo.textContent = nombre;

        const BotonVerPerfil = document.createElement("button");
        BotonVerPerfil.type = "button";
        BotonVerPerfil.textContent = "Ver perfil";
        BotonVerPerfil.addEventListener("click", function(){
            const nombre = nombreAmigo.textContent;
            window.location.href = "perfil-amigo.html?usuario="+ encodeURIComponent(nombre);
        });
//unir todos los elementis
        nuevoAmigo.appendChild(foto);
        nuevoAmigo.appendChild(nombreAmigo);
        nuevoAmigo.appendChild(BotonVerPerfil);

        document.querySelector("#ContenedorMisAmigos").appendChild(nuevoAmigo);
        //eliminar solicitud
        solicitud.remove();

        actualizarContador();
        });
 //rechazar solicitud
 BotonRechazar.addEventListener("click", function(){
    solicitud.remove();
    actualizarContador();
 });
});