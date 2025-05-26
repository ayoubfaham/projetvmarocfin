/**
 * Header component functionality
 */

document.addEventListener('DOMContentLoaded', () => {
  // Elements
  const header = document.getElementById('header');
  const menuToggle = document.getElementById('menuToggle');
  const navMenu = document.querySelector('.nav-menu');
  
  // Handle scroll event to change header style
  function handleScroll() {
    if (window.scrollY > 50) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  }
  
  // Toggle mobile menu
  function toggleMenu() {
    menuToggle.classList.toggle('active');
    navMenu.classList.toggle('active');
    document.body.classList.toggle('menu-open');
  }
  
  // Close menu when clicking outside
  function closeMenuOutside(event) {
    if (navMenu.classList.contains('active') && 
        !navMenu.contains(event.target) && 
        !menuToggle.contains(event.target)) {
      menuToggle.classList.remove('active');
      navMenu.classList.remove('active');
      document.body.classList.remove('menu-open');
    }
  }
  
  // Close menu when clicking on a link
  function setupNavLinks() {
    const navLinks = document.querySelectorAll('.nav-list a');
    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        menuToggle.classList.remove('active');
        navMenu.classList.remove('active');
        document.body.classList.remove('menu-open');
      });
    });
  }
  
  // Set active menu link based on current page
  function setActiveLink() {
    const currentUrl = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-list a');
    
    navLinks.forEach(link => {
      if (link.getAttribute('href') === currentUrl || 
          (currentUrl === '/' && link.getAttribute('href') === 'index.html')) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });
  }
  
  // Initialize header functionality
  function init() {
    // Initial check for scroll position
    handleScroll();
    
    // Set active link
    setActiveLink();
    
    // Setup event listeners
    window.addEventListener('scroll', handleScroll);
    menuToggle.addEventListener('click', toggleMenu);
    document.addEventListener('click', closeMenuOutside);
    
    // Setup nav links
    setupNavLinks();
  }
  
  // Call initialization
  init();
});