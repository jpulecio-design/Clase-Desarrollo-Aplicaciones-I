<?php
/* ============================================================
   enviarmail.php
   Recibe los datos del formulario "Amigos de Flash Gordon"
   que está en cascada.html (method="post")
   ============================================================ */

// Correo que recibirá los registros (cámbialo si quieres)
$correoDestino = 'jpulecio@unimayor.edu.co';

// Carpeta donde se guardarán los archivos subidos (créala si no existe)
$carpetaSubidas = __DIR__ . '/uploads/';

// 1. Solo aceptamos envíos por POST (si alguien abre el archivo directo, lo devolvemos)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cascada.html');
    exit;
}

// Función para mostrar texto en el HTML de forma segura (evita inyectar código)
function e($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// 2. Leemos cada campo usando su atributo "name"
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$mail = trim($_POST['mail'] ?? '');
$password = $_POST['password'] ?? '';
$telefono = trim($_POST['telefono'] ?? '');
$sitio = trim($_POST['sitio'] ?? '');
$nacimiento = trim($_POST['nacimiento'] ?? '');
$buscar = trim($_POST['buscar'] ?? '');
$genero = $_POST['genero'] ?? '';
$personaje = $_POST['personaje'] ?? '';
$adiccion = $_POST['adiccion'] ?? '';
$color = $_POST['color_preferido'] ?? '';
$comics = $_POST['comics'] ?? '';
$origen = trim($_POST['origen'] ?? '');

// Checkboxes (si no se marcan, no llegan; los convertimos en array vacío)
$intereses = [
    'video' => 'Videojuegos',
    'series' => 'Series',
    'peliculas' => 'Películas',
    'deporte' => 'Deporte',
    'musica' => 'Música',
    'tecnologia' => 'Tecnología',
];
$interesesMarcados = [];
foreach ($intereses as $clave => $etiqueta) {
    if (!empty($_POST[$clave])) {
        $interesesMarcados[] = $etiqueta;
    }
}

// Valores permitidos para los radio button
$personajes = [
    'flash' => 'Flash Gordon',
    'ming' => 'Ming, el despiadado',
    'aura' => 'La Princesa Aura',
    'vultan' => 'Príncipe Vultan',
    'dale' => 'Dale Arden',
];

$generos = [
    'M' => 'Masculino',
    'F' => 'Femenino',
    'T' => 'Transgénero',
    'NS' => 'Prefiere no decir',
];

// 3. Validamos en el servidor (HTML5 valida en el navegador, pero se puede saltar)
$errores = [];

if ($nombre === '') {
    $errores[] = 'El nombre es obligatorio.';
}
if ($apellido === '') {
    $errores[] = 'El apellido es obligatorio.';
}
if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El correo electrónico no es válido.';
}
if ($password === '') {
    $errores[] = 'La contraseña es obligatoria.';
}
if ($telefono !== '' && !preg_match('/^[0-9+\s()-]{7,20}$/', $telefono)) {
    $errores[] = 'El teléfono no tiene un formato válido.';
}
if ($sitio !== '' && !filter_var($sitio, FILTER_VALIDATE_URL)) {
    $errores[] = 'El sitio web no es una URL válida.';
}
if ($nacimiento !== '') {
    $fecha = DateTime::createFromFormat('Y-m-d', $nacimiento);
    if (!$fecha || $fecha->format('Y-m-d') !== $nacimiento) {
        $errores[] = 'La fecha de nacimiento no es válida.';
    }
}
if (!array_key_exists($genero, $generos)) {
    $errores[] = 'Selecciona un género.';
}
if (!array_key_exists($personaje, $personajes)) {
    $errores[] = 'Selecciona un personaje de la lista.';
}
if (filter_var($adiccion, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 7]]) === false) {
    $errores[] = 'Los cómics por semana deben estar entre 1 y 7.';
}
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
    $errores[] = 'El color no es válido.';
}
if (filter_var($comics, FILTER_VALIDATE_INT, ['options' => ['min_range' => 100, 'max_range' => 9000]]) === false) {
    $errores[] = 'Los cómics diarios deben estar entre 100 y 9000.';
}

