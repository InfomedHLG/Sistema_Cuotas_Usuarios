<?php
require_once('debug.php');

debugLog("Cargando validaciones.php");

function validaIp($ip){
    debugLog("Validando IP", $ip);
    
    $ipPattern = '^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$';
    if (preg_match('/'.$ipPattern.'/', $ip, $arr)){
        debugLog("IP coincide con el patrón", $arr);
        
        $biger = 0;
        $i = 0;
        while ((!$biger) && ($i<=count($arr)) ){
            if ($arr[$i] > 255){
                $biger = 1;
                debugLog("IP inválida: octeto mayor a 255", [
                    'octeto' => $arr[$i],
                    'posición' => $i
                ]);
                return false;
            }
            $i++;
        }
        debugLog("IP válida");
        return true;
    } else {
        debugLog("IP no coincide con el patrón");
        return false;
    }
}

function validaCuota($quota){
    debugLog("Validando cuota", $quota);
    
    $idPattern = '^[0-9]+$';
    if (preg_match('/'.$idPattern.'/', $quota)){
        debugLog("Cuota válida");
        return true;
    } else {
        debugLog("Cuota inválida: no es un número positivo");
        return false;
    }
}

function estadoCuota($conexion, $usuario, &$quota = 0, &$used = 0, &$used_quota_24h = 0, &$nombre_institucion = '', &$error = '') {
    debugLog("Consultando estado de cuota", [
        'usuario' => $usuario
    ]);
    
    $query = 'SELECT ' . QUOTA . ',' . USED . ',' . USED_QUOTA_24H . ',' . ORGANIZATION . 
             ' FROM ' . TABLE_NAME . 
             ' WHERE ' . CLIENTE_IP . '=?';
    
    debugLog("Query a ejecutar", $query);
    
    $stmt = $conexion->prepare($query);
    if (!$stmt) {
        debugLog("Error preparando la consulta", $conexion->error);
        return;
    }
    
    $stmt->bind_param("s", $usuario);
    $success = $stmt->execute();
    
    if (!$success) {
        debugLog("Error ejecutando la consulta", $stmt->error);
        $error = $stmt->error;
        $stmt->close();
        return;
    }
    
    $result = $stmt->get_result();
    
    if ($result) {
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            debugLog("Datos obtenidos", $row);
            
            $quota = $row[QUOTA];
            $used = $row[USED];
            $used_quota_24h = $row[USED_QUOTA_24H];
            $nombre_institucion = $row[ORGANIZATION];
            
            if (!$quota) {
                debugLog("Usuario sin cuota asignada");
                $error = '1';
            } else {
                debugLog("Datos de cuota procesados correctamente", [
                    'quota' => $quota,
                    'used' => $used,
                    'used_24h' => $used_quota_24h,
                    'institucion' => $nombre_institucion
                ]);
            }
        } else {
            debugLog("No se encontraron datos para el usuario");
            $error = '2';
        }
    } else {
        debugLog("Error en la consulta", $conexion->error);
        $error = $conexion->error;
    }
    
    $stmt->close();
    debugLog("Consulta finalizada", [
        'error' => $error
    ]);
}

debugLog("validaciones.php cargado completamente");
?>