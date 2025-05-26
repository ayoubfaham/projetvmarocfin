/**
 * Home page functionality
 */

document.addEventListener('DOMContentLoaded', () => {
  // Initialize city cards
  function initCityCards() {
    const citiesGrid = document.getElementById('citiesGrid');
    if (!citiesGrid) return;
    
    // Clear existing content
    citiesGrid.innerHTML = '';
    
    // Only display first 6 cities
    const citiesToShow = cities.slice(0, 6);
    
    // Create city cards
    citiesToShow.forEach(city => {
      const cityCard = document.createElement('div');
      cityCard.className = 'city-card card';
      cityCard.innerHTML = `
        <div class="city-card-image">
          <img src="${city.image}" alt="${city.name}">
        </div>
        <div class="city-card-content">
          <h3 class="city-card-title">${city.name}</h3>
          <p class="city-card-subtitle">${city.subtitle}</p>
          <p class="city-card-description">${city.description}</p>
          <div class="city-card-footer">
            <span class="city-card-attractions">${city.attractions} Attractions</span>
            <a href="pages/city.html?id=${city.id}" class="btn btn-sm btn-secondary">Explore</a>
          </div>
        </div>
      `;
      citiesGrid.appendChild(cityCard);
    });
  }
  
  // Initialize experience cards
  function initExperienceCards() {
    const experiencesGrid = document.getElementById('experiencesGrid');
    if (!experiencesGrid) return;
    
    // Clear existing content
    experiencesGrid.innerHTML = '';
    
    // Only display first 6 experiences
    const experiencesToShow = experiences.slice(0, 6);
    
    // Create experience cards
    experiencesToShow.forEach(experience => {
      const experienceCard = document.createElement('div');
      experienceCard.className = 'experience-card card';
      experienceCard.innerHTML = `
        <div class="experience-card-image">
          <img src="${experience.image}" alt="${experience.title}">
        </div>
        <div class="experience-card-content">
          <h3 class="experience-card-title">${experience.title}</h3>
          <p class="experience-card-location">
            <i>📍</i> ${experience.location}
          </p>
          <p class="experience-card-description">${experience.description}</p>
          <div class="experience-card-footer">
            <span class="experience-card-category">${experience.category}</span>
            <a href="pages/experience.html?id=${experience.id}" class="btn btn-sm btn-secondary">Details</a>
          </div>
        </div>
      `;
      experiencesGrid.appendChild(experienceCard);
    });
  }
  
  // Initialize review cards
  function initReviewCards() {
    const reviewsGrid = document.getElementById('reviewsGrid');
    if (!reviewsGrid) return;
    
    // Clear existing content
    reviewsGrid.innerHTML = '';
    
    // Create review cards
    reviews.forEach(review => {
      const reviewCard = document.createElement('div');
      reviewCard.className = 'review-card';
      reviewCard.innerHTML = `
        <div class="review-card-header">
          <div class="review-card-avatar">
            <img src="${review.avatar}" alt="${review.name}">
          </div>
          <div class="review-card-info">
            <h3 class="review-card-name">${review.name}</h3>
            <p class="review-card-location">${review.location}</p>
          </div>
        </div>
        <div class="review-card-rating">
          ${createStarRating(review.rating)}
        </div>
        <p class="review-card-content">${review.content}</p>
        <p class="review-card-date">${review.date}</p>
      `;
      reviewsGrid.appendChild(reviewCard);
    });
  }
  
  // Handle recommendations form submission
  function handleRecommendationsForm() {
    const form = document.getElementById('recommendationsForm');
    if (!form) return;
    
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      
      // Get form data
      const arrivalDate = document.getElementById('arrivalDate').value;
      const departureDate = document.getElementById('departureDate').value;
      const interests = Array.from(document.querySelectorAll('input[name="interests"]:checked'))
        .map(checkbox => checkbox.value);
      
      // Save form data to localStorage for use in recommendations page
      saveToLocalStorage('travelDates', {
        arrival: arrivalDate,
        departure: departureDate
      });
      
      saveToLocalStorage('travelInterests', interests);
      
      // Redirect to recommendations page
      window.location.href = 'pages/recommendations.html';
    });
  }
  
  // Set up scroll animations
  function setupScrollAnimations() {
    const sections = document.querySelectorAll('.section');
    
    // Function to check if elements are in viewport and animate them
    function checkAnimations() {
      sections.forEach(section => {
        animateOnScroll(section);
      });
    }
    
    // Initial check
    checkAnimations();
    
    // Add scroll event listener with debounce
    window.addEventListener('scroll', debounce(checkAnimations, 50));
  }
  
  // Initialize all functionality
  function init() {
    initCityCards();
    initExperienceCards();
    initReviewCards();
    handleRecommendationsForm();
    setupScrollAnimations();
  }
  
  // Call initialization
  init();
});