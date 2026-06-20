$(document).ready(function () {

    // ============================================================
    // INICIALIZACIÓN
    // ============================================================
    let proyectoActualId = null;

    cargarPerfil();

    // ============================================================
    // LOGOUT
    // ============================================================
    $('#btnLogout').on('click', function () {
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
                    success: function (response) {
                        if (response.status === 'success') {
                            window.location.href = BASE_URL + '/';
                        }
                    }
                });
            }
        });
    });

    // ============================================================
    // PERFIL (navbar avatar)
    // ============================================================
    function cargarPerfil() {
        $.ajax({
            url: BASE_URL + '/api/perfil',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success' && response.data) {
                    const user = response.data;
                    if (user.foto) {
                        $('#navUserAvatar').attr('src', BASE_URL + '/view/' + user.foto);
                    }
                    // Cargar el proyecto para saber si puede publicar
                    cargarProyecto();
                } else {
                    mostrarSinProyecto();
                }
            },
            error: function () {
                mostrarSinProyecto();
            }
        });
    }

    function cargarProyecto() {
        $.ajax({
            url: BASE_URL + '/api/proyecto',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                $('#feedPageLoading').addClass('d-none');
                if (response.status === 'success' && response.data) {
                    const proyecto = response.data;
                    // Avatar del proyecto en el creador de posts
                    if (proyecto.foto) {
                        $('#feedCreatorAvatar').attr('src', BASE_URL + '/view/' + proyecto.foto);
                    }
                    const nombres = proyecto.pareja_nombre
                        ? proyecto.creador_nombre + ' & ' + proyecto.pareja_nombre
                        : proyecto.creador_nombre;
                    $('#feedCreatorName').text(proyecto.nombre + ' (' + nombres + ')');
                    $('#feedPageContent').removeClass('d-none');
                    cargarFeed();
                } else {
                    mostrarSinProyecto();
                }
            },
            error: function () {
                $('#feedPageLoading').addClass('d-none');
                mostrarSinProyecto();
            }
        });
    }

    function mostrarSinProyecto() {
        $('#feedPageLoading').addClass('d-none');
        $('#feedPageContent').addClass('d-none');
        $('#feedNoProject').removeClass('d-none');
    }

    // ============================================================
    // CARGAR Y RENDERIZAR FEED
    // ============================================================
    function cargarFeed() {
        $.ajax({
            url: BASE_URL + '/api/feed',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    proyectoActualId = response.proyecto_actual_id;
                    renderFeed(response.data);
                } else {
                    $('#globalFeedContainer').html(`
                        <div class="alert alert-warning border-0 shadow-sm text-center p-4">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-3 mb-2 d-block"></i>
                            <p class="mb-0">${escapeHtml(response.message || 'No se pudieron cargar las publicaciones.')}</p>
                        </div>
                    `);
                }
            },
            error: function () {
                $('#globalFeedContainer').html(`
                    <div class="alert alert-danger border-0 shadow-sm text-center p-4">
                        <i class="bi bi-x-circle-fill text-danger fs-3 mb-2 d-block"></i>
                        <p class="mb-0">Error de conexión al cargar el muro.</p>
                    </div>
                `);
            }
        });
    }

    function renderFeed(posts) {
        const $container = $('#globalFeedContainer');
        $container.empty();

        if (!posts || posts.length === 0) {
            $container.html(`
                <div class="text-center py-5 bg-white rounded-4 border p-4 shadow-sm">
                    <i class="bi bi-postcard-heart text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
                    <h5 class="mt-3 font-heading text-dark">El muro está vacío</h5>
                    <p class="text-muted small">Sé el primero en compartir un estado o foto de su historia de amor.</p>
                </div>
            `);
            return;
        }

        const DEFAULT_AVATAR = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23D4A3AE'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/></svg>";

        posts.forEach(post => {
            const esMio = parseInt(post.proyecto_vida_id) === parseInt(proyectoActualId);
            const avatarUrl = post.proyecto_foto ? (BASE_URL + '/view/' + post.proyecto_foto) : DEFAULT_AVATAR;
            const nombresPareja = post.pareja_nombre
                ? `${post.creador_nombre} & ${post.pareja_nombre}`
                : post.creador_nombre;
            const tituloPerfil = `${escapeHtml(post.proyecto_nombre)} <span class="text-muted" style="font-size:0.8rem;font-weight:400;">(${escapeHtml(nombresPareja)})</span>`;

            // Botón de eliminar
            const deleteDropdown = esMio
                ? `<div class="dropdown ms-auto">
                       <button class="btn btn-link text-muted p-1 border-0 shadow-none" type="button" data-bs-toggle="dropdown">
                           <i class="bi bi-three-dots-vertical"></i>
                       </button>
                       <ul class="dropdown-menu dropdown-menu-end border shadow-sm">
                           <li><a class="dropdown-item text-danger btn-delete-post" href="#" data-id="${post.id}">
                               <i class="bi bi-trash-fill me-2"></i>Eliminar
                           </a></li>
                       </ul>
                   </div>`
                : '';

            // Contenido
            const contentHtml = post.contenido ? `<p class="feed-post-content mb-3">${escapeHtml(post.contenido)}</p>` : '';
            const imgHtml = post.foto
                ? `<div class="mb-3"><img src="${BASE_URL}/view/${escapeHtml(post.foto)}" class="img-fluid feed-post-img rounded shadow-sm"></div>`
                : '';

            // Shared post
            let sharedHtml = '';
            if (post.shared_post_id) {
                if (post.shared_contenido !== null) {
                    const sharedAvatar = post.shared_proyecto_foto ? (BASE_URL + '/view/' + post.shared_proyecto_foto) : DEFAULT_AVATAR;
                    const sharedImgHtml = post.shared_foto
                        ? `<div class="mt-2"><img src="${BASE_URL}/view/${escapeHtml(post.shared_foto)}" class="img-fluid rounded" style="max-height:200px;object-fit:cover;width:100%;"></div>`
                        : '';
                    sharedHtml = `
                        <div class="shared-post-container border rounded p-3 mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <img src="${sharedAvatar}" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
                                <div>
                                    <h6 class="mb-0 small fw-bold text-dark font-heading">${escapeHtml(post.shared_proyecto_nombre)}</h6>
                                    <span class="text-muted" style="font-size:0.72rem;">${calcularTiempoHace(post.shared_created_at)}</span>
                                </div>
                            </div>
                            <p class="mb-0 text-muted small">${escapeHtml(post.shared_contenido)}</p>
                            ${sharedImgHtml}
                        </div>`;
                } else {
                    sharedHtml = `<div class="alert alert-light border rounded small text-muted p-3 mb-3">
                        <i class="bi bi-info-circle me-1"></i>Esta publicación compartida ya no está disponible.
                    </div>`;
                }
            }

            // Resumen reacciones
            const totalReac = parseInt(post.reacciones.total_general) || 0;
            const totalCom = post.comentarios.length || 0;
            let reacEmojis = '';
            if (totalReac > 0) {
                for (const type in post.reacciones.totales) {
                    reacEmojis += obtenerEmojiReaccion(type) + ' ';
                }
            }
            const reacsSummary = `
                <div class="reactions-summary d-flex align-items-center py-2 px-1">
                    <span class="me-1">${reacEmojis}</span>
                    ${totalReac > 0 ? `<span class="fw-semibold text-rose">${totalReac} ${totalReac === 1 ? 'reacción' : 'reacciones'}</span>` : `<span class="text-muted small">Sé el primero en reaccionar</span>`}
                    <span class="ms-auto text-muted small">${totalCom} ${totalCom === 1 ? 'comentario' : 'comentarios'}</span>
                </div>`;

            // Reacción actual
            const reacAct = post.reaccion_actual;
            const reacActClase = reacAct ? 'active' : '';
            const reacActEmoji = reacAct ? obtenerEmojiReaccion(reacAct) : '<i class="bi bi-heart me-1"></i>';
            const reacActNombre = reacAct ? obtenerNombreReaccion(reacAct) : 'Reaccionar';
            const reacActColor = reacAct ? obtenerColorReaccion(reacAct) : 'inherit';

            // Comentarios
            let commentsHtml = '';
            if (post.comentarios && post.comentarios.length > 0) {
                post.comentarios.forEach(com => {
                    const comEsMio = parseInt(com.proyecto_vida_id) === parseInt(proyectoActualId);
                    const comAvatar = com.proyecto_foto ? (BASE_URL + '/view/' + com.proyecto_foto) : DEFAULT_AVATAR;
                    const deleteBtn = comEsMio
                        ? `<i class="bi bi-trash btn-delete-comment ms-2" data-id="${com.id}" style="cursor:pointer;font-size:0.8rem;"></i>`
                        : '';
                    commentsHtml += `
                        <div class="comment-item d-flex align-items-start mb-3" data-id="${com.id}">
                            <img src="${comAvatar}" class="rounded-circle comment-avatar me-2" style="width:32px;height:32px;">
                            <div class="flex-grow-1">
                                <div class="comment-bubble">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div>
                                            <span class="comment-author-name">${escapeHtml(com.proyecto_nombre)}</span>
                                            <span class="comment-author-sub">(${escapeHtml(com.autor_nombre)})</span>
                                        </div>
                                        ${deleteBtn}
                                    </div>
                                    <p class="comment-text">${escapeHtml(com.contenido)}</p>
                                </div>
                                <span class="comment-time ms-2">${calcularTiempoHace(com.created_at)}</span>
                            </div>
                        </div>`;
                });
            }

            const postCard = `
                <div class="card feed-post-card shadow-sm border-0" data-id="${post.id}">
                    <div class="card-body p-4 pb-3">
                        <div class="d-flex align-items-center mb-3">
                            <img src="${avatarUrl}" class="rounded-circle feed-post-avatar me-3" alt="Avatar">
                            <div>
                                <h5 class="mb-0 feed-post-title fs-6">${tituloPerfil}</h5>
                                <span class="feed-post-time">${calcularTiempoHace(post.created_at)}</span>
                            </div>
                            ${deleteDropdown}
                        </div>
                        ${contentHtml}${imgHtml}${sharedHtml}
                        ${reacsSummary}
                        <div class="feed-post-actions d-flex justify-content-around align-items-center">
                            <div class="reactions-container">
                                <div class="reactions-tray">
                                    <button class="reaction-emoji-btn" data-id="${post.id}" data-type="me_gusta" title="Me gusta">👍</button>
                                    <button class="reaction-emoji-btn" data-id="${post.id}" data-type="me_encanta" title="Me encanta">❤️</button>
                                    <button class="reaction-emoji-btn" data-id="${post.id}" data-type="me_divierte" title="Me divierte">😆</button>
                                    <button class="reaction-emoji-btn" data-id="${post.id}" data-type="me_asombra" title="Me asombra">😮</button>
                                    <button class="reaction-emoji-btn" data-id="${post.id}" data-type="me_entristece" title="Me entristece">😢</button>
                                    <button class="reaction-emoji-btn" data-id="${post.id}" data-type="me_enoja" title="Me enoja">😡</button>
                                </div>
                                <button class="btn btn-feed-action btn-reaction-toggle ${reacActClase}" data-id="${post.id}" style="color:${reacActColor};">
                                    ${reacActEmoji} <span class="d-none d-sm-inline">${reacActNombre}</span>
                                </button>
                            </div>
                            <button class="btn btn-feed-action btn-comment-focus" data-id="${post.id}">
                                <i class="bi bi-chat-left text-muted me-1"></i> <span class="d-none d-sm-inline">Comentar</span>
                            </button>
                            <button class="btn btn-feed-action btn-share-post"
                                data-id="${post.id}"
                                data-project="${escapeHtml(post.proyecto_nombre)}"
                                data-content="${escapeHtml(post.contenido || '')}"
                                data-img="${post.foto ? BASE_URL + '/view/' + post.foto : ''}"
                                data-avatar="${post.proyecto_foto ? BASE_URL + '/view/' + post.proyecto_foto : ''}"
                                data-date="${calcularTiempoHace(post.created_at)}">
                                <i class="bi bi-share text-muted me-1"></i> <span class="d-none d-sm-inline">Compartir</span>
                            </button>
                        </div>
                    </div>
                    <div class="comments-section">
                        <div class="comments-list-container mb-3" id="comments-list-${post.id}">${commentsHtml}</div>
                        <form class="comment-form" data-id="${post.id}">
                            <div class="comment-input-area">
                                <input type="text" class="form-control comment-form-control flex-grow-1" placeholder="Escribe un comentario..." name="contenido" required>
                                <button type="submit" class="btn btn-primary-custom btn-comment-submit shadow-none">
                                    <i class="bi bi-send-fill text-white" style="font-size:0.9rem;"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>`;

            $container.append(postCard);
        });
    }

    // ============================================================
    // CREAR PUBLICACIÓN
    // ============================================================
    $('#btnTriggerPostFile').on('click', function () {
        $('#postFoto').trigger('click');
    });

    $('#postFoto').on('change', function () {
        const file = this.files[0];
        if (!file) return;
        if (file.size > 10 * 1024 * 1024) {
            Swal.fire('Imagen muy pesada', 'La imagen no debe superar 10MB.', 'warning');
            $(this).val('');
            return;
        }
        const reader = new FileReader();
        reader.onload = function (e) {
            $('#postImagePreview').attr('src', e.target.result);
            $('#postImagePreviewContainer').removeClass('d-none');
        };
        reader.readAsDataURL(file);
    });

    $('#btnRemovePostImage').on('click', function () {
        $('#postFoto').val('');
        $('#postImagePreview').attr('src', '');
        $('#postImagePreviewContainer').addClass('d-none');
    });

    $('#feedPostForm').on('submit', function (e) {
        e.preventDefault();
        const contenido = $('#postContenido').val().trim();
        const fileInput = $('#postFoto')[0];
        if (!contenido && !(fileInput.files && fileInput.files[0])) {
            Swal.fire('Publicación vacía', 'Escribe algo o sube una foto.', 'warning');
            return;
        }
        const $btn = $('#btnSubmitPost');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Preparando...');

        const rawFile = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;

        // Comprimir imagen en el cliente si hay una
        const compresionPromise = rawFile
            ? comprimirImagenCliente(rawFile, 1280, 0.82)
            : Promise.resolve(null);

        compresionPromise.then(function (archivoComprimido) {
            $btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Publicando...');

            const formData = new FormData();
            formData.append('contenido', contenido);
            if (archivoComprimido) {
                formData.append('foto', archivoComprimido, archivoComprimido.name);
            }

            $.ajax({
                url: BASE_URL + '/api/feed/crear',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    $btn.prop('disabled', false).html('<i class="bi bi-send-fill me-1"></i> Publicar');
                    if (response.status === 'success') {
                        $('#feedPostForm')[0].reset();
                        $('#btnRemovePostImage').click();
                        cargarFeed();
                        Swal.fire({ icon: 'success', title: 'Publicado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).html('<i class="bi bi-send-fill me-1"></i> Publicar');
                    Swal.fire('Error', 'No se pudo publicar.', 'error');
                }
            });
        });
    });

    // ============================================================
    // REACCIONES
    // ============================================================
    $(document).on('click', '.reaction-emoji-btn', function () {
        ejecutarReaccion($(this).data('id'), $(this).data('type'));
    });

    $(document).on('click', '.btn-reaction-toggle', function () {
        ejecutarReaccion($(this).data('id'), 'me_gusta');
    });

    function ejecutarReaccion(postId, tipo) {
        $.ajax({
            url: BASE_URL + '/api/feed/reaccionar',
            type: 'POST',
            data: { post_id: postId, tipo: tipo },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') cargarFeed();
            }
        });
    }

    // ============================================================
    // COMENTARIOS
    // ============================================================
    $(document).on('click', '.btn-comment-focus', function () {
        const postId = $(this).data('id');
        $(`.comment-form[data-id="${postId}"] .comment-form-control`).focus();
    });

    $(document).on('submit', '.comment-form', function (e) {
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
            success: function (response) {
                if (response.status === 'success') {
                    $input.val('');
                    cargarFeed();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'No se pudo enviar el comentario.', 'error');
            }
        });
    });

    $(document).on('click', '.btn-delete-comment', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar comentario?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#D4A3AE',
            cancelButtonColor: '#EBEBEB',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE_URL + '/api/feed/comentario/eliminar',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            $(`.comment-item[data-id="${id}"]`).fadeOut(300, function () { $(this).remove(); });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    }
                });
            }
        });
    });

    // ============================================================
    // ELIMINAR POST
    // ============================================================
    $(document).on('click', '.btn-delete-post', function (e) {
        e.preventDefault();
        const postId = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar publicación?',
            text: 'Se borrarán todos sus comentarios y reacciones.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#D4A3AE',
            cancelButtonColor: '#EBEBEB',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE_URL + '/api/feed/eliminar',
                    type: 'POST',
                    data: { id: postId },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            $(`.feed-post-card[data-id="${postId}"]`).fadeOut(350, function () {
                                $(this).remove();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'No se pudo conectar.', 'error');
                    }
                });
            }
        });
    });

    // ============================================================
    // COMPARTIR
    // ============================================================
    $(document).on('click', '.btn-share-post', function () {
        const postId = $(this).data('id');
        const pName  = $(this).data('project');
        const content = $(this).data('content');
        const img    = $(this).data('img');
        const avatar = $(this).data('avatar');
        const date   = $(this).data('date');

        $('#sharePostIdInput').val(postId);
        $('#sharePostContenido').val('');
        $('#shareOriginalProjectName').text(pName);
        $('#shareOriginalContent').text(content || 'Sin texto');
        $('#shareOriginalDate').text(date);
        $('#shareOriginalPhoto').attr('src', avatar || '');
        if (img) { $('#shareOriginalImg').attr('src', img).removeClass('d-none'); }
        else      { $('#shareOriginalImg').addClass('d-none'); }

        new bootstrap.Modal(document.getElementById('sharePostModal')).show();
    });

    $('#sharePostForm').on('submit', function (e) {
        e.preventDefault();
        const postId   = $('#sharePostIdInput').val();
        const contenido = $('#sharePostContenido').val().trim();

        $.ajax({
            url: BASE_URL + '/api/feed/compartir',
            type: 'POST',
            data: { post_id: postId, contenido: contenido },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    const inst = bootstrap.Modal.getInstance(document.getElementById('sharePostModal'));
                    if (inst) inst.hide();
                    cargarFeed();
                    Swal.fire({ icon: 'success', title: 'Compartido', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'No se pudo compartir.', 'error');
            }
        });
    });

    // ============================================================
    // PERFIL MODAL - botón en navbar
    // ============================================================
    $('#btnOpenPerfil').on('click', function () {
        new bootstrap.Modal(document.getElementById('perfilModal')).show();
    });

    // ============================================================
    // HELPERS
    // ============================================================
    function calcularTiempoHace(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr.replace(/-/g, '/'));
        const now  = new Date();
        const sec  = Math.floor((now - date) / 1000);
        const min  = Math.floor(sec / 60);
        const hr   = Math.floor(min / 60);
        const days = Math.floor(hr / 24);
        if (sec < 60)  return 'Hace un momento';
        if (min < 60)  return `Hace ${min} min`;
        if (hr  < 24)  return `Hace ${hr} ${hr === 1 ? 'hora' : 'horas'}`;
        if (days < 7)  return `Hace ${days} ${days === 1 ? 'día' : 'días'}`;
        return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function obtenerEmojiReaccion(tipo) {
        const map = { me_gusta: '👍', me_encanta: '❤️', me_divierte: '😆', me_asombra: '😮', me_entristece: '😢', me_enoja: '😡' };
        return map[tipo] || '👍';
    }

    function obtenerNombreReaccion(tipo) {
        const map = { me_gusta: 'Me gusta', me_encanta: 'Me encanta', me_divierte: 'Me divierte', me_asombra: 'Me asombra', me_entristece: 'Me entristece', me_enoja: 'Me enoja' };
        return map[tipo] || 'Me gusta';
    }

    function obtenerColorReaccion(tipo) {
        const map = { me_gusta: '#D4A3AE', me_encanta: '#dc3545', me_divierte: '#ffc107', me_asombra: '#ffc107', me_entristece: '#0dcaf0', me_enoja: '#fd7e14' };
        return map[tipo] || 'inherit';
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

});
