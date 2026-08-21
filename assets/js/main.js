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

// Mostrar confirmación antes de enviar formularios críticos
document.addEventListener('DOMContentLoaded', function() {
    
    // Confirmación para cerrar tickets
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

    // Confirmación elegante con SweetAlert2 para acciones destructivas
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

    // Auto-dismiss de alerts después de 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Validación en tiempo real de passwords
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.length > 0 && this.value.length < 6) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });
});

// Función para copiar al portapapeles
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copiado al portapapeles');
    });
}

// Función para formatear fecha
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

// Función para validar email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Función para mostrar notificación
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

// Función para loading
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

/**
 * Marcar notificaciones como leídas (AJAX)
 */
document.addEventListener('DOMContentLoaded', function() {
    const markAsReadButtons = document.querySelectorAll('.mark-as-read-btn');
    
    markAsReadButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const notifId = this.dataset.notifId;
            const notifItem = document.querySelector(`[data-notif-id="${notifId}"]`);
            
            // Hacer petición AJAX
            fetch(baseUrl + '/api/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'notif_id=' + encodeURIComponent(notifId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Animar la desaparición de la notificación
                    notifItem.style.transition = 'opacity 0.3s ease-out, max-height 0.3s ease-out';
                    notifItem.style.opacity = '0';
                    notifItem.style.maxHeight = '0';
                    notifItem.style.overflow = 'hidden';
                    
                    // Remover del DOM después de la animación
                    setTimeout(() => {
                        notifItem.remove();
                        
                        // Actualizar el contador de notificaciones
                        const badge = document.querySelector('#notifDropdown .badge');
                        if (badge) {
                            let count = parseInt(badge.textContent) || 0;
                            count--;
                            
                            if (count > 0) {
                                badge.textContent = count;
                            } else {
                                badge.remove();
                                // Mostrar mensaje de "No hay notificaciones pendientes"
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

