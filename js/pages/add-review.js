/**
 * Add Review page functionality
 */

document.addEventListener('DOMContentLoaded', () => {
  // Get form elements
  const reviewForm = document.getElementById('reviewForm');
  const visitTypeSelect = document.getElementById('visitType');
  const citySelect = document.querySelector('.city-select');
  const attractionSelect = document.querySelector('.attraction-select');
  const additionalYes = document.getElementById('additionalYes');
  const additionalNo = document.getElementById('additionalNo');
  const additionalQuestions = document.getElementById('additionalQuestions');
  
  // Populate city select
  const cityDropdown = document.getElementById('city');
  if (cityDropdown) {
    // Add default option
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Select a city';
    cityDropdown.appendChild(defaultOption);
    
    // Add cities
    cities.forEach(city => {
      const option = document.createElement('option');
      option.value = city.id;
      option.textContent = city.name;
      cityDropdown.appendChild(option);
    });
  }
  
  // Populate attraction select
  const attractionDropdown = document.getElementById('attraction');
  if (attractionDropdown) {
    // Add default option
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Select an attraction';
    attractionDropdown.appendChild(defaultOption);
    
    // Add attractions
    experiences.forEach(exp => {
      const option = document.createElement('option');
      option.value = exp.id;
      option.textContent = exp.title;
      attractionDropdown.appendChild(option);
    });
  }
  
  // Toggle city/attraction select based on visit type
  if (visitTypeSelect) {
    visitTypeSelect.addEventListener('change', () => {
      const selectedType = visitTypeSelect.value;
      
      if (selectedType === 'city') {
        citySelect.classList.remove('hidden');
        attractionSelect.classList.add('hidden');
      } else if (selectedType === 'attraction') {
        citySelect.classList.add('hidden');
        attractionSelect.classList.remove('hidden');
      } else {
        citySelect.classList.add('hidden');
        attractionSelect.classList.add('hidden');
      }
    });
  }
  
  // Toggle additional questions
  if (additionalYes && additionalNo) {
    additionalYes.addEventListener('change', () => {
      if (additionalYes.checked) {
        additionalQuestions.classList.remove('hidden');
      }
    });
    
    additionalNo.addEventListener('change', () => {
      if (additionalNo.checked) {
        additionalQuestions.classList.add('hidden');
      }
    });
  }
  
  // Form submission
  if (reviewForm) {
    reviewForm.addEventListener('submit', (e) => {
      e.preventDefault();
      
      // Validate email
      const email = document.getElementById('email').value;
      if (!validateEmail(email)) {
        alert('Please enter a valid email address');
        return;
      }
      
      // Get form data
      const formData = {
        name: document.getElementById('name').value,
        email: email,
        location: document.getElementById('location').value,
        rating: document.querySelector('input[name="rating"]:checked').value,
        visitType: visitTypeSelect.value,
        reviewText: document.getElementById('reviewText').value,
        consentGiven: document.getElementById('consent').checked,
        timestamp: new Date().toISOString()
      };
      
      // Add city or attraction if selected
      if (visitTypeSelect.value === 'city' && document.getElementById('city').value) {
        formData.cityId = document.getElementById('city').value;
      } else if (visitTypeSelect.value === 'attraction' && document.getElementById('attraction').value) {
        formData.attractionId = document.getElementById('attraction').value;
      }
      
      // Add additional feedback if provided
      if (additionalYes.checked) {
        formData.additionalFeedback = {
          foodRating: document.querySelector('input[name="foodRating"]:checked')?.value || null,
          hospitalityRating: document.querySelector('input[name="hospitalityRating"]:checked')?.value || null,
          transportFeedback: document.getElementById('transportFeedback').value,
          improvementFeedback: document.getElementById('improvementFeedback').value
        };
      }
      
      // In a real app, we would send this data to a server
      console.log('Review submitted:', formData);
      
      // For demo purposes, we'll simulate a successful submission
      // Create success message
      const successMessage = document.createElement('div');
      successMessage.className = 'success-message';
      successMessage.innerHTML = `
        <h3>Thank you for your feedback!</h3>
        <p>Your review has been submitted successfully and will be published after verification.</p>
        <p>We appreciate you taking the time to share your experience in Morocco.</p>
      `;
      
      // Insert success message before the form
      const formContainer = document.querySelector('.review-form-container');
      formContainer.parentNode.insertBefore(successMessage, formContainer);
      
      // Hide form
      formContainer.style.display = 'none';
      
      // Show success message with animation
      setTimeout(() => {
        successMessage.classList.add('visible');
        // Scroll to success message
        successMessage.scrollIntoView({ behavior: 'smooth' });
      }, 100);
    });
  }
});