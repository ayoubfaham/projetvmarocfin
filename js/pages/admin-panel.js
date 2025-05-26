/**
 * Admin Panel functionality
 */

document.addEventListener('DOMContentLoaded', () => {
  // Check authentication
  function checkAuth() {
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
    
    if (!authData || !authData.isAuthenticated) {
      // Not authenticated, redirect to login
      window.location.href = 'admin-login.html';
      return false;
    }
    
    // Check if authentication is expired (24 hours)
    const currentTime = new Date().getTime();
    const authTime = authData.timestamp;
    const authDuration = 24 * 60 * 60 * 1000; // 24 hours in milliseconds
    
    if (currentTime - authTime >= authDuration) {
      // Authentication expired, clear data and redirect
      localStorage.removeItem('authData');
      sessionStorage.removeItem('authData');
      window.location.href = 'admin-login.html';
      return false;
    }
    
    // Display username
    const usernameElement = document.getElementById('adminUsername');
    if (usernameElement && authData.username) {
      usernameElement.textContent = authData.username;
    }
    
    return true;
  }
  
  // Handle logout
  function setupLogout() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', () => {
        // Clear authentication data
        localStorage.removeItem('authData');
        sessionStorage.removeItem('authData');
        
        // Redirect to login page
        window.location.href = 'admin-login.html';
      });
    }
  }
  
  // Handle sidebar navigation
  function setupNavigation() {
    const menuItems = document.querySelectorAll('.admin-sidebar-menu-item');
    const sections = document.querySelectorAll('.admin-section');
    
    menuItems.forEach(item => {
      item.addEventListener('click', (e) => {
        e.preventDefault();
        
        const targetSection = item.getAttribute('data-section');
        
        // Update active menu item
        menuItems.forEach(mi => mi.classList.remove('active'));
        item.classList.add('active');
        
        // Show target section, hide others
        sections.forEach(section => {
          if (section.id === targetSection) {
            section.classList.add('active');
          } else {
            section.classList.remove('active');
          }
        });
      });
    });
  }
  
  // Populate cities table
  function populateCitiesTable() {
    const tableBody = document.getElementById('citiesTableBody');
    if (!tableBody) return;
    
    // Clear existing content
    tableBody.innerHTML = '';
    
    // Add rows for each city
    cities.forEach(city => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td><img src="${city.image}" alt="${city.name}" class="admin-table-image"></td>
        <td>${city.name}</td>
        <td>${city.subtitle}</td>
        <td>${city.attractions}</td>
        <td class="admin-table-actions">
          <button class="btn btn-sm btn-secondary edit-city" data-id="${city.id}">Edit</button>
          <button class="btn btn-sm btn-primary delete-city" data-id="${city.id}">Delete</button>
        </td>
      `;
      tableBody.appendChild(row);
    });
    
    // Setup edit buttons
    const editButtons = document.querySelectorAll('.edit-city');
    editButtons.forEach(button => {
      button.addEventListener('click', () => {
        const cityId = parseInt(button.getAttribute('data-id'));
        editCity(cityId);
      });
    });
    
    // Setup delete buttons
    const deleteButtons = document.querySelectorAll('.delete-city');
    deleteButtons.forEach(button => {
      button.addEventListener('click', () => {
        const cityId = parseInt(button.getAttribute('data-id'));
        deleteCity(cityId);
      });
    });
  }
  
  // Handle city form
  function setupCityForm() {
    const addBtn = document.getElementById('addCityBtn');
    const cancelBtn = document.getElementById('cancelCityBtn');
    const form = document.getElementById('cityForm');
    const formContainer = document.getElementById('cityFormContainer');
    const formTitle = document.getElementById('cityFormTitle');
    const imageInput = document.getElementById('cityImage');
    const imagePreview = document.getElementById('cityImagePreview');
    
    // Show form when Add button is clicked
    if (addBtn) {
      addBtn.addEventListener('click', () => {
        formTitle.textContent = 'Add New City';
        form.reset();
        document.getElementById('cityId').value = '';
        imagePreview.innerHTML = '';
        formContainer.classList.remove('hidden');
      });
    }
    
    // Hide form when Cancel button is clicked
    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => {
        formContainer.classList.add('hidden');
      });
    }
    
    // Show image preview when URL is entered
    if (imageInput && imagePreview) {
      imageInput.addEventListener('input', () => {
        const imageUrl = imageInput.value.trim();
        if (imageUrl) {
          imagePreview.innerHTML = `<img src="${imageUrl}" alt="Preview">`;
        } else {
          imagePreview.innerHTML = '';
        }
      });
    }
    
    // Handle form submission
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const cityId = document.getElementById('cityId').value;
        const name = document.getElementById('cityName').value;
        const subtitle = document.getElementById('citySubtitle').value;
        const description = document.getElementById('cityDescription').value;
        const image = document.getElementById('cityImage').value;
        
        if (cityId) {
          // Update existing city
          const index = cities.findIndex(city => city.id === parseInt(cityId));
          if (index !== -1) {
            cities[index] = {
              ...cities[index],
              name,
              subtitle,
              description,
              image
            };
          }
        } else {
          // Add new city
          const newId = Math.max(...cities.map(city => city.id)) + 1;
          cities.push({
            id: newId,
            name,
            subtitle,
            description,
            image,
            attractions: 0
          });
        }
        
        // Update table and hide form
        populateCitiesTable();
        formContainer.classList.add('hidden');
      });
    }
  }
  
  // Edit city
  function editCity(cityId) {
    const city = cities.find(c => c.id === cityId);
    if (!city) return;
    
    // Fill form with city data
    document.getElementById('cityId').value = city.id;
    document.getElementById('cityName').value = city.name;
    document.getElementById('citySubtitle').value = city.subtitle;
    document.getElementById('cityDescription').value = city.description;
    document.getElementById('cityImage').value = city.image;
    document.getElementById('cityImagePreview').innerHTML = `<img src="${city.image}" alt="Preview">`;
    
    // Show form with updated title
    document.getElementById('cityFormTitle').textContent = `Edit City: ${city.name}`;
    document.getElementById('cityFormContainer').classList.remove('hidden');
  }
  
  // Delete city
  function deleteCity(cityId) {
    if (confirm('Are you sure you want to delete this city?')) {
      const index = cities.findIndex(city => city.id === cityId);
      if (index !== -1) {
        cities.splice(index, 1);
        populateCitiesTable();
      }
    }
  }
  
  // Populate attractions table
  function populateAttractionsTable() {
    const tableBody = document.getElementById('attractionsTableBody');
    if (!tableBody) return;
    
    // Clear existing content
    tableBody.innerHTML = '';
    
    // Add rows for each attraction
    experiences.forEach(exp => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td><img src="${exp.image}" alt="${exp.title}" class="admin-table-image"></td>
        <td>${exp.title}</td>
        <td>${exp.location}</td>
        <td>${exp.category}</td>
        <td class="admin-table-actions">
          <button class="btn btn-sm btn-secondary edit-attraction" data-id="${exp.id}">Edit</button>
          <button class="btn btn-sm btn-primary delete-attraction" data-id="${exp.id}">Delete</button>
        </td>
      `;
      tableBody.appendChild(row);
    });
    
    // Setup edit buttons
    const editButtons = document.querySelectorAll('.edit-attraction');
    editButtons.forEach(button => {
      button.addEventListener('click', () => {
        const attractionId = parseInt(button.getAttribute('data-id'));
        editAttraction(attractionId);
      });
    });
    
    // Setup delete buttons
    const deleteButtons = document.querySelectorAll('.delete-attraction');
    deleteButtons.forEach(button => {
      button.addEventListener('click', () => {
        const attractionId = parseInt(button.getAttribute('data-id'));
        deleteAttraction(attractionId);
      });
    });
  }
  
  // Setup attraction form
  function setupAttractionForm() {
    const addBtn = document.getElementById('addAttractionBtn');
    const cancelBtn = document.getElementById('cancelAttractionBtn');
    const form = document.getElementById('attractionForm');
    const formContainer = document.getElementById('attractionFormContainer');
    const formTitle = document.getElementById('attractionFormTitle');
    const citySelect = document.getElementById('attractionCity');
    const imageInput = document.getElementById('attractionImage');
    const imagePreview = document.getElementById('attractionImagePreview');
    
    // Populate city dropdown
    if (citySelect) {
      citySelect.innerHTML = '';
      cities.forEach(city => {
        const option = document.createElement('option');
        option.value = city.name;
        option.textContent = city.name;
        citySelect.appendChild(option);
      });
    }
    
    // Show form when Add button is clicked
    if (addBtn) {
      addBtn.addEventListener('click', () => {
        formTitle.textContent = 'Add New Attraction';
        form.reset();
        document.getElementById('attractionId').value = '';
        imagePreview.innerHTML = '';
        formContainer.classList.remove('hidden');
      });
    }
    
    // Hide form when Cancel button is clicked
    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => {
        formContainer.classList.add('hidden');
      });
    }
    
    // Show image preview when URL is entered
    if (imageInput && imagePreview) {
      imageInput.addEventListener('input', () => {
        const imageUrl = imageInput.value.trim();
        if (imageUrl) {
          imagePreview.innerHTML = `<img src="${imageUrl}" alt="Preview">`;
        } else {
          imagePreview.innerHTML = '';
        }
      });
    }
    
    // Handle form submission
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const attractionId = document.getElementById('attractionId').value;
        const title = document.getElementById('attractionName').value;
        const location = document.getElementById('attractionCity').value;
        const category = document.getElementById('attractionCategory').value;
        const description = document.getElementById('attractionDescription').value;
        const image = document.getElementById('attractionImage').value;
        
        if (attractionId) {
          // Update existing attraction
          const index = experiences.findIndex(exp => exp.id === parseInt(attractionId));
          if (index !== -1) {
            experiences[index] = {
              ...experiences[index],
              title,
              location,
              category,
              description,
              image
            };
          }
        } else {
          // Add new attraction
          const newId = Math.max(...experiences.map(exp => exp.id)) + 1;
          experiences.push({
            id: newId,
            title,
            location,
            category,
            description,
            image
          });
        }
        
        // Update table and hide form
        populateAttractionsTable();
        formContainer.classList.add('hidden');
      });
    }
  }
  
  // Edit attraction
  function editAttraction(attractionId) {
    const attraction = experiences.find(a => a.id === attractionId);
    if (!attraction) return;
    
    // Fill form with attraction data
    document.getElementById('attractionId').value = attraction.id;
    document.getElementById('attractionName').value = attraction.title;
    document.getElementById('attractionCity').value = attraction.location;
    document.getElementById('attractionCategory').value = attraction.category;
    document.getElementById('attractionDescription').value = attraction.description;
    document.getElementById('attractionImage').value = attraction.image;
    document.getElementById('attractionImagePreview').innerHTML = `<img src="${attraction.image}" alt="Preview">`;
    
    // Show form with updated title
    document.getElementById('attractionFormTitle').textContent = `Edit Attraction: ${attraction.title}`;
    document.getElementById('attractionFormContainer').classList.remove('hidden');
  }
  
  // Delete attraction
  function deleteAttraction(attractionId) {
    if (confirm('Are you sure you want to delete this attraction?')) {
      const index = experiences.findIndex(exp => exp.id === attractionId);
      if (index !== -1) {
        experiences.splice(index, 1);
        populateAttractionsTable();
      }
    }
  }
  
  // Populate reviews table
  function populateReviewsTable() {
    const tableBody = document.getElementById('reviewsTableBody');
    if (!tableBody) return;
    
    // Clear existing content
    tableBody.innerHTML = '';
    
    // Add rows for each review
    reviews.forEach(review => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${review.name}</td>
        <td>${createStarRating(review.rating)}</td>
        <td>${truncateText(review.content, 80)}</td>
        <td>${review.date}</td>
        <td class="admin-table-actions">
          <button class="btn btn-sm btn-secondary approve-review" data-id="${review.id}">Approve</button>
          <button class="btn btn-sm btn-primary delete-review" data-id="${review.id}">Delete</button>
        </td>
      `;
      tableBody.appendChild(row);
    });
    
    // Setup delete buttons
    const deleteButtons = document.querySelectorAll('.delete-review');
    deleteButtons.forEach(button => {
      button.addEventListener('click', () => {
        const reviewId = parseInt(button.getAttribute('data-id'));
        deleteReview(reviewId);
      });
    });
  }
  
  // Delete review
  function deleteReview(reviewId) {
    if (confirm('Are you sure you want to delete this review?')) {
      const index = reviews.findIndex(review => review.id === reviewId);
      if (index !== -1) {
        reviews.splice(index, 1);
        populateReviewsTable();
      }
    }
  }
  
  // Settings form handling
  function setupSettingsForm() {
    const form = document.getElementById('settingsForm');
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const siteName = document.getElementById('siteName').value;
        const siteDescription = document.getElementById('siteDescription').value;
        const adminEmail = document.getElementById('adminEmail').value;
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        // Validate passwords
        if (newPassword) {
          if (currentPassword !== 'admin123') {
            alert('Current password is incorrect');
            return;
          }
          
          if (newPassword !== confirmPassword) {
            alert('New passwords do not match');
            return;
          }
        }
        
        // Save settings (in a real app, this would update a database)
        alert('Settings saved successfully');
      });
    }
  }
  
  // Initialize the admin panel
  function init() {
    if (!checkAuth()) return;
    
    setupLogout();
    setupNavigation();
    populateCitiesTable();
    setupCityForm();
    populateAttractionsTable();
    setupAttractionForm();
    populateReviewsTable();
    setupSettingsForm();
  }
  
  // Call initialization
  init();
});