$(document).ready(function() {
    
    // Arrays para guardar los datos cargados y poder filtrar localmente
    let inventarioItems = [];
    let necesidadesItems = [];
    let projectInfo = null;

    // Inicialización
    cargarProyecto();
    cargarParejasDisponibles();

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
});
