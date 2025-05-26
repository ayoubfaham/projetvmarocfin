document.addEventListener('DOMContentLoaded', () => {
    // Gestion de la recherche
    const searchInput = document.querySelector('.search-container input');
    const searchButton = document.querySelector('.search-container button');
    const cityCards = document.querySelectorAll('.city-card');

    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase();
        
        cityCards.forEach(card => {
            const title = card.querySelector('h3').textContent.toLowerCase();
            const description = card.querySelector('p').textContent.toLowerCase();
            const details = card.querySelector('.city-details').textContent.toLowerCase();

            if (title.includes(searchTerm) || 
                description.includes(searchTerm) || 
                details.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', performSearch);
    searchButton.addEventListener('click', performSearch);

    // Animation des cartes au survol
    cityCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });
    });

    // Gestion du menu mobile
    const createMobileMenu = () => {
        const nav = document.querySelector('nav');
        const menuButton = document.createElement('button');
        menuButton.className = 'mobile-menu-button';
        menuButton.innerHTML = '<i class="fas fa-bars"></i>';
        
        nav.insertBefore(menuButton, nav.querySelector('.nav-menu'));

        menuButton.addEventListener('click', () => {
            const navMenu = document.querySelector('.nav-menu');
            navMenu.classList.toggle('active');
        });
    };

    // Créer le menu mobile si la largeur de l'écran est inférieure à 768px
    if (window.innerWidth < 768) {
        createMobileMenu();
    }

    // Recréer le menu mobile lors du redimensionnement de la fenêtre
    window.addEventListener('resize', () => {
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        if (window.innerWidth < 768 && !mobileMenuButton) {
            createMobileMenu();
        } else if (window.innerWidth >= 768 && mobileMenuButton) {
            mobileMenuButton.remove();
            document.querySelector('.nav-menu').classList.remove('active');
        }
    });

    // Animation du hero section
    const heroContent = document.querySelector('.hero-content');
    if (heroContent) {
        heroContent.style.opacity = '0';
        heroContent.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            heroContent.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            heroContent.style.opacity = '1';
            heroContent.style.transform = 'translateY(0)';
        }, 100);
    }

    // Gestion de la navigation mobile
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    const navLinks = document.querySelector('.nav-links');

    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }

    // Animation des cartes de destination
    const destinationCards = document.querySelectorAll('.destination-card');
    destinationCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-10px)';
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });
    });

    // Gestion du formulaire de recherche
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const searchTerm = searchForm.querySelector('input').value;
            window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
        });
    }

    // Animation du scroll
    const scrollElements = document.querySelectorAll('.scroll-animate');
    const elementInView = (el, percentageScroll = 100) => {
        const elementTop = el.getBoundingClientRect().top;
        return (
            elementTop <= 
            ((window.innerHeight || document.documentElement.clientHeight) * (percentageScroll/100))
        );
    };

    const displayScrollElement = (element) => {
        element.classList.add('scrolled');
    };

    const handleScrollAnimation = () => {
        scrollElements.forEach((el) => {
            if (elementInView(el, 100)) {
                displayScrollElement(el);
            }
        });
    };

    window.addEventListener('scroll', () => {
        handleScrollAnimation();
    });

    // Initial check for elements in view
    handleScrollAnimation();
});

// Gestion des messages flash
function showFlashMessage(message, type = 'success') {
    const flashContainer = document.createElement('div');
    flashContainer.className = `flash-message ${type}`;
    flashContainer.textContent = message;

    document.body.appendChild(flashContainer);

    // Animation d'entrée
    setTimeout(() => {
        flashContainer.classList.add('show');
    }, 100);

    // Suppression après 3 secondes
    setTimeout(() => {
        flashContainer.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(flashContainer);
        }, 300);
    }, 3000);
}

// Gestion des formulaires
function handleFormSubmit(formId, successMessage) {
    const form = document.getElementById(formId);
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showFlashMessage(successMessage || data.message);
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    }
                } else {
                    showFlashMessage(data.message || 'Une erreur est survenue', 'error');
                }
            } catch (error) {
                showFlashMessage('Une erreur est survenue', 'error');
                console.error('Form submission error:', error);
            }
        });
    }
}

// Initialisation des formulaires
document.addEventListener('DOMContentLoaded', () => {
    handleFormSubmit('login-form', 'Connexion réussie !');
    handleFormSubmit('register-form', 'Inscription réussie !');
    handleFormSubmit('contact-form', 'Message envoyé avec succès !');
}); 