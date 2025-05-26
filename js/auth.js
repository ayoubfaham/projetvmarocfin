document.addEventListener('DOMContentLoaded', () => {
    // Gestion du formulaire de connexion
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const remember = document.querySelector('input[name="remember"]').checked;

            // Ici, vous pouvez ajouter la logique de connexion
            console.log('Tentative de connexion:', { email, password, remember });
            
            // Simulation de connexion réussie
            alert('Connexion réussie !');
            window.location.href = 'index.html';
        });
    }

    // Gestion du formulaire d'inscription
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const fullname = document.getElementById('fullname').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const terms = document.querySelector('input[name="terms"]').checked;

            // Validation des mots de passe
            if (password !== confirmPassword) {
                alert('Les mots de passe ne correspondent pas !');
                return;
            }

            // Ici, vous pouvez ajouter la logique d'inscription
            console.log('Tentative d\'inscription:', { fullname, email, password, terms });
            
            // Simulation d'inscription réussie
            alert('Inscription réussie ! Vous pouvez maintenant vous connecter.');
            window.location.href = 'login.html';
        });
    }

    // Gestion des boutons de connexion sociale
    const socialButtons = document.querySelectorAll('.social-btn');
    socialButtons.forEach(button => {
        button.addEventListener('click', () => {
            const provider = button.classList.contains('google') ? 'Google' : 'Facebook';
            console.log(`Tentative de connexion avec ${provider}`);
            alert(`Connexion avec ${provider} non implémentée pour le moment.`);
        });
    });

    // Gestion du lien "Mot de passe oublié"
    const forgotPasswordLink = document.querySelector('.forgot-password');
    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', (e) => {
            e.preventDefault();
            alert('Fonctionnalité de réinitialisation de mot de passe non implémentée pour le moment.');
        });
    }

    // Fonction pour afficher les messages d'erreur
    function showError(form, message) {
        // Supprimer les messages d'erreur existants
        const existingError = form.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }

        // Créer et afficher le nouveau message d'erreur
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        form.insertBefore(errorDiv, form.querySelector('button'));
    }

    // Animation des boutons
    const buttons = document.querySelectorAll('.auth-button');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', () => {
            button.style.transform = 'translateY(-2px)';
        });

        button.addEventListener('mouseleave', () => {
            button.style.transform = 'translateY(0)';
        });
    });
}); 