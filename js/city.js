document.addEventListener('DOMContentLoaded', () => {
    // Configuration de la pagination
    const placesPerPage = 7;
    const placeCards = document.querySelectorAll('.place-card');
    const totalPlaces = placeCards.length;
    const totalPages = Math.ceil(totalPlaces / placesPerPage);
    
    // Éléments de pagination
    const pageNumbers = document.querySelector('.page-numbers');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    
    let currentPage = 1;

    // Fonction pour afficher les lieux de la page courante
    function displayPlaces(page) {
        const start = (page - 1) * placesPerPage;
        const end = start + placesPerPage;
        
        placeCards.forEach((card, index) => {
            card.style.display = (index >= start && index < end) ? 'block' : 'none';
        });
    }

    // Fonction pour mettre à jour les boutons de pagination
    function updatePaginationButtons() {
        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage === totalPages;
        
        document.querySelectorAll('.page-number').forEach((button, index) => {
            button.classList.toggle('active', index + 1 === currentPage);
        });
    }

    // Gestionnaires d'événements pour la pagination
    pageNumbers.addEventListener('click', (e) => {
        if (e.target.classList.contains('page-number')) {
            currentPage = parseInt(e.target.textContent);
            displayPlaces(currentPage);
            updatePaginationButtons();
        }
    });

    prevBtn.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            displayPlaces(currentPage);
            updatePaginationButtons();
        }
    });

    nextBtn.addEventListener('click', () => {
        if (currentPage < totalPages) {
            currentPage++;
            displayPlaces(currentPage);
            updatePaginationButtons();
        }
    });

    // Gestion des catégories
    const categoryButtons = document.querySelectorAll('.category-btn');
    
    categoryButtons.forEach(button => {
        button.addEventListener('click', () => {
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            const category = button.dataset.category;
            placeCards.forEach(card => {
                card.style.display = (category === 'all' || card.dataset.category === category) ? 'block' : 'none';
            });
            
            currentPage = 1;
            updatePaginationButtons();
        });
    });

    // Gestion de la recherche
    const searchInput = document.querySelector('.search-container input');
    const searchButton = document.querySelector('.search-container button');

    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase();
        
        placeCards.forEach(card => {
            const title = card.querySelector('h3').textContent.toLowerCase();
            const description = card.querySelector('p').textContent.toLowerCase();
            const address = card.querySelector('.address').textContent.toLowerCase();
            
            card.style.display = (title.includes(searchTerm) || 
                                description.includes(searchTerm) || 
                                address.includes(searchTerm)) ? 'block' : 'none';
        });
        
        currentPage = 1;
        updatePaginationButtons();
    }

    searchButton.addEventListener('click', performSearch);
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') performSearch();
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
            if (mobileMenuBtn) mobileMenuBtn.remove();
            navMenu.style.display = 'flex';
        }
    };

    // Initialisation
    displayPlaces(1);
    updatePaginationButtons();
    window.addEventListener('resize', createMobileMenu);
    createMobileMenu();

    // Gestion des boutons de connexion et d'inscription
    const loginBtn = document.querySelector('.login-btn');
    const registerBtn = document.querySelector('.register-btn');

    loginBtn.addEventListener('click', () => {
        // Redirection vers la page de connexion
        window.location.href = 'login.html';
    });

    registerBtn.addEventListener('click', () => {
        // Redirection vers la page d'inscription
        window.location.href = 'register.html';
    });
}); 