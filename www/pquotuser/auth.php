<?php
session_start();
require_once('config.php');
require_once('constants.php');
require_once('connect.php');
require_once('validaciones.php');
require_once('debug.php');

debugLog("Iniciando proceso de autenticación");

if (isset($_POST['submit'])) {
    $usuario = $_POST['username'];
    $password = isset($_POST['password']) ? '[CONTRASEÑA_OCULTA]' : 'No proporcionada';
    
    debugLog("Intento de inicio de sesión", [
        'usuario' => $usuario,
        'servidor' => LDAP_SERVER,
        'puerto' => LDAP_PORT,
        'tipo' => USE_AD ? 'Active Directory' : 'LDAP'
    ]);
    
    $ds = ldap_connect(LDAP_SERVER, LDAP_PORT);
    
    if ($ds) {
        debugLog("Conexión LDAP establecida");
        
        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, LDAP_PROTOCOL_VERSION);
        ldap_set_option($ds, LDAP_OPT_REFERRALS, LDAP_REFERRALS);
        
        // Realizar bind inicial si es necesario
        if (LDAP_BIND_REQUIRED) {
            debugLog("Intentando bind administrativo");
            try {
                $bind_admin = ldap_bind($ds, LDAP_ADMIN_DN, LDAP_ADMIN_PASSWORD);
                if (!$bind_admin) {
                    debugLog("Error en bind administrativo", ldap_error($ds));
                    header("Location: login.php?error=500");
                    exit;
                }
                debugLog("Bind administrativo exitoso");
            } catch (Exception $e) {
                debugLog("Excepción en bind administrativo", $e->getMessage());
                header("Location: login.php?error=500");
                exit;
            }
        }
        
        // Definir filtro de búsqueda
        if (USE_AD) {
            $searchFilter = "(&(objectClass=user)(objectCategory=person)(sAMAccountName=$usuario))";
            $attributes = array("displayName", "sAMAccountName", "distinguishedName");
        } else {
            $searchFilter = "(uid=$usuario)";
            $attributes = array("cn", "uid");
        }

        debugLog("Parámetros de búsqueda", [
            'base_dn' => LDAP_BASE_DN,
            'filtro' => $searchFilter,
            'atributos' => $attributes
        ]);
        
        // Buscar usuario
        $search = ldap_search($ds, LDAP_BASE_DN, $searchFilter, $attributes, 0, 0, 0);
        if (!$search) {
            debugLog("Error en búsqueda LDAP", ldap_error($ds));
            header("Location: login.php?error=403");
            exit;
        }

        $entries = ldap_get_entries($ds, $search);
        debugLog("Resultados de búsqueda", [
            'cantidad' => $entries['count']
        ]);

        if ($entries['count'] == 0) {
            debugLog("Usuario no encontrado", $usuario);
            header("Location: login.php?error=403");
            exit;
        }

        // Obtener DN y nombre completo
        if (USE_AD) {
            $user_dn = $entries[0]['distinguishedname'][0];
            $fullname = isset($entries[0]['displayname'][0]) ? $entries[0]['displayname'][0] : $usuario;
        } else {
            $user_dn = $entries[0]['dn'];
            $fullname = isset($entries[0]['cn'][0]) ? $entries[0]['cn'][0] : $usuario;
        }

        debugLog("Información de usuario encontrada", [
            'dn' => $user_dn,
            'nombre_completo' => $fullname
        ]);
        
        try {
            $bind = @ldap_bind($ds, $user_dn, $_POST['password']);
            
            if ($bind) {
                debugLog("Autenticación exitosa", $usuario);
                
                // Establecer sesión
                $_SESSION['username'] = $usuario;
                $_SESSION['authenticated'] = true;
                $_SESSION['fullname'] = $fullname;
                
                if (isset($entries[0]['dn'])) {
                    $_SESSION['user_dn'] = $entries[0]['dn'];
                }

                debugLog("Sesión establecida", [
                    'username' => $usuario,
                    'fullname' => $fullname
                ]);

                // Actualizar información en base de datos
                $conexion = conexion();
                
                // Actualizar organización
                $updateQuery = "UPDATE " . TABLE_NAME . " SET organization = ? WHERE " . CLIENTE_IP . " = ?";
                if ($updateStmt = $conexion->prepare($updateQuery)) {
                    $updateStmt->bind_param("ss", $fullname, $usuario);
                    $updateStmt->execute();
                    $updateStmt->close();
                    debugLog("Organización actualizada");
                }

                // Verificar cuota
                $query = "SELECT * FROM " . TABLE_NAME . " WHERE " . CLIENTE_IP . "=?";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("s", $usuario);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    debugLog("Usuario tiene cuota asignada, redirigiendo a index.php");
                    header("Location: index.php");
                } else {
                    debugLog("Usuario sin cuota asignada");
                    header("Location: login.php?error=200");
                }
                exit;
            } else {
                debugLog("Fallo en autenticación", [
                    'usuario' => $usuario,
                    'error' => ldap_error($ds)
                ]);
                header("Location: login.php?error=403");
                exit;
            }
        } catch (Exception $e) {
            debugLog("Error en proceso de autenticación", $e->getMessage());
            header("Location: login.php?error=500");
            exit;
        } finally {
            ldap_close($ds);
            debugLog("Conexión LDAP cerrada");
        }
    } else {
        debugLog("Error conectando al servidor LDAP", ldap_error($ds));
        header("Location: login.php?error=500");
        exit;
    }
}

debugLog("Fin del proceso de autenticación");
?>