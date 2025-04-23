<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro Educativo</title>
    
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"> 

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="../css/chatbot.css">

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <header>
        <nav class="navbar-top">
            <div class="nav-container">
                <ul class="nav-links-left">
                    <li><a href="eventos">Eventos</a></li>
                    <li><a href="contacto">Contacto</a></li>
                </ul>
                <div class="logo">
                    <img src="../img/logo.png" alt="Logo Centro Educativo">
                </div>
                <div class="nav-links-right">
                    <a href="nosotros">Nosotros</a>
                    <a href="#" class="login-btn" onclick="abrirLoginModal()">Iniciar Sesión</a>
                </div>
            </div>
        </nav>
    </header>

    <script>
    function abrirLoginModal() {
        // Crear el contenedor del modal
        const modalContainer = document.createElement('div');
        modalContainer.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        `;

        // Cargar el contenido de login.php
        fetch('../login.php')
            .then(response => response.text())
            .then(html => {
                modalContainer.innerHTML = `
                    <div style="position: relative; background: white; padding: 20px; border-radius: 10px; width: 90%; max-width: 800px;">
                        <button onclick="cerrarLoginModal()" style="position: absolute; right: 10px; top: 10px; z-index: 1001; background: none; border: none; color: #000; font-size: 24px; cursor: pointer;">&times;</button>
                        ${html}
                    </div>
                `;
                document.body.appendChild(modalContainer);
                document.body.style.overflow = 'hidden';

                // Agregar evento de clic al contenedor modal
                modalContainer.addEventListener('click', (e) => {
                    // Si el clic fue directamente en el contenedor modal (fondo oscuro)
                    if (e.target === modalContainer) {
                        cerrarLoginModal();
                    }
                });
            });
    }

    function cerrarLoginModal() {
        const modal = document.querySelector('div[style*="position: fixed"]');
        if (modal) {
            modal.remove();
            document.body.style.overflow = 'auto';
        }
    }
    </script>

    <!-- Agregar los estilos del login -->
    <style>
    #loginModal .modal-content {
        background: transparent;
        border: none;
    }

    #loginModal .container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 80vh;
    }

    #loginModal .modal-dialog {
        max-width: 900px;
        margin: 1.75rem auto;
    }

    /* Importar los estilos del login original */
    @import url('../css/login_estilo.css');

    .container {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 2rem;
        padding: 20px;
    }

    .login-form {
        flex: 1;
        max-width: 400px;
    }

    .logo-section {
        flex: 1;
        max-width: 300px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .logo-section img {
        max-width: 100%;
        height: auto;
    }

    @media (max-width: 768px) {
        .container {
            flex-direction: column;
        }
        
        .logo-section {
            order: -1;
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.querySelector('.toggle-password');
        const passwordInput = document.querySelector('input[type="password"]');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
        });
    });
    </script>
</body>
</html> 