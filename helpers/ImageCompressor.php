<?php
/**
 * ImageCompressor
 *
 * Helper para comprimir y redimensionar imágenes con GD.
 * Convierte cualquier formato soportado a JPEG comprimido.
 */
class ImageCompressor {

    /**
     * Comprime y guarda la imagen como JPEG.
     *
     * @param string $tmpPath    Ruta al archivo temporal
     * @param string $destDir    Directorio destino
     * @param string $prefix     Prefijo del nombre de archivo
     * @param int    $maxWidth   Ancho máximo en píxeles (default: 1280)
     * @param int    $quality    Calidad JPEG 0-100 (default: 80)
     * @return string|null       Nombre del archivo guardado, o null si falla
     */
    public static function comprimir(
        string $tmpPath,
        string $destDir,
        string $prefix  = 'img_',
        int    $maxWidth = 1280,
        int    $quality  = 80
    ): ?string {

        if (!extension_loaded('gd')) {
            error_log('ImageCompressor: extensión GD no disponible.');
            return null;
        }

        if (!file_exists($tmpPath) || !is_readable($tmpPath)) {
            error_log('ImageCompressor: archivo no existe o no es legible: ' . $tmpPath);
            return null;
        }

        // Obtener información de la imagen usando el contenido real (ignora extensión)
        $info = @getimagesize($tmpPath);
        if ($info === false) {
            error_log('ImageCompressor: getimagesize falló para: ' . $tmpPath);
            return null;
        }

        $mimeType   = $info['mime'];
        $origWidth  = (int)$info[0];
        $origHeight = (int)$info[1];

        if ($origWidth <= 0 || $origHeight <= 0) {
            error_log('ImageCompressor: dimensiones inválidas: ' . $origWidth . 'x' . $origHeight);
            return null;
        }

        // Crear recurso GD según tipo MIME real
        $sourceImage = self::crearDesdeArchivo($mimeType, $tmpPath);

        if ($sourceImage === null) {
            error_log('ImageCompressor: no se pudo crear recurso GD. MIME: ' . $mimeType);
            return null;
        }

        // Corregir orientación EXIF (fotos de móvil giradas)
        if ($mimeType === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($tmpPath);
            if (!empty($exif['Orientation']) && $exif['Orientation'] != 1) {
                $rotated = self::corregirOrientacion($sourceImage, (int)$exif['Orientation']);
                if ($rotated !== null) {
                    imagedestroy($sourceImage);
                    $sourceImage = $rotated;
                }
            }
        }

        $origWidth  = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        // Calcular dimensiones finales
        if ($origWidth > $maxWidth) {
            $ratio     = $maxWidth / $origWidth;
            $newWidth  = $maxWidth;
            $newHeight = (int)round($origHeight * $ratio);
        } else {
            $newWidth  = $origWidth;
            $newHeight = $origHeight;
        }

        // Crear canvas de destino
        $destImage = imagecreatetruecolor($newWidth, $newHeight);
        if ($destImage === false) {
            imagedestroy($sourceImage);
            error_log('ImageCompressor: no se pudo crear canvas de destino.');
            return null;
        }

        // Fondo blanco (para PNG transparentes)
        $white = imagecolorallocate($destImage, 255, 255, 255);
        imagefill($destImage, 0, 0, $white);

        // Redimensionar con alta calidad
        if (!imagecopyresampled(
            $destImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $origWidth, $origHeight
        )) {
            imagedestroy($sourceImage);
            imagedestroy($destImage);
            error_log('ImageCompressor: imagecopyresampled falló.');
            return null;
        }

        imagedestroy($sourceImage);

        // Crear directorio si no existe
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
            imagedestroy($destImage);
            error_log('ImageCompressor: no se pudo crear directorio: ' . $destDir);
            return null;
        }

        if (!is_writable($destDir)) {
            imagedestroy($destImage);
            error_log('ImageCompressor: directorio no escribible: ' . $destDir);
            return null;
        }

        // Guardar como JPEG
        $fileName    = uniqid($prefix) . '.jpg';
        $destination = rtrim($destDir, '/') . '/' . $fileName;

        if (!imagejpeg($destImage, $destination, $quality)) {
            imagedestroy($destImage);
            error_log('ImageCompressor: imagejpeg falló al guardar: ' . $destination);
            return null;
        }

        imagedestroy($destImage);

        return $fileName;
    }

    /**
     * Crea recurso GD desde el archivo según su MIME real.
     */
    private static function crearDesdeArchivo(string $mimeType, string $path): ?\GdImage {
        $resource = null;

        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/pjpeg':
                $resource = @imagecreatefromjpeg($path);
                break;
            case 'image/png':
            case 'image/x-png':
                $resource = @imagecreatefrompng($path);
                break;
            case 'image/gif':
                $resource = @imagecreatefromgif($path);
                break;
            case 'image/webp':
                $resource = @imagecreatefromwebp($path);
                break;
            case 'image/bmp':
            case 'image/x-bmp':
            case 'image/x-ms-bmp':
                $resource = @imagecreatefrombmp($path);
                break;
            case 'image/avif':
                if (function_exists('imagecreatefromavif')) {
                    $resource = @imagecreatefromavif($path);
                }
                break;
            default:
                // Intento genérico con imagecreatefromstring
                $content = @file_get_contents($path);
                if ($content !== false) {
                    $resource = @imagecreatefromstring($content);
                }
                break;
        }

        if ($resource === false) {
            return null;
        }

        return $resource ?: null;
    }

    /**
     * Corrige la orientación de una imagen según su dato EXIF.
     */
    private static function corregirOrientacion(\GdImage $image, int $orientation): ?\GdImage {
        $degrees = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($degrees === 0) {
            return $image;
        }

        $rotated = @imagerotate($image, $degrees, 0);
        return ($rotated !== false) ? $rotated : null;
    }

    /**
     * Valida que el archivo sea una imagen permitida antes de procesar.
     *
     * @param array $file       Elemento de $_FILES
     * @param int   $maxSizeMB  Tamaño máximo en MB (default: 10)
     * @return string|null      Mensaje de error, o null si es válido
     */
    public static function validar(array $file, int $maxSizeMB = 10): ?string {
        // Verificar error de subida de PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errores = [
                UPLOAD_ERR_INI_SIZE   => 'La imagen supera el límite del servidor (' . ini_get('upload_max_filesize') . ').',
                UPLOAD_ERR_FORM_SIZE  => 'La imagen supera el límite del formulario.',
                UPLOAD_ERR_PARTIAL    => 'La imagen se subió de forma incompleta. Intenta de nuevo.',
                UPLOAD_ERR_NO_FILE    => 'No se recibió ningún archivo.',
                UPLOAD_ERR_NO_TMP_DIR => 'Error interno del servidor (sin directorio temporal).',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir en el servidor.',
            ];
            return $errores[$file['error']] ?? 'Error desconocido al subir el archivo (código ' . $file['error'] . ').';
        }

        // Verificar tamaño
        $maxBytes = $maxSizeMB * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            return "La imagen no debe superar {$maxSizeMB}MB.";
        }

        if ($file['size'] === 0) {
            return 'El archivo está vacío.';
        }

        // Verificar que sea imagen real con getimagesize (más seguro que MIME del browser)
        $allowedMimes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
            'image/webp', 'image/bmp', 'image/avif',
        ];

        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            return 'El archivo no es una imagen válida.';
        }

        if (!in_array($info['mime'], $allowedMimes)) {
            return 'Formato no permitido. Usa JPG, PNG, GIF o WEBP.';
        }

        return null; // válido
    }
}
