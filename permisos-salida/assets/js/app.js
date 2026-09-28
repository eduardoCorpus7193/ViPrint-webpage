(() => {
    'use strict';

    const startInput = document.querySelector('[data-start-time]');
    const endInput = document.querySelector('[data-end-time]');
    const durationPreview = document.querySelector('[data-duration-preview]');

    const renderDuration = () => {
        if (!startInput || !endInput || !durationPreview) return;
        if (!startInput.value || !endInput.value) {
            durationPreview.textContent = 'Captura las horas para calcular la duración';
            durationPreview.classList.remove('text-danger');
            return;
        }

        const [startHour, startMinute] = startInput.value.split(':').map(Number);
        const [endHour, endMinute] = endInput.value.split(':').map(Number);
        const start = (startHour * 60) + startMinute;
        const end = (endHour * 60) + endMinute;
        const minutes = end - start;

        if (minutes <= 0) {
            durationPreview.textContent = 'El regreso debe ser posterior a la salida';
            durationPreview.classList.add('text-danger');
            return;
        }

        durationPreview.classList.remove('text-danger');
        const hours = Math.floor(minutes / 60);
        const rest = minutes % 60;
        const parts = [];
        if (hours) parts.push(`${hours} h`);
        if (rest) parts.push(`${rest} min`);
        durationPreview.textContent = `Duración estimada: ${parts.join(' ')}`;
    };

    [startInput, endInput].forEach((input) => {
        if (input) input.addEventListener('input', renderDuration);
    });
    renderDuration();

    document.querySelectorAll('[data-confirm-delete]').forEach((button) => {
        button.addEventListener('click', (event) => {
            const message = button.getAttribute('data-confirm-delete') || '¿Deseas eliminar este registro?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
})();