// 4. Procesamos el archivo subido (si hay)
$archivoGuardado = '';
if (!empty($_FILES['archivo']['name']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    // Validamos que sea una imagen real
    $infoImagen = @getimagesize($_FILES['archivo']['tmp_name']);
    if ($infoImagen === false) {
        $errores[] = 'El archivo subido no es una imagen válida.';
    } else {
        // Creamos la carpeta si no existe
        if (!is_dir($carpetaSubidas)) {
            @mkdir($carpetaSubidas, 0777, true);
        }

        // Nombre único para evitar sobreescribir
        $extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
        $nombreArchivo = 'subida_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $rutaDestino = $carpetaSubidas . $nombreArchivo;

        if (move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaDestino)) {
            $archivoGuardado = $nombreArchivo;
        } else {
            $errores[] = 'No se pudo guardar el archivo subido.';
        }
    }
}

// 5. Si todo está bien, intentamos enviar el correo
$correoEnviado = false;
if (empty($errores)) {
    // Quitamos saltos de línea del nombre para que nadie meta cabeceras falsas
    $nombreLimpio = str_replace(["\r", "\n"], ' ', $nombre);

    $asunto = 'Nuevo registro - Amigos de Flash Gordon';

    $mensaje = "Nuevo registro en el club:\n\n"
        . "Nombre: $nombreLimpio $apellido\n"
        . "Correo: $mail\n"
        . "Teléfono: " . ($telefono ?: 'No indicado') . "\n"
        . "Sitio web: " . ($sitio ?: 'No indicado') . "\n"
        . "Fecha de nacimiento: " . ($nacimiento ?: 'No indicada') . "\n"
        . "Búsqueda interna: " . ($buscar ?: 'Sin búsqueda') . "\n"
        . "Género: {$generos[$genero]}\n"
        . "Personaje preferido: {$personajes[$personaje]}\n"
        . "Intereses: " . (empty($interesesMarcados) ? 'Ninguno' : implode(', ', $interesesMarcados)) . "\n"
        . "Cómics por semana: $adiccion\n"
        . "Cómics diarios: $comics\n"
        . "Color de las mayas: $color\n"
        . "Archivo subido: " . ($archivoGuardado ?: 'Ninguno') . "\n"
        . "Origen: " . ($origen ?: 'No especificado') . "\n";
    // La contraseña NO se envía por correo (por seguridad)

    $cabeceras = "From: Tutor Web <no-responder@unimayor.edu.co>\r\n"
        . "Reply-To: $mail\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";

    // El @ evita que salga un warning si el servidor no tiene correo configurado (XAMPP)
    $correoEnviado = @mail($correoDestino, $asunto, $mensaje, $cabeceras);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tutor HTML5 Registro</title>
    <link rel="shortcut icon" href="../imagenes/favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <link rel="stylesheet" href="css/estilo2.css">
</head>

<body>

    <header>
        <div id="carouselExampleAutoplaying" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="../imagenes/banner-clase--amarillo-31-08-26.png" class="d-block w-100" alt="...">
                </div>
                <div class="carousel-item">
                    <img src="../imagenes/banner-clase-31-08-26.png" class="d-block w-100" alt="...">
                </div>
                <div class="carousel-item">
                    <img src="../imagenes/banner-clase-azul-31-08-26.png" class="d-block w-100" alt="...">
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleAutoplaying"
                data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleAutoplaying"
                data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </header>

    <nav>
        <ul>
            <li><a href="home.html"> Inicio </a> </li>
            <li><a href="conceptos.html"> Conceptos</a></li>
            <li><a href="apis.html"> Api </a></li>
            <li><a href="frontend.html"> Frontend </a></li>
            <li><a href="backend.html"> Backend </a></li>
            <li><a href="cascada.html"> CSS </a></li>
            <li><a href="datos.html"> Presentacion </a></li>
        </ul>
    </nav>

    <section class="container my-4" style="max-width: 800px;">

        <?php if (!empty($errores)): ?>

            <!-- ============ HAY ERRORES ============ -->
            <h2 class="text-center">No se pudo completar el registro</h2>
            <div class="alert alert-warning">
                <strong>Revisa lo siguiente:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errores as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="text-center">
                <a href="javascript:history.back()" class="btn btn-primary">Volver al formulario</a>
            </div>

        <?php else: ?>

            <!-- ============ REGISTRO CORRECTO ============ -->
            <h2 class="text-center">¡Bienvenido al club, <?= e($nombre . ' ' . $apellido) ?>!</h2>

            <?php if ($correoEnviado): ?>
                <div class="alert alert-success">
                    Tus datos se enviaron correctamente por correo.
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Los datos llegaron bien al servidor, pero el correo no se pudo enviar.
                    Esto es normal si estás trabajando en <strong>localhost (XAMPP / Laragon)</strong>,
                    porque no tienen un servidor de correo configurado.
                </div>
            <?php endif; ?>

            <h3>Datos recibidos</h3>
            <table class="table table-bordered mb-4">
                <tr>
                    <th style="width: 40%;">Nombre completo</th>
                    <td><?= e($nombre . ' ' . $apellido) ?></td>
                </tr>
                <tr>
                    <th>Correo electrónico</th>
                    <td><?= e($mail) ?></td>
                </tr>
                <tr>
                    <th>Contraseña</th>
                    <td><?= str_repeat('•', min(strlen($password), 12)) ?>
                        <span class="small">(no se muestra por seguridad)</span>
                    </td>
                </tr>
                <tr>
                    <th>Teléfono</th>
                    <td><?= $telefono !== '' ? e($telefono) : 'No indicado' ?></td>
                </tr>
                <tr>
                    <th>Sitio web</th>
                    <td>
                        <?php if ($sitio !== ''): ?>
                            <a href="<?= e($sitio) ?>" target="_blank"><?= e($sitio) ?></a>
                        <?php else: ?>
                            No indicado
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Fecha de nacimiento</th>
                    <td><?= $nacimiento !== '' ? e($nacimiento) : 'No indicada' ?></td>
                </tr>
                <tr>
                    <th>Búsqueda interna</th>
                    <td><?= $buscar !== '' ? e($buscar) : 'Sin búsqueda' ?></td>
                </tr>
                <tr>
                    <th>Género</th>
                    <td><?= e($generos[$genero]) ?></td>
                </tr>
                <tr>
                    <th>Personaje preferido</th>
                    <td><?= e($personajes[$personaje]) ?></td>
                </tr>
                <tr>
                    <th>Información de interés</th>
                    <td>
                        <?= empty($interesesMarcados) ? 'Ninguno' : e(implode(', ', $interesesMarcados)) ?>
                    </td>
                </tr>
                <tr>
                    <th>Cómics por semana</th>
                    <td><?= e($adiccion) ?></td>
                </tr>
                <tr>
                    <th>Cómics diarios</th>
                    <td><?= e($comics) ?></td>
                </tr>
                <tr>
                    <th>Color ideal de las mayas</th>
                    <td>
                        <span style="display:inline-block; width:22px; height:22px; border:1px solid #F5F5F5;
                              border-radius:4px; vertical-align:middle; background-color:<?= e($color) ?>;"></span>
                        <?= e($color) ?>
                    </td>
                </tr>
                <tr>
                    <th>Archivo subido</th>
                    <td>
                        <?php if ($archivoGuardado !== ''): ?>
                            <a href="uploads/<?= e($archivoGuardado) ?>" target="_blank">
                                <img src="uploads/<?= e($archivoGuardado) ?>" alt="Imagen subida"
                                    style="max-width: 200px; border-radius: 8px;">
                            </a>
                        <?php else: ?>
                            Ninguno
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Origen (campo oculto)</th>
                    <td><?= $origen !== '' ? e($origen) : 'No especificado' ?></td>
                </tr>
            </table>

            <div class="text-center">
                <a href="cascada.html" class="btn btn-primary">Volver a la página de CSS</a>
            </div>

        <?php endif; ?>

    </section>

    <aside></aside>
    <footer>
        <h4> Correo de contacto </h4>
        <a href="mailto:jpulecio@unimayor.edu.co"> Correo pule </a>
        <h3> Fuera de nuestro sistema </h3>
        <a href="https://www.unimayor.edu.co"> Mi universidad </a>
    </footer>

</body>

</html>