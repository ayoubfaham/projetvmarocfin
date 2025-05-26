/**
 * Utility helper functions for VMaroc application
 */

/**
 * Creates a star rating element
 * @param {number} rating - Rating value (1-5)
 * @returns {string} HTML string of star rating
 */
function createStarRating(rating) {
  let stars = '';
  const fullStar = '<span class="star full">★</span>';
  const emptyStar = '<span class="star empty">☆</span>';
  
  for (let i = 1; i <= 5; i++) {
    if (i <= rating) {
      stars += fullStar;
    } else {
      stars += emptyStar;
    }
  }
  
  return stars;
}

/**
 * Format date to a readable string
 * @param {Date} date - Date object
 * @returns {string} Formatted date string
 */
function formatDate(date) {
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(date).toLocaleDateString('en-US', options);
}

/**
 * Truncate text to a specified length and add ellipsis
 * @param {string} text - Text to truncate
 * @param {number} length - Maximum length
 * @returns {string} Truncated text
 */
function truncateText(text, length = 100) {
  if (text.length <= length) return text;
  return text.substring(0, length) + '...';
}

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @returns {boolean} True if valid, false otherwise
 */
function validateEmail(email) {
  const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(String(email).toLowerCase());
}

/**
 * Check if an element is in viewport
 * @param {HTMLElement} element - Element to check
 * @returns {boolean} True if in viewport, false otherwise
 */
function isInViewport(element) {
  const rect = element.getBoundingClientRect();
  return (
    rect.top <= (window.innerHeight || document.documentElement.clientHeight) &&
    rect.bottom >= 0
  );
}

/**
 * Add animation class when element is in viewport
 * @param {HTMLElement} element - Element to animate
 */
function animateOnScroll(element) {
  if (isInViewport(element) && !element.classList.contains('animate')) {
    element.classList.add('animate');
  }
}

/**
 * Generate a random ID
 * @returns {string} Random ID
 */
function generateId() {
  return Math.random().toString(36).substring(2, 15);
}

/**
 * Debounce function to limit how often a function can be called
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
function debounce(func, wait = 100) {
  let timeout;
  return function(...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      func.apply(this, args);
    }, wait);
  };
}

/**
 * Save data to localStorage
 * @param {string} key - Storage key
 * @param {any} value - Value to store
 */
function saveToLocalStorage(key, value) {
  try {
    localStorage.setItem(key, JSON.stringify(value));
  } catch (error) {
    console.error('Error saving to localStorage:', error);
  }
}

/**
 * Get data from localStorage
 * @param {string} key - Storage key
 * @param {any} defaultValue - Default value if key doesn't exist
 * @returns {any} Stored value or default
 */
function getFromLocalStorage(key, defaultValue = null) {
  try {
    const item = localStorage.getItem(key);
    return item ? JSON.parse(item) : defaultValue;
  } catch (error) {
    console.error('Error getting from localStorage:', error);
    return defaultValue;
  }
}