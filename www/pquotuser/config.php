<?php

// Tipo de servidor (true para AD, false para LDAP)
define('USE_AD', false);

// Configuración del servidor LDAP/AD
define('LDAP_SERVER', '10.10.10.110');
define('LDAP_PORT', 389);

// Base DN para LDAP
define('LDAP_BASE_DN', 'ou=usuarios,dc=cpicm,dc=hlg,dc=sld,dc=cu');

// Credenciales de administrador
define('LDAP_ADMIN_DN', 'CN=squid,OU=Usuarios,OU=CPICMHLG,DC=cpicm,DC=hlg,DC=sld,DC=cu');
define('LDAP_ADMIN_PASSWORD', 'Cpicm2024*');
define('LDAP_BIND_REQUIRED', false);

// Opciones de LDAP
define('LDAP_PROTOCOL_VERSION', 3);
define('LDAP_REFERRALS', 0);


// Configuración de la base de datos
define('DB_HOST', '10.10.10.110');
define('DB_USER', 'pquot');
define('DB_PASS', 'pquotwebdb');
define('DB_NAME', 'pquot');
define('DB_PORT', 3306);

// Configuración para la actualización automática
define('AUTO_UPDATE_ENABLED', true);     // true para activar, false para desactivar
define('AUTO_UPDATE_SECONDS', 1);        // tiempo en segundos

// Configuración para debugging
define('DEBUG_MODE', false);     // true para mostrar mensajes de debug, false para ocultarlos

?>