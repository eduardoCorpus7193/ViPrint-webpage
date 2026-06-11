(() => {
    'use strict';

    document.querySelectorAll('form[novalidate]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    const employeeSelect = document.getElementById('empleado_id');
    const startInput = document.getElementById('fecha_inicio');
    const endInput = document.getElementById('fecha_fin');
    const daysInput = document.getElementById('dias_solicitados');
    const employeeInfo = document.getElementById('employeeInfo');

    function parseLocalDate(value) {
        if (!value) return null;
        const parts = value.split('-').map(Number);
        return new Date(parts[0], parts[1] - 1, parts[2], 12, 0, 0);
    }

    function countMonSat(startValue, endValue) {
        const start = parseLocalDate(startValue);
        const end = parseLocalDate(endValue);
        if (!start || !end || end < start) return 0;

        let count = 0;
        const cursor = new Date(start);
        while (cursor <= end) {
            if (cursor.getDay() !== 0) count++;
            cursor.setDate(cursor.getDate() + 1);
        }
        return count;
    }

    function updateDays() {
        if (!startInput || !endInput || !daysInput) return;
        const days = countMonSat(startInput.value, endInput.value);
        if (days > 0) daysInput.value = String(days);
    }

    function updateEmployeeInfo() {
        if (!employeeSelect || !employeeInfo) return;
        const option = employeeSelect.options[employeeSelect.selectedIndex];
        if (!option || !option.value) {
            employeeInfo.innerHTML = 'Selecciona un empleado para consultar su saldo.';
            return;
        }
        const available = option.dataset.available || '0';
        const hireDate = option.dataset.hire || '';
        const seniority = option.dataset.seniority || '0';
        employeeInfo.innerHTML = `<strong>${available} día(s) solicitables</strong><br><span class="text-secondary">Ingreso: ${hireDate} · Antigüedad: ${seniority} año(s)</span>`;
    }

    startInput?.addEventListener('change', updateDays);
    endInput?.addEventListener('change', updateDays);
    employeeSelect?.addEventListener('change', updateEmployeeInfo);
    updateEmployeeInfo();
})();
