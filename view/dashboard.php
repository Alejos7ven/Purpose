<?php
$baseUrl = '/proposito';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard - Nuestro Proyecto de Vida</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
    
    <!-- Local Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/view/assets/vendor/bootstrap.min.css">
    <!-- Local Bootstrap Icons -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/view/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/view/assets/css/dashboard.css?v=1.9">
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
                    <li class="nav-item me-lg-3 mb-2 mb-lg-0">
                        <span class="nav-link user-pill px-3 py-1.5">
                            <i class="bi bi-person-circle me-1.5"></i>
                            Hola, <strong id="navUserName"><?php echo htmlspecialchars($_SESSION['user_nombre']); ?></strong>
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

    <!-- Main Content Container -->
    <div class="container pb-5">
        
        <!-- LOADING CONTAINER -->
        <div id="loadingContainer" class="text-center py-5">
            <div class="spinner-border text-rose" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted mt-3">Cargando tu proyecto de vida juntos...</p>
        </div>

        <!-- NO PROJECT CONTAINER -->
        <div id="noProjectContainer" class="row justify-content-center py-5 d-none">
            <div class="col-md-8 text-center">
                <div class="no-project-card p-5 shadow-sm">
                    <div class="illustration-area mb-4">
                        <i class="bi bi-house-heart illustration-icon"></i>
                    </div>
                    <h2 class="section-title">¡Comienza su Proyecto de Vida!</h2>
                    <p class="lead text-muted mb-4">
                        Aún no han creado un proyecto de vida compartido. Configuren su proyecto para establecer sus fechas especiales de noviazgo, compromiso y matrimonio, y comiencen a planificar su futuro organizando su inventario y necesidades.
                    </p>
                    <button class="btn btn-create-project px-4 py-2.5" data-bs-toggle="modal" data-bs-target="#createProjectModal">
                        <i class="bi bi-plus-circle me-2"></i> Crear Proyecto de Vida
                    </button>
                </div>
            </div>
        </div>

        <!-- ACTIVE PROJECT CONTAINER -->
        <div id="activeProjectContainer" class="d-none">
            <!-- Project Header Card -->
            <div class="card project-header-card shadow-sm border-0 overflow-hidden mb-4">
                <div class="row g-0">
                    <div class="col-md-4 project-img-container">
                        <img id="projectPhoto" src="" alt="Foto de pareja" class="img-fluid project-img">
                    </div>
                    <div class="col-md-8 d-flex flex-column justify-content-between">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h2 id="projectTitle" class="project-title mb-0"></h2>
                                <button class="btn btn-edit-project btn-sm" data-bs-toggle="modal" data-bs-target="#editProjectModal">
                                    <i class="bi bi-pencil-square me-1"></i> Editar
                                </button>
                            </div>
                            <p id="projectDescription" class="project-description text-muted mb-4"></p>
                            
                            <!-- Participants -->
                            <div class="row participants-row g-3">
                                <div class="col-sm-6">
                                    <div class="participant-card p-3 d-flex align-items-center">
                                        <i class="bi bi-person-fill participant-icon me-2"></i>
                                        <div>
                                            <div class="small text-muted">Creador(a)</div>
                                            <div id="projectCreatorName" class="fw-bold text-dark"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="participant-card p-3 d-flex align-items-center">
                                        <i class="bi bi-person-heart participant-icon pareja-icon me-2"></i>
                                        <div>
                                            <div class="small text-muted">Pareja</div>
                                            <div id="projectPartnerName" class="fw-bold text-dark">Sin pareja asignada</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Milestones Row -->
            <div class="row g-3 mb-5">
                <!-- Noviazgo Card -->
                <div class="col-md-4">
                    <div class="card milestone-card shadow-sm border-0 p-3 h-100 text-center bg-milestone-noviazgo">
                        <div class="milestone-icon mb-2"><i class="bi bi-chat-heart-fill"></i></div>
                        <h5 class="milestone-name">Fecha de Noviazgo</h5>
                        <h4 id="dateNoviazgo" class="milestone-date text-dark">--/--/----</h4>
                        <p id="counterNoviazgo" class="milestone-counter text-muted small mt-2 mb-0"></p>
                    </div>
                </div>
                <!-- Compromiso Card -->
                <div class="col-md-4">
                    <div class="card milestone-card shadow-sm border-0 p-3 h-100 text-center bg-milestone-compromiso">
                        <div class="milestone-icon mb-2"><i class="bi bi-gem"></i></div>
                        <h5 class="milestone-name">Fecha de Compromiso</h5>
                        <h4 id="dateCompromiso" class="milestone-date text-dark">--/--/----</h4>
                        <p id="counterCompromiso" class="milestone-counter text-muted small mt-2 mb-0"></p>
                    </div>
                </div>
                <!-- Matrimonio Card -->
                <div class="col-md-4">
                    <div class="card milestone-card shadow-sm border-0 p-3 h-100 text-center bg-milestone-matrimonio">
                        <div class="milestone-icon mb-2"><i class="bi bi-suit-heart-fill"></i></div>
                        <h5 class="milestone-name">Fecha de Matrimonio</h5>
                        <h4 id="dateMatrimonio" class="milestone-date text-dark">--/--/----</h4>
                        <p id="counterMatrimonio" class="milestone-counter text-muted small mt-2 mb-0"></p>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs custom-tabs mb-4 justify-content-center" id="dashboardTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active px-4 py-2.5" id="inventario-tab" data-bs-toggle="tab" data-bs-target="#inventario-pane" type="button" role="tab" aria-controls="inventario-pane" aria-selected="true">
                        <i class="bi bi-box-seam me-2"></i> Inventario de lo que ya tenemos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-4 py-2.5" id="necesidades-tab" data-bs-toggle="tab" data-bs-target="#necesidades-pane" type="button" role="tab" aria-controls="necesidades-pane" aria-selected="false">
                        <i class="bi bi-card-checklist me-2"></i> Lista de lo que necesitamos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-4 py-2.5" id="estadisticas-tab" data-bs-toggle="tab" data-bs-target="#estadisticas-pane" type="button" role="tab" aria-controls="estadisticas-pane" aria-selected="false">
                        <i class="bi bi-bar-chart-line me-2"></i> Estadísticas y Presupuesto
                    </button>
                </li>
            </ul>

            <!-- Tabs Content -->
            <div class="tab-content card shadow-sm p-4 border-0 tab-content-card" id="dashboardTabsContent">
                
                <!-- INVENTARIO PANE -->
                <div class="tab-pane fade show active" id="inventario-pane" role="tabpanel" aria-labelledby="inventario-tab" tabindex="0">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
                        <div>
                            <h3 class="pane-title mb-0">Artículos Disponibles</h3>
                            <p class="text-muted small mb-0">Cosas que ya tienen compradas, regaladas o listas para su hogar.</p>
                        </div>
                        <button class="btn btn-add-item px-3 py-2" data-bs-toggle="modal" data-bs-target="#addInventarioModal">
                            <i class="bi bi-plus-circle me-1.5"></i> Agregar al Inventario
                        </button>
                    </div>

                    <!-- Search and filters -->
                    <div class="row g-2 mb-4">
                        <div class="col-md-6">
                            <div class="input-group search-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" id="searchInventario" class="form-control border-start-0 ps-0" placeholder="Buscar artículo...">
                            </div>
                        </div>
                        <div class="col-md-6 d-flex gap-2">
                            <select id="filterInventarioCategoria" class="form-select select-filter">
                                <option value="">Todas las categorías</option>
                                <option value="Electrodomésticos">Electrodomésticos</option>
                                <option value="Muebles">Muebles</option>
                                <option value="Cocina">Cocina</option>
                                <option value="Dormitorio">Dormitorio</option>
                                <option value="Baño">Baño</option>
                                <option value="Tecnología">Tecnología</option>
                                <option value="Decoración">Decoración</option>
                                <option value="Boda">Boda 💍</option>
                                <option value="Otro">Otro</option>
                            </select>
                            <select id="filterInventarioEstado" class="form-select select-filter">
                                <option value="">Todos los estados</option>
                                <option value="Nuevo">Nuevo</option>
                                <option value="Como nuevo">Como nuevo</option>
                                <option value="Usado">Usado</option>
                                <option value="Por reparar">Por reparar</option>
                            </select>
                        </div>
                    </div>

                    <!-- Inventory Table / Grid -->
                    <div class="table-responsive">
                        <table class="table custom-table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Artículo</th>
                                    <th>Categoría</th>
                                    <th>Estado</th>
                                    <th class="text-center">Cantidad</th>
                                    <th>Registrado por</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="inventarioTableBody">
                                <!-- Ajax content loaded here -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div id="inventarioEmptyState" class="text-center py-5 d-none">
                        <i class="bi bi-box2 text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">No se encontraron artículos en tu inventario.</p>
                    </div>
                </div>

                <!-- NECESIDADES PANE -->
                <div class="tab-pane fade" id="necesidades-pane" role="tabpanel" aria-labelledby="necesidades-tab" tabindex="0">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
                        <div>
                            <h3 class="pane-title mb-0">Cosas que necesitamos</h3>
                            <p class="text-muted small mb-0">Lista de deseos y necesidades pendientes para organizar y adquirir.</p>
                        </div>
                        <button class="btn btn-add-item btn-necesidad px-3 py-2" data-bs-toggle="modal" data-bs-target="#addNecesidadModal">
                            <i class="bi bi-plus-circle me-1.5"></i> Agregar Necesidad
                        </button>
                    </div>

                    <!-- Needs filters -->
                    <div class="row g-2 mb-4">
                        <div class="col-md-6">
                            <div class="input-group search-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" id="searchNecesidades" class="form-control border-start-0 ps-0" placeholder="Buscar necesidad...">
                            </div>
                        </div>
                        <div class="col-md-6 d-flex gap-2">
                            <select id="filterNecesidadesPrioridad" class="form-select select-filter">
                                <option value="">Todas las prioridades</option>
                                <option value="Alta">Prioridad Alta</option>
                                <option value="Media">Prioridad Media</option>
                                <option value="Baja">Prioridad Baja</option>
                            </select>
                            <select id="filterNecesidadesCategoria" class="form-select select-filter">
                                <option value="">Todas las categorías</option>
                                <option value="Electrodomésticos">Electrodomésticos</option>
                                <option value="Muebles">Muebles</option>
                                <option value="Cocina">Cocina</option>
                                <option value="Dormitorio">Dormitorio</option>
                                <option value="Baño">Baño</option>
                                <option value="Tecnología">Tecnología</option>
                                <option value="Decoración">Decoración</option>
                                <option value="Boda">Boda 💍</option>
                                <option value="Otro">Otro</option>
                                <option value="Sin Categoría">Sin Categoría</option>
                            </select>
                        </div>
                    </div>

                    <!-- Needs List / Cards Grid -->
                    <div class="row row-cols-1 row-cols-md-2 g-3" id="necesidadesContainer">
                        <!-- Ajax content loaded here -->
                    </div>

                    <div id="necesidadesEmptyState" class="text-center py-5 d-none">
                        <i class="bi bi-card-checklist text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">No tienes necesidades o deseos pendientes en tu lista.</p>
                    </div>
                </div>

                <!-- ESTADISTICAS PANE -->
                <div class="tab-pane fade" id="estadisticas-pane" role="tabpanel" aria-labelledby="estadisticas-tab" tabindex="0">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
                        <div>
                            <h3 class="pane-title mb-0">Estadísticas y Presupuesto</h3>
                            <p class="text-muted small mb-0">Análisis visual del progreso, inversiones y presupuesto de su proyecto juntos.</p>
                        </div>
                        <button class="btn btn-secondary-custom px-3 py-2" id="btnRefreshStats">
                            <i class="bi bi-arrow-clockwise me-1.5"></i> Actualizar
                        </button>
                    </div>

                    <!-- KPI CARDS -->
                    <div class="row g-3 mb-4">
                        <!-- Total Inversion -->
                        <div class="col-md-4">
                            <div class="card kpi-card shadow-sm border-0 h-100 p-3 bg-kpi-inversion">
                                <div class="d-flex align-items-center">
                                    <div class="kpi-icon bg-soft-rose me-3">
                                        <i class="bi bi-cash-stack"></i>
                                    </div>
                                    <div>
                                        <div class="kpi-label text-muted small">Inversión Realizada</div>
                                        <h3 class="kpi-value mb-0 text-dark" id="statTotalInversion">$0.00</h3>
                                        <div class="kpi-subtext text-muted small mt-1">Suma del valor en inventario</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Presupuesto Necesario -->
                        <div class="col-md-4">
                            <div class="card kpi-card shadow-sm border-0 h-100 p-3 bg-kpi-presupuesto">
                                <div class="d-flex align-items-center">
                                    <div class="kpi-icon bg-soft-beige me-3">
                                        <i class="bi bi-wallet2"></i>
                                    </div>
                                    <div>
                                        <div class="kpi-label text-muted small">Presupuesto Necesario</div>
                                        <h3 class="kpi-value mb-0 text-dark" id="statTotalPresupuesto">$0.00</h3>
                                        <div class="kpi-subtext text-muted small mt-1">Suma de necesidades pendientes</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Progreso -->
                        <div class="col-md-4">
                            <div class="card kpi-card shadow-sm border-0 h-100 p-3 bg-kpi-progreso">
                                <div class="d-flex align-items-center">
                                    <div class="kpi-icon bg-soft-green me-3">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                    <div class="w-100">
                                        <div class="kpi-label text-muted small">Progreso de Adquisiciones</div>
                                        <h3 class="kpi-value mb-0 text-dark" id="statProgresoPorcentaje">0%</h3>
                                        <div class="progress mt-2" style="height: 8px;">
                                            <div class="progress-bar bg-success-pastel" id="statProgresoBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="d-flex justify-content-between text-muted small mt-1" style="font-size: 0.75rem;">
                                            <span id="statProgresoAdquirido">0 adquiridos</span>
                                            <span id="statProgresoTotal">0 total</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CHARTS ROW 1 -->
                    <div class="row g-4 mb-4">
                        <!-- Compras por Mes -->
                        <div class="col-lg-8">
                            <div class="card chart-card shadow-sm border-0 p-4">
                                <h5 class="card-title font-heading mb-3"><i class="bi bi-graph-up text-rose me-2"></i>Inversión y Compras por Mes</h5>
                                <div class="chart-container" style="position: relative; height:300px; width:100%">
                                    <canvas id="chartComprasMes"></canvas>
                                </div>
                            </div>
                        </div>
                        <!-- Necesidades por Prioridad -->
                        <div class="col-lg-4">
                            <div class="card chart-card shadow-sm border-0 p-4 h-100">
                                <h5 class="card-title font-heading mb-3"><i class="bi bi-pie-chart text-rose me-2"></i>Necesidades por Prioridad</h5>
                                <div class="chart-container d-flex align-items-center justify-content-center" style="position: relative; height:250px; width:100%">
                                    <canvas id="chartPrioridad"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CHARTS ROW 2 -->
                    <div class="row g-4">
                        <!-- Inventario por Categoría -->
                        <div class="col-md-6">
                            <div class="card chart-card shadow-sm border-0 p-4 h-100">
                                <h5 class="card-title font-heading mb-3"><i class="bi bi-tags text-rose me-2"></i>Inversión por Categoría</h5>
                                <div class="chart-container d-flex align-items-center justify-content-center" style="position: relative; height:250px; width:100%">
                                    <canvas id="chartCategoria"></canvas>
                                </div>
                            </div>
                        </div>
                        <!-- Resumen y Consejos -->
                        <div class="col-md-6">
                            <div class="card chart-card shadow-sm border-0 p-4 h-100">
                                <h5 class="card-title font-heading mb-3"><i class="bi bi-lightbulb text-rose me-2"></i>Resumen de Planificación</h5>
                                <div class="d-flex flex-column justify-content-between h-100">
                                    <div class="summary-list mb-3">
                                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                            <span class="text-muted">Total de Artículos en Inventario:</span>
                                            <strong class="text-dark" id="statSummaryInventarioQty">0</strong>
                                        </div>
                                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                            <span class="text-muted">Total de Necesidades Pendientes:</span>
                                            <strong class="text-dark" id="statSummaryNecesidadesQty">0</strong>
                                        </div>
                                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                            <span class="text-muted">Presupuesto Promedio por Necesidad:</span>
                                            <strong class="text-dark" id="statSummaryAvgPresupuesto">$0.00</strong>
                                        </div>
                                        <div class="d-flex justify-content-between pb-2">
                                            <span class="text-muted">Valor Promedio en Inventario:</span>
                                            <strong class="text-dark" id="statSummaryAvgValor">$0.00</strong>
                                        </div>
                                    </div>
                                    <div class="p-3 bg-light rounded-3 text-muted small mt-auto border-start border-rose border-3" style="border-left: 4px solid #D4A3AE !important;">
                                        <i class="bi bi-info-circle-fill text-rose me-1"></i>
                                        <strong>Consejo para el Presupuesto:</strong> Prioricen las necesidades marcadas con prioridad <em>Alta</em>. Recuerden que pueden mover artículos del inventario de vuelta a necesidades o viceversa para recalcular automáticamente sus métricas.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- ==================== MODALS ==================== -->

    <!-- CREATE PROJECT MODAL -->
    <div class="modal" id="createProjectModal" data-bs-backdrop="static" aria-labelledby="createProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-custom border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title font-heading" id="createProjectModalLabel"><i class="bi bi-heart-fill text-rose me-2"></i> Crear Proyecto de Vida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="createProjectForm" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="cProjectNombre" class="form-label">Nombre de su Proyecto de Vida *</label>
                            <input type="text" class="form-control" id="cProjectNombre" name="nombre" placeholder="Ej: Nuestro Nido de Amor, Jose & Marlys" required>
                        </div>
                        <div class="mb-3">
                            <label for="cProjectDescripcion" class="form-label">Descripción o Lema de Vida</label>
                            <textarea class="form-control" id="cProjectDescripcion" name="descripcion" rows="3" placeholder="Una bonita frase o planes que tengan juntos..."></textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="cProjectNoviazgo" class="form-label">Fecha de Noviazgo</label>
                                <input type="date" class="form-control" id="cProjectNoviazgo" name="fecha_noviazgo">
                            </div>
                            <div class="col-6">
                                <label for="cProjectCompromiso" class="form-label">Fecha de Compromiso</label>
                                <input type="date" class="form-control" id="cProjectCompromiso" name="fecha_compromiso">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="cProjectMatrimonio" class="form-label">Fecha de Matrimonio</label>
                            <input type="date" class="form-control" id="cProjectMatrimonio" name="fecha_matrimonio">
                        </div>
                        <div class="mb-3">
                            <label for="cProjectPareja" class="form-label">Tu Pareja * (Máximo 2 integrantes en total)</label>
                            <select class="form-select" id="cProjectPareja" name="pareja_id" required>
                                <option value="" disabled selected>Selecciona a tu pareja...</option>
                                <!-- Dynamic partners list loaded here -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="cProjectFoto" class="form-label">Foto de Pareja (Opcional, máx 2MB)</label>
                            <input type="file" class="form-control" id="cProjectFoto" name="foto" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary-custom">Crear Proyecto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- EDIT PROJECT MODAL -->
    <div class="modal" id="editProjectModal" data-bs-backdrop="static" aria-labelledby="editProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-custom border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title font-heading" id="editProjectModalLabel"><i class="bi bi-pencil-square text-rose me-2"></i> Editar Proyecto de Vida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editProjectForm" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="eProjectNombre" class="form-label">Nombre de su Proyecto de Vida *</label>
                            <input type="text" class="form-control" id="eProjectNombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="eProjectDescripcion" class="form-label">Descripción o Lema de Vida</label>
                            <textarea class="form-control" id="eProjectDescripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="eProjectNoviazgo" class="form-label">Fecha de Noviazgo</label>
                                <input type="date" class="form-control" id="eProjectNoviazgo" name="fecha_noviazgo">
                            </div>
                            <div class="col-6">
                                <label for="eProjectCompromiso" class="form-label">Fecha de Compromiso</label>
                                <input type="date" class="form-control" id="eProjectCompromiso" name="fecha_compromiso">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="eProjectMatrimonio" class="form-label">Fecha de Matrimonio</label>
                            <input type="date" class="form-control" id="eProjectMatrimonio" name="fecha_matrimonio">
                        </div>
                        <div class="mb-3">
                            <label for="eProjectPareja" class="form-label">Tu Pareja *</label>
                            <select class="form-select" id="eProjectPareja" name="pareja_id" required>
                                <option value="" disabled>Selecciona a tu pareja...</option>
                                <!-- Dynamic partners list loaded here -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="eProjectFoto" class="form-label">Cambiar Foto de Pareja (Opcional, máx 2MB)</label>
                            <input type="file" class="form-control" id="eProjectFoto" name="foto" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary-custom">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ADD INVENTARIO MODAL -->
    <div class="modal" id="addInventarioModal" data-bs-backdrop="static" aria-labelledby="addInventarioModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-custom border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title font-heading" id="addInventarioModalLabel"><i class="bi bi-box-seam text-rose me-2"></i> Agregar al Inventario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addInventarioForm">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="iNombre" class="form-label">Nombre del Artículo *</label>
                            <input type="text" class="form-control" id="iNombre" name="nombre" placeholder="Ej: Microondas, Sofá de 3 puestos" required>
                        </div>
                        <div class="mb-3">
                            <label for="iDescripcion" class="form-label">Descripción o Notas (Ej. Marca, color...)</label>
                            <textarea class="form-control" id="iDescripcion" name="descripcion" rows="2" placeholder="Ej. Marca Samsung, color gris metalizado"></textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="iCantidad" class="form-label">Cantidad *</label>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary btn-modal-qty-minus"><i class="bi bi-dash"></i></button>
                                    <input type="number" class="form-control text-center input-modal-qty" id="iCantidad" name="cantidad" value="1" min="1" required>
                                    <button type="button" class="btn btn-outline-secondary btn-modal-qty-plus"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-6">
                                <label for="iEstado" class="form-label">Estado del Artículo *</label>
                                <select class="form-select" id="iEstado" name="estado" required>
                                    <option value="Nuevo">Nuevo</option>
                                    <option value="Como nuevo">Como nuevo</option>
                                    <option value="Usado" selected>Usado</option>
                                    <option value="Por reparar">Por reparar</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <label for="iCategoria" class="form-label">Categoría *</label>
                                <select class="form-select" id="iCategoria" name="categoria" required>
                                    <option value="Electrodomésticos">Electrodomésticos</option>
                                    <option value="Muebles" selected>Muebles</option>
                                    <option value="Cocina">Cocina</option>
                                    <option value="Dormitorio">Dormitorio</option>
                                    <option value="Baño">Baño</option>
                                    <option value="Tecnología">Tecnología</option>
                                    <option value="Decoración">Decoración</option>
                                    <option value="Boda">Boda 💍</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <label for="iEmoji" class="form-label">Emoji Icono</label>
                                <input type="text" class="form-control text-center" id="iEmoji" name="emoji" placeholder="🛋️, 💍..." maxlength="5">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="iValor" class="form-label">Precio Unitario</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control input-modal-unit-price" id="iValor" name="valor" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-6">
                                <label for="iValorTotal" class="form-label">Precio Total</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control input-modal-total-price" id="iValorTotal" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary-custom">Agregar Artículo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- EDIT INVENTARIO MODAL -->
    <div class="modal" id="editInventarioModal" data-bs-backdrop="static" aria-labelledby="editInventarioModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-custom border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title font-heading" id="editInventarioModalLabel"><i class="bi bi-pencil-square text-rose me-2"></i> Editar Artículo de Inventario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editInventarioForm">
                    <input type="hidden" id="eItemId" name="id">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="eItemNombre" class="form-label">Nombre del Artículo *</label>
                            <input type="text" class="form-control" id="eItemNombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="eItemDescripcion" class="form-label">Descripción o Notas</label>
                            <textarea class="form-control" id="eItemDescripcion" name="descripcion" rows="2"></textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="eItemCantidad" class="form-label">Cantidad *</label>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary btn-modal-qty-minus"><i class="bi bi-dash"></i></button>
                                    <input type="number" class="form-control text-center input-modal-qty" id="eItemCantidad" name="cantidad" min="1" required>
                                    <button type="button" class="btn btn-outline-secondary btn-modal-qty-plus"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-6">
                                <label for="eItemEstado" class="form-label">Estado del Artículo *</label>
                                <select class="form-select" id="eItemEstado" name="estado" required>
                                    <option value="Nuevo">Nuevo</option>
                                    <option value="Como nuevo">Como nuevo</option>
                                    <option value="Usado">Usado</option>
                                    <option value="Por reparar">Por reparar</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <label for="eItemCategoria" class="form-label">Categoría *</label>
                                <select class="form-select" id="eItemCategoria" name="categoria" required>
                                    <option value="Electrodomésticos">Electrodomésticos</option>
                                    <option value="Muebles">Muebles</option>
                                    <option value="Cocina">Cocina</option>
                                    <option value="Dormitorio">Dormitorio</option>
                                    <option value="Baño">Baño</option>
                                    <option value="Tecnología">Tecnología</option>
                                    <option value="Decoración">Decoración</option>
                                    <option value="Boda">Boda 💍</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <label for="eItemEmoji" class="form-label">Emoji Icono</label>
                                <input type="text" class="form-control text-center" id="eItemEmoji" name="emoji" placeholder="🛋️, 💍..." maxlength="5">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="eItemValor" class="form-label">Precio Unitario</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control input-modal-unit-price" id="eItemValor" name="valor" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-6">
                                <label for="eItemValorTotal" class="form-label">Precio Total</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control input-modal-total-price" id="eItemValorTotal" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary-custom">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ADD NECESIDAD MODAL -->
    <div class="modal" id="addNecesidadModal" data-bs-backdrop="static" aria-labelledby="addNecesidadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-custom border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title font-heading" id="addNecesidadModalLabel"><i class="bi bi-card-checklist text-rose me-2"></i> Agregar Necesidad o Deseo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addNecesidadForm">
                    <div class="modal-body p-4">
                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <label for="nNombre" class="form-label">¿Qué necesitan? *</label>
                                <input type="text" class="form-control" id="nNombre" name="nombre" placeholder="Ej: Juego de Sábanas King, Licuadora" required>
                            </div>
                            <div class="col-4">
                                <label for="nEmoji" class="form-label">Emoji Icono</label>
                                <input type="text" class="form-control text-center" id="nEmoji" name="emoji" placeholder="💍, ☕..." maxlength="5">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="nDescripcion" class="form-label">Descripción o Notas</label>
                            <textarea class="form-control" id="nDescripcion" name="descripcion" rows="2" placeholder="Notas sobre marca preferida, dimensiones o dónde comprarlo..."></textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="nCantidad" class="form-label">Cantidad *</label>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary btn-modal-qty-minus"><i class="bi bi-dash"></i></button>
                                    <input type="number" class="form-control text-center input-modal-qty" id="nCantidad" name="cantidad" value="1" min="1" required>
                                    <button type="button" class="btn btn-outline-secondary btn-modal-qty-plus"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-6">
                                <label for="nPrioridad" class="form-label">Prioridad *</label>
                                <select class="form-select" id="nPrioridad" name="prioridad" required>
                                    <option value="Alta">Alta</option>
                                    <option value="Media" selected>Media</option>
                                    <option value="Baja">Baja</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="nCategoria" class="form-label">Categoría *</label>
                            <select class="form-select" id="nCategoria" name="categoria">
                                <option value="" selected>Sin Categoría</option>
                                <option value="Electrodomésticos">Electrodomésticos</option>
                                <option value="Muebles">Muebles</option>
                                <option value="Cocina">Cocina</option>
                                <option value="Dormitorio">Dormitorio</option>
                                <option value="Baño">Baño</option>
                                <option value="Tecnología">Tecnología</option>
                                <option value="Decoración">Decoración</option>
                                <option value="Boda">Boda 💍</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="nPresupuesto" class="form-label">Presupuesto Unitario</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control input-modal-unit-price" id="nPresupuesto" name="presupuesto" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-6">
                                <label for="nPresupuestoTotal" class="form-label">Presupuesto Total</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control input-modal-total-price" id="nPresupuestoTotal" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary-custom">Agregar a la Lista</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- EDIT NECESIDAD MODAL -->
    <div class="modal" id="editNecesidadModal" data-bs-backdrop="static" aria-labelledby="editNecesidadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-custom border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title font-heading" id="editNecesidadModalLabel"><i class="bi bi-pencil-square text-rose me-2"></i> Editar Necesidad o Deseo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editNecesidadForm">
                    <input type="hidden" id="eNecesidadId" name="id">
                    <div class="modal-body p-4">
                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <label for="eNecesidadNombre" class="form-label">¿Qué necesitan? *</label>
                                <input type="text" class="form-control" id="eNecesidadNombre" name="nombre" required>
                            </div>
                            <div class="col-4">
                                <label for="eNecesidadEmoji" class="form-label">Emoji Icono</label>
                                <input type="text" class="form-control text-center" id="eNecesidadEmoji" name="emoji" placeholder="☕, 💍..." maxlength="5">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="eNecesidadDescripcion" class="form-label">Descripción o Notas</label>
                            <textarea class="form-control" id="eNecesidadDescripcion" name="descripcion" rows="2"></textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="eNecesidadCantidad" class="form-label">Cantidad *</label>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary btn-modal-qty-minus"><i class="bi bi-dash"></i></button>
                                    <input type="number" class="form-control text-center input-modal-qty" id="eNecesidadCantidad" name="cantidad" min="1" required>
                                    <button type="button" class="btn btn-outline-secondary btn-modal-qty-plus"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                            <div class="col-6">
                                <label for="eNecesidadPrioridad" class="form-label">Prioridad *</label>
                                <select class="form-select" id="eNecesidadPrioridad" name="prioridad" required>
                                    <option value="Alta">Alta</option>
                                    <option value="Media">Media</option>
                                    <option value="Baja">Baja</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="eNecesidadCategoria" class="form-label">Categoría *</label>
                            <select class="form-select" id="eNecesidadCategoria" name="categoria">
                                <option value="">Sin Categoría</option>
                                <option value="Electrodomésticos">Electrodomésticos</option>
                                <option value="Muebles">Muebles</option>
                                <option value="Cocina">Cocina</option>
                                <option value="Dormitorio">Dormitorio</option>
                                <option value="Baño">Baño</option>
                                <option value="Tecnología">Tecnología</option>
                                <option value="Decoración">Decoración</option>
                                <option value="Boda">Boda 💍</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="eNecesidadPresupuesto" class="form-label">Presupuesto Unitario</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control input-modal-unit-price" id="eNecesidadPresupuesto" name="presupuesto" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-6">
                                <label for="eNecesidadPresupuestoTotal" class="form-label">Presupuesto Total</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control input-modal-total-price" id="eNecesidadPresupuestoTotal" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="eNecesidadAdquirido" name="adquirido" value="1">
                                <label class="form-check-label fw-bold" for="eNecesidadAdquirido">Marcar como adquirido</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary-custom">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Local Vendor JS -->
    <script src="<?php echo $baseUrl; ?>/view/assets/vendor/jquery.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/view/assets/vendor/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/view/assets/vendor/sweetalert2.all.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/view/assets/vendor/chart.min.js"></script>
    <!-- Custom JS -->
    <script>
        const BASE_URL = '<?php echo $baseUrl; ?>';
        const CURRENT_USER_ID = <?php echo $_SESSION['user_id']; ?>;
    </script>
    <script src="<?php echo $baseUrl; ?>/view/assets/js/dashboard.js?v=2.1"></script>
</body>
</html>
