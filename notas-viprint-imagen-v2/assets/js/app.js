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
});
function calcPartidas(){
  let total=0;
  document.querySelectorAll('.partida-row').forEach(row=>{
    const qty=parseFloat(row.querySelector('.part-cantidad')?.value||'0')||0;
    const price=parseFloat(row.querySelector('.part-precio')?.value||'0')||0;
    const sub=qty*price; total+=sub;
    const out=row.querySelector('.part-total'); if(out) out.textContent = sub.toLocaleString('es-MX',{style:'currency',currency:'MXN'});
  });
  const grand=document.querySelector('#total-preview'); if(grand) grand.textContent=total.toLocaleString('es-MX',{style:'currency',currency:'MXN'});
}
function addPartida(){
  const tpl=document.querySelector('#partida-template'); const wrap=document.querySelector('#partidas-wrap');
  if(tpl && wrap){ wrap.insertAdjacentHTML('beforeend', tpl.innerHTML); calcPartidas(); }
}
function removePartida(btn){ const row=btn.closest('.partida-row'); if(row){row.remove(); calcPartidas();}}
