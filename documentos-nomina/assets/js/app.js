(() => {
    'use strict';

    const amountInput = document.querySelector('[data-amount-input]');
    const amountPreview = document.querySelector('[data-amount-preview]');

    const renderAmount = () => {
        if (!amountInput || !amountPreview) return;
        const value = Number(amountInput.value || 0);
        amountPreview.textContent = value.toLocaleString('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 2
        });
    };

    if (amountInput) {
        amountInput.addEventListener('input', renderAmount);
        renderAmount();
    }

    document.querySelectorAll('[data-confirm-delete]').forEach((button) => {
        button.addEventListener('click', (event) => {
            const message = button.getAttribute('data-confirm-delete') || '¿Deseas eliminar este registro?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
})();
