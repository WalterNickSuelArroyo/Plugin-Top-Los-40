// Plugin Top 40 - JavaScript Frontend
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.top40-arrow').forEach(btn => {
        btn.addEventListener('click', function () {
            const extra = this.closest('.top40-item').querySelector('.top40-extra');
            extra.style.display = extra.style.display === 'flex' ? 'none' : 'flex';
            this.classList.toggle('rotado');
        });
    });
});