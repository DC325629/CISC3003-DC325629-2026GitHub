'use strict';

/**
 * add event listener on multiple elements
 */

const addEventOnElements = function(elements, eventType, callback) {
	if (!elements || !elements.length) {
		return;
	}

	for(let i = 0, len = elements.length; i < len; i++) {
		elements[i].addEventListener(eventType, callback);
	}
}

/**
 * MOBILE NAV
 */

const navbar = document.querySelector('[data-navbar]');
const navbarToggler = document.querySelectorAll('[data-nav-toggler]');
const navbarLinks = document.querySelectorAll('[data-nav-link]');
const overlay = document.querySelector('[data-overlay]');

const togglerNav = function() {
	if (!navbar || !overlay) {
		return;
	}

	navbar.classList.toggle('active');
	overlay.classList.toggle('active');
}

addEventOnElements(navbarToggler, 'click', togglerNav);

const closeNav = function() {
	if (!navbar || !overlay) {
		return;
	}

	navbar.classList.remove('active');
	overlay.classList.remove('active');
}

addEventOnElements(navbarLinks, 'click', closeNav);


/**
 * HEADER & BACK TOP BTN
 */

const header = document.querySelector('[data-header]');
const backTopBtn = document.querySelector('[data-back-top-btn]');

window.addEventListener('scroll', function() {
	if (header) {
		header.classList[window.scrollY > 50 ? 'add' : 'remove']('active');
	}

	if (backTopBtn) {
		backTopBtn.classList[window.scrollY > 50 ? 'add' : 'remove']('active');
	}
});
