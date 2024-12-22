<?php
session_start();
require_once('debug.php');

debugLog("Iniciando login.php");

// Si el usuario ya está autenticado, redirigir a index.php
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    debugLog("Usuario ya autenticado, redirigiendo a index.php", [
        'username' => $_SESSION['username'] ?? 'No disponible'
    ]);
    header("Location: index.php");
    exit;
}

// Verificar si hay error en la URL
if(isset($_GET['error'])) {
    debugLog("Error recibido en login", [
        'código_error' => $_GET['error']
    ]);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Cuota CPICM Infomed - Holguín</title>
    <link rel="stylesheet" href="assets/css/fonts.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>


<body>
    <?php debugLog("Iniciando renderizado del cuerpo"); ?>

   <div class="login-container">
        <div class="login-box">
            <img src="assets/images/logoinfomed.png" alt="Logo Infomed" class="login-logo">
            <h2>Sistema de Cuota CPICM</h2>
            
            <?php if(isset($_GET['error'])): ?>
                <?php debugLog("Mostrando mensaje de error"); ?>
                <div class="error-message">
                    <?php 
                        switch($_GET['error']) {
                            case '403':
                                echo "Usuario o contraseña incorrectos";
                                break;
                            case '200':
                                echo "No tiene cuota asignada";
                                break;
                            case '500':
                                echo "Error en el servidor";
                                break;
                            default:
                                echo "Error de autenticación";
                        }
                    ?>
                </div>
            <?php endif; ?>

            <form action="auth.php" method="POST">
                <?php debugLog("Renderizando formulario"); ?>
                <div class="input-group">
                    <input type="text" class="input" required name="username" id="username">
                    <label class="label" for="username">Usuario</label>
                </div>

                <div class="input-group">
                    <div class="password-container">
                        <input type="password" class="input input-password" required name="password" id="password">
                        <label class="label" for="password">Contraseña</label>
                        <button type="button" class="eye-button" id="togglePassword">
                        <object data="assets/images/eye.svg" type="image/svg+xml" class="eye-icon"></object>
                    </button>
                </div>
            </div>  
                <button type="submit" name="submit" class="login-button">
                    Iniciar Sesión
                </button>
            </form>
        </div>
    </div>

<script src="assets/js/login.js"></script>

<?php debugLog("Página de login completamente cargada"); ?>
</body>
</html>