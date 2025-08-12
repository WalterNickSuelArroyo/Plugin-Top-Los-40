// Plugin Top 40 - JavaScript Frontend
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.top40-arrow').forEach(btn => {
        btn.addEventListener('click', function () {
            const extra = this.closest('.top40-item').querySelector('.top40-extra');
            extra.style.display = extra.style.display === 'flex' ? 'none' : 'flex';
            this.classList.toggle('rotado');
        });
    });

    // Funcionalidad para la administración
    if (document.body.classList.contains('wp-admin')) {
        // Confirmar cambios de votos
        document.querySelectorAll('.top40-votos-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const input = this.querySelector('input[name="nuevos_votos"]');
                const originalValue = input.defaultValue;
                const newValue = input.value;
                
                if (originalValue !== newValue) {
                    if (!confirm(`¿Estás seguro de cambiar los votos de ${originalValue} a ${newValue}?`)) {
                        e.preventDefault();
                        input.value = originalValue;
                    }
                }
            });
        });

        // Seleccionar contenido del input al hacer clic
        document.querySelectorAll('.top40-votos-form input[type="number"]').forEach(input => {
            input.addEventListener('focus', function() {
                this.select();
            });
        });
    }
});