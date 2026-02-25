  document.addEventListener('DOMContentLoaded', () => {
    const storedUsername = localStorage.getItem('username') || 'User';

    const navbarUsernameEl = document.getElementById('navbar-username');
    const heroUsernameEl   = document.getElementById('hero-username');

    if (navbarUsernameEl) navbarUsernameEl.textContent = storedUsername;
    if (heroUsernameEl) heroUsernameEl.textContent = storedUsername;
  });
