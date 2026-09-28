
(() => {
  const page = document.body.dataset.page || 'inicio';
  const waNumber = '524497520167';
  const callNumber = '+524499404580';

  const navItems = [
    ['inicio', 'Inicio', 'index.html'],
    ['servicios', 'Servicios', 'servicios.html'],
    ['impermeabilizacion', 'Impermeabilización', 'impermeabilizacion.html'],
    ['pintura', 'Pintura', 'pintura.html'],
    ['proyectos', 'Proyectos', 'proyectos.html'],
    ['nosotros', 'Nosotros', 'nosotros.html']
  ];

  const navHtml = navItems.map(([key, label, href]) =>
    `<a href="${href}" ${page === key ? 'aria-current="page"' : ''}>${label}</a>`
  ).join('');

  const header = document.querySelector('[data-site-header]');
  if (header) {
    header.innerHTML = `
      <div class="topbar">
        <div class="container topbar__inner">
          <div class="topbar__items">
            <a href="tel:${callNumber}">Llamadas: 449 940 4580</a>
            <span>Atención 24/7</span>
          </div>
          <div class="topbar__items">
            <span>Cotización sin costo</span>
            <span>Servicio local y foráneo</span>
          </div>
        </div>
      </div>
      <header class="site-header">
        <div class="container header__inner">
          <a class="brand" href="index.html" aria-label="Ir al inicio">
            <img src="assets/img/logo-hernandez.svg" alt="Impermeabilizantes y Pinturas Hernández">
          </a>
          <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false"><span></span></button>
          <nav class="nav" aria-label="Navegación principal">
            ${navHtml}
            <a class="nav__cta" href="contacto.html" ${page === 'contacto' ? 'aria-current="page"' : ''}>Solicitar cotización</a>
          </nav>
        </div>
      </header>`;
  }

  const footer = document.querySelector('[data-site-footer]');
  if (footer) {
    footer.innerHTML = `
      <footer class="site-footer footer">
        <div class="container footer__grid">
          <div class="footer__brand">
            <img src="assets/img/logo-hernandez.svg" alt="Impermeabilizantes y Pinturas Hernández">
            <p>Soluciones de impermeabilización, pintura y mantenimiento para casas, negocios, empresas y proyectos de construcción.</p>
            <div class="btn-row">
              <a class="btn btn--primary btn--sm" href="https://wa.me/${waNumber}?text=${encodeURIComponent('Hola, quiero solicitar una cotización sin costo.')}" target="_blank" rel="noopener">WhatsApp</a>
              <a class="btn btn--light btn--sm" href="tel:${callNumber}">Llamar ahora</a>
            </div>
          </div>
          <div>
            <h3>Secciones</h3>
            <nav class="footer-nav">
              <a href="servicios.html">Todos los servicios</a>
              <a href="impermeabilizacion.html">Impermeabilización</a>
              <a href="pintura.html">Pintura</a>
              <a href="proyectos.html">Galería de proyectos</a>
              <a href="contacto.html">Contacto</a>
            </nav>
          </div>
          <div>
            <h3>Contacto</h3>
            <nav class="footer-nav">
              <a href="tel:${callNumber}">449 940 4580</a>
              <a href="https://wa.me/${waNumber}" target="_blank" rel="noopener">WhatsApp 449 752 0167</a>
              <a href="mailto:cesarimper76@gmail.com">cesarimper76@gmail.com</a>
              <span>Atención 24/7</span>
              <span>Aguascalientes y cobertura foránea</span>
            </nav>
          </div>
        </div>
        <div class="container footer__bottom">
          <span>© <span data-year></span> Impermeabilizantes y Pinturas Hernández.</span>
          <span>Propuesta web lista para integrar fotografías reales.</span>
        </div>
      </footer>
      <div class="float-actions" aria-label="Accesos rápidos">
        <a class="float-btn float-btn--wa" href="https://wa.me/${waNumber}?text=${encodeURIComponent('Hola, quiero solicitar información.')}" target="_blank" rel="noopener" aria-label="Contactar por WhatsApp">WA</a>
        <a class="float-btn float-btn--call" href="tel:${callNumber}" aria-label="Llamar">TEL</a>
      </div>`;
  }

  document.querySelectorAll('[data-year]').forEach(el => el.textContent = new Date().getFullYear());

  const toggle = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.nav');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
    });
    nav.addEventListener('click', event => {
      if (event.target.closest('a')) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  document.querySelectorAll('[data-filter]').forEach(button => {
    button.addEventListener('click', () => {
      const filter = button.dataset.filter;
      document.querySelectorAll('[data-filter]').forEach(btn => btn.classList.remove('is-active'));
      button.classList.add('is-active');
      document.querySelectorAll('[data-category]').forEach(card => {
        card.hidden = filter !== 'todos' && card.dataset.category !== filter;
      });
    });
  });

  const quoteForm = document.querySelector('[data-quote-form]');
  if (quoteForm) {
    quoteForm.addEventListener('submit', event => {
      event.preventDefault();
      const data = new FormData(quoteForm);
      const required = ['nombre', 'telefono', 'servicio', 'mensaje'];
      const missing = required.some(key => !String(data.get(key) || '').trim());
      const status = quoteForm.querySelector('[data-form-status]');
      if (missing) {
        if (status) status.textContent = 'Completa los campos obligatorios para continuar.';
        return;
      }
      const message = [
        'Hola, quiero solicitar una cotización sin costo.',
        '',
        `Nombre: ${data.get('nombre')}`,
        `Teléfono: ${data.get('telefono')}`,
        `Servicio: ${data.get('servicio')}`,
        `Tipo de inmueble: ${data.get('inmueble') || 'No especificado'}`,
        `Ubicación: ${data.get('ubicacion') || 'No especificada'}`,
        '',
        `Descripción: ${data.get('mensaje')}`
      ].join('\n');
      window.open(`https://wa.me/${waNumber}?text=${encodeURIComponent(message)}`, '_blank', 'noopener');
      if (status) status.textContent = 'Se abrió WhatsApp con la información de tu solicitud.';
    });
  }
})();
