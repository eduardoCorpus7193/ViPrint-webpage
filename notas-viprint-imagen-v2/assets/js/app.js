document.addEventListener('input', function(e){
  if(e.target.matches('.part-cantidad,.part-precio')) calcPartidas();
});
document.addEventListener('change', function(e){
  if(e.target.matches('.catalogo-select')) {
    const opt=e.target.selectedOptions[0];
    const row=e.target.closest('.partida-row');
    if(opt && row){
      const desc=opt.dataset.desc||''; const price=opt.dataset.price||''; const tipo=opt.dataset.tipo||'articulo';
      row.querySelector('.part-desc').value = desc;
      row.querySelector('.part-precio').value = price;
      row.querySelector('.part-tipo').value = tipo;
      calcPartidas();
    }
  }
  if(e.target.matches('[name="requiere_factura"]')) calcPartidas();
});
function calcPartidas(){
  let subtotal=0;
  document.querySelectorAll('.partida-row').forEach(row=>{
    const desc=(row.querySelector('.part-desc')?.value||'').trim().toLowerCase();
    const qty=parseFloat(row.querySelector('.part-cantidad')?.value||'0')||0;
    const price=parseFloat(row.querySelector('.part-precio')?.value||'0')||0;
    const sub=qty*price;
    if(desc !== 'iva 16% factura' && desc !== 'iva') subtotal+=sub;
    const out=row.querySelector('.part-total'); if(out) out.textContent = sub.toLocaleString('es-MX',{style:'currency',currency:'MXN'});
  });
  const facturaSelect=document.querySelector('[name="requiere_factura"]');
  const requiereFactura = facturaSelect && String(facturaSelect.value)==='1';
  const iva = requiereFactura ? subtotal*0.16 : 0;
  const total = subtotal + iva;
  const grand=document.querySelector('#total-preview'); if(grand) grand.textContent=total.toLocaleString('es-MX',{style:'currency',currency:'MXN'});
  const sub=document.querySelector('#subtotal-preview'); if(sub) sub.textContent=subtotal.toLocaleString('es-MX',{style:'currency',currency:'MXN'});
  const ivaOut=document.querySelector('#iva-preview'); if(ivaOut) ivaOut.textContent=iva.toLocaleString('es-MX',{style:'currency',currency:'MXN'});
}
function addPartida(){
  const tpl=document.querySelector('#partida-template'); const wrap=document.querySelector('#partidas-wrap');
  if(tpl && wrap){ wrap.insertAdjacentHTML('beforeend', tpl.innerHTML); calcPartidas(); }
}
function removePartida(btn){ const row=btn.closest('.partida-row'); if(row){row.remove(); calcPartidas();}}
