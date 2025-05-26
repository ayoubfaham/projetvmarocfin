<template>
  <div class="auth-page">
    <div class="auth-container">
      <div class="auth-card">
        <h1>Connexion</h1>
        <form @submit.prevent="handleLogin" class="auth-form">
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
              placeholder="Votre mot de passe"
            >
          </div>
          <div class="form-options">
            <label class="remember-me">
              <input type="checkbox" v-model="rememberMe">
              <span>Se souvenir de moi</span>
            </label>
            <router-link to="/auth/forgot-password" class="forgot-password">
              Mot de passe oublié ?
            </router-link>
          </div>
          <button type="submit" class="btn btn-primary btn-block">
            Se connecter
          </button>
        </form>
        <div class="auth-footer">
          <p>
            Pas encore de compte ?
            <router-link to="/auth/register">S'inscrire</router-link>
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
const email = ref('')
const password = ref('')
const rememberMe = ref(false)

const handleLogin = async () => {
  try {
    // Ici, vous implémenterez la logique de connexion
    console.log('Login attempt:', {
      email: email.value,
      password: password.value,
      rememberMe: rememberMe.value
    })
    
    // Redirection après connexion réussie
    router.push('/')
  } catch (error) {
    console.error('Login error:', error)
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
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;

  .remember-me {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--gray-600);
    cursor: pointer;

    input[type="checkbox"] {
      width: 1rem;
      height: 1rem;
    }
  }

  .forgot-password {
    color: var(--accent);
    text-decoration: none;
    font-size: 0.875rem;

    &:hover {
      text-decoration: underline;
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