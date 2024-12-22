<?php
require_once('debug.php');  // Agregar esta línea

function getColorByPercentage($percentage) {
    debugLog("Calculando color para porcentaje", $percentage);

    $colorStart = [76, 175, 80]; // Verde claro
    $colorMiddle = [255, 235, 59]; // Amarillo claro
    $colorEnd = [244, 67, 54]; // Rojo oscuro

    if ($percentage >= 100) {
        debugLog("Usando color rojo para 100% o más");
        // Rojo oscuro para 100% o más
        return "rgb(" . implode(",", $colorEnd) . ")";
    } elseif ($percentage <= 50) {
        debugLog("Mezclando verde a amarillo para porcentaje <= 50");
        // Mezcla de verde a amarillo
        $ratio = $percentage / 50;
        $color = blendColors($colorStart, $colorMiddle, $ratio);
    } else {
        debugLog("Mezclando amarillo a rojo para porcentaje > 50");
        // Mezcla de amarillo a rojo
        $ratio = ($percentage - 50) / 50;
        $color = blendColors($colorMiddle, $colorEnd, $ratio);
    }

    $resultColor = "rgb(" . implode(",", $color) . ")";
    debugLog("Color resultante", $resultColor);
    return $resultColor;
}

function blendColors($start, $end, $ratio) {
    debugLog("Mezclando colores con ratio", $ratio);
    debugLog("Color inicio", $start);
    debugLog("Color fin", $end);

    $result = [];
    for ($i = 0; $i < 3; $i++) {
        $result[] = round($start[$i] * (1 - $ratio) + $end[$i] * $ratio);
    }

    debugLog("Color mezclado resultante", $result);
    return $result;
}
?>