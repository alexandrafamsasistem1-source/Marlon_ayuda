/**
 * Script JavaScript principal
 */

window.originalAlert = window.alert.bind(window);

window.alert = function(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'info',
            title: 'Aviso',
            text: String(message ?? ''),
            confirmButtonText: 'Aceptar'
        });
        return;
    }

    return window.originalAlert ? window.originalAlert(message) : undefined;
};

// Eventos que se ejecutan al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. INICIALIZACIÓN GLOBAL DE TOOLTIPS (Bootstrap 5)
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 2. CAMBIO DINÁMICO DE ICONO DE ALERTA DE CONTRASEÑA (Rojo -> Verde)
    const passwordInput = document.getElementById('password_nueva') || document.getElementById('password');
    const alertIcon = document.getElementById('password-alert-icon');

    if (passwordInput && alertIcon) {
        passwordInput.addEventListener('input', function () {
            const val = this.value;
            
            // Criterios: Mínimo 8 caracteres, al menos 1 letra y 1 número
            const hasMinLength = val.length >= 8;
            const hasLetter = /[A-Za-z]/.test(val);
            const hasNumber = /[0-9]/.test(val);

            if (hasMinLength && hasLetter && hasNumber) {
                // Cumple requisitos -> Icono verde de Check
                alertIcon.classList.remove('fa-exclamation-circle', 'text-danger');
                alertIcon.classList.add('fa-check-circle', 'text-success');

                const tooltip = bootstrap.Tooltip.getInstance(alertIcon);
                if (tooltip) {
                    alertIcon.setAttribute('data-bs-original-title', '<b>¡Contraseña Segura!</b><br>Cumple con todos los requisitos.');
                }
            } else {
                // No cumple -> Icono rojo de Alerta
                alertIcon.classList.remove('fa-check-circle', 'text-success');
                alertIcon.classList.add('fa-exclamation-circle', 'text-danger');

                const tooltip = bootstrap.Tooltip.getInstance(alertIcon);
                if (tooltip) {
                    alertIcon.setAttribute('data-bs-original-title', '<b>Requisitos de contraseña:</b><br>• Mínimo 8 caracteres<br>• Al menos una letra<br>• Al menos un número');
                }
            }
        });
    }

    // 3. FUNCIONALIDAD DEL BOTÓN OJITO (Mostrar / Ocultar Contraseña)
    document.querySelectorAll('.toggle-password-btn').forEach(button => {
        button.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');

            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        });
    });

    // 4. Confirmación para cerrar tickets
    const formsToConfirm = document.querySelectorAll('form[data-confirm]');
    formsToConfirm.forEach(form => {
        form.addEventListener('submit', function(e) {
            const message = this.dataset.confirm || '¿Estás seguro?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // 5. Confirmación elegante con SweetAlert2 para acciones destructivas
    const swalConfirmElements = document.querySelectorAll('[data-swal-confirm]');
    swalConfirmElements.forEach(element => {
        element.addEventListener('click', function(e) {
            e.preventDefault();

            const message = this.dataset.swalConfirm || '¿Estás seguro?';
            const title = this.dataset.swalTitle || 'Confirmar acción';
            const confirmText = this.dataset.swalConfirmText || 'Sí, continuar';
            const cancelText = this.dataset.swalCancelText || 'Cancelar';
            const href = this.dataset.swalHref || this.getAttribute('href');

            const runAction = () => {
                if (this.tagName === 'FORM') {
                    this.submit();
                    return;
                }

                if (href) {
                    window.location.href = href;
                }
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title,
                    text: message,
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: cancelText,
                    reverseButtons: true,
                    focusCancel: true
                }).then(result => {
                    if (result.isConfirmed) {
                        runAction();
                    }
                });
            } else if (window.originalAlert && window.confirm(message)) {
                runAction();
            }
        });
    });

    // 6. Auto-dismiss de alerts después de 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });

    // 7. Notificaciones como leídas (AJAX)
    const markAsReadButtons = document.querySelectorAll('.mark-as-read-btn');
    markAsReadButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const notifId = this.dataset.notifId;
            const notifItem = document.querySelector(`[data-notif-id="${notifId}"]`);
            
            fetch((typeof baseUrl !== 'undefined' ? baseUrl : '') + '/api/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'notif_id=' + encodeURIComponent(notifId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && notifItem) {
                    notifItem.style.transition = 'opacity 0.3s ease-out, max-height 0.3s ease-out';
                    notifItem.style.opacity = '0';
                    notifItem.style.maxHeight = '0';
                    notifItem.style.overflow = 'hidden';
                    
                    setTimeout(() => {
                        notifItem.remove();
                        
                        const badge = document.querySelector('#notifDropdown .badge');
                        if (badge) {
                            let count = parseInt(badge.textContent) || 0;
                            count--;
                            
                            if (count > 0) {
                                badge.textContent = count;
                            } else {
                                badge.remove();
                                const dropdownList = document.querySelector('.dropdown-menu ul');
                                if (dropdownList) {
                                    const emptyMsg = dropdownList.querySelector('.text-muted');
                                    if (!emptyMsg) {
                                        const li = document.createElement('li');
                                        li.innerHTML = '<span class="dropdown-item text-muted">No hay notificaciones pendientes.</span>';
                                        dropdownList.prepend(li);
                                    }
                                }
                            }
                        }
                    }, 300);
                } else {
                    console.error('Error al marcar notificación:', data.message);
                }
            })
            .catch(error => console.error('Error en la petición:', error));
        });
    });
});

// Funciones auxiliares globales
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copiado al portapapeles');
    });
}

function formatDate(dateString) {
    const options = { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('es-ES', options);
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showNotification(message, type = 'info') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type,
            title: type.charAt(0).toUpperCase() + type.slice(1),
            text: message,
            confirmButtonText: 'Aceptar'
        });
        return;
    }

    window.alert(message);
}

function setButtonLoading(buttonId, loading = true) {
    const button = document.getElementById(buttonId);
    if (!button) return;

    if (loading) {
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando...';
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.originalText || 'Enviar';
    }
}

console.log('Sistema de Tickets - Script iniciado');