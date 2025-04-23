

<?php include 'includes/conexion.php'; ?>
<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="css/carousel.css">
<link rel="stylesheet" href="css/estilos.css">
<main>
    <div class="container-fluid p-0">
        <div id="header-carousel" class="carousel slide carousel-fade" data-ride="carousel">
            <ol class="carousel-indicators">
                <li data-target="#header-carousel" data-slide-to="0" class="active"></li>
                <li data-target="#header-carousel" data-slide-to="1"></li>
                <li data-target="#header-carousel" data-slide-to="2"></li>
            </ol>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img class="w-100" src="img/pagprincipal/slide1.jpg" alt="Imagen 1">
                    <div class="carousel-caption d-flex align-items-center justify-content-center">
                        <div class="p-5" style="width: 100%; max-width: 900px;">
                            <h5 class="text-white text-uppercase mb-md-3">Inscripcion abierta</h5>
                            <h1 class="display-3 text-white mb-md-4">La Mejor Educación al mejor precio</h1>
                            <a href="" class="btn btn-primary py-md-2 px-md-4 font-weight-semi-bold mt-2">Saber Más</a>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <img class="w-100" src="img/pagprincipal/slide2.jpg" alt="Imagen 2">
                    <div class="carousel-caption d-flex align-items-center justify-content-center">
                        <div class="p-5" style="width: 100%; max-width: 900px;">
                            <h5 class="text-white text-uppercase mb-md-3">Mejores Cursos</h5>
                            <h1 class="display-3 text-white mb-md-4">La Mejor Plataforma de Aprendizaje</h1>
                            <a href="" class="btn btn-primary py-md-2 px-md-4 font-weight-semi-bold mt-2">Saber Más</a>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <img class="w-100" src="img/pagprincipal/slide3.jpg" alt="Imagen 3">
                    <div class="carousel-caption d-flex align-items-center justify-content-center">
                        <div class="p-5" style="width: 100%; max-width: 900px;">
                            <h5 class="text-white text-uppercase mb-md-3">Mejores Cursos </h5>
                            <h1 class="display-3 text-white mb-md-4">Nueva Forma de Aprender</h1>
                            <a href="" class="btn btn-primary py-md-2 px-md-4 font-weight-semi-bold mt-2">Saber Más</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Botones laterales -->
<div class="botones-laterales">
    <div class="d-flex flex-column gap-2">
        <!-- Calendario Académico -->
        <a href="calendario.php" class="btn btn-light border d-flex align-items-center mb-2">
            <img src="img/iconos/calendario.png" alt="Calendario" style="width: 40px; margin-right: 10px;">
            <span class="text-success">Calendario<br>Académico</span>
        </a>

        <!-- Solicitud de Ingreso -->
        <a href="solicitud.php" class="btn btn-light border d-flex align-items-center mb-2">
            <img src="img/iconos/solicitud.png" alt="Solicitud" style="width: 40px; margin-right: 10px;">
            <span class="text-success">Solicitud</span>
        </a>

        <!-- Readmisión -->
        <a href="admision.php" class="btn btn-light border d-flex align-items-center mb-2">
            <img src="img/iconos/admision.png" alt="Readmisión" style="width: 40px; margin-right: 10px;">
            <span class="text-success">Admisión</span>
        </a>

        <!-- Plataforma de Aprendizaje -->
        <a href="plataforma.php" class="btn btn-light border d-flex align-items-center mb-2">
            <img src="img/iconos/plataforma.png" alt="Plataforma" style="width: 40px; margin-right: 10px;">
            <span class="text-success">Plataforma<br>Aprendizaje</span>
        </a>
    </div>
</div>

<footer>
    <div class="footer-content">
        <p>&copy; 2024 Centro Educativo. Todos los derechos reservados.</p>
    </div>
</footer>

<?php include 'includes/chatbot.php'; ?>



    </body>
</html>