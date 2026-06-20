/**
 * imageUtils.js
 * Utilidades de compresión de imágenes en el navegador usando Canvas API.
 * Se comprime antes de enviar al servidor para reducir tiempo de carga.
 */

/**
 * Comprime un File de imagen usando Canvas y devuelve un nuevo File JPEG.
 *
 * @param {File}   file       - El archivo de imagen original
 * @param {number} maxWidth   - Ancho máximo en px (default: 1280)
 * @param {number} quality    - Calidad JPEG 0.0-1.0 (default: 0.82)
 * @returns {Promise<File>}   - Promise con el archivo comprimido
 */
function comprimirImagenCliente(file, maxWidth = 1280, quality = 0.82) {
    return new Promise((resolve, reject) => {
        if (!file || !file.type.startsWith('image/')) {
            resolve(file); // devolver sin cambios si no es imagen
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            const img = new Image();
            img.onload = function () {
                // Calcular nuevas dimensiones
                let width  = img.width;
                let height = img.height;

                if (width > maxWidth) {
                    height = Math.round(height * (maxWidth / width));
                    width  = maxWidth;
                }

                // Dibujar en canvas
                const canvas = document.createElement('canvas');
                canvas.width  = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                // Fondo blanco para PNGs con transparencia
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, width, height);
                ctx.drawImage(img, 0, 0, width, height);

                // Convertir a Blob JPEG comprimido
                canvas.toBlob(
                    (blob) => {
                        if (!blob) {
                            // Si falla la compresión, devolver archivo original
                            resolve(file);
                            return;
                        }
                        // Crear nuevo File con nombre .jpg
                        const baseName = file.name.replace(/\.[^/.]+$/, '');
                        const comprimido = new File([blob], baseName + '.jpg', {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        resolve(comprimido);
                    },
                    'image/jpeg',
                    quality
                );
            };
            img.onerror = () => resolve(file); // fallback si la imagen falla
            img.src = e.target.result;
        };
        reader.onerror = () => resolve(file);
        reader.readAsDataURL(file);
    });
}
