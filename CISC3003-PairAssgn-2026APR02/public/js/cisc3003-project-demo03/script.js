const header = document.querySelector('.header');
const hamburger = document.querySelector('.header__toggle');
const overlay = document.querySelector('.overlay');
const html = document.documentElement;

function toggleMenu() {
  header.classList.toggle('open');

  if (header.classList.contains('open')) {
    overlay.classList.add('fade-in');
    document.body.classList.add('noscroll');
    html.classList.add('noscroll');
  } else {
    overlay.classList.remove('fade-in');
    document.body.classList.remove('noscroll');
    html.classList.remove('noscroll');
  }
}

hamburger.addEventListener('click', toggleMenu);

overlay.addEventListener('click', toggleMenu);

window.addEventListener('resize', () => {
  if (window.innerWidth >= 1024 && header.classList.contains('open')) {
    toggleMenu();
  }
});