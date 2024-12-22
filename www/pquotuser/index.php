<?php

session_start();
require_once('debug.php');

debugLog("Iniciando index.php");

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

require_once('validaciones.php');
require_once('connect.php');
require_once('functions.php');

$conexion = conexion();
$usuario = $_SESSION['username'];
$ip = $_SERVER['REMOTE_ADDR'];

debugLog("Datos de conexión", [
    'usuario' => $usuario,
    'ip' => $ip
]);

// Obtener los datos del usuario
estadoCuota($conexion, $usuario, $quota, $used, $used_quota_24h, $nombre_institucion, $error);

debugLog("Datos iniciales obtenidos", [
    'quota' => $quota,
    'used' => $used,
    'used_quota_24h' => $used_quota_24h,
    'nombre_institucion' => $nombre_institucion,
    'error' => $error
]);

// Conversiones iniciales
$quota_new = round(($quota / 1024) / 1024, 2);
$used_new = round(($used / 1024) / 1024, 2);

debugLog("Conversiones iniciales", [
    'quota_new' => $quota_new,
    'used_new' => $used_new
]);

// Formateo de Cuota Asignada
$CuotaAsignada = round(($quota / 1024) / 1024, 2);

if ($CuotaAsignada >= 1024) {
    $CuotaAsignada = round($CuotaAsignada / 1024, 2);
    $unidadCuotaAsignada = 'GB';
} elseif ($CuotaAsignada >= 1) {
    $unidadCuotaAsignada = 'MB';
} else {
    $CuotaAsignada = round($CuotaAsignada * 1024, 2);
    $unidadCuotaAsignada = 'KB';
}

debugLog("Cuota Asignada formateada", [
    'valor' => $CuotaAsignada,
    'unidad' => $unidadCuotaAsignada
]);

// Formateo de Consumo
$ConsumoUser = round(($used / 1024) / 1024, 2);

if ($ConsumoUser >= 1024) {
    $ConsumoUser = round($ConsumoUser / 1024, 2);
    $unidadConsumoUser = 'GB';
} elseif ($ConsumoUser >= 1) {
    $unidadConsumoUser = 'MB';
} else {
    $ConsumoUser = round($ConsumoUser * 1024, 2);
    $unidadConsumoUser = 'KB';
}

debugLog("Consumo formateado", [
    'valor' => $ConsumoUser,
    'unidad' => $unidadConsumoUser
]);

// Formateo de consumo 24h
$used_24h = round(($used_quota_24h / 1024) / 1024, 2);

if ($used_24h >= 1024) {
    $used_24h = round($used_24h / 1024, 2);
    $unidad = 'GB';
} elseif ($used_24h >= 1) {
    $unidad = 'MB';
} else {
    $used_24h = round($used_24h * 1024, 2);
    $unidad = 'KB';
}

debugLog("Consumo 24h formateado", [
    'valor' => $used_24h,
    'unidad' => $unidad
]);

// Cálculo de disponibilidad
$disponibilidad = $quota_new - $used_new;

if ($disponibilidad <= 0) {
    $disponibilidad = '0';
} else {
    if ($disponibilidad >= 1024) {
        $disponibilidad = round($disponibilidad / 1024, 2) . ' GB';
    } elseif ($disponibilidad >= 1) {
        $disponibilidad = round($disponibilidad, 2) . ' MB';
    } else {
        $disponibilidad = round($disponibilidad * 1024, 2) . ' KB';
    }
}

debugLog("Disponibilidad calculada", $disponibilidad);

// Cálculo de utilización
$utilizacion = ($quota_new != 0) ? ($used_new * 100 / $quota_new) : 0;
$resto = 100 - $utilizacion;

if ($utilizacion >= 100) {
    $utilizacion = '100';
}

debugLog("Utilización calculada", [
    'utilizacion' => $utilizacion,
    'resto' => $resto
]);

debugLog("Incluyendo header.php");
include 'header.php';
?>






