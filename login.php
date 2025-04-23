<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso al Sistema Escolar</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            display: flex;
            width: 850px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .login-form {
            flex: 1;
            padding: 40px;
        }
        .logo-section {
            flex: 1;
            background-color: #3f51b5;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .logo-section img {
            max-width: 90%;
            height: auto;
        }
        h1 {
            color: #3f51b5;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .password-field {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
            user-select: none;
        }
        .radio-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .radio-group label {
            margin-right: 20px;
            margin-left: 5px;
            cursor: pointer;
            display: inline-block;
        }
        .radio-group input {
            cursor: pointer;
        }
        .btn-ingresar {
            width: 100%;
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-ingresar:hover {
            background-color: #45a049;
        }
        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }
        .forgot-password a {
            color: #3f51b5;
            text-decoration: none;
        }
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }
        .inicializar {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #3f51b5;
            text-decoration: none;
        }
        .inicializar:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h1>Ingreso al Sistema</h1>
            <?php
            session_start();
            if(isset($_SESSION['error'])) {
                echo '<div class="error-message">' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']);
            }
            ?>
            <form action="includes/procesar_login.php" method="POST">
                <div class="form-group">
                    <label>Correo:</label>
                    <input type="email" name="email" placeholder="Ingrese su correo electrónico" required>
                </div>
                <div class="form-group">
                    <label>Contraseña:</label>
                    <div class="password-field">
                        <input type="password" name="password" placeholder="Ingrese su contraseña" required>
                        <span class="toggle-password">👁️</span>
                    </div>
                </div>
                <div class="radio-group">
                    <input type="radio" id="estudiante" name="tipo" value="1" required>
                    <label for="estudiante">Estudiante</label>
                    <input type="radio" id="profesor" name="tipo" value="2">
                    <label for="profesor">Profesor</label>
                    <input type="radio" id="empleado" name="tipo" value="3">
                    <label for="empleado">Empleado</label>
                </div>
                <button type="submit" class="btn-ingresar">Ingresar</button>
                <div class="forgot-password">
                    <a href="#">¿Olvidó su contraseña?</a>
                </div>
            </form>
            
        </div>
        <div class="logo-section">
            <img src="img/logo.png" alt="Logo Centro Educativo" onerror="this.src='img/default-logo.png'">
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.querySelector('.toggle-password');
            const passwordField = document.querySelector('input[name="password"]');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                togglePassword.textContent = type === 'password' ? '👁️' : '🔒';
            });
        });
    </script>
</body>
</html> 