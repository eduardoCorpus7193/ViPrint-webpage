(function () {
    function toNumber(value) {
        value = String(value || '').replace(',', '.').replace(/[^0-9.\-]/g, '');
        var n = parseFloat(value);
        return isNaN(n) ? 0 : n;
    }

    function formatMoney(n) {
        return n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function calculateTotals() {
        var total = 0;
        document.querySelectorAll('.detail-row').forEach(function (row) {
            var qty = toNumber(row.querySelector('.js-cantidad') && row.querySelector('.js-cantidad').value);
            var price = toNumber(row.querySelector('.js-precio') && row.querySelector('.js-precio').value);
            var importe = qty * price;
            var importeInput = row.querySelector('.js-importe');
            if (importeInput) importeInput.value = importe.toFixed(2);
            total += importe;
        });
        var totalInput = document.querySelector('#total');
        if (totalInput && total > 0) totalInput.value = total.toFixed(2);
        calculateSaldo();
    }

    function calculateSaldo() {
        var total = toNumber(document.querySelector('#total') && document.querySelector('#total').value);
        var anticipo = toNumber(document.querySelector('#anticipo') && document.querySelector('#anticipo').value);
        var saldoEl = document.querySelector('#saldo-preview');
        if (saldoEl) saldoEl.textContent = '$' + formatMoney(Math.max(0, total - anticipo)) + ' MXN';
    }

    function refreshCatalogOptions() {
        var empresa = document.querySelector('#empresa');
        if (!empresa) return;
        var empresaValue = empresa.value;
        document.querySelectorAll('.js-catalogo').forEach(function (select) {
            Array.prototype.forEach.call(select.options, function (option) {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }
                option.hidden = option.getAttribute('data-empresa') !== empresaValue;
            });
            var selected = select.options[select.selectedIndex];
            if (selected && selected.hidden) select.value = '';
        });
    }

    document.addEventListener('input', function (e) {
        if (e.target.matches('.js-cantidad, .js-precio, #total, #anticipo')) {
            calculateTotals();
        }
    });

    document.addEventListener('change', function (e) {
        if (e.target.matches('#empresa')) {
            refreshCatalogOptions();
        }
        if (e.target.matches('.js-catalogo')) {
            var opt = e.target.options[e.target.selectedIndex];
            var row = e.target.closest('.detail-row');
            if (!opt || !row) return;
            var desc = opt.getAttribute('data-nombre') || '';
            var price = opt.getAttribute('data-precio') || '';
            var tipo = opt.getAttribute('data-tipo') || 'articulo';
            if (desc && row.querySelector('.js-descripcion')) row.querySelector('.js-descripcion').value = desc;
            if (price && row.querySelector('.js-precio')) row.querySelector('.js-precio').value = parseFloat(price).toFixed(2);
            if (tipo && row.querySelector('.js-tipo')) row.querySelector('.js-tipo').value = tipo;
            calculateTotals();
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target.matches('.js-add-row')) {
            e.preventDefault();
            var container = document.querySelector('#detalles-container');
            var tpl = document.querySelector('#detalle-template');
            if (!container || !tpl) return;
            var html = tpl.innerHTML.replace(/__INDEX__/g, String(document.querySelectorAll('.detail-row').length));
            var div = document.createElement('div');
            div.innerHTML = html;
            container.appendChild(div.firstElementChild);
            refreshCatalogOptions();
        }
        if (e.target.matches('.js-remove-row')) {
            e.preventDefault();
            var row = e.target.closest('.detail-row');
            if (row && document.querySelectorAll('.detail-row').length > 1) {
                row.remove();
                calculateTotals();
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        refreshCatalogOptions();
        calculateTotals();
    });
})();
