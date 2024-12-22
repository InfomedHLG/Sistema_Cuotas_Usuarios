<?php
require_once('debug.php');

debugLog("Iniciando header.php");

// Verificar si el usuario está autenticado
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    debugLog("Usuario no autenticado, redirigiendo a login.php");
    header("Location: login.php");
    exit;
}

debugLog("Usuario autenticado", [
    'username' => $_SESSION['username'],
    'fullname' => $_SESSION['fullname'] ?? 'No disponible'
]);

debugLog("Variables de estado", [
    'error' => $error ?? 'No definido',
    'utilizacion' => $utilizacion ?? 'No definido',
    'CuotaAsignada' => $CuotaAsignada ?? 'No definido',
    'unidadCuotaAsignada' => $unidadCuotaAsignada ?? 'No definido',
    'ConsumoUser' => $ConsumoUser ?? 'No definido',
    'unidadConsumoUser' => $unidadConsumoUser ?? 'No definido',
    'disponibilidad' => $disponibilidad ?? 'No definido'
]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Cuota CPICM Infomed - Holguín</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/fonts.css">
    <script src="assets/js/tailwindcss.js"></script>

</head>
<body>
    <?php debugLog("Iniciando renderizado del cuerpo de la página"); ?>
    <div class="page-wrapper">
        <header class="main-header">
            <div class="header-container">
                <div class="logo-container">
                    <img src="assets/images/logoinfomed.png" alt="Logo Infomed" class="logo-image">
                </div>
                
                <div class="header-title">
                    <h1>Sistema de Cuota CPICM </h1>
                    <p class="subtitle">Infomed Holguín</p>
                </div>
            </div>
        </header>

        <main class="main-content">
            <?php if ($error == '1'): ?>
                <?php debugLog("Mostrando vista de usuario sin cuota"); ?>
                <div class="status-card">
                    <div class="institution-info">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800 mb-1">
                                    <?php echo isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : ''; ?>
                                </h2>
                                <p class="text-sm text-gray-600 italic font-semibold">
                                    (usuario: <?php echo htmlspecialchars($_SESSION['username']); ?>)
                                </p>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <a href="detalles.php?usuario=<?php echo urlencode($_SESSION['username']); ?>" 
                                   class="inline-flex items-center px-3 py-1 text-sm text-blue-600 hover:text-blue-800 font-medium rounded hover:bg-blue-50 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Ver Detalles
                                </a>
                                <a href="logout.php" class="inline-flex items-center px-3 py-1 text-sm text-red-600 hover:text-red-800 font-medium rounded hover:bg-red-50 transition-colors duration-200">
                                    Cerrar Sesión
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="quota-info">
                        <div class="quota-text">
                            Usted no cuenta con cuota de internet, pero ha consumido 
                            <span class="highlight"><?php echo $ConsumoUser; ?> <?php echo $unidadConsumoUser; ?></span>
                        </div>

                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-label">Disponibilidad:</span>
                                <span class="stat-value" id="disponibilidad"><?php echo $disponibilidad; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Consumo:</span>
                                <span class="stat-value" id="consumo"><?php echo $ConsumoUser; ?> <?php echo $unidadConsumoUser; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error == '2'): ?>
                <?php debugLog("Mostrando vista de servicio no disponible"); ?>
                <div class="status-card">
                    <div class="institution-info">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800 mb-1">
                                    <?php echo isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : ''; ?>
                                </h2>
                                <p class="text-sm text-gray-600 italic font-semibold">
                                    (usuario: <?php echo htmlspecialchars($_SESSION['username']); ?>)
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="logout.php" class="inline-flex items-center px-3 py-1 text-sm text-red-600 hover:text-red-800 font-medium rounded hover:bg-red-50 transition-colors duration-200">
                                    Cerrar Sesión
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="quota-info">
                        <div class="alert alert-danger" style="text-align: center; padding: 30px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                            <div class="alert-content">
                                <i class="alert-icon" style="font-size: 3em; margin-bottom: 15px; display: block;">🚫</i>
                                <h3 style="font-size: 1.5em; margin-bottom: 15px; color: #d32f2f; font-weight: bold;">Servicio No Disponible</h3>
                                <p style="font-size: 1.1em; margin-bottom: 15px; color: #333;">
                                    Lo sentimos, este usuario no tiene acceso al servicio de Internet.
                                </p>
                                <div style="width: 50px; height: 2px; background: #d32f2f; margin: 20px auto;"></div>
                                <p style="font-size: 0.95em; color: #666; line-height: 1.5;">
                                    Si considera que esto es un error, por favor contacte al<br>
                                    administrador del sistema para obtener asistencia.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error == ''): ?>
                <?php debugLog("Mostrando vista normal de cuota"); ?>
                <div class="status-card">
                    <div class="institution-info">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800 mb-1">
                                    <?php echo isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : ''; ?>
                                </h2>
                                <p class="text-sm text-gray-600 italic font-semibold">
                                    (Usuario: <?php echo htmlspecialchars($_SESSION['username']); ?>)
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="detalles.php?usuario=<?php echo urlencode($_SESSION['username']); ?>" 
                                   class="inline-flex items-center px-3 py-1 text-sm text-blue-600 hover:text-blue-800 font-medium rounded hover:bg-blue-50 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Ver Detalles
                                </a>
                                <a href="logout.php" class="inline-flex items-center px-3 py-1 text-sm text-red-600 hover:text-red-800 font-medium rounded hover:bg-red-50 transition-colors duration-200">
                                    Cerrar Sesión
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="quota-info">
                        <div class="quota-text">
                            Usted ha consumido el 
                            <span class="percentage" id="utilizacion"><?php echo round($utilizacion, 1); ?>%</span> 
                            de su cuota asignada de 
                            <span class="quota" id="cuota-asignada"><?php echo $CuotaAsignada; ?> <?php echo $unidadCuotaAsignada; ?></span>
                        </div>

                        <div class="progress-container">
                            <div class="progress-bar" 
                                style="width:<?php echo min($utilizacion, 100); ?>%; 
                                       background-color: <?php echo getColorByPercentage($utilizacion); ?>;
                                       border-radius: 10px;"
                                data-progress="<?php echo round($utilizacion, 1); ?>">
                            </div>
                        </div>

                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-label">Disponibilidad:</span>
                                <span class="stat-value" id="disponibilidad"><?php echo $disponibilidad; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Consumo:</span>
                                <span class="stat-value" id="consumo"><?php echo $ConsumoUser; ?> <?php echo $unidadConsumoUser; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <?php debugLog("Finalizado renderizado del header"); ?>
</body>
</html>