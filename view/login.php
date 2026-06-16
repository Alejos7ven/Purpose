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
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/view/assets/css/login.css?v=1.1">
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
                    
                    <div class="footer-note mt-4">
                        <p class="text-muted small">&copy; <?php echo date('Y'); ?> Con amor para siempre</p>
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
    <script src="<?php echo $baseUrl; ?>/view/assets/js/login.js?v=1.1"></script>
</body>
</html>
