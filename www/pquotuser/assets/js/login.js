// Configuración de debug
const debugMode = window.debugMode || false;

function debugLog(message, data = null) {
    if (debugMode) {
        if (data) {
            console.log('[DEBUG]', message, data);
        } else {
            console.log('[DEBUG]', message);
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    debugLog('Página de login cargada');
    
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const svgObject = document.querySelector('.eye-icon');

    // Esperar a que el SVG se cargue completamente
    svgObject.addEventListener('load', function() {
        debugLog('SVG cargado');
        const svgDoc = svgObject.contentDocument;
        const eyeOpen = svgDoc.querySelector('.eye-open');
        const eyeClosed = svgDoc.querySelector('.eye-closed');

        togglePassword.addEventListener('click', function() {
            debugLog('Toggle password clicked');
            if (password.type === 'password') {
                password.type = 'text';
                eyeOpen.style.display = 'none';
                eyeClosed.style.display = 'block';
                debugLog('Password visible');
            } else {
                password.type = 'password';
                eyeOpen.style.display = 'block';
                eyeClosed.style.display = 'none';
                debugLog('Password oculto');
            }
        });

        togglePassword.addEventListener('mouseenter', function() {
            debugLog('Mouse sobre botón de password');
            if (password.type === 'password') {
                eyeOpen.style.animation = 'closeEye 0.4s ease';
            }
        });

        svgDoc.addEventListener('animationend', function() {
            debugLog('Animación de ojo completada');
            eyeOpen.style.animation = '';
        });
    });

    // Error handling para la carga del SVG
    svgObject.addEventListener('error', function(error) {
        debugLog('Error al cargar el SVG', error);
        console.error('Error al cargar el SVG:', error);
    });
    
    // Debug para el formulario
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        debugLog('Formulario enviado', {
            username: document.getElementById('username').value,
            passwordLength: document.getElementById('password').value.length
        });
    });
});