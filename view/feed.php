<?php
$baseUrl = '/proposito';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Muro de Parejas - Purpose</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
    <!-- Local Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/view/assets/vendor/bootstrap.min.css">
    <!-- Local Bootstrap Icons -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/view/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <!-- Shared + Feed CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/view/assets/css/dashboard.css?v=3.0">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/view/assets/css/feed.css?v=1.0">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom shadow-sm py-3 mb-4">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo $baseUrl; ?>/dashboard">
                <i class="bi bi-heart-fill brand-icon me-2"></i>
                <span class="brand-text">Purpose</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center mt-3 mt-lg-0">
                    <!-- Navegación principal -->
                    <li class="nav-item me-lg-2 mb-2 mb-lg-0">
                        <a class="nav-link nav-page-link" href="<?php echo $baseUrl; ?>/dashboard">
                            <i class="bi bi-grid-1x2-fill me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item me-lg-3 mb-2 mb-lg-0">
                        <a class="nav-link nav-page-link active" href="<?php echo $baseUrl; ?>/feed">
                            <i class="bi bi-chat-square-heart-fill me-1"></i> Muro
                        </a>
                    </li>
                    <!-- Separador -->
                    <li class="nav-item me-lg-3 mb-2 mb-lg-0">
                        <span class="nav-link user-pill px-3 py-1.5 d-flex align-items-center" id="btnOpenPerfil" style="cursor: pointer;" title="Configurar Perfil">
                            <img id="navUserAvatar" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23D4A3AE'><path d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/></svg>" alt="Avatar" class="rounded-circle me-1.5" style="width: 24px; height: 24px; object-fit: cover; border: 1px solid #D4A3AE;">
                            Hola,&nbsp;<strong id="navUserName"><?php echo htmlspecialchars($_SESSION['user_nombre']); ?></strong>
                        </span>
                    </li>
                    <li class="nav-item">
                        <button id="btnLogout" class="btn btn-logout w-100">
                            <i class="bi bi-box-arrow-right me-1"></i> Cerrar Sesión
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Feed Container -->
    <div class="container pb-5">

        <!-- Loading inicial -->
        <div id="feedPageLoading" class="text-center py-5">
            <div class="spinner-border text-rose" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted mt-3">Cargando el muro de parejas...</p>
        </div>

        <!-- Feed Layout: columna central tipo red social -->
        <div id="feedPageContent" class="d-none">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-7 col-xl-6">

                    <!-- Cabecera de página -->
                    <div class="feed-page-header mb-4 text-center">
                        <h2 class="font-heading fw-bold" style="color: var(--text-dark);">
                            <i class="bi bi-chat-square-heart-fill text-rose me-2"></i>Muro de Parejas
                        </h2>
                        <p class="text-muted small mb-0">El espacio para compartir su historia de amor con el mundo.</p>
                    </div>

                    <!-- Creador de publicación -->
                    <div class="card feed-creator-card shadow-sm border-0 mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3 gap-3">
                                <img id="feedCreatorAvatar" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23D4A3AE'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/></svg>" class="rounded-circle feed-post-avatar" alt="Mi perfil" style="width: 48px; height: 48px;">
                                <div class="flex-grow-1">
                                    <p class="mb-0 fw-bold text-dark small" id="feedCreatorName">Mi pareja</p>
                                    <p class="mb-0 text-muted" style="font-size: 0.75rem;">¿Qué está pasando hoy?</p>
                                </div>
                            </div>
                            <form id="feedPostForm" enctype="multipart/form-data">
                                <div class="form-group mb-3">
                                    <textarea class="form-control post-input" id="postContenido" name="contenido" rows="3" placeholder="Comparte un estado, fotos, logros o anécdotas de su proyecto de vida..."></textarea>
                                </div>
                                <div id="postImagePreviewContainer" class="position-relative mb-3 d-none">
                                    <img id="postImagePreview" src="" alt="Vista previa" class="img-fluid rounded w-100" style="max-height: 350px; object-fit: cover;">
                                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 rounded-circle" id="btnRemovePostImage" style="opacity: 0.9; z-index: 10;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <button type="button" class="btn btn-upload-photo px-3 py-2 shadow-none border" id="btnTriggerPostFile" style="background-color: var(--primary-rose-light); border-color: var(--border-color) !important;">
                                        <i class="bi bi-image text-rose me-2"></i>Foto
                                    </button>
                                    <input type="file" id="postFoto" name="foto" class="d-none" accept="image/*">
                                    <button type="submit" class="btn btn-primary-custom px-4 py-2" id="btnSubmitPost">
                                        <i class="bi bi-send-fill me-1"></i> Publicar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Contenedor de posts -->
                    <div id="globalFeedContainer">
                        <!-- Cargado dinámicamente -->
                    </div>

                </div>
            </div>
        </div>

        <!-- Sin proyecto activo -->
        <div id="feedNoProject" class="d-none row justify-content-center py-5">
            <div class="col-md-8 text-center">
                <div class="no-project-card p-5 shadow-sm">
                    <div class="illustration-area mb-4">
                        <i class="bi bi-chat-square-heart illustration-icon"></i>
                    </div>
                    <h2 class="section-title">Aún no tienen proyecto</h2>
                    <p class="lead text-muted mb-4">Para acceder al muro de parejas primero deben crear su proyecto de vida compartido.</p>
                    <a href="<?php echo $baseUrl; ?>/dashboard" class="btn btn-primary-custom px-4 py-2.5">
                        <i class="bi bi-arrow-left me-2"></i> Ir al Dashboard
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- SHARE POST MODAL -->
    <div class="modal" id="sharePostModal" tabindex="-1" aria-labelledby="sharePostModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-custom border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title font-heading" id="sharePostModalLabel"><i class="bi bi-share-fill text-rose me-2"></i> Compartir Publicación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="sharePostForm">
                    <input type="hidden" id="sharePostIdInput" name="post_id" value="">
                    <div class="modal-body p-4">
                        <div class="form-group mb-3">
                            <label for="sharePostContenido" class="form-label fw-bold">Añade un comentario (opcional):</label>
                            <textarea class="form-control" id="sharePostContenido" name="contenido" rows="3" placeholder="¿Qué quieres comentar al compartir esta publicación?"></textarea>
                        </div>
                        <div class="card bg-light border-0 p-3 rounded">
                            <div class="d-flex align-items-center mb-2">
                                <img id="shareOriginalPhoto" src="" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                <div>
                                    <h6 id="shareOriginalProjectName" class="mb-0 small fw-bold text-dark">Nombre de Pareja</h6>
                                    <span id="shareOriginalDate" class="text-muted small" style="font-size: 0.75rem;">Fecha</span>
                                </div>
                            </div>
                            <p id="shareOriginalContent" class="mb-2 text-muted small mb-0"></p>
                            <img id="shareOriginalImg" src="" class="img-fluid rounded d-none mt-2" style="max-height: 200px; object-fit: cover; width: 100%;">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary-custom" id="btnConfirmShare">Compartir ahora</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- PROFILE MODAL (reutilizado en feed) -->
    <div class="modal" id="perfilModal" tabindex="-1" aria-labelledby="perfilModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-custom border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title font-heading" id="perfilModalLabel"><i class="bi bi-person-gear text-rose me-2"></i> Mi Perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted text-center">Para editar tu perfil completo ve al <a href="<?php echo $baseUrl; ?>/dashboard" class="text-rose fw-bold">Dashboard</a>.</p>
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
        const CURRENT_USER_ID = <?php echo $_SESSION['user_id']; ?>;
    </script>
    <script src="<?php echo $baseUrl; ?>/view/assets/js/imageUtils.js?v=1.1"></script>
    <script src="<?php echo $baseUrl; ?>/view/assets/js/feed.js?v=1.2"></script>
</body>
</html>
