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
});
