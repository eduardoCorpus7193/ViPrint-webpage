(() => {
    'use strict';

    const form = document.querySelector('#overtimeForm');
    if (!form) return;

    const start = document.querySelector('#hora_inicio');
    const end = document.querySelector('#hora_fin');
    const hidden = document.querySelector('#total_horas');
    const display = document.querySelector('#total_visual');

    function calculateHours() {
        if (!start.value || !end.value) {
            hidden.value = '0';
            display.textContent = '0 h';
            return;
        }

        const [startHour, startMinute] = start.value.split(':').map(Number);
        const [endHour, endMinute] = end.value.split(':').map(Number);
        const startMinutes = (startHour * 60) + startMinute;
        let endMinutes = (endHour * 60) + endMinute;

        if (endMinutes < startMinutes) {
            endMinutes += 24 * 60;
        }

        const totalMinutes = Math.max(0, endMinutes - startMinutes);
        hidden.value = (totalMinutes / 60).toFixed(2);

        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        display.textContent = minutes === 0 ? `${hours} h` : `${hours} h ${minutes} min`;
    }

    start.addEventListener('input', calculateHours);
    end.addEventListener('input', calculateHours);
    calculateHours();

    form.addEventListener('submit', (event) => {
        calculateHours();
        if (!form.checkValidity() || Number(hidden.value) <= 0) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
})();
