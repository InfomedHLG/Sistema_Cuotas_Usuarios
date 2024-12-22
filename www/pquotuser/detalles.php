<?php
require_once('config.php');
require_once('validaciones.php');
require_once('connect.php');
require_once('debug.php');  // Agregar esta línea



debugLog("Iniciando página de detalles");

$conexion = conexion();
$usuario = isset($_GET['usuario']) ? $_GET['usuario'] : '';

debugLog("Usuario solicitado", $usuario);

// Validar usuario
if (empty($usuario)) {
    debugLog("Usuario vacío, redirigiendo a index.php");
    header('Location: index.php');
    exit;
}

// Obtener datos del usuario
$query = "SELECT * FROM " . TABLE_NAME . " WHERE " . CLIENTE_IP . "=?";
debugLog("Query a ejecutar", $query);

$stmt = $conexion->prepare($query);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    debugLog("No se encontraron resultados para el usuario", $usuario);
    header('Location: index.php');
    exit;
}

$userData = $result->fetch_assoc();
debugLog("Datos del usuario obtenidos", $userData);

// Función para determinar la unidad y convertir el valor
function formatearConsumo($bytes) {
    debugLog("Formateando consumo", $bytes);
    
    $gb = 1024 * 1024 * 1024;
    $mb = 1024 * 1024;
    $kb = 1024;

    if ($bytes >= $gb) {
        $resultado = [round($bytes / $gb, 2), 'GB'];
    } elseif ($bytes >= $mb) {
        $resultado = [round($bytes / $mb, 2), 'MB'];
    } else {
        $resultado = [round($bytes / $kb, 2), 'KB'];
    }

    debugLog("Resultado del formateo", $resultado);
    return $resultado;
}

// Formatear los consumos
debugLog("Formateando consumos");
list($used_24h, $unidad_24h) = formatearConsumo($userData[USED_QUOTA_24H]);
list($used_mensual, $unidad_mensual) = formatearConsumo($userData['used_quota_mensual']);
list($used_anual, $unidad_anual) = formatearConsumo($userData['used_quota_anual']);

debugLog("Consumos formateados", [
    '24h' => "$used_24h $unidad_24h",
    'mensual' => "$used_mensual $unidad_mensual",
    'anual' => "$used_anual $unidad_anual"
]);

// Función para obtener nombre completo del LDAP
function obtenerNombreCompleto($usuario) {
    debugLog("Obteniendo nombre completo para usuario", $usuario);
    
    try {
        debugLog("Conectando a " . (USE_AD ? 'Active Directory' : 'LDAP'), [
            'servidor' => LDAP_SERVER,
            'puerto' => LDAP_PORT
        ]);
        
        $ds = ldap_connect(LDAP_SERVER, LDAP_PORT);
        
        if ($ds) {
            debugLog("Conexión LDAP establecida");
            
            ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, LDAP_PROTOCOL_VERSION);
            ldap_set_option($ds, LDAP_OPT_REFERRALS, LDAP_REFERRALS);
            
            if (LDAP_BIND_REQUIRED) {
                try {
                    debugLog("Intentando bind administrativo");
                    $bind_admin = ldap_bind($ds, LDAP_ADMIN_DN, LDAP_ADMIN_PASSWORD);
                    if (!$bind_admin) {
                        throw new Exception("Fallo en bind: " . ldap_error($ds));
                    }
                    debugLog("Bind administrativo exitoso");
                } catch (Exception $e) {
                    debugLog("Error en bind administrativo", $e->getMessage());
                    return $usuario;
                }
            }
            
            // Configurar búsqueda según tipo de servidor
            if (USE_AD) {
                $searchFilter = "(&(objectClass=user)(objectCategory=person)(sAMAccountName=$usuario))";
                $attributes = array("displayName");
            } else {
                $searchFilter = "(uid=$usuario)";
                $attributes = array("cn");
            }
            
            debugLog("Parámetros de búsqueda", [
                'base_dn' => LDAP_BASE_DN,
                'filtro' => $searchFilter,
                'atributos' => $attributes
            ]);
            
            $search = ldap_search($ds, LDAP_BASE_DN, $searchFilter, $attributes, 0, 0, 0);
            if (!$search) {
                throw new Exception("Error en búsqueda: " . ldap_error($ds));
            }

            $entries = ldap_get_entries($ds, $search);
            debugLog("Resultados encontrados", $entries['count']);
            
            if ($entries['count'] > 0) {
                $nombreCompleto = USE_AD ? 
                    (isset($entries[0]['displayname'][0]) ? $entries[0]['displayname'][0] : $usuario) :
                    (isset($entries[0]['cn'][0]) ? $entries[0]['cn'][0] : $usuario);
                
                debugLog("Nombre completo obtenido", $nombreCompleto);
                return $nombreCompleto;
            }
            
            ldap_close($ds);
        }
    } catch (Exception $e) {
        debugLog("Error en obtenerNombreCompleto", $e->getMessage());
    }
    return $usuario;
}

$nombreCompleto = obtenerNombreCompleto($usuario);
debugLog("Nombre completo final", $nombreCompleto);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de cuota de Infomed - Holguín</title>
    <link rel="stylesheet" href="assets/css/css2.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="assets/js/tailwindcss.js"></script>
</head>
<body>
    <div class="page-wrapper">
        <header class="main-header">
            <div class="header-container">
                <div class="logo-container">
                    <img src="assets/images/logoinfomed.png" alt="Logo Infomed" class="logo-image">
                </div>
                
                <div class="header-title">
                    <h1>Sistema de Cuotas</h1>
                    <p class="subtitle">Infomed Holguín</p>
                </div>
            </div>
        </header>

        <!-- Inicio del contenido principal -->
        <main class="main-content">
            <div class="status-card">
              <div class="institution-info">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 mb-1">
                                <?php echo htmlspecialchars($nombreCompleto); ?>
                            </h2>
                            <p class="text-sm text-gray-600 italic font-semibold">
                                (Usuario: <?php echo htmlspecialchars($usuario); ?>)
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="index.php" 
                               class="inline-flex items-center px-3 py-1 text-sm text-blue-600 hover:text-blue-800 font-medium rounded hover:bg-blue-50 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                Volver
                            </a>
                            <a href="logout.php" class="inline-flex items-center px-3 py-1 text-sm text-red-600 hover:text-red-800 font-medium rounded hover:bg-red-50 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>


    <div class="details-content mt-32"> <!-- Margen muy grande (128px) -->
    <div class="stats-grid grid grid-cols-3 gap-4">
        <div class="stat-item">
            <span class="stat-label">Consumo Semanal:</span>
            <span class="stat-value"><?php echo $used_24h; ?> <?php echo $unidad_24h; ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Consumo Mensual:</span>
            <span class="stat-value"><?php echo $used_mensual; ?> <?php echo $unidad_mensual; ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Consumo Anual:</span>
            <span class="stat-value"><?php echo $used_anual; ?> <?php echo $unidad_anual; ?></span>
        </div>
    </div>
</div>

           </div>
        </main>

        <?php include 'footer.php'; ?>
    </div>
</body>
</html>