(function () {
    function toNumber(value) {
        var n = parseFloat(String(value || '0').replace(/,/g, ''));
        return isNaN(n) ? 0 : n;
    }

    function money(n) {
        return '$' + Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function recalcTotals() {
        var subtotal = 0;
        document.querySelectorAll('.quote-item').forEach(function (item) {
            var qty = toNumber(item.querySelector('[name="cantidad[]"]').value);
            var price = toNumber(item.querySelector('[name="precio_unitario[]"]').value);
            var importe = qty * price;
            item.querySelector('.importe-view').textContent = money(importe);
            item.querySelector('[name="importe[]"]').value = importe.toFixed(2);
            subtotal += importe;
        });
        var applyIva = document.querySelector('[name="aplicar_iva"]') && document.querySelector('[name="aplicar_iva"]').checked;
        var pct = toNumber(document.querySelector('[name="porcentaje_iva"]') ? document.querySelector('[name="porcentaje_iva"]').value : 16);
        var iva = applyIva ? subtotal * pct / 100 : 0;
        var total = subtotal + iva;
        var subtotalView = document.getElementById('subtotalView');
        var ivaView = document.getElementById('ivaView');
        var totalView = document.getElementById('totalView');
        if (subtotalView) subtotalView.textContent = money(subtotal);
        if (ivaView) ivaView.textContent = money(iva);
        if (totalView) totalView.textContent = money(total);
    }

    function bindItem(item) {
        item.querySelectorAll('input, select, textarea').forEach(function (el) {
            el.addEventListener('input', recalcTotals);
            el.addEventListener('change', recalcTotals);
        });
        var removeBtn = item.querySelector('.remove-item');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                if (document.querySelectorAll('.quote-item').length <= 1) {
                    alert('La cotización debe tener al menos una partida.');
                    return;
                }
                item.remove();
                recalcTotals();
            });
        }
        var promoSelect = item.querySelector('.promo-select');
        if (promoSelect) {
            promoSelect.addEventListener('change', function () {
                var opt = promoSelect.options[promoSelect.selectedIndex];
                if (!opt || !opt.value) return;
                item.querySelector('[name="tipo[]"]').value = 'promocion';
                item.querySelector('[name="promocion_id[]"]').value = opt.value;
                item.querySelector('[name="descripcion[]"]').value = opt.getAttribute('data-desc') || opt.textContent;
                item.querySelector('[name="precio_unitario[]"]').value = opt.getAttribute('data-price') || '0.00';
                recalcTotals();
            });
        }
    }

    var itemTemplate = document.getElementById('itemTemplate');
    var itemsWrap = document.getElementById('itemsWrap');
    var addItemBtn = document.getElementById('addItemBtn');
    if (itemsWrap) {
        itemsWrap.querySelectorAll('.quote-item').forEach(bindItem);
        if (addItemBtn && itemTemplate) {
            addItemBtn.addEventListener('click', function () {
                var clone = itemTemplate.content.cloneNode(true);
                var node = clone.querySelector('.quote-item');
                itemsWrap.appendChild(clone);
                bindItem(node);
                recalcTotals();
            });
        }
        document.querySelectorAll('[name="aplicar_iva"], [name="porcentaje_iva"]').forEach(function (el) {
            el.addEventListener('input', recalcTotals);
            el.addEventListener('change', recalcTotals);
        });
        recalcTotals();
    }

    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.getAttribute('data-confirm'))) e.preventDefault();
        });
    });
})();
