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
        // Confirmar cambios de votos y estadísticas
        document.querySelectorAll('.top40-votos-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const inputs = this.querySelectorAll('input[type="number"]');
                let changes = [];
                
                inputs.forEach(input => {
                    const originalValue = input.defaultValue;
                    const newValue = input.value;
                    
                    if (originalValue !== newValue) {
                        const fieldName = input.name.replace('nuevos_', '').replace('nuevas_', '').replace('nueva_', '');
                        changes.push(`${fieldName}: ${originalValue} → ${newValue}`);
                    }
                });
                
                if (changes.length > 0) {
                    const message = `¿Estás seguro de realizar estos cambios?\n\n${changes.join('\n')}`;
                    if (!confirm(message)) {
                        e.preventDefault();
                        // Restaurar valores originales
                        inputs.forEach(input => {
                            input.value = input.defaultValue;
                        });
                    }
                }
            });
        });

        // Seleccionar contenido del input al hacer clic
        document.querySelectorAll('.top40-votos-form input[type="number"]').forEach(input => {
            input.addEventListener('focus', function() {
                this.select();
            });
            
            // Validaciones específicas por campo
            input.addEventListener('input', function() {
                const name = this.name;
                const value = parseInt(this.value);
                
                if (name.includes('posicion') && (value < 1 || value > 40)) {
                    this.setCustomValidity('La posición debe estar entre 1 y 40');
                } else if (name.includes('semanas') && value < 0) {
                    this.setCustomValidity('Las semanas no pueden ser negativas');
                } else {
                    this.setCustomValidity('');
                }
            });
        });

        // Indicador visual para campos con valores manuales
        document.querySelectorAll('small[title="Valor manual"]').forEach(indicator => {
            indicator.style.cursor = 'help';
            indicator.addEventListener('click', function() {
                alert('Este campo tiene un valor establecido manualmente por el administrador.');
            });
        });
    }
});