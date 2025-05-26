/**
 * Admin Login page functionality
 */

document.addEventListener('DOMContentLoaded', () => {
  // Login form handling
  const loginForm = document.getElementById('loginForm');
  
  if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
      e.preventDefault();
      
      // Get form data
      const username = document.getElementById('username').value;
      const password = document.getElementById('password').value;
      const rememberMe = document.getElementById('rememberMe').checked;
      
      // In a real app, we would make an API call to verify credentials
      // For demo purposes, we'll use hardcoded credentials
      if (username === 'admin' && password === 'admin123') {
        // Store authentication status
        const authData = {
          isAuthenticated: true,
          username: username,
          role: 'admin',
          timestamp: new Date().getTime()
        };
        
        // Store authentication in session or localStorage based on "remember me"
        if (rememberMe) {
          saveToLocalStorage('authData', authData);
        } else {
          // Using sessionStorage for session-only storage
          sessionStorage.setItem('authData', JSON.stringify(authData));
        }
        
        // Redirect to admin panel
        window.location.href = 'admin-panel.html';
      } else {
        // Show error message
        alert('Invalid username or password. Please try again.');
      }
    });
  }
  
  // Check if user is already logged in
  function checkAuthentication() {
    // Check localStorage first
    const authDataLocal = getFromLocalStorage('authData');
    
    // Then check sessionStorage
    let authDataSession = null;
    try {
      const authSessionStr = sessionStorage.getItem('authData');
      if (authSessionStr) {
        authDataSession = JSON.parse(authSessionStr);
      }
    } catch (error) {
      console.error('Error parsing session auth data:', error);
    }
    
    const authData = authDataLocal || authDataSession;
    
    if (authData && authData.isAuthenticated) {
      // Check if authentication is expired (24 hours)
      const currentTime = new Date().getTime();
      const authTime = authData.timestamp;
      const authDuration = 24 * 60 * 60 * 1000; // 24 hours in milliseconds
      
      if (currentTime - authTime < authDuration) {
        // Authentication is valid, redirect to admin panel
        window.location.href = 'admin-panel.html';
      } else {
        // Authentication expired, clear data
        localStorage.removeItem('authData');
        sessionStorage.removeItem('authData');
      }
    }
  }
  
  // Initialize page
  function init() {
    // Check if user is already authenticated
    checkAuthentication();
  }
  
  // Call initialization
  init();
});