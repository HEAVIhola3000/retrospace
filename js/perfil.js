const BotonUsuario=document.querySelector("#BotonUsuario");
const NombreUsuario=document.querySelector("#NombreUsuario");
const ArtistaFavorito=document.querySelector("#ArtistaFavorito");
const BotonArtista=document.querySelector("#BotonArtista");
const CancionFavorita=document.querySelector("#CancionFavorita");
const BotonCancion=document.querySelector("#BotonCancion");
const BotonVerAmigos=document.querySelector("#BotonVerAmigos");

BotonUsuario.addEventListener("click",function(){
    let nuevoUsuario =prompt("Escribre tu nuevo nombre de usuario: ");
    if (nuevoUsuario !==null && nuevoUsuario.trim() !== ""){
        NombreUsuario.textContent=nuevoUsuario.trim();
    }
});

BotonArtista.addEventListener("click",function(){
    let nuevoArtista =prompt("Escribe tu nuevo Artista favorito: ");
    if(nuevoArtista !==null && nuevoArtista.trim() !==""){
       ArtistaFavorito.textContent=nuevoArtista.trim();
    }
});

BotonCancion.addEventListener("click",function(){
    let nuevaCancion =prompt("Escribe tu nueva cancion favorita: ");
    if (nuevaCancion !==null && nuevaCancion.trim() !== ""){
       CancionFavorita.textContent=nuevaCancion.trim();
    }
});

//crear publicaciones
const FormularioPublicacion=document.querySelector("#FormularioPublicacion");
const TextoPublicacion = document.querySelector("#TextoPublicacion");
const Muro = document.querySelector("#Muro");
const TituloPublicacion = document.querySelector("#TituloPublicacion");

FormularioPublicacion.addEventListener("submit", function(event){
    const tituloTexto = TituloPublicacion.value.trim();

    //para evitar q la pagina se recargue
    event.preventDefault();

    //se obtiene el texto esc5rito
    const contenido= TextoPublicacion.value.trim();

    //se comprueba que el texnbo no este vacio
    if (contenido === ""|| tituloTexto ==="") {
        return;
    }

    //crear elememento de la pucblicacion 
    const nuevaPublicacion=document.createElement("article");
    nuevaPublicacion.classList.add("publicacion");

    //crear el titulo
    const titulo=document.createElement("h3");
    titulo.textContent = tituloTexto;

    //crea el parrafo con el contenido
    const texto = document.createElement("p");
    texto.textContent = contenido;

    //crear espacio para los fukin comentarios
    const comentarios= document.createElement("div");
    comentarios.classList.add("listaComentarios");

    const tituloComentarios= document.createElement("p");
    tituloComentarios.textContent = "Comentarios";

    comentarios.appendChild(tituloComentarios);

    //crear formulario para los comentariosuwu

    const formularioComentario = document.createElement("form");

    const inputComentario = document.createElement("input");
    inputComentario.type = "text";
    inputComentario.placeholder = "Escribe un comentario...";
    inputComentario.required=true;

    const botonComentario = document.createElement("button");
    botonComentario.type = "submit";
    botonComentario.textContent = "Enviar";

    //agregar elementos al formmxdxdx
    formularioComentario.appendChild(inputComentario);
    formularioComentario.appendChild(botonComentario);

    //detectar cuando se envia un comentariouwuw
    formularioComentario.addEventListener("submit", function(event){
        event.preventDefault();
        const contenidoComentario = inputComentario.value.trim();

        if(contenidoComentario ===""){
            return;
        }
        // pos crear el comentario 
        const nuevoComentario = document.createElement("p");
        nuevoComentario.textContent = "chavi: " + contenidoComentario;

        //mostrar comentarioUWU en la publi
        comentarios.appendChild(nuevoComentario);

        //limpiar y resetear el formulario
        formularioComentario.reset();
    });
    //agregar el formulario
            comentarios.appendChild(formularioComentario);



    //unir todos los elementosuwu
    nuevaPublicacion.appendChild(titulo);
    nuevaPublicacion.appendChild(texto);
    nuevaPublicacion.appendChild(comentarios);

    //agregar la publi al muro osiosiosi
    Muro.appendChild(nuevaPublicacion);

    //limpiarrr el formulariozz
    FormularioPublicacion.reset();

});

//cambiar foto de perfil ouyeaosiosi
const FotoPerfil = document.querySelector("#FotoPerfil");
const CambiarFoto = document.querySelector("#CambiarFoto");
const ArchivoFoto = document.querySelector("#ArchivoFoto");

//pues para abrir el selector de imag4enes 
CambiarFoto.addEventListener("click", function(){
    ArchivoFoto.click();
});

//Detectar cuando se selecciona una imagen
ArchivoFoto.addEventListener("change", function(){
    const archivo = ArchivoFoto.files[0];
    if (!archivo){
        return;
    }
    //comprobar imagen
    if (!archivo.type.startsWith("image/")){
        ArchivoFoto.value = "";
        return;
    }

    //mostrar new fotoUWU
    FotoPerfil.src = URL.createObjectURL(archivo);
});

