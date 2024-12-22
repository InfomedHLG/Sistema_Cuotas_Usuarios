<?php
session_start();
require_once('debug.php');
require_once('connect.php');
require_once('constants.php');

debugLog("Iniciando get_data.php");

// Verificar si el usuario está autenticado
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    debugLog("Usuario no autenticado, terminando");
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

// Obtener el usuario de la sesión
$usuario = $_SESSION['username'];
debugLog("Procesando datos para usuario", $usuario);

try {
    // Crear conexión
    debugLog("Estableciendo conexión a la base de datos");
    $conn = conexion();

    // Consulta SQL
    $sql = "SELECT * FROM " . TABLE_NAME . " WHERE " . CLIENTE_IP . " = ?";
    debugLog("Query a ejecutar", $sql);
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        debugLog("Datos encontrados para el usuario");
        $row = $result->fetch_assoc();
        debugLog("Datos obtenidos", $row);
        
        // Cálculos
        $quota = $row[QUOTA];
        $used = $row[USED];
        $used_24h = $row[USED_QUOTA_24H];
        
        debugLog("Valores base", [
            'quota' => $quota,
            'used' => $used,
            'used_24h' => $used_24h
        ]);
        
        // Calcular utilización y disponibilidad
        $utilizacion = ($quota > 0) ? ($used / $quota) * 100 : 0;
        debugLog("Utilización calculada", $utilizacion);
        
        // Formatear los valores
        $cuotaFormatted = formatBytesWithUnit($quota);
        $consumoFormatted = formatBytesWithUnit($used);
        $disponibilidadFormatted = formatBytesWithUnit(max(0, $quota - $used));
        $used24hFormatted = formatBytesWithUnit($used_24h);
        
        debugLog("Valores formateados", [
            'cuota' => $cuotaFormatted,
            'consumo' => $consumoFormatted,
            'disponibilidad' => $disponibilidadFormatted,
            'used24h' => $used24hFormatted
        ]);
        
        // Preparar respuesta
        $response = [
            'error' => '',
            'utilizacion' => round($utilizacion, 1),
            'CuotaAsignada' => $cuotaFormatted['value'],
            'unidadCuotaAsignada' => $cuotaFormatted['unit'],
            'disponibilidad' => $disponibilidadFormatted['value'] . ' ' . $disponibilidadFormatted['unit'],
            'ConsumoUser' => $consumoFormatted['value'],
            'unidadConsumoUser' => $consumoFormatted['unit'],
            'used_24h' => $used24hFormatted['value'],
            'unidad_24h' => $used24hFormatted['unit'],
            'fullname' => $_SESSION['fullname'],
            'username' => $_SESSION['username']
        ];
        
        debugLog("Respuesta preparada", $response);
    } else {
        debugLog("No se encontraron datos para el usuario");
        $response = [
            'error' => '2' // No tiene servicio de Internet
        ];
    }

} catch (Exception $e) {
    debugLog("Error en el proceso", [
        'mensaje' => $e->getMessage(),
        'archivo' => $e->getFile(),
        'línea' => $e->getLine()
    ]);
    
    $response = [
        'error' => 'Error en el servidor: ' . $e->getMessage()
    ];
} finally {
    if (isset($conn)) {
        debugLog("Cerrando conexión a la base de datos");
        $conn->close();
    }
}

// Función para formatear bytes
function formatBytesWithUnit($bytes, $precision = 2) {
    debugLog("Formateando bytes", $bytes);
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    $result = [
        'value' => round($bytes, $precision),
        'unit' => $units[$pow]
    ];
    
    debugLog("Resultado del formateo", $result);
    return $result;
}

// Devolver JSON
debugLog("Enviando respuesta JSON");
header('Content-Type: application/json');
echo json_encode($response);
?>