/*=============== SHOW MENU ===============*/
const sections = document.querySelectorAll('section[id]');
window.addEventListener('scroll', () => {
    const scrollY = window.pageYOffset;
    sections.forEach(current => {
        const sectionHeight = current.offsetHeight;
        const sectionTop = current.offsetTop - 100;
        const sectionId = current.getAttribute('id');
        const navLink = document.querySelector(`.nav__list a[href*=${sectionId}]`);
        if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
            navLink?.classList.add('active-link');
        } else {
            navLink?.classList.remove('active-link');
        }
    });
});

const navMenu = document.getElementById('nav-menu'),
      navToggle = document.getElementById('nav-toggle'),
      navClose = document.getElementById('nav-close');

if (navToggle) {
    navToggle.addEventListener('click', () => {
        navMenu.classList.add('show-menu');
    });
}

if (navClose) {
    navClose.addEventListener('click', () => {
        navMenu.classList.remove('show-menu');
    });
}

/*=============== REMOVE MENU MOBILE ===============*/
const navLinks = document.querySelectorAll('.nav__link');
const linkAction = () => {
    const navMenu = document.getElementById('nav-menu');
    if (navMenu) navMenu.classList.remove('show-menu');
};
navLinks.forEach(link => link.addEventListener('click', linkAction));

/*=============== SHADOW HEADER ===============*/
const shadowHeader = () => {
    const header = document.getElementById('header');
    if (header) {
        window.scrollY >= 50 ? header.classList.add('shadow-header') : header.classList.remove('shadow-header');
    }
};
window.addEventListener('scroll', shadowHeader);

/*=============== SHOW SCROLL UP ===============*/
const scrollUp = () => {
    const scrollUpBtn = document.getElementById('scroll-up');
    if (scrollUpBtn) {
        window.scrollY >= 350 ? scrollUpBtn.classList.add('show-scroll') : scrollUpBtn.classList.remove('show-scroll');
    }
};
window.addEventListener('scroll', scrollUp);

/*=============== DARK LIGHT THEME ===============*/
const themeButton = document.getElementById('theme-button');
const darkTheme = 'dark-theme';
const iconMoon = 'ri-moon-line';
const iconSun = 'ri-sun-line';

document.addEventListener('DOMContentLoaded', () => {
    const selectedTheme = localStorage.getItem('selected-theme');
    const selectedIcon = localStorage.getItem('selected-icon');

    if (selectedTheme) {
        document.body.classList[selectedTheme === 'dark' ? 'add' : 'remove'](darkTheme);
        if (selectedIcon === iconMoon) {
            themeButton.classList.remove(iconSun);
            themeButton.classList.add(iconMoon);
        } else {
            themeButton.classList.remove(iconMoon);
            themeButton.classList.add(iconSun);
        }
    } else {
        if (!themeButton.classList.contains(iconMoon) && !themeButton.classList.contains(iconSun)) {
            themeButton.classList.add(iconMoon);
        }
    }

    if (themeButton) {
        themeButton.addEventListener('click', () => {
            document.body.classList.toggle(darkTheme);
            themeButton.classList.toggle(iconMoon);
            themeButton.classList.toggle(iconSun);
            const currentTheme = document.body.classList.contains(darkTheme) ? 'dark' : 'light';
            const currentIcon = themeButton.classList.contains(iconMoon) ? iconMoon : iconSun;
            localStorage.setItem('selected-theme', currentTheme);
            localStorage.setItem('selected-icon', currentIcon);
        });
    }
});

/*=============== CONTACT FORM (Demo mode) ===============*/
const contactForm = document.getElementById('contact-form');
if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
        e.preventDefault();
        alert('Thank you for your message! (Demo mode)');
        contactForm.reset();
    });
}

/*=============== SCROLL REVEAL ANIMATION ===============*/
if (typeof ScrollReveal !== 'undefined') {
    const sr = ScrollReveal({
        origin: 'top',
        distance: '60px',
        duration: 2500,
        delay: 300,
        reset: false,
    });

    sr.reveal('.home__data, .home__social, .about__content, .personal-info, .personal-photos-section, .contact__data');
    sr.reveal('.home__image, .about__images', { origin: 'bottom' });
    sr.reveal('.contact__form', { origin: 'right' });
    
    if (document.querySelector('.demo-section')) {
        sr.reveal('.demo-title, .demo-period', { interval: 100 });
        sr.reveal('.demo-overview', { origin: 'left' });
        sr.reveal('.demo-details h3, .tech-stack, .gallery, .demo-links', { interval: 200 });
    }
}