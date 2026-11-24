<?php
/**
 * Guarda la foto subida ya optimizada: la achica a un máximo de 900 px de lado,
 * la reorienta (las fotos de celular vienen "de costado") y le saca los datos
 * ocultos (EXIF, ubicación GPS). Una foto de 4 MB queda en ~80 KB: la tienda
 * carga mucho más rápido, sobre todo con datos móviles.
 *
 * Si el servidor no tiene la extensión GD, guarda el archivo tal cual vino.
 */
function guardarImagenOptimizada(string $origen, string $destino, string $extension, int $maxLado = 900): bool {
    if (!function_exists('imagecreatetruecolor')) {
        return move_uploaded_file($origen, $destino);
    }

    $extension = strtolower($extension);
    $img = null;
    if ($extension === 'jpg' || $extension === 'jpeg') {
        $img = @imagecreatefromjpeg($origen);
    } elseif ($extension === 'png') {
        $img = @imagecreatefrompng($origen);
    } elseif ($extension === 'webp' && function_exists('imagecreatefromwebp')) {
        $img = @imagecreatefromwebp($origen);
    }
    if (!$img) {
        return move_uploaded_file($origen, $destino);
    }

    // Orientación de las fotos de celular (solo JPG)
    if (($extension === 'jpg' || $extension === 'jpeg') && function_exists('exif_read_data')) {
        $exif = @exif_read_data($origen);
        $giro = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 0] ?? 0;
        if ($giro) {
            $girada = imagerotate($img, $giro, 0);
            if ($girada) { imagedestroy($img); $img = $girada; }
        }
    }

    $ancho = imagesx($img);
    $alto  = imagesy($img);
    $mayor = max($ancho, $alto);
    if ($mayor > $maxLado) {
        $escala = $maxLado / $mayor;
        $nuevoAncho = max(1, (int) round($ancho * $escala));
        $nuevoAlto  = max(1, (int) round($alto * $escala));
        $chica = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        if ($extension === 'png' || $extension === 'webp') {
            imagealphablending($chica, false);
            imagesavealpha($chica, true);
        }
        imagecopyresampled($chica, $img, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
        imagedestroy($img);
        $img = $chica;
    }

    if ($extension === 'png') {
        imagesavealpha($img, true);
        $ok = imagepng($img, $destino, 8);
    } elseif ($extension === 'webp' && function_exists('imagewebp')) {
        $ok = imagewebp($img, $destino, 82);
    } else {
        $ok = imagejpeg($img, $destino, 82);
    }
    imagedestroy($img);
    return (bool) $ok;
}
