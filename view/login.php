<?php
$baseUrl = '/proposito';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Iniciar Sesión - Nuestro Propósito</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
    
    <!-- Local Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/view/assets/vendor/bootstrap.min.css">
    <!-- Local Bootstrap Icons -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/view/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/view/assets/css/login.css?v=1.2">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="login-card p-4 shadow-sm text-center">
                    <div class="logo-area mb-3">
                        <i class="bi bi-heart-fill heart-logo"></i>
                    </div>
                    <h1 class="brand-title">Purpose</h1>
                    <p class="brand-subtitle mb-4">Nuestro proyecto de vida juntos</p>
                    
                    <form id="loginForm" novalidate>
                        <div class="mb-3 text-start">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="ejemplo@correo.com" required autocomplete="email">
                            </div>
                        </div>
                        <div class="mb-4 text-start">
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-login w-100 py-2">
                            <span>Ingresar</span> <i class="bi bi-arrow-right-short ms-1"></i>
                        </button>
                    </form>
                    
                    <!-- Registro Form -->
                    <form id="registerForm" class="d-none" novalidate>
                        <div class="row g-2 mb-3">
                            <div class="col-6 text-start">
                                <label for="regNombre" class="form-label">Nombre *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="regNombre" name="nombre" placeholder="Nombre" required>
                                </div>
                            </div>
                            <div class="col-6 text-start">
                                <label for="regApellido" class="form-label">Apellido</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="regApellido" name="apellido" placeholder="Apellido">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3 text-start">
                            <label for="regEmail" class="form-label">Correo Electrónico *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="regEmail" name="email" placeholder="ejemplo@correo.com" required>
                            </div>
                        </div>
                        <div class="mb-3 text-start">
                            <label for="regTlf" class="form-label">Teléfono / Celular</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="text" class="form-control" id="regTlf" name="tlf" placeholder="Ej: +58 412 1234567">
                            </div>
                        </div>
                        <div class="mb-3 text-start">
                            <label for="regPassword" class="form-label">Contraseña *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="regPassword" name="password" placeholder="Mínimo 6 caracteres" required>
                            </div>
                        </div>
                        <div class="mb-4 text-start">
                            <label for="regMensaje" class="form-label">Mensaje de solicitud (Por favor incluye el correo de tu pareja) *</label>
                            <textarea class="form-control" id="regMensaje" name="mensaje" rows="3" placeholder="Hola, solicito una cuenta. El correo de mi pareja es..." required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-login w-100 py-2">
                            <span>Enviar Solicitud</span> <i class="bi bi-send-fill ms-1"></i>
                        </button>
                    </form>

                    <div class="mt-3">
                        <a href="#" id="toggleAuthMode" class="text-rose small fw-bold" style="color: #D4A3AE; text-decoration: none;">¿No tienes una cuenta? Solicita una aquí</a>
                    </div>
                    
                    <div class="footer-note mt-4">
                        <p class="text-muted small">&copy; <?php echo date('Y'); ?> Con Amor. Jose De Marzo. Dedicado al amor de mi vida. Marlys Silva.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Local Vendor JS -->
    <script src="<?php echo $baseUrl; ?>/view/assets/vendor/jquery.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/view/assets/vendor/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/view/assets/vendor/sweetalert2.all.min.js"></script>
    <!-- Custom JS -->
    <script>
        const BASE_URL = '<?php echo $baseUrl; ?>';
    </script>
    <script src="<?php echo $baseUrl; ?>/view/assets/js/login.js?v=1.2"></script>
</body>
</html>
