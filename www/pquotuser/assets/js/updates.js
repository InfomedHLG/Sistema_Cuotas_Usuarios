
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