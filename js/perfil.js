const BotonUsuario = document.querySelector("#BotonUsuario");
const NombreUsuario = document.querySelector("#NombreUsuario");
const ArtistaFavorito = document.querySelector("#ArtistaFavorito");
const BotonArtista = document.querySelector("#BotonArtista");
const CancionFavorita = document.querySelector("#CancionFavorita");
const BotonCancion = document.querySelector("#BotonCancion");

const formEditarPerfil = document.querySelector("#FormEditarPerfil");
const inputNombreUsuario = document.querySelector("#InputNombreUsuario");
const inputArtistaFavorito = document.querySelector("#InputArtistaFavorito");
const inputCancionFavorita = document.querySelector("#InputCancionFavorita");

function mostrarGuardarPerfil() {
    if (formEditarPerfil) {
        formEditarPerfil.style.display = "block";
    }
}

if (BotonUsuario) {
    BotonUsuario.addEventListener("click", function () {
        const nuevoUsuario = prompt("Escribe tu nuevo nombre de usuario:", NombreUsuario.textContent.trim());
        if (nuevoUsuario !== null && nuevoUsuario.trim() !== "") {
            const limpio = nuevoUsuario.trim();
            NombreUsuario.textContent = limpio;
            if (inputNombreUsuario) inputNombreUsuario.value = limpio;
            mostrarGuardarPerfil();
        }
    });
}

if (BotonArtista) {
    BotonArtista.addEventListener("click", function () {
        const nuevoArtista = prompt("Escribe tu nuevo Artista favorito:", ArtistaFavorito.textContent.trim());
        if (nuevoArtista !== null && nuevoArtista.trim() !== "") {
            const limpio = nuevoArtista.trim();
            ArtistaFavorito.textContent = limpio;
            if (inputArtistaFavorito) inputArtistaFavorito.value = limpio;
            mostrarGuardarPerfil();
        }
    });
}

if (BotonCancion) {
    BotonCancion.addEventListener("click", function () {
        const nuevaCancion = prompt("Escribe tu nueva cancion favorita:", CancionFavorita.textContent.trim());
        if (nuevaCancion !== null && nuevaCancion.trim() !== "") {
            const limpio = nuevaCancion.trim();
            CancionFavorita.textContent = limpio;
            if (inputCancionFavorita) inputCancionFavorita.value = limpio;
            mostrarGuardarPerfil();
        }
    });
}

// Subir foto de perfil al servidor
const FotoPerfil = document.querySelector("#FotoPerfil");
const CambiarFoto = document.querySelector("#CambiarFoto");
const ArchivoFoto = document.querySelector("#ArchivoFoto");
const FormFotoPerfil = document.querySelector("#FormFotoPerfil");

if (CambiarFoto && ArchivoFoto) {
    CambiarFoto.addEventListener("click", function () {
        ArchivoFoto.click();
    });
}

if (ArchivoFoto) {
    ArchivoFoto.addEventListener("change", function () {
        const archivo = ArchivoFoto.files[0];
        if (!archivo) return;

        if (!archivo.type.startsWith("image/")) {
            ArchivoFoto.value = "";
            return;
        }

        if (FotoPerfil) {
            FotoPerfil.src = URL.createObjectURL(archivo);
        }

        if (FormFotoPerfil) {
            FormFotoPerfil.submit();
        }
    });
}

// Función bonus
const bn = document.querySelector("#bonus");
const isBtn = document.querySelector("#insertar");
if (isBtn && bn) {
    isBtn.addEventListener("click", () => {
        bn.classList.toggle("solidSnake");
    });
}

function insertarBonus() {
    const txt = document.querySelector("#texto").value;
    const rs = document.querySelector("#resultado");
    const nuevaEdicion = document.createElement("div");
    nuevaEdicion.innerHTML = txt;
    rs.appendChild(nuevaEdicion);
}