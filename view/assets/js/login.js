$(document).ready(function() {
    // Escuchar el evento submit del formulario de login
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();

        // Validar variables
        const email = $('#email').val().trim();
        const password = $('#password').val();

        if (email === '' || password === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Campos vacíos',
                text: 'Por favor, rellene todos los campos obligatorios.',
                confirmButtonColor: '#D4A3AE'
            });
            return;
        }

        // Mostrar indicador de carga en el botón
        const $submitBtn = $(this).find('button[type="submit"]');
        const originalBtnHtml = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Validando...');

        // Realizar request con AJAX
        $.ajax({
            url: BASE_URL + '/api/login',
            type: 'POST',
            data: {
                email: email,
                password: password
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Feedback exitoso
                    Swal.fire({
                        icon: 'success',
                        title: '¡Acceso Concedido!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                    }).then(() => {
                        window.location.href = BASE_URL + '/dashboard';
                    });
                } else {
                    // Restaurar botón
                    $submitBtn.prop('disabled', false).html(originalBtnHtml);
                    
                    // Feedback de error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de autenticación',
                        text: response.message,
                        confirmButtonColor: '#D4A3AE'
                    });
                }
            },
            error: function(xhr, status, error) {
                // Restaurar botón
                $submitBtn.prop('disabled', false).html(originalBtnHtml);
                
                let errorMsg = 'No se pudo conectar con el servidor. Por favor, intente de nuevo.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Red',
                    text: errorMsg,
                    confirmButtonColor: '#D4A3AE'
                });
            }
        });
    });

    // Alternar entre login y registro
    $('#toggleAuthMode').on('click', function(e) {
        e.preventDefault();
        const $link = $(this);
        
        if ($('#loginForm').hasClass('d-none')) {
            // Mostrar Login
            $('#registerForm').addClass('d-none');
            $('#loginForm').removeClass('d-none');
            $('.brand-subtitle').text('Nuestro proyecto de vida juntos');
            $link.text('¿No tienes una cuenta? Solicita una aquí');
        } else {
            // Mostrar Registro
            $('#loginForm').addClass('d-none');
            $('#registerForm').removeClass('d-none');
            $('.brand-subtitle').text('Solicitar cuenta para planificar juntos');
            $link.text('¿Ya tienes una cuenta? Inicia sesión aquí');
        }
    });

    // Enviar solicitud de registro
    $('#registerForm').on('submit', function(e) {
        e.preventDefault();
        
        const nombre = $('#regNombre').val().trim();
        const email = $('#regEmail').val().trim();
        const password = $('#regPassword').val();
        const mensaje = $('#regMensaje').val().trim();
        
        if (nombre === '' || email === '' || password === '' || mensaje === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Campos obligatorios',
                text: 'Por favor, rellene todos los campos obligatorios (*).',
                confirmButtonColor: '#D4A3AE'
            });
            return;
        }

        if (password.length < 6) {
            Swal.fire({
                icon: 'warning',
                title: 'Contraseña corta',
                text: 'La contraseña debe tener al menos 6 caracteres.',
                confirmButtonColor: '#D4A3AE'
            });
            return;
        }

        const $submitBtn = $(this).find('button[type="submit"]');
        const originalBtnHtml = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enviando...');

        $.ajax({
            url: BASE_URL + '/api/registrarse',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Solicitud Enviada!',
                        text: response.message,
                        confirmButtonColor: '#D4A3AE'
                    }).then(() => {
                        // Limpiar formulario y volver a login
                        $('#registerForm')[0].reset();
                        $('#toggleAuthMode').click();
                    });
                } else {
                    $submitBtn.prop('disabled', false).html(originalBtnHtml);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de registro',
                        text: response.message,
                        confirmButtonColor: '#D4A3AE'
                    });
                }
            },
            error: function(xhr, status, error) {
                $submitBtn.prop('disabled', false).html(originalBtnHtml);
                
                let errorMsg = 'No se pudo procesar el registro. Por favor, intente más tarde.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: errorMsg,
                    confirmButtonColor: '#D4A3AE'
                });
            }
        });
    });
});
