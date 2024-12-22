<?php
require_once('config.php');
require_once('constants.php');
require_once('debug.php');  // Agregar esta línea


function conexion() {
    debugLog("Iniciando conexión a la base de datos");
    debugLog("Parámetros de conexión", [
        'host' => DB_HOST,
        'user' => DB_USER,
        'database' => DB_NAME,
        'port' => DB_PORT
    ]);

    try {
        $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

        if ($conexion->connect_error) {
            debugLog("Error de conexión a la base de datos", $conexion->connect_error);
            error_log("Error de conexión a la base de datos: " . $conexion->connect_error);
            die("Connection failed: " . $conexion->connect_error);
        }

        debugLog("Conexión establecida exitosamente");
        
        // Configurar charset
        $conexion->set_charset("utf8");
        debugLog("Charset configurado a UTF-8");

        return $conexion;
    } catch (Exception $e) {
        debugLog("Excepción capturada en conexión", [
            'mensaje' => $e->getMessage(),
            'archivo' => $e->getFile(),
            'línea' => $e->getLine()
        ]);
        error_log("Error al crear la conexión: " . $e->getMessage());
        die("Error de conexión a la base de datos");
    }
}
?>