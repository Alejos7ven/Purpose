$(document).ready(function() {
    
    // Arrays para guardar los datos cargados y poder filtrar localmente
    let inventarioItems = [];
    let necesidadesItems = [];
    let projectInfo = null;

    // Inicialización
    cargarProyecto();
    cargarParejasDisponibles();
    cargarPerfilNavbar();

    // ==================== LOGOUT ====================
    $('#btnLogout').on('click', function() {
        Swal.fire({
            title: '¿Cerrar Sesión?',
            text: "Volverás a la pantalla de ingreso.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#D4A3AE',
            cancelButtonColor: '#EBEBEB',
            confirmButtonText: 'Sí, salir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE_URL + '/api/logout',
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            window.location.href = BASE_URL + '/';
                        }
                    }
                });
            }
        });
    });

    // ==================== PROJECT LOADING & RENDER ====================
    function cargarProyecto() {
        $('#loadingContainer').removeClass('d-none');
        $('#noProjectContainer').addClass('d-none');
        $('#activeProjectContainer').addClass('d-none');

        $.ajax({
            url: BASE_URL + '/api/proyecto',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#loadingContainer').addClass('d-none');
                
                if (response.status === 'success' && response.data) {
                    projectInfo = response.data;
                    renderProyecto(projectInfo);
                    $('#activeProjectContainer').removeClass('d-none');
                    
                    // Cargar inventario y necesidades
                    cargarInventario();
                    cargarNecesidades();

                } else {
                    $('#noProjectContainer').removeClass('d-none');
                }
            },
            error: function() {
                $('#loadingContainer').addClass('d-none');
                Swal.fire('Error', 'No se pudo conectar con el servidor para cargar el proyecto.', 'error');
            }
        });
    }

    function renderProyecto(project) {
        $('#projectTitle').text(project.nombre);
        $('#projectDescription').text(project.descripcion || 'Sin descripción o lema establecido aún.');
        $('#projectCreatorName').text(project.creador_nombre);
        
        if (project.pareja_nombre) {
            $('#projectPartnerName').text(project.pareja_nombre);
        } else {
            $('#projectPartnerName').text('Sin pareja asignada');
        }

        // Renderizar Foto
        const fotoUrl = project.foto ? (BASE_URL + '/view/' + project.foto) : (BASE_URL + '/view/assets/vendor/bootstrap-icons/icons/heart-fill.svg');
        $('#projectPhoto').attr('src', fotoUrl);
        // Si es la imagen por defecto, ajustar clase CSS
        if (!project.foto) {
            $('#projectPhoto').addClass('p-5 opacity-25');
        } else {
            $('#projectPhoto').removeClass('p-5 opacity-25');
        }

        // Renderizar Hitos y contadores
        renderHito('#dateNoviazgo', '#counterNoviazgo', project.fecha_noviazgo, 'Noviazgo');
        renderHito('#dateCompromiso', '#counterCompromiso', project.fecha_compromiso, 'Compromiso');
        renderHito('#dateMatrimonio', '#counterMatrimonio', project.fecha_matrimonio, 'Matrimonio');

        // Pre-rellenar formulario de edición
        $('#eProjectNombre').val(project.nombre);
        $('#eProjectDescripcion').val(project.descripcion);
        $('#eProjectNoviazgo').val(project.fecha_noviazgo);
        $('#eProjectCompromiso').val(project.fecha_compromiso);
        $('#eProjectMatrimonio').val(project.fecha_matrimonio);
        
        // Esperar a que la lista de parejas cargue y seleccionar la correspondiente
        setTimeout(() => {
            $('#eProjectPareja').val(project.pareja_id);
        }, 300);
    }

    function renderHito(dateSelector, counterSelector, dateStr, type) {
        if (!dateStr) {
            $(dateSelector).text('Pendiente');
            $(counterSelector).html('<span class="fst-italic opacity-50">Por establecer</span>');
            return;
        }

        // Formatear fecha para mostrar
        const dateObj = new Date(dateStr + 'T00:00:00');
        const formattedDate = dateObj.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
        $(dateSelector).text(formattedDate);

        // Calcular contador
        const now = new Date();
        now.setHours(0,0,0,0);
        const target = new Date(dateStr + 'T00:00:00');
        target.setHours(0,0,0,0);
        
        const diffTime = target.getTime() - now.getTime();
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        let text = '';
        if (diffDays === 0) {
            text = '¡Es hoy! ♥';
        } else if (diffDays > 0) {
            text = `Faltan ${diffDays} día${diffDays > 1 ? 's' : ''}`;
        } else {
            // Fecha en el pasado. Calcular años y meses
            const daysPassed = Math.abs(diffDays);
            
            let years = now.getFullYear() - target.getFullYear();
            let months = now.getMonth() - target.getMonth();
            let days = now.getDate() - target.getDate();

            if (days < 0) {
                months--;
            }
            if (months < 0) {
                years--;
                months += 12;
            }

            if (years > 0) {
                text = `Hace ${years} año${years > 1 ? 's' : ''}${months > 0 ? ` y ${months} mes${months > 1 ? 'es' : ''}` : ''}`;
            } else if (months > 0) {
                text = `Hace ${months} mes${months > 1 ? 'es' : ''}`;
            } else {
                text = `Hace ${daysPassed} día${daysPassed > 1 ? 's' : ''}`;
            }
        }
        $(counterSelector).text(text);
    }

    function cargarParejasDisponibles() {
        $.ajax({
            url: BASE_URL + '/api/proyecto/parejas',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const $cSelect = $('#cProjectPareja');
                    const $eSelect = $('#eProjectPareja');
                    
                    // Limpiar excepto el por defecto
                    $cSelect.find('option:not(:first)').remove();
                    $eSelect.find('option:not(:first)').remove();

                    response.data.forEach(user => {
                        const opt = `<option value="${user.id}">${user.nombre} (${user.email})</option>`;
                        $cSelect.append(opt);
                        $eSelect.append(opt);
                    });
                }
            }
        });
    }

    // ==================== PROJECT ACTIONS ====================
    
    // Crear Proyecto Form
    $('#createProjectForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const formData = new FormData($form[0]);

        $form.find('button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Creando...');

        $.ajax({
            url: BASE_URL + '/api/proyecto/crear',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                $form.find('button[type="submit"]').prop('disabled', false).text('Crear Proyecto');
                if (response.status === 'success') {
                    $('#createProjectModal').modal('hide');
                    $form[0].reset();
                    Swal.fire({
                        icon: 'success',
                        title: '¡Felicidades!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        cargarProyecto();
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                $form.find('button[type="submit"]').prop('disabled', false).text('Crear Proyecto');
                Swal.fire('Error', 'Error al procesar la creación.', 'error');
            }
        });
    });

    // Editar Proyecto Form
    $('#editProjectForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const formData = new FormData($form[0]);

        $form.find('button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Guardando...');

        $.ajax({
            url: BASE_URL + '/api/proyecto/actualizar',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                $form.find('button[type="submit"]').prop('disabled', false).text('Guardar Cambios');
                if (response.status === 'success') {
                    $('#editProjectModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Actualizado',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        cargarProyecto();
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                $form.find('button[type="submit"]').prop('disabled', false).text('Guardar Cambios');
                Swal.fire('Error', 'Error al procesar la actualización.', 'error');
            }
        });
    });


    // ==================== INVENTARIO ACTIONS ====================

    function cargarInventario() {
        $.ajax({
            url: BASE_URL + '/api/inventario',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    inventarioItems = response.data;
                    filtrarYRenderizarInventario();
                    recargarEstadisticasSiActivo();
                }
            }
        });
    }

    function filtrarYRenderizarInventario() {
        const query = $('#searchInventario').val().toLowerCase().trim();
        const categoria = $('#filterInventarioCategoria').val();
        const estado = $('#filterInventarioEstado').val();

        // Filtrar
        const itemsFiltrados = inventarioItems.filter(item => {
            const matchesQuery = item.nombre.toLowerCase().includes(query) || (item.descripcion && item.descripcion.toLowerCase().includes(query));
            const matchesCategoria = !categoria || item.categoria === categoria;
            const matchesEstado = !estado || item.estado === estado;
            return matchesQuery && matchesCategoria && matchesEstado;
        });

        // Renderizar
        const $tbody = $('#inventarioTableBody');
        $tbody.empty();

        if (itemsFiltrados.length === 0) {
            $('#inventarioEmptyState').removeClass('d-none');
            $('.table-responsive').addClass('d-none');
        } else {
            $('#inventarioEmptyState').addClass('d-none');
            $('.table-responsive').removeClass('d-none');

            itemsFiltrados.forEach(item => {
                const valorBadge = item.valor 
                    ? `<div class="mt-1">
                           <span class="badge bg-light text-muted border" style="font-size: 0.7rem;" title="Precio Unitario">
                               <i class="bi bi-tag-fill me-1 text-rose"></i> $${parseFloat(item.valor).toFixed(2)} c/u
                           </span>
                           <span class="badge bg-soft-rose border text-rose ms-1" style="font-size: 0.7rem;" title="Total: Precio * Cantidad">
                               Total: $${(parseFloat(item.valor) * parseInt(item.cantidad)).toFixed(2)}
                           </span>
                       </div>` 
                    : '';
                const emojiStr = item.emoji ? `<span class="me-2 fs-4" style="line-height: 1;">${escapeHtml(item.emoji)}</span>` : '';
                const tr = `
                    <tr data-id="${item.id}">
                        <td>
                            <div class="fw-bold text-dark d-flex align-items-center">${emojiStr}${escapeHtml(item.nombre)}</div>
                            <div class="text-muted small">${escapeHtml(item.descripcion || 'Sin descripción')}</div>
                            ${valorBadge}
                        </td>
                         <td><span class="item-category-badge ${getCategoryClass(item.categoria)}">${getCategoryIcon(item.categoria)} ${escapeHtml(item.categoria || 'Sin Categoría')}</span></td>
                        <td><span class="item-state-badge">${escapeHtml(item.estado)}</span></td>
                        <td class="text-center">
                            <div class="d-inline-flex align-items-center justify-content-center gap-2">
                                <button class="btn btn-sm btn-qty-minus px-2 py-0.5" title="Disminuir cantidad"><i class="bi bi-dash"></i></button>
                                <span class="fw-bold text-rose fs-5 item-qty-display">${item.cantidad}</span>
                                <button class="btn btn-sm btn-qty-plus px-2 py-0.5" title="Aumentar cantidad"><i class="bi bi-plus"></i></button>
                            </div>
                        </td>
                        <td>
                            <div class="small fw-semibold">${escapeHtml(item.creador_nombre)}</div>
                            <div class="text-muted" style="font-size:0.75rem;">${formatearFechaHumana(item.created_at)}</div>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-action btn-action-edit btn-move-item-need" title="Mover a lista de necesidades" style="color: #E6B89C;"><i class="bi bi-reply-fill fs-5"></i></button>
                            <button class="btn btn-action btn-action-edit btn-edit-item" title="Editar"><i class="bi bi-pencil-fill"></i></button>
                            <button class="btn btn-action btn-action-delete btn-delete-item" title="Eliminar"><i class="bi bi-trash-fill"></i></button>
                        </td>
                    </tr>
                `;
                $tbody.append(tr);
            });
        }
    }

    // Filtros de inventario
    $('#searchInventario, #filterInventarioCategoria, #filterInventarioEstado').on('input change', filtrarYRenderizarInventario);

    // Agregar Artículo Inventario Form
    $('#addInventarioForm').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        
        $.ajax({
            url: BASE_URL + '/api/inventario/crear',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#addInventarioModal').modal('hide');
                    $form[0].reset();
                    Swal.fire({
                        icon: 'success',
                        title: 'Agregado',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    cargarInventario();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        });
    });

    // Abrir Modal de Edición de Inventario
    $(document).on('click', '.btn-edit-item', function() {
        const id = $(this).closest('tr').data('id');
        const item = inventarioItems.find(x => x.id == id);
        
        if (item) {
            $('#eItemId').val(item.id);
            $('#eItemNombre').val(item.nombre);
            $('#eItemDescripcion').val(item.descripcion);
            $('#eItemCantidad').val(item.cantidad);
            $('#eItemEstado').val(item.estado);
            $('#eItemCategoria').val(item.categoria);
            $('#eItemValor').val(item.valor);
            $('#eItemEmoji').val(item.emoji);
            
            // Prellenar precio total
            const total = item.valor ? (parseFloat(item.valor) * parseInt(item.cantidad)).toFixed(2) : '';
            $('#eItemValorTotal').val(total);
            
            $('#editInventarioModal').modal('show');
        }
    });

    // Guardar Edición de Inventario Form
    $('#editInventarioForm').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);

        $.ajax({
            url: BASE_URL + '/api/inventario/actualizar',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#editInventarioModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Actualizado',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    cargarInventario();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        });
    });

    // Mover Artículo del Inventario de vuelta a Necesidades
    $(document).on('click', '.btn-move-item-need', function() {
        const id = $(this).closest('tr').data('id');
        const item = inventarioItems.find(x => x.id == id);

        if (item) {
            Swal.fire({
                title: '¿Mover a Necesidades?',
                text: `¿Estás seguro de que quieres trasladar "${item.nombre}" de vuelta a la lista de necesidades?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#D4A3AE',
                cancelButtonColor: '#EBEBEB',
                confirmButtonText: 'Sí, mover',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: BASE_URL + '/api/inventario/mover_necesidades',
                        type: 'POST',
                        data: { id: id },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Trasladado',
                                    text: response.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                cargarInventario();
                                cargarNecesidades();
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        }
                    });
                }
            });
        }
    });

    // Eliminar Artículo del Inventario
    $(document).on('click', '.btn-delete-item', function() {
        const id = $(this).closest('tr').data('id');
        const item = inventarioItems.find(x => x.id == id);

        if (item) {
            Swal.fire({
                title: '¿Eliminar Artículo?',
                text: `¿Estás seguro de que quieres eliminar "${item.nombre}" del inventario?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#D4A3AE',
                cancelButtonColor: '#EBEBEB',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: BASE_URL + '/api/inventario/eliminar',
                        type: 'POST',
                        data: { id: id },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Eliminado',
                                    text: response.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                cargarInventario();
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        }
                    });
                }
            });
        }
    });

    // Decrementar cantidad rápida en inventario
    $(document).on('click', '.btn-qty-minus', function() {
        const id = $(this).closest('tr').data('id');
        const item = inventarioItems.find(x => x.id == id);
        if (item) {
            if (parseInt(item.cantidad) <= 1) {
                Swal.fire({
                    title: '¿Eliminar Artículo?',
                    text: `La cantidad es 1. ¿Desean eliminar "${item.nombre}" del inventario?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#D4A3AE',
                    cancelButtonColor: '#EBEBEB',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        eliminarArticuloInventarioDirecto(id);
                    }
                });
                return;
            }
            actualizarCantidadInventario(item, parseInt(item.cantidad) - 1);
        }
    });

    // Incrementar cantidad rápida en inventario
    $(document).on('click', '.btn-qty-plus', function() {
        const id = $(this).closest('tr').data('id');
        const item = inventarioItems.find(x => x.id == id);
        if (item) {
            actualizarCantidadInventario(item, parseInt(item.cantidad) + 1);
        }
    });

    function actualizarCantidadInventario(item, nuevaCantidad) {
        $.ajax({
            url: BASE_URL + '/api/inventario/actualizar',
            type: 'POST',
            data: {
                id: item.id,
                nombre: item.nombre,
                descripcion: item.descripcion,
                cantidad: nuevaCantidad,
                categoria: item.categoria,
                estado: item.estado,
                valor: item.valor
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    cargarInventario();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        });
    }

    function eliminarArticuloInventarioDirecto(id) {
        $.ajax({
            url: BASE_URL + '/api/inventario/eliminar',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'El artículo ha sido eliminado.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    cargarInventario();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        });
    }


    // ==================== NECESIDADES ACTIONS ====================

    function cargarNecesidades() {
        $.ajax({
            url: BASE_URL + '/api/necesidades',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    necesidadesItems = response.data;
                    filtrarYRenderizarNecesidades();
                    recargarEstadisticasSiActivo();
                }
            }
        });
    }

    function filtrarYRenderizarNecesidades() {
        const query = $('#searchNecesidades').val().toLowerCase().trim();
        const prioridad = $('#filterNecesidadesPrioridad').val();
        const categoria = $('#filterNecesidadesCategoria').val();

        // Filtrar
        const itemsFiltrados = necesidadesItems.filter(item => {
            const matchesQuery = item.nombre.toLowerCase().includes(query) || (item.descripcion && item.descripcion.toLowerCase().includes(query));
            const matchesPrioridad = !prioridad || item.prioridad === prioridad;
            
            let matchesCategoria = true;
            if (categoria) {
                if (categoria === 'Sin Categoría') {
                    matchesCategoria = !item.categoria;
                } else {
                    matchesCategoria = item.categoria === categoria;
                }
            }
            
            return matchesQuery && matchesPrioridad && matchesCategoria;
        });

        const $container = $('#necesidadesContainer');
        $container.empty();

        if (itemsFiltrados.length === 0) {
            $('#necesidadesEmptyState').removeClass('d-none');
        } else {
            $('#necesidadesEmptyState').addClass('d-none');

            itemsFiltrados.forEach(item => {
                const priorityBadgeClass = `badge-priority-${item.prioridad.toLowerCase()}`;
                const acquiredClass = item.adquirido ? 'acquired' : '';
                const btnAcquiredText = item.adquirido ? '<i class="bi bi-check-circle-fill me-1"></i> Adquirido' : '<i class="bi bi-circle me-1"></i> Marcar Adquirido';
                const btnAcquiredClass = item.adquirido ? 'btn-acquired-style' : 'btn-pending-style';
                
                // Botón de trasladar al inventario
                const moveInventoryBtn = item.adquirido 
                    ? `<button class="btn btn-move-inventory btn-move-item-inv text-nowrap" title="Pasar este artículo al inventario"><i class="bi bi-box-arrow-in-right me-1"></i> Mover</button>` 
                    : '';

                const labelText = item.adquirido == 1 ? 'Adquirido' : 'Marcar como adquirido';
                const totalPresupuestoCalculado = item.presupuesto ? (parseFloat(item.presupuesto) * parseInt(item.cantidad)).toFixed(2) : '0.00';
                const presupuestoBadge = item.presupuesto 
                    ? `<div class="mt-2">
                           <span class="badge bg-light text-muted border" style="font-size: 0.75rem;" title="Presupuesto Unitario">
                               <i class="bi bi-wallet2 me-1 text-primary"></i> $${parseFloat(item.presupuesto).toFixed(2)} c/u
                           </span>
                           <span class="badge bg-soft-beige border text-dark ms-1" style="font-size: 0.75rem;" title="Presupuesto Total">
                               Total: $${totalPresupuestoCalculado}
                           </span>
                       </div>` 
                    : '';

                const emojiStr = item.emoji ? `<span class="me-2 fs-4" style="line-height: 1;">${escapeHtml(item.emoji)}</span>` : '';
                const cardHtml = `
                    <div class="col" data-id="${item.id}">
                        <div class="card need-card h-100 p-3 shadow-sm border-0 ${acquiredClass}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge ${priorityBadgeClass} mb-2">Prioridad ${escapeHtml(item.prioridad)}</span>
                                    <span class="item-category-badge ${getCategoryClass(item.categoria)} mb-2 d-inline-block ms-1" style="font-size: 0.72rem; padding: 0.35em 0.65em; vertical-align: top;">${getCategoryIcon(item.categoria)} ${escapeHtml(item.categoria || 'Sin Categoría')}</span>
                                    <h4 class="need-title h5 mb-1 d-flex align-items-center">${emojiStr}${escapeHtml(item.nombre)}</h4>
                                    <div class="text-muted small">${escapeHtml(item.descripcion || 'Sin notas adicionales')}</div>
                                    ${presupuestoBadge}
                                </div>
                                <div class="d-flex flex-column align-items-end">
                                    <div class="text-rose fw-bold fs-5 mb-2">Cant: ${item.cantidad}</div>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-action btn-action-edit btn-edit-need" title="Editar"><i class="bi bi-pencil-fill"></i></button>
                                        <button class="btn btn-action btn-action-delete btn-delete-need" title="Eliminar"><i class="bi bi-trash-fill"></i></button>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-2 opacity-10">
                            
                            <div class="d-flex justify-content-between align-items-center mt-auto pt-2">
                                <div class="small text-muted">
                                    Pedida por: <strong>${escapeHtml(item.creador_nombre)}</strong>
                                </div>
                                <div class="d-inline-flex align-items-center text-nowrap gap-2">
                                    ${moveInventoryBtn}
                                    <div class="d-inline-flex align-items-center text-nowrap">
                                        <input class="btn-toggle-adquirido-need" type="checkbox" id="checkNeed_${item.id}" ${item.adquirido == 1 ? 'checked' : ''} style="cursor: pointer; width: 1.15rem; height: 1.15rem; margin: 0 0.35rem 0 0;">
                                        <label class="small fw-bold text-muted" for="checkNeed_${item.id}" style="cursor: pointer; user-select: none;">${labelText}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $container.append(cardHtml);
            });
        }
    }

    // Filtros de necesidades
    $('#searchNecesidades, #filterNecesidadesPrioridad, #filterNecesidadesCategoria').on('input change', filtrarYRenderizarNecesidades);

    // Agregar Necesidad Form
    $('#addNecesidadForm').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);

        $.ajax({
            url: BASE_URL + '/api/necesidades/crear',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#addNecesidadModal').modal('hide');
                    $form[0].reset();
                    Swal.fire({
                        icon: 'success',
                        title: 'Agregado',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    cargarNecesidades();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        });
    });

    // Abrir Modal de Edición de Necesidad
    $(document).on('click', '.btn-edit-need', function() {
        const id = $(this).closest('.col').data('id');
        const item = necesidadesItems.find(x => x.id == id);

        if (item) {
            $('#eNecesidadId').val(item.id);
            $('#eNecesidadNombre').val(item.nombre);
            $('#eNecesidadDescripcion').val(item.descripcion);
            $('#eNecesidadCantidad').val(item.cantidad);
            $('#eNecesidadCategoria').val(item.categoria || '');
            $('#eNecesidadPrioridad').val(item.prioridad);
            $('#eNecesidadPresupuesto').val(item.presupuesto);
            $('#eNecesidadEmoji').val(item.emoji);
            
            // Prellenar presupuesto total
            const total = item.presupuesto ? (parseFloat(item.presupuesto) * parseInt(item.cantidad)).toFixed(2) : '';
            $('#eNecesidadPresupuestoTotal').val(total);
            
            $('#eNecesidadAdquirido').prop('checked', item.adquirido == 1);
            $('#editNecesidadModal').modal('show');
        }
    });

    // Guardar Edición de Necesidad Form
    $('#editNecesidadForm').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);

        $.ajax({
            url: BASE_URL + '/api/necesidades/actualizar',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#editNecesidadModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Actualizado',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    cargarNecesidades();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        });
    });

    // Alternar Adquirido / Pendiente
    $(document).on('change', '.btn-toggle-adquirido-need', function() {
        const id = $(this).closest('.col').data('id');
        const item = necesidadesItems.find(x => x.id == id);
        
        if (item) {
            const nuevoEstado = this.checked ? 1 : 0;
            
            $.ajax({
                url: BASE_URL + '/api/necesidades/marcar_adquirido',
                type: 'POST',
                data: {
                    id: id,
                    adquirido: nuevoEstado
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        if (nuevoEstado === 1) {
                            // Si se marca como adquirido, ofrecer mover directamente a inventario
                            Swal.fire({
                                icon: 'success',
                                title: '¡Excelente!',
                                text: 'Artículo marcado como adquirido. ¿Desean trasladarlo directamente al inventario de cosas que ya tienen?',
                                showCancelButton: true,
                                confirmButtonColor: '#D4A3AE',
                                cancelButtonColor: '#EBEBEB',
                                confirmButtonText: 'Sí, mover al inventario',
                                cancelButtonText: 'No, dejar en la lista'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    moverNecesidadAInventario(id);
                                } else {
                                    cargarNecesidades();
                                }
                            });
                        } else {
                            cargarNecesidades();
                        }
                    } else {
                        cargarNecesidades();
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    cargarNecesidades();
                    Swal.fire('Error', 'Error al actualizar el estado.', 'error');
                }
            });
        }
    });

    // Trasladar Necesidad a Inventario directamente
    $(document).on('click', '.btn-move-item-inv', function() {
        const id = $(this).closest('.col').data('id');
        moverNecesidadAInventario(id);
    });

    function moverNecesidadAInventario(id) {
        $.ajax({
            url: BASE_URL + '/api/necesidades/mover_inventario',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Traslado exitoso!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    // Recargar ambas vistas
                    cargarInventario();
                    cargarNecesidades();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        });
    }

    // Eliminar Necesidad
    $(document).on('click', '.btn-delete-need', function() {
        const id = $(this).closest('.col').data('id');
        const item = necesidadesItems.find(x => x.id == id);

        if (item) {
            Swal.fire({
                title: '¿Eliminar Necesidad?',
                text: `¿Estás seguro de que quieres eliminar "${item.nombre}" de la lista?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#D4A3AE',
                cancelButtonColor: '#EBEBEB',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: BASE_URL + '/api/necesidades/eliminar',
                        type: 'POST',
                        data: { id: id },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Eliminada',
                                    text: response.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                cargarNecesidades();
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        }
                    });
                }
            });
        }
    });


    // ==================== HELPERS ====================

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // ==================== STATISTICS & CHARTS ====================
    
    // Variables de instancias de gráficos
    let chartComprasMesInstance = null;
    let chartPrioridadInstance = null;
    let chartCategoriaInstance = null;

    // Listener para recargar estadísticas al entrar en la pestaña
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        if (e.target.id === 'estadisticas-tab') {
            cargarEstadisticas();
        }
    });

    // Botón de actualizar estadísticas manualmente
    $('#btnRefreshStats').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1.5"></span> Actualizando...');
        
        cargarEstadisticas();
        
        setTimeout(() => {
            $btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise me-1.5"></i> Actualizar');
        }, 800);
    });

    function recargarEstadisticasSiActivo() {
        if ($('#estadisticas-tab').hasClass('active')) {
            cargarEstadisticas();
        }
    }

    function cargarEstadisticas() {
        $.ajax({
            url: BASE_URL + '/api/proyecto/estadisticas',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    renderEstadisticas(response.data);
                }
            },
            error: function() {
                console.error("Error al cargar estadísticas de proyecto.");
            }
        });
    }

    function formatCurrency(val) {
        return '$' + parseFloat(val).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function renderEstadisticas(data) {
        // 1. Renderizar KPI
        $('#statTotalInversion').text(formatCurrency(data.total_inversion));
        $('#statTotalPresupuesto').text(formatCurrency(data.total_presupuesto));
        
        const progreso = data.progreso;
        $('#statProgresoPorcentaje').text(progreso.porcentaje + '%');
        $('#statProgresoBar').css('width', progreso.porcentaje + '%').attr('aria-valuenow', progreso.porcentaje);
        $('#statProgresoAdquirido').text(`${progreso.adquirido} adquiridos`);
        $('#statProgresoTotal').text(`${progreso.total} total`);

        // 2. Renderizar Resumen de Planificación
        $('#statSummaryInventarioQty').text(progreso.adquirido);
        $('#statSummaryNecesidadesQty').text(progreso.pendiente);
        
        const avgPresupuesto = progreso.pendiente > 0 ? (data.total_presupuesto / progreso.pendiente) : 0;
        const avgValor = progreso.adquirido > 0 ? (data.total_inversion / progreso.adquirido) : 0;
        
        $('#statSummaryAvgPresupuesto').text(formatCurrency(avgPresupuesto));
        $('#statSummaryAvgValor').text(formatCurrency(avgValor));

        // 3. Gráfico: Compras e Inversión por Mes (Mixed)
        const labelsMes = data.compras_mes.map(item => {
            const parts = item.mes.split('-');
            if (parts.length === 2) {
                const date = new Date(parts[0], parts[1] - 1, 1);
                return date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
            }
            return item.mes;
        });

        const ctxMes = document.getElementById('chartComprasMes').getContext('2d');
        if (chartComprasMesInstance) {
            chartComprasMesInstance.destroy();
        }
        chartComprasMesInstance = new Chart(ctxMes, {
            type: 'bar',
            data: {
                labels: labelsMes.length > 0 ? labelsMes : ['Sin Datos'],
                datasets: [
                    {
                        label: 'Inversión ($)',
                        data: labelsMes.length > 0 ? data.compras_mes.map(item => parseFloat(item.total_inversion)) : [0],
                        backgroundColor: 'rgba(212, 163, 174, 0.6)',
                        borderColor: '#D4A3AE',
                        borderWidth: 2,
                        borderRadius: 6,
                        yAxisID: 'y-inversion',
                        order: 2
                    },
                    {
                        label: 'Artículos Comprados',
                        data: labelsMes.length > 0 ? data.compras_mes.map(item => parseInt(item.cantidad_total)) : [0],
                        type: 'line',
                        fill: false,
                        borderColor: '#4E8A6D',
                        backgroundColor: '#4E8A6D',
                        tension: 0.3,
                        borderWidth: 3,
                        pointBackgroundColor: '#4E8A6D',
                        pointRadius: 4,
                        yAxisID: 'y-cantidad',
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { family: 'Plus Jakarta Sans', size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.datasetIndex === 0) {
                                    label += '$' + context.raw.toFixed(2);
                                } else {
                                    label += context.raw;
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    'y-inversion': {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Inversión ($)',
                            font: { family: 'Plus Jakarta Sans', weight: 'bold' }
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    },
                    'y-cantidad': {
                        type: 'linear',
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'Cantidad',
                            font: { family: 'Plus Jakarta Sans', weight: 'bold' }
                        },
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // 4. Gráfico: Necesidades por Prioridad (Doughnut)
        const priorities = ['Alta', 'Media', 'Baja'];
        const valuesPrioridad = priorities.map(p => {
            const found = data.necesidades_prioridad.find(item => item.prioridad === p);
            return found ? parseInt(found.cantidad) : 0;
        });

        const ctxP = document.getElementById('chartPrioridad').getContext('2d');
        if (chartPrioridadInstance) {
            chartPrioridadInstance.destroy();
        }
        chartPrioridadInstance = new Chart(ctxP, {
            type: 'doughnut',
            data: {
                labels: priorities,
                datasets: [{
                    data: valuesPrioridad,
                    backgroundColor: [
                        'rgba(217, 83, 79, 0.65)',
                        'rgba(218, 119, 36, 0.65)',
                        'rgba(51, 122, 183, 0.65)'
                    ],
                    borderColor: [
                        '#D9534F',
                        '#DA7724',
                        '#337AB7'
                    ],
                    borderWidth: 1.5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: { family: 'Plus Jakarta Sans', size: 11 }
                        }
                    }
                },
                cutout: '65%'
            }
        });

        // 5. Gráfico: Comparativa por Categoría (Realizado vs Estimado)
        const categoriesList = ['Electrodomésticos', 'Muebles', 'Cocina', 'Dormitorio', 'Baño', 'Tecnología', 'Decoración', 'Boda', 'Otro', 'Sin Categoría'];
        
        const spentData = categoriesList.map(cat => {
            const found = data.inventario_categoria.find(item => item.categoria === cat);
            return found ? parseFloat(found.inversion_total) : 0;
        });
        
        const budgetData = categoriesList.map(cat => {
            const found = data.necesidades_categoria.find(item => item.categoria === cat);
            return found ? parseFloat(found.presupuesto_total) : 0;
        });

        const ctxC = document.getElementById('chartCategoria').getContext('2d');
        if (chartCategoriaInstance) {
            chartCategoriaInstance.destroy();
        }
        chartCategoriaInstance = new Chart(ctxC, {
            type: 'bar',
            data: {
                labels: categoriesList,
                datasets: [
                    {
                        label: 'Inversión Realizada ($)',
                        data: spentData,
                        backgroundColor: 'rgba(212, 163, 174, 0.6)',
                        borderColor: '#D4A3AE',
                        borderWidth: 1.5,
                        borderRadius: 4
                    },
                    {
                        label: 'Presupuesto Estimado ($)',
                        data: budgetData,
                        backgroundColor: 'rgba(78, 138, 109, 0.6)',
                        borderColor: '#4E8A6D',
                        borderWidth: 1.5,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: { family: 'Plus Jakarta Sans', size: 10 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.dataset.label.split(' ')[0]}: $${context.raw.toFixed(2)}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    function formatearFechaHumana(dateStr) {
        if (!dateStr) return '';
        const dateObj = new Date(dateStr.replace(/-/g, '/'));
        return dateObj.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    // ==================== MODAL HELPERS (QTY BUTTONS & DUAL PRICING) ====================

    // Disminuir cantidad en modales
    $(document).on('click', '.btn-modal-qty-minus', function() {
        const $input = $(this).siblings('.input-modal-qty');
        let val = parseInt($input.val()) || 1;
        if (val > 1) {
            $input.val(val - 1).trigger('input');
        }
    });

    // Incrementar cantidad en modales
    $(document).on('click', '.btn-modal-qty-plus', function() {
        const $input = $(this).siblings('.input-modal-qty');
        let val = parseInt($input.val()) || 1;
        $input.val(val + 1).trigger('input');
    });

    // Recalcular total cuando cambia el unitario o la cantidad
    $(document).on('input change', '.input-modal-unit-price, .input-modal-qty', function() {
        const $modal = $(this).closest('.modal');
        const $qtyInput = $modal.find('.input-modal-qty');
        const $unitInput = $modal.find('.input-modal-unit-price');
        const $totalInput = $modal.find('.input-modal-total-price');

        const qty = parseInt($qtyInput.val()) || 1;
        const unit = parseFloat($unitInput.val()) || 0;

        if (unit > 0) {
            $totalInput.val((unit * qty).toFixed(2));
        } else {
            $totalInput.val('');
        }
    });

    // Recalcular unitario cuando cambia el total
    $(document).on('input', '.input-modal-total-price', function() {
        const $modal = $(this).closest('.modal');
        const $qtyInput = $modal.find('.input-modal-qty');
        const $unitInput = $modal.find('.input-modal-unit-price');

        const qty = parseInt($qtyInput.val()) || 1;
        const total = parseFloat($(this).val()) || 0;

        if (total > 0) {
            $unitInput.val((total / qty).toFixed(2));
        } else {
            $unitInput.val('');
        }
    });

    function getCategoryIcon(category) {
        switch (category) {
            case 'Electrodomésticos': return '<i class="bi bi-plug-fill me-1 text-muted"></i>';
            case 'Muebles': return '<i class="bi bi-lamp-fill me-1 text-muted"></i>';
            case 'Cocina': return '<i class="bi bi-cup-hot-fill me-1 text-muted"></i>';
            case 'Dormitorio': return '<i class="bi bi-moon-stars-fill me-1 text-muted"></i>';
            case 'Baño': return '<i class="bi bi-droplet-fill me-1 text-muted"></i>';
            case 'Tecnología': return '<i class="bi bi-laptop-fill me-1 text-muted"></i>';
            case 'Decoración': return '<i class="bi bi-flower1 me-1 text-muted"></i>';
            case 'Boda': return '<i class="bi bi-gem me-1 text-muted"></i>';
            default: return '<i class="bi bi-tag-fill me-1 text-muted"></i>';
        }
    }

    function getCategoryClass(category) {
        if (!category) return 'badge-cat-default';
        const normalized = category
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9]/g, '');
        return 'badge-cat-' + normalized;
    }

    // ==================== PROFILE MANAGEMENT & ADMIN SOLICITUDES ====================

    function cargarPerfilNavbar() {
        $.ajax({
            url: BASE_URL + '/api/perfil',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    const user = response.data;
                    $('#navUserName').text(user.nombre);
                    if (user.foto) {
                        $('#navUserAvatar').attr('src', BASE_URL + '/view/' + user.foto);
                    } else {
                        $('#navUserAvatar').attr('src', "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23D4A3AE'><path d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/></svg>");
                    }
                }
            }
        });
    }

    // Abrir modal de perfil
    $('#btnOpenPerfil').on('click', function() {
        // Cargar datos actuales
        $.ajax({
            url: BASE_URL + '/api/perfil',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.data) {
                    const user = response.data;
                    $('#profileNombre').val(user.nombre);
                    $('#profileApellido').val(user.apellido || '');
                    $('#profileTlf').val(user.tlf || '');
                    $('#profileEmail').val(user.email);
                    
                    if (user.foto) {
                        $('#profileAvatarPreview').attr('src', BASE_URL + '/view/' + user.foto);
                    } else {
                        $('#profileAvatarPreview').attr('src', "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23D4A3AE'><path d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/></svg>");
                    }
                    
                    // Resetear pestaña y form de contraseña
                    $('#profile-datos-tab').tab('show');
                    $('#profilePassForm')[0].reset();
                    
                    $('#perfilModal').modal('show');
                } else {
                    Swal.fire('Error', 'No se pudieron cargar los datos de tu perfil.', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error de red al cargar el perfil.', 'error');
            }
        });
    });

    // Vista previa de la foto seleccionada
    $('#profileFoto').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validar tipo y tamaño antes de previsualizar
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                Swal.fire('Tipo no permitido', 'Por favor selecciona una imagen JPG, PNG, GIF o WEBP.', 'warning');
                $(this).val('');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                Swal.fire('Archivo muy grande', 'La imagen no debe pesar más de 10MB.', 'warning');
                $(this).val('');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                $('#profileAvatarPreview').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    // Guardar cambios datos personales
    $('#profileDatosForm').on('submit', function(e) {
        e.preventDefault();
        
        const nombre = $('#profileNombre').val().trim();
        if (nombre === '') {
            Swal.fire('Campo requerido', 'El nombre es obligatorio.', 'warning');
            return;
        }

        const formData = new FormData(this);
        const $submitBtn = $(this).find('button[type="submit"]');
        const originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Guardando...');

        $.ajax({
            url: BASE_URL + '/api/perfil/actualizar',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                $submitBtn.prop('disabled', false).text(originalText);
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Perfil Actualizado',
                        text: response.message,
                        timer: 1550,
                        showConfirmButton: false
                    });
                    
                    // Actualizar en el navbar
                    $('#navUserName').text(response.data.nombre);
                    if (response.data.foto) {
                        $('#navUserAvatar').attr('src', BASE_URL + '/view/' + response.data.foto);
                    }
                    
                    // Limpiar input file
                    $('#profileFoto').val('');
                    
                    // Actualizar creador_nombre en el proyecto si coincide
                    cargarProyecto();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false).text(originalText);
                let msg = 'No se pudo guardar la información del perfil.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    // Guardar cambios contraseña
    $('#profilePassForm').on('submit', function(e) {
        e.preventDefault();
        
        const currentPass = $('#profileCurrentPass').val();
        const newPass = $('#profileNewPass').val();
        const confirmPass = $('#profileConfirmPass').val();

        if (currentPass === '' || newPass === '' || confirmPass === '') {
            Swal.fire('Campos requeridos', 'Todos los campos son obligatorios.', 'warning');
            return;
        }

        if (newPass !== confirmPass) {
            Swal.fire('Contraseñas no coinciden', 'La nueva contraseña y su confirmación no coinciden.', 'warning');
            return;
        }

        if (newPass.length < 6) {
            Swal.fire('Contraseña corta', 'La nueva contraseña debe tener al menos 6 caracteres.', 'warning');
            return;
        }

        const $submitBtn = $(this).find('button[type="submit"]');
        const originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Actualizando...');

        $.ajax({
            url: BASE_URL + '/api/perfil/cambiar_password',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $submitBtn.prop('disabled', false).text(originalText);
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Contraseña Actualizada',
                        text: response.message,
                        timer: 1550,
                        showConfirmButton: false
                    }).then(() => {
                        $('#perfilModal').modal('hide');
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false).text(originalText);
                let msg = 'No se pudo cambiar la contraseña.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    // ==================== ADMIN PANEL FOR REGISTRATION APPROVALS ====================
    if ($('#profile-admin-tab').length > 0) {
        // Cargar solicitudes cuando se entra al tab de admin
        $('#profile-admin-tab').on('shown.bs.tab', function () {
            cargarSolicitudesPendientes();
        });

        $('#btnRefreshSolicitudes').on('click', function() {
            cargarSolicitudesPendientes();
        });

        function cargarSolicitudesPendientes() {
            const $body = $('#adminSolicitudesBody');
            $body.html('<tr><td colspan="3" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2 text-rose"></span>Cargando solicitudes...</td></tr>');
            $('#noSolicitudesAlert').addClass('d-none');
            $('#tableSolicitudes').removeClass('d-none');

            $.ajax({
                url: BASE_URL + '/api/admin/solicitudes',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        const items = response.data || [];
                        if (items.length === 0) {
                            $body.empty();
                            $('#tableSolicitudes').addClass('d-none');
                            $('#noSolicitudesAlert').removeClass('d-none');
                            return;
                        }

                        let html = '';
                        items.forEach(function(item) {
                            const dateStr = formatearFechaHumana(item.created_at);
                            const tlf = item.tlf ? item.tlf : 'No especificado';
                            const apellido = item.apellido ? item.apellido : '';
                            const fullNombre = `${item.nombre} ${apellido}`.trim();
                            
                            html += `
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">${escapeHtml(fullNombre)}</div>
                                        <div class="text-muted small" style="font-size: 0.8rem;">${escapeHtml(item.email)}</div>
                                        <div class="text-muted small" style="font-size: 0.75rem;"><i class="bi bi-telephone me-1"></i>${escapeHtml(tlf)}</div>
                                    </td>
                                    <td>
                                        <div class="bg-light p-2 rounded text-muted" style="white-space: pre-wrap; font-size: 0.78rem; line-height: 1.3; max-width: 250px;">${escapeHtml(item.mensaje_registro || 'Sin mensaje')}</div>
                                        <div class="text-muted mt-1" style="font-size: 0.7rem;"><i class="bi bi-calendar-event me-1"></i>${dateStr}</div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex gap-1.5 justify-content-end">
                                            <button class="btn btn-sm btn-success-pastel text-dark btn-aprobar-solicitud px-2 py-1" data-id="${item.id}" data-name="${escapeHtml(item.nombre)}" title="Aprobar">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger-pastel text-dark btn-rechazar-solicitud px-2 py-1" data-id="${item.id}" data-name="${escapeHtml(item.nombre)}" title="Rechazar">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                        $body.html(html);
                    } else {
                        $body.html('<tr><td colspan="3" class="text-center text-danger py-4">Error al cargar las solicitudes.</td></tr>');
                    }
                },
                error: function() {
                    $body.html('<tr><td colspan="3" class="text-center text-danger py-4">Error de conexión al cargar solicitudes.</td></tr>');
                }
            });
        }

        // Acciones de Aprobación / Rechazo
        $(document).on('click', '.btn-aprobar-solicitud', function() {
            const id = $(this).data('id');
            const nombre = $(this).data('name');
            procesarSolicitudAdmin(id, nombre, 'aprobar');
        });

        $(document).on('click', '.btn-rechazar-solicitud', function() {
            const id = $(this).data('id');
            const nombre = $(this).data('name');
            procesarSolicitudAdmin(id, nombre, 'rechazar');
        });

        function procesarSolicitudAdmin(id, nombre, accion) {
            const titulo = accion === 'aprobar' ? '¿Aprobar solicitud?' : '¿Rechazar solicitud?';
            const texto = accion === 'aprobar' 
                ? `El usuario ${nombre} podrá acceder al sistema con su correo y contraseña.` 
                : `Se denegará el acceso al usuario ${nombre}.`;
            const confirmColor = accion === 'aprobar' ? '#A8E6CF' : '#FFD3B6';
            const actionText = accion === 'aprobar' ? 'Aprobar' : 'Rechazar';

            Swal.fire({
                title: titulo,
                text: texto,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#EBEBEB',
                confirmButtonText: `Sí, ${actionText}`,
                cancelButtonText: 'Cancelar',
                customClass: {
                    confirmButton: 'text-dark fw-bold'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.showLoading();
                    $.ajax({
                        url: BASE_URL + '/api/admin/solicitudes/procesar',
                        type: 'POST',
                        data: {
                            id: id,
                            accion: accion
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: response.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                cargarSolicitudesPendientes();
                                // También refrescar el listado de parejas por si ahora está disponible para asignar
                                if (typeof cargarParejasDisponibles === 'function') {
                                    cargarParejasDisponibles();
                                }
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'No se pudo completar la operación en el servidor.', 'error');
                        }
                    });
                }
            });
        }
    }

    // ==========================================================================
    // FEED DE PAREJAS (RED SOCIAL) - FUNCIONALIDAD COMPLETA
    // ==========================================================================
    let feedPosts = [];
    let proyectoActualId = null;

    function cargarFeed() {
        $.ajax({
            url: BASE_URL + '/api/feed',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    feedPosts = response.data;
                    proyectoActualId = response.proyecto_actual_id;
                    renderFeed(feedPosts);
                } else {
                    $('#globalFeedContainer').html(`
                        <div class="alert alert-warning border-0 shadow-sm text-center p-4">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-3 mb-2"></i>
                            <p class="mb-0">${escapeHtml(response.message || 'No se pudieron cargar las publicaciones.')}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                $('#globalFeedContainer').html(`
                    <div class="alert alert-danger border-0 shadow-sm text-center p-4">
                        <i class="bi bi-x-circle-fill text-danger fs-3 mb-2"></i>
                        <p class="mb-0">Error de conexión al cargar el feed.</p>
                    </div>
                `);
            }
        });
    }

    function renderFeed(posts) {
        const $container = $('#globalFeedContainer');
        $container.empty();

        if (posts.length === 0) {
            $container.html(`
                <div class="text-center py-5 bg-white rounded-4 border p-4 shadow-sm">
                    <i class="bi bi-postcard-heart text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
                    <h5 class="mt-3 font-heading text-dark">El muro está vacío</h5>
                    <p class="text-muted small">Sé el primero en compartir un estado o foto de su historia de amor.</p>
                </div>
            `);
            return;
        }

        posts.forEach(post => {
            const esMio = parseInt(post.proyecto_vida_id) === parseInt(proyectoActualId);
            const tieneFoto = post.foto ? true : false;
            const tieneShared = post.shared_post_id ? true : false;

            // Foto de perfil de pareja
            const avatarUrl = post.proyecto_foto 
                ? (BASE_URL + '/view/' + post.proyecto_foto) 
                : `data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23D4A3AE'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/></svg>`;

            // Nombre de pareja
            const nombresPareja = post.pareja_nombre ? `${post.creador_nombre} & ${post.pareja_nombre}` : post.creador_nombre;
            const tituloPerfil = `${escapeHtml(post.proyecto_nombre)} <span class="text-muted fs-6 font-body">(${escapeHtml(nombresPareja)})</span>`;

            // Botón de eliminar si es mío
            const deleteDropdown = esMio 
                ? `<div class="dropdown ms-auto">
                       <button class="btn btn-link text-muted p-1 border-0 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                           <i class="bi bi-three-dots-vertical"></i>
                       </button>
                       <ul class="dropdown-menu dropdown-menu-end border shadow-sm" style="z-index: 1050;">
                           <li>
                               <a class="dropdown-item text-danger btn-delete-post" href="#" data-id="${post.id}">
                                   <i class="bi bi-trash-fill me-2"></i>Eliminar Publicación
                               </a>
                           </li>
                       </ul>
                   </div>`
                : '';

            // Renderizar contenido de la publicación
            const contentHtml = post.contenido ? `<p class="feed-post-content mb-3">${escapeHtml(post.contenido)}</p>` : '';
            const imgHtml = tieneFoto 
                ? `<div class="mb-3 text-center">
                       <img src="${BASE_URL}/view/${escapeHtml(post.foto)}" class="img-fluid feed-post-img rounded shadow-sm">
                   </div>` 
                : '';

            // Renderizar publicación compartida
            let sharedHtml = '';
            if (tieneShared) {
                if (post.shared_contenido !== null) {
                    const sharedAvatarUrl = post.shared_proyecto_foto 
                        ? (BASE_URL + '/view/' + post.shared_proyecto_foto) 
                        : `data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23D4A3AE'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/></svg>`;

                    const sharedImgHtml = post.shared_foto 
                        ? `<div class="mt-2 text-center">
                               <img src="${BASE_URL}/view/${escapeHtml(post.shared_foto)}" class="img-fluid rounded" style="max-height: 250px; object-fit: cover; width: 100%;">
                           </div>` 
                        : '';

                    sharedHtml = `
                        <div class="shared-post-container border rounded p-3 mb-3 bg-light">
                            <div class="d-flex align-items-center mb-2">
                                <img src="${sharedAvatarUrl}" class="rounded-circle feed-post-avatar me-2" alt="Avatar Compartido" style="width: 32px; height: 32px;">
                                <div>
                                    <h6 class="mb-0 small fw-bold text-dark font-heading">${escapeHtml(post.shared_proyecto_nombre)}</h6>
                                    <span class="text-muted small" style="font-size: 0.75rem;">${calcularTiempoHace(post.shared_created_at)}</span>
                                </div>
                            </div>
                            <p class="mb-0 text-muted small">${escapeHtml(post.shared_contenido)}</p>
                            ${sharedImgHtml}
                        </div>
                    `;
                } else {
                    sharedHtml = `
                        <div class="alert alert-light border rounded small text-muted p-3 mb-3">
                            <i class="bi bi-info-circle me-1.5"></i>Esta publicación compartida ya no está disponible.
                        </div>
                    `;
                }
            }

            // Summary of reactions and comments
            const totalReacciones = parseInt(post.reacciones.total_general) || 0;
            const totalComentarios = post.comentarios.length || 0;

            let reacsSummaryHtml = '';
            if (totalReacciones > 0) {
                let badges = '';
                const types = post.reacciones.totales;
                for (const type in types) {
                    badges += `${obtenerEmojiReaccion(type)} `;
                }
                reacsSummaryHtml = `
                    <div class="reactions-summary d-flex align-items-center py-2 px-3">
                        <span class="me-1">${badges}</span>
                        <span class="fw-semibold text-rose">${totalReacciones} ${totalReacciones === 1 ? 'reacción' : 'reacciones'}</span>
                        <span class="ms-auto">${totalComentarios} ${totalComentarios === 1 ? 'comentario' : 'comentarios'}</span>
                    </div>
                `;
            } else {
                reacsSummaryHtml = `
                    <div class="reactions-summary d-flex align-items-center py-2 px-3 text-muted" style="font-size:0.75rem;">
                        <span>Sé el primero en reaccionar</span>
                        <span class="ms-auto">${totalComentarios} ${totalComentarios === 1 ? 'comentario' : 'comentarios'}</span>
                    </div>
                `;
            }

            // Reacción actual
            const reacAct = post.reaccion_actual;
            const reacActClase = reacAct ? 'active' : '';
            const reacActNombre = reacAct ? obtenerNombreReaccion(reacAct) : 'Reaccionar';
            const reacActEmoji = reacAct ? obtenerEmojiReaccion(reacAct) : '<i class="bi bi-heart me-1.5"></i>';
            const reacActColor = reacAct ? obtenerColorReaccion(reacAct) : 'inherit';

            // Comentarios HTML
            let commentsListHtml = '';
            if (post.comentarios && post.comentarios.length > 0) {
                post.comentarios.forEach(com => {
                    const comEsMio = parseInt(com.proyecto_vida_id) === parseInt(proyectoActualId);
                    const deleteComBtn = comEsMio 
                        ? `<i class="bi bi-trash btn-delete-comment ms-2" data-id="${com.id}" title="Eliminar comentario"></i>`
                        : '';
                    const comAvatar = com.proyecto_foto 
                        ? (BASE_URL + '/view/' + com.proyecto_foto) 
                        : `data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23D4A3AE'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/></svg>`;

                    commentsListHtml += `
                        <div class="comment-item d-flex align-items-start mb-3" data-id="${com.id}">
                            <img src="${comAvatar}" class="rounded-circle comment-avatar me-2" alt="Avatar" style="width: 32px; height: 32px;">
                            <div class="flex-grow-1">
                                <div class="comment-bubble">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div>
                                            <span class="comment-author-name">${escapeHtml(com.proyecto_nombre)}</span>
                                            <span class="comment-author-sub">(${escapeHtml(com.autor_nombre)})</span>
                                        </div>
                                        ${deleteComBtn}
                                    </div>
                                    <p class="comment-text">${escapeHtml(com.contenido)}</p>
                                </div>
                                <span class="comment-time ms-2">${calcularTiempoHace(com.created_at)}</span>
                            </div>
                        </div>
                    `;
                });
            }

            const postCard = `
                <div class="card feed-post-card shadow-sm border-0" data-id="${post.id}">
                    <div class="card-body p-4 pb-3">
                        <!-- Post Header -->
                        <div class="d-flex align-items-center mb-3">
                            <img src="${avatarUrl}" class="rounded-circle feed-post-avatar me-3" alt="Avatar de Pareja">
                            <div>
                                <h5 class="mb-0 feed-post-title fs-6">${tituloPerfil}</h5>
                                <span class="feed-post-time">${calcularTiempoHace(post.created_at)}</span>
                            </div>
                            ${deleteDropdown}
                        </div>

                        <!-- Post Body Content -->
                        ${contentHtml}
                        ${imgHtml}
                        ${sharedHtml}

                        <!-- Summary rows -->
                        ${reacsSummaryHtml}

                        <!-- Action buttons -->
                        <div class="feed-post-actions d-flex justify-content-around align-items-center">
                            <div class="reactions-container">
                                <div class="reactions-tray border shadow-sm rounded-pill bg-white">
                                    <button type="button" class="reaction-emoji-btn" data-id="${post.id}" data-type="me_gusta" title="Me gusta">👍</button>
                                    <button type="button" class="reaction-emoji-btn" data-id="${post.id}" data-type="me_encanta" title="Me encanta">❤️</button>
                                    <button type="button" class="reaction-emoji-btn" data-id="${post.id}" data-type="me_divierte" title="Me divierte">😆</button>
                                    <button type="button" class="reaction-emoji-btn" data-id="${post.id}" data-type="me_asombra" title="Me asombra">😮</button>
                                    <button type="button" class="reaction-emoji-btn" data-id="${post.id}" data-type="me_entristece" title="Me entristece">😢</button>
                                    <button type="button" class="reaction-emoji-btn" data-id="${post.id}" data-type="me_enoja" title="Me enoja">😡</button>
                                </div>
                                <button type="button" class="btn btn-feed-action btn-reaction-toggle ${reacActClase}" data-id="${post.id}" style="color: ${reacActColor};">
                                    ${reacActEmoji} <span class="d-none d-sm-inline">${reacActNombre}</span>
                                </button>
                            </div>

                            <button type="button" class="btn btn-feed-action btn-comment-focus" data-id="${post.id}">
                                <i class="bi bi-chat-left text-muted me-1.5"></i> <span class="d-none d-sm-inline">Comentar</span>
                            </button>

                            <button type="button" class="btn btn-feed-action btn-share-post" data-id="${post.id}" data-project="${escapeHtml(post.proyecto_nombre)}" data-content="${escapeHtml(post.contenido || '')}" data-img="${post.foto ? `${BASE_URL}/view/${post.foto}` : ''}" data-avatar="${post.proyecto_foto ? `${BASE_URL}/view/${post.proyecto_foto}` : ''}" data-date="${calcularTiempoHace(post.created_at)}">
                                <i class="bi bi-share text-muted me-1.5"></i> <span class="d-none d-sm-inline">Compartir</span>
                            </button>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div class="comments-section">
                        <div class="comments-list-container mb-3" id="comments-list-${post.id}">
                            ${commentsListHtml}
                        </div>
                        
                        <!-- Comment Form -->
                        <form class="comment-form" data-id="${post.id}">
                            <div class="comment-input-area">
                                <input type="text" class="form-control comment-form-control flex-grow-1" placeholder="Escribe un comentario..." name="contenido" required>
                                <button type="submit" class="btn btn-primary-custom btn-comment-submit shadow-none">
                                    <i class="bi bi-send-fill text-white" style="font-size:0.9rem;"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            $container.append(postCard);
        });
    }

    function calcularTiempoHace(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr.replace(/-/g, '/'));
        const now = new Date();
        const diffMs = now - date;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHr = Math.floor(diffMin / 60);
        const diffDays = Math.floor(diffHr / 24);

        if (diffSec < 60) return 'Hace un momento';
        if (diffMin < 60) return `Hace ${diffMin} min`;
        if (diffHr < 24) return `Hace ${diffHr} ${diffHr === 1 ? 'hora' : 'horas'}`;
        if (diffDays < 7) return `Hace ${diffDays} ${diffDays === 1 ? 'día' : 'días'}`;
        
        return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function obtenerEmojiReaccion(tipo) {
        switch(tipo) {
            case 'me_gusta': return '👍';
            case 'me_encanta': return '❤️';
            case 'me_divierte': return '😆';
            case 'me_asombra': return '😮';
            case 'me_entristece': return '😢';
            case 'me_enoja': return '😡';
            default: return '👍';
        }
    }

    function obtenerNombreReaccion(tipo) {
        switch(tipo) {
            case 'me_gusta': return 'Me gusta';
            case 'me_encanta': return 'Me encanta';
            case 'me_divierte': return 'Me divierte';
            case 'me_asombra': return 'Me asombra';
            case 'me_entristece': return 'Me entristece';
            case 'me_enoja': return 'Me enoja';
            default: return 'Me gusta';
        }
    }

    function obtenerColorReaccion(tipo) {
        switch(tipo) {
            case 'me_gusta': return '#D4A3AE'; // rose
            case 'me_encanta': return '#dc3545'; // red
            case 'me_divierte': return '#ffc107'; // yellow
            case 'me_asombra': return '#ffc107'; // yellow
            case 'me_entristece': return '#0dcaf0'; // teal
            case 'me_enoja': return '#fd7e14'; // orange
            default: return 'var(--text-muted)';
        }
    }

    // Eventos del Feed
    $('#btnTriggerPostFile').on('click', function() {
        $('#postFoto').trigger('click');
    });

    $('#postFoto').on('change', function() {
        const file = this.files[0];
        if (file) {
            if (file.size > 10 * 1024 * 1024) {
                Swal.fire('Imagen muy pesada', 'La imagen no debe superar los 10MB.', 'warning');
                $(this).val('');
                return;
            }
            if (!file.type.match('image.*')) {
                Swal.fire('Formato incorrecto', 'Por favor selecciona un archivo de imagen válido.', 'warning');
                $(this).val('');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                $('#postImagePreview').attr('src', e.target.result);
                $('#postImagePreviewContainer').removeClass('d-none');
            }
            reader.readAsDataURL(file);
        }
    });

    $('#btnRemovePostImage').on('click', function() {
        $('#postFoto').val('');
        $('#postImagePreview').attr('src', '');
        $('#postImagePreviewContainer').addClass('d-none');
    });

    $('#feedPostForm').on('submit', function(e) {
        e.preventDefault();
        
        const contenido = $('#postContenido').val().trim();
        const fileInput = $('#postFoto')[0];
        const tieneArchivo = fileInput.files && fileInput.files[0];

        if (!contenido && !tieneArchivo) {
            Swal.fire('Publicación vacía', 'Escribe un estado o sube una foto antes de publicar.', 'warning');
            return;
        }

        const formData = new FormData(this);
        $('#btnSubmitPost').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Publicando...');

        $.ajax({
            url: BASE_URL + '/api/feed/crear',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                $('#btnSubmitPost').prop('disabled', false).html('<i class="bi bi-send-fill me-1.5"></i> Publicar');
                if (response.status === 'success') {
                    $('#feedPostForm')[0].reset();
                    $('#btnRemovePostImage').click();
                    cargarFeed();
                    Swal.fire({
                        icon: 'success',
                        title: 'Publicado con éxito',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                $('#btnSubmitPost').prop('disabled', false).html('<i class="bi bi-send-fill me-1.5"></i> Publicar');
                Swal.fire('Error', 'No se pudo enviar la publicación al servidor.', 'error');
            }
        });
    });

    // Reacciones
    $(document).on('click', '.reaction-emoji-btn', function() {
        const postId = $(this).data('id');
        const tipo = $(this).data('type');
        ejecutarReaccion(postId, tipo);
    });

    $(document).on('click', '.btn-reaction-toggle', function() {
        const postId = $(this).data('id');
        ejecutarReaccion(postId, 'me_gusta');
    });

    function ejecutarReaccion(postId, tipo) {
        $.ajax({
            url: BASE_URL + '/api/feed/reaccionar',
            type: 'POST',
            data: { post_id: postId, tipo: tipo },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    cargarFeed();
                }
            }
        });
    }

    // Comentarios
    $(document).on('click', '.btn-comment-focus', function() {
        const postId = $(this).data('id');
        $(`.comment-form[data-id="${postId}"] .comment-form-control`).focus();
    });

    $(document).on('submit', '.comment-form', function(e) {
        e.preventDefault();
        const postId = $(this).data('id');
        const $input = $(this).find('.comment-form-control');
        const contenido = $input.val().trim();

        if (!contenido) return;

        $.ajax({
            url: BASE_URL + '/api/feed/comentar',
            type: 'POST',
            data: { post_id: postId, contenido: contenido },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $input.val('');
                    cargarFeed();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'No se pudo enviar el comentario.', 'error');
            }
        });
    });

    $(document).on('click', '.btn-delete-comment', function() {
        const commentId = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar comentario?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#D4A3AE',
            cancelButtonColor: '#EBEBEB',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE_URL + '/api/feed/comentario/eliminar',
                    type: 'POST',
                    data: { id: commentId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $(`.comment-item[data-id="${commentId}"]`).fadeOut(350, function() {
                                $(this).remove();
                                cargarFeed();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                    }
                });
            }
        });
    });

    // Eliminar Post
    $(document).on('click', '.btn-delete-post', function(e) {
        e.preventDefault();
        const postId = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar publicación?',
            text: "Se borrarán de forma permanente todos los comentarios y reacciones asociados.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#D4A3AE',
            cancelButtonColor: '#EBEBEB',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE_URL + '/api/feed/eliminar',
                    type: 'POST',
                    data: { id: postId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $(`.feed-post-card[data-id="${postId}"]`).fadeOut(350, function() {
                                $(this).remove();
                                cargarFeed();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                    }
                });
            }
        });
    });

    // Compartir
    $(document).on('click', '.btn-share-post', function() {
        const postId = $(this).data('id');
        const pName = $(this).data('project');
        const content = $(this).data('content');
        const img = $(this).data('img');
        const avatar = $(this).data('avatar');
        const date = $(this).data('date');

        $('#sharePostIdInput').val(postId);
        $('#sharePostContenido').val('');
        $('#shareOriginalProjectName').text(pName);
        $('#shareOriginalContent').text(content || 'Sin texto');
        $('#shareOriginalDate').text(date);

        if (avatar) {
            $('#shareOriginalPhoto').attr('src', avatar);
        } else {
            $('#shareOriginalPhoto').attr('src', `data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23D4A3AE'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/></svg>`);
        }

        if (img) {
            $('#shareOriginalImg').attr('src', img).removeClass('d-none');
        } else {
            $('#shareOriginalImg').addClass('d-none');
        }

        const shareModal = new bootstrap.Modal(document.getElementById('sharePostModal'));
        shareModal.show();
    });

    $('#sharePostForm').on('submit', function(e) {
        e.preventDefault();
        
        const postId = $('#sharePostIdInput').val();
        const contenido = $('#sharePostContenido').val().trim();

        $.ajax({
            url: BASE_URL + '/api/feed/compartir',
            type: 'POST',
            data: { post_id: postId, contenido: contenido },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const modalEl = document.getElementById('sharePostModal');
                    const modalInst = bootstrap.Modal.getInstance(modalEl);
                    if (modalInst) modalInst.hide();
                    
                    cargarFeed();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Publicación compartida',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'No se pudo compartir la publicación.', 'error');
            }
        });
    });

    // Detectar cambio de pestaña para recargar el feed
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        if (e.target.id === 'feed-tab') {
            cargarFeed();
        }
    });

});

