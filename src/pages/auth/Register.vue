<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-card">
        <h1>Inscription</h1>
        <form @submit.prevent="handleRegister" class="auth-form">
          <div class="form-group">
            <label for="name">Nom complet</label>
            <input
              type="text"
              id="name"
              v-model="name"
              required
              placeholder="Votre nom complet"
            >
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input
              type="email"
              id="email"
              v-model="email"
              required
              placeholder="votre@email.com"
            >
          </div>
          <div class="form-group">
            <label for="password">Mot de passe</label>
            <input
              type="password"
              id="password"
              v-model="password"
              required
              placeholder="Créez un mot de passe"
            >
          </div>
          <div class="form-group">
            <label for="confirmPassword">Confirmer le mot de passe</label>
            <input
              type="password"
              id="confirmPassword"
              v-model="confirmPassword"
              required
              placeholder="Confirmez votre mot de passe"
            >
          </div>
          <div class="form-options">
            <label class="terms">
              <input type="checkbox" v-model="acceptTerms" required>
              <span>J'accepte les <a href="/terms">conditions d'utilisation</a></span>
            </label>
          </div>
          <button type="submit" class="btn btn-primary btn-block">
            Créer un compte
          </button>
        </form>
        <div class="auth-footer">
          <p>
            Déjà un compte ?
            <router-link to="/auth/login">Se connecter</router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const acceptTerms = ref(false)

const handleRegister = async () => {
  try {
    if (password.value !== confirmPassword.value) {
      alert('Les mots de passe ne correspondent pas')
      return
    }

    // Ici, vous implémenterez la logique d'inscription
    console.log('Register attempt:', {
      name: name.value,
      email: email.value,
      password: password.value,
      acceptTerms: acceptTerms.value
    })
    
    // Redirection après inscription réussie
    router.push('/auth/login')
  } catch (error) {
    console.error('Register error:', error)
  }
}
</script>

<style lang="scss" scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
  padding: 2rem;
}

.auth-container {
  width: 100%;
  max-width: 400px;
}

.auth-card {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);

  h1 {
    font-size: 2rem;
    color: var(--primary);
    margin-bottom: 2rem;
    text-align: center;
    font-family: 'Playfair Display', serif;
  }
}

.auth-form {
  .form-group {
    margin-bottom: 1.5rem;

    label {
      display: block;
      margin-bottom: 0.5rem;
      color: var(--gray-700);
      font-weight: 500;
    }

    input {
      width: 100%;
      padding: 0.875rem 1rem;
      border: 1px solid var(--gray-300);
      border-radius: 6px;
      font-size: 1rem;
      transition: all 0.3s ease;

      &:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(247, 110, 17, 0.1);
      }
    }
  }
}

.form-options {
  margin-bottom: 1.5rem;

  .terms {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--gray-600);
    cursor: pointer;

    input[type="checkbox"] {
      width: 1rem;
      height: 1rem;
    }

    a {
      color: var(--accent);
      text-decoration: none;

      &:hover {
        text-decoration: underline;
      }
    }
  }
}

.btn-block {
  width: 100%;
  padding: 1rem;
  font-size: 1rem;
  font-weight: 600;
}

.auth-footer {
  margin-top: 2rem;
  text-align: center;
  color: var(--gray-600);

  a {
    color: var(--accent);
    text-decoration: none;
    font-weight: 500;

    &:hover {
      text-decoration: underline;
    }
  }
}
</style> 