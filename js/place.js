document.addEventListener('DOMContentLoaded', () => {
    // Gestion de la galerie d'images
    const mainImage = document.querySelector('.main-image img');
    const thumbnails = document.querySelectorAll('.thumbnail-gallery img');

    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', () => {
            // Mise à jour de l'image principale
            mainImage.src = thumbnail.src.replace('-1', '-main');
            mainImage.alt = thumbnail.alt;

            // Mise à jour de la miniature active
            thumbnails.forEach(thumb => thumb.classList.remove('active'));
            thumbnail.classList.add('active');

            // Animation de transition
            mainImage.style.opacity = '0';
            setTimeout(() => {
                mainImage.style.opacity = '1';
            }, 300);
        });
    });

    // Gestion du bouton de réservation
    const bookBtn = document.querySelector('.book-btn');
    bookBtn.addEventListener('click', () => {
        // Vérifier si l'utilisateur est connecté
        const isLoggedIn = false; // À remplacer par la vraie vérification

        if (!isLoggedIn) {
            // Rediriger vers la page de connexion
            window.location.href = 'login.html';
        } else {
            // Ouvrir le modal de réservation
            alert('Fonctionnalité de réservation à venir !');
        }
    });

    // Animation de l'en-tête au chargement
    const placeHeader = document.querySelector('.place-header');
    placeHeader.style.opacity = '0';
    placeHeader.style.transform = 'translateY(20px)';

    setTimeout(() => {
        placeHeader.style.transition = 'all 1s ease';
        placeHeader.style.opacity = '1';
        placeHeader.style.transform = 'translateY(0)';
    }, 100);

    // Animation des sections au défilement
    const sections = document.querySelectorAll('.info-section, .services-section, .description-section, .location-section');
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    sections.forEach(section => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(20px)';
        section.style.transition = 'all 0.5s ease';
        observer.observe(section);
    });

    // Gestion du menu mobile
    const createMobileMenu = () => {
        const nav = document.querySelector('nav');
        const navMenu = document.querySelector('.nav-menu');
        
        if (window.innerWidth <= 768 && !document.querySelector('.mobile-menu-btn')) {
            const mobileMenuBtn = document.createElement('button');
            mobileMenuBtn.className = 'mobile-menu-btn';
            mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            
            nav.insertBefore(mobileMenuBtn, navMenu);
            navMenu.style.display = 'none';
            
            mobileMenuBtn.addEventListener('click', () => {
                navMenu.style.display = navMenu.style.display === 'none' ? 'flex' : 'none';
            });
        } else if (window.innerWidth > 768) {
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            if (mobileMenuBtn) {
                mobileMenuBtn.remove();
            }
            navMenu.style.display = 'flex';
        }
    };

    window.addEventListener('resize', createMobileMenu);
    createMobileMenu();

    // Gestion des services au survol
    const serviceItems = document.querySelectorAll('.service-item');
    serviceItems.forEach(item => {
        item.addEventListener('mouseenter', () => {
            item.style.transform = 'translateY(-5px)';
            item.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.1)';
        });

        item.addEventListener('mouseleave', () => {
            item.style.transform = 'translateY(0)';
            item.style.boxShadow = 'none';
        });
    });
}); 