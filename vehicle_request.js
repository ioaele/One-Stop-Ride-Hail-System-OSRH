function showSignupMessage(message, type) {
  const alertBox = document.getElementById('alert');
  const alertText = document.getElementById('alert-text');

  if (alertBox && alertText) {
    alertText.textContent = message;
    alertBox.style.display = 'block';
    alertBox.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
    alertBox.style.color = type === 'success' ? '#155724' : '#721c24';
    alertBox.style.border = type === 'success' ? '1px solid #c3e6cb' : '1px solid #f5c6cb';
<<<<<<< HEAD
    
    // Scroll to alert
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
=======
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
  } else {
    alert(message);
  }
}

<<<<<<< HEAD
// Handle specific error types with custom actions
function handleErrorType(errorType, message) {
  switch (errorType) {
    case 'duplicate_service':
      // Driver already has vehicle for this service type
      showSignupMessage(message, 'error');
      // Optionally highlight the service type dropdown
      const serviceTypeSelect = document.getElementById('service_type');
      if (serviceTypeSelect) {
        serviceTypeSelect.style.border = '2px solid red';
        serviceTypeSelect.focus();
      }
      break;
      
    case 'duplicate_plate':
      // License plate already exists
      showSignupMessage(message, 'error');
      const licensePlateInput = document.getElementById('license_plate');
      if (licensePlateInput) {
        licensePlateInput.style.border = '2px solid red';
        licensePlateInput.focus();
      }
      break;
      
    case 'criteria_not_met':
      // Vehicle doesn't meet service criteria
      showSignupMessage(message, 'error');
      break;
      
    default:
      showSignupMessage(message, 'error');
  }
}

// Clear error styling when user changes input
function clearErrorStyling() {
  const serviceTypeSelect = document.getElementById('service_type');
  const licensePlateInput = document.getElementById('license_plate');
  
  if (serviceTypeSelect) {
    serviceTypeSelect.addEventListener('change', function() {
      this.style.border = '';
    });
  }
  
  if (licensePlateInput) {
    licensePlateInput.addEventListener('input', function() {
      this.style.border = '';
    });
  }
}

=======
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
function submitDriverVehicleRequest() {
  const submitBtn = document.getElementById('submitBtn');
  
  submitBtn.disabled = true;
  submitBtn.textContent = 'Checking...';

  const serviceType = document.getElementById('service_type');
  const vehicleType = document.getElementById('vehicle_type');
  const licensePlate = document.getElementById('license_plate');
  const seats = document.getElementById('seats');
  const luggageVolume = document.getElementById('luggage_volume');
  const luggageWeight = document.getElementById('luggage_weight');
  const photoInterior = document.getElementById('photo_interior');
  const photoExterior = document.getElementById('photo_exterior');

  if (!serviceType || !vehicleType || !licensePlate || !seats || !luggageVolume || !luggageWeight) {
    showSignupMessage('Some form fields are missing. Please refresh the page.', 'error');
    submitBtn.disabled = false;
    submitBtn.textContent = 'Continue';
    return;
  }

  if (!serviceType.value || !vehicleType.value || !licensePlate.value || !seats.value || !luggageVolume.value || !luggageWeight.value) {
    showSignupMessage('Please fill in all required fields.', 'error');
    submitBtn.disabled = false;
    submitBtn.textContent = 'Continue';
    return;
  }

  if (!photoInterior || !photoInterior.files || !photoInterior.files[0]) {
    showSignupMessage('Please select an interior photo.', 'error');
    submitBtn.disabled = false;
    submitBtn.textContent = 'Continue';
    return;
  }

  const interiorFile = photoInterior.files[0];
  const interiorSizeKB = Math.round(interiorFile.size / 1024);

  if (interiorFile.size > 500 * 1024) {
    showSignupMessage(
      `Interior photo is too large: ${interiorSizeKB}KB. Maximum allowed is 500KB. Please compress your image.`,
      'error'
    );
    submitBtn.disabled = false;
    submitBtn.textContent = 'Continue';
    return;
  }

  if (!photoExterior || !photoExterior.files || !photoExterior.files[0]) {
    showSignupMessage('Please select an exterior photo.', 'error');
    submitBtn.disabled = false;
    submitBtn.textContent = 'Continue';
    return;
  }

  const exteriorFile = photoExterior.files[0];
  const exteriorSizeKB = Math.round(exteriorFile.size / 1024);

  if (exteriorFile.size > 500 * 1024) {
    showSignupMessage(
      `Exterior photo is too large: ${exteriorSizeKB}KB. Maximum allowed is 500KB. Please compress your image.`,
      'error'
    );
    submitBtn.disabled = false;
    submitBtn.textContent = 'Continue';
    return;
  }

  const totalSize = interiorFile.size + exteriorFile.size;
  const totalSizeMB = (totalSize / 1024 / 1024).toFixed(2);

  if (totalSize > 5 * 1024 * 1024) {
    showSignupMessage(
      `Total file size is ${totalSizeMB}MB. Maximum allowed is 5MB. Please use smaller images.`,
      'error'
    );
    submitBtn.disabled = false;
    submitBtn.textContent = 'Continue';
    return;
  }

  submitBtn.textContent = 'Uploading...';

  const formData = new FormData();
  
  const usersId = localStorage.getItem('users_id');
<<<<<<< HEAD
  
=======
  if (usersId) {
    formData.append('users_id', usersId);
  }
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6

  formData.append('service_type', serviceType.value);
  formData.append('vehicle_type', vehicleType.value);
  formData.append('license_plate', licensePlate.value);
  formData.append('seats', seats.value);
  formData.append('luggage_volume', luggageVolume.value);
  formData.append('luggage_weight', luggageWeight.value);
  formData.append('photo_interior', interiorFile);
  formData.append('photo_exterior', exteriorFile);

  fetch('vehicle_request.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.text())
  .then(text => {
    console.log('RAW RESPONSE:', text);
    
<<<<<<< HEAD
  
=======
    // Check for PHP errors first
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
    if (text.includes('<b>') || text.includes('Fatal error') || text.includes('Warning')) {
      const errorMatch = text.match(/<b>(.+?)<\/b>/g);
      if (errorMatch) {
        const errorMsg = errorMatch.map(m => m.replace(/<\/?b>/g, '')).join(' ');
        showSignupMessage('Server error: ' + errorMsg, 'error');
      } else {
        showSignupMessage('Server error. Please try again.', 'error');
      }
      submitBtn.disabled = false;
      submitBtn.textContent = 'Continue';
      return;
    }
    
<<<<<<< HEAD
 
=======
    // Parse JSON response
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      showSignupMessage('Server returned invalid response.', 'error');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Continue';
      return;
    }

    console.log('PARSED JSON:', data);

    if (data.success) {
<<<<<<< HEAD
    
=======
      // ✅ SAVE vehicle_id FROM RESPONSE
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
      const vehicleIdFromResponse = data.vehicle_id || data.new_vehicle_id;

      if (vehicleIdFromResponse) {
        localStorage.setItem('vehicle_id', vehicleIdFromResponse.toString());
        sessionStorage.setItem('vehicle_id', vehicleIdFromResponse.toString());
        console.log('Saved vehicle_id:', vehicleIdFromResponse);
      } else {
        console.warn('No vehicle_id found in response JSON');
      }

      showSignupMessage(data.message || 'Vehicle registered successfully!', 'success');
      setTimeout(() => {
        window.location.href = 'vehicle_doc_request.html';
      }, 1000);
    } else {
<<<<<<< HEAD
      
      const errorType = data.error_type || 'general';
      const errorMessage = data.message || 'Submission failed';
      
      handleErrorType(errorType, errorMessage);
      
=======
      let msg = data.message || 'Submission failed';
      showSignupMessage(msg, 'error');
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
      submitBtn.disabled = false;
      submitBtn.textContent = 'Continue';
    }
  })
  .catch(error => {
    console.error('Fetch error:', error);
    showSignupMessage('Network error. Please check your connection and try again.', 'error');
    submitBtn.disabled = false;
    submitBtn.textContent = 'Continue';
  });
}

document.addEventListener('DOMContentLoaded', function() {
  const submitBtn = document.getElementById('submitBtn');
  
  if (submitBtn) {
    submitBtn.addEventListener('click', function(e) {
<<<<<<< HEAD
=======
      // 🔴 IMPORTANT: prevent normal form submit (page reload)
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
      if (e && e.preventDefault) e.preventDefault();
      submitDriverVehicleRequest();
    });
  }
<<<<<<< HEAD
  
  // Setup error clearing listeners
  clearErrorStyling();
});
=======
});
>>>>>>> ff1ede1e5f11d363663d8ef9fa49e1d7869c40b6
