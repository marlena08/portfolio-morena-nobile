const filters = document.querySelectorAll('.filter');
const projects = document.querySelectorAll('.project');

filters.forEach((filter) => filter.addEventListener('click', () => {
  filters.forEach((item) => { item.classList.remove('active'); item.setAttribute('aria-selected', 'false'); });
  filter.classList.add('active');
  filter.setAttribute('aria-selected', 'true');
  const category = filter.dataset.filter;
  projects.forEach((project) => { project.hidden = category !== 'all' && !project.dataset.category.includes(category); });
}));

document.querySelectorAll('.process-step').forEach((step) => step.addEventListener('click', () => {
  document.querySelectorAll('.process-step').forEach((item) => {
    item.classList.remove('active'); item.setAttribute('aria-expanded', 'false'); item.querySelector('i').textContent = '+';
  });
  step.classList.add('active'); step.setAttribute('aria-expanded', 'true'); step.querySelector('i').textContent = '−';
}));

// Modale progetto: attiva solo se la pagina la contiene (in index non c'è,
// e senza questo controllo lo script si bloccava qui e il resto non partiva)
const modal = document.querySelector('.project-modal');
if (modal) {
  document.querySelectorAll('.project-link').forEach((link) => link.addEventListener('click', () => {
    modal.querySelector('h2').textContent = link.dataset.project;
    modal.showModal();
  }));
  const modalClose = document.querySelector('.modal-close');
  if (modalClose) modalClose.addEventListener('click', () => modal.close());
  modal.addEventListener('click', (event) => { if (event.target === modal) modal.close(); });
}