<script>
function getColorByPercentage(percentage) {
    if (percentage >= 90) return '#ef4444';      
    if (percentage >= 75) return '#f97316';      
    if (percentage >= 50) return '#eab308';      
    return '#22c55e';                            
}

document.addEventListener('DOMContentLoaded', function() {
    const autoUpdateEnabled = <?php echo AUTO_UPDATE_ENABLED ? 'true' : 'false'; ?>;
    const updateInterval = <?php echo AUTO_UPDATE_SECONDS; ?> * 1000;
    const debugMode = <?php echo DEBUG_MODE ? 'true' : 'false'; ?>;

    function debug(message, data = null) {
        if (debugMode) {
            if (data) {
                console.log(message, data);
            } else {
                console.log(message);
            }
        }
    }

    function updateProgress() {
        debug('Iniciando actualización de datos');
        fetch('get_data.php')
            .then(response => response.json())
            .then(data => {
                debug('Datos recibidos:', data);

                if (data.error === '') {
                    debug('Actualizando elementos en la página');
                    
                    // Actualizar porcentaje de utilización
                    const utilizacionElement = document.querySelector('.percentage');
                    if (utilizacionElement) {
                        utilizacionElement.textContent = data.utilizacion + '%';
                        debug('Utilización actualizada', data.utilizacion + '%');
                    }

                    // Actualizar cuota asignada
                    const cuotaElement = document.querySelector('.quota');
                    if (cuotaElement) {
                        cuotaElement.textContent = data.CuotaAsignada + ' ' + data.unidadCuotaAsignada;
                        debug('Cuota actualizada', data.CuotaAsignada + ' ' + data.unidadCuotaAsignada);
                    }

                    // Actualizar disponibilidad
                    const disponibilidadElement = document.querySelector('#disponibilidad');
                    if (disponibilidadElement) {
                        disponibilidadElement.textContent = data.disponibilidad;
                        debug('Disponibilidad actualizada', data.disponibilidad);
                    }

                    // Actualizar consumo
                    const consumoElement = document.querySelector('#consumo');
                    if (consumoElement) {
                        consumoElement.textContent = data.ConsumoUser + ' ' + data.unidadConsumoUser;
                        debug('Consumo actualizado', data.ConsumoUser + ' ' + data.unidadConsumoUser);
                    }

                    // Actualizar consumo 24h
                    const consumo24hElement = document.querySelector('#consumo24h');
                    if (consumo24hElement) {
                        consumo24hElement.textContent = data.used_24h + ' ' + data.unidad_24h;
                        debug('Consumo 24h actualizado', data.used_24h + ' ' + data.unidad_24h);
                    }

                    // Actualizar nombre completo
                    const fullnameElement = document.querySelector('#fullname');
                    if (fullnameElement) {
                        fullnameElement.textContent = data.fullname;
                        debug('Nombre completo actualizado', data.fullname);
                    }

                    // Actualizar barra de progreso
                    const progressBar = document.querySelector('.progress-bar');
                    if (progressBar) {
                        const utilizacion = Math.min(data.utilizacion, 100);
                        progressBar.style.width = utilizacion + '%';
                        progressBar.style.backgroundColor = getColorByPercentage(data.utilizacion);
                        progressBar.setAttribute('data-progress', data.utilizacion);
                        debug('Barra de progreso actualizada', {
                            width: utilizacion + '%',
                            color: getColorByPercentage(data.utilizacion)
                        });
                    }
                }
            })
            .catch(error => {
                debug('Error al obtener los datos:', error);
            });
    }

    // Solo ejecutar si la actualización automática está habilitada
    if (autoUpdateEnabled && document.querySelector('.quota-info')) {
        debug('Iniciando actualización automática...');
        setInterval(updateProgress, updateInterval);
        updateProgress(); // Primera actualización
    } else {
        debug('Actualización automática deshabilitada o elemento no encontrado');
    }
});
</script>

<?php 
debugLog("Incluyendo footer.php");
include 'footer.php'; 
debugLog("Finalizado index.php");
?>