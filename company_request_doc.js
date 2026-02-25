document.addEventListener('DOMContentLoaded', () => {
  const storedUsername = localStorage.getItem('username') || 'User';

  const navbarUsernameEl = document.getElementById('navbar-username');
  const heroUsernameEl   = document.getElementById('hero-username');

  if (navbarUsernameEl) navbarUsernameEl.textContent = storedUsername;
  if (heroUsernameEl) heroUsernameEl.textContent = storedUsername;

  const vehicleId = localStorage.getItem('vehicle_id') || sessionStorage.getItem('vehicle_id');
  if (vehicleId) {
      console.log('Vehicle ID found in storage:', vehicleId);
  } else {
      console.warn('Vehicle ID not found in storage');
  }
});

document.addEventListener('DOMContentLoaded', function() {

  function showSignupMessage(message, type) {
      const alertBox = document.getElementById('alert');
      const alertText = document.getElementById('alert-text');
  
      if (alertBox && alertText) {
          alertText.textContent = message;
          alertBox.style.display = 'block';
          alertBox.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
          alertBox.style.color = type === 'success' ? '#155724' : '#721c24';
          alertBox.style.border = type === 'success' ? '1px solid #c3e6cb' : '1px solid #f5c6cb';
      } else {
          alert(message);
      }
  }

  function validateFileSize(fileInput, maxSizeMB, fileName) {
      if (!fileInput || !fileInput.files || !fileInput.files[0]) {
          return { valid: false, message: fileName + ': No file selected' };
      }
    
      const file = fileInput.files[0];
      const maxSizeBytes = maxSizeMB * 1024 * 1024;
      const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
    
      if (file.size > maxSizeBytes) {
          return {
              valid: false,
              message: fileName + ' is ' + fileSizeMB + 'MB. Maximum allowed is ' + maxSizeMB + 'MB.'
          };
      }
    
      return { valid: true };
  }

  function validateDates(issueDate, expirationDate, docName, checkOneMonth) {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
    
      if (!issueDate) {
          return { valid: false, message: docName + ': Issue date is required' };
      }
    
      const issue = new Date(issueDate);
      issue.setHours(0, 0, 0, 0);
    
      if (issue > today) {
          return {
              valid: false,
              message: docName + ': Invalid issue date.'
          };
      }
    
      if (checkOneMonth) {
          const oneMonthAgo = new Date(today);
          oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);
      
          if (issue < oneMonthAgo) {
              return {
                  valid: false,
                  message: docName + ': Issue date must not be older than 1 month.'
              };
          }
      }
    
      if (expirationDate) {
          const exp = new Date(expirationDate);
          exp.setHours(0, 0, 0, 0);
      
          if (exp <= today) {
              return {
                  valid: false,
                  message: docName + ': Invalid expiration date.'
              };
          }
      
          if (exp <= issue) {
              return {
                  valid: false,
                  message: docName + ': Invalid expiration date.'
              };
          }
      }
    
      return { valid: true };
  }

  function submitDriverVehicleRequest() {
      const submitBtn = document.getElementById('submitBtn');
      const form = document.getElementById('driverVehicleForm');  
  
      if (!submitBtn || !form) {
          console.error('submitBtn or form not found');
          return;
      }

      const vehicleId = localStorage.getItem('vehicle_id') || sessionStorage.getItem('vehicle_id');
    
      if (!vehicleId || vehicleId.trim() === '') {
          showSignupMessage('Vehicle ID is missing. Please complete vehicle registration first.', 'error');
          return;
      }
    
      console.log('Vehicle ID retrieved for submission:', vehicleId);
  
      submitBtn.disabled = true;
      submitBtn.textContent = 'Checking...';
  
      try {
          const docMappings = [
              { 
                  name: 'Vehicle Registration',
                  codeId: 'veh_reg_code',
                  dateId: 'veh_reg_publish',
                  expId: 'veh_reg_exp',
                  fileId: 'veh_reg_file',
                  checkOneMonth: false,
                  required: true
              },
              { 
                  name: 'MOT Certificate',
                  codeId: 'veh_mot_code',
                  dateId: 'mot_publish',
                  expId: 'mot_exp',
                  fileId: 'mot_file',
                  checkOneMonth: false,
                  required: true
              },
              { 
                  name: 'Vehicle Classification Certificate',
                  codeId: 'veh_cl_code',
                  dateId: 'veh_publish',
                  expId: 'v_doc_exp_date',
                  fileId: 'image_pdf',
                  checkOneMonth: false,
                  required: true
              }
          ];
  
          for (const doc of docMappings) {
              const code = document.getElementById(doc.codeId)?.value.trim();
              const issueDate = document.getElementById(doc.dateId)?.value;
              const expirationDate = doc.expId ? document.getElementById(doc.expId)?.value : null;
              const fileInput = document.getElementById(doc.fileId);
              const hasFile = fileInput?.files && fileInput.files.length > 0;
        
              const hasAnyData = code || issueDate || expirationDate || hasFile;
  
              if (!doc.required && !hasAnyData) {
                  continue;
              }
  
              if (doc.required || hasAnyData) {
                  if (!code) {
                      showSignupMessage('Please fill in document code for ' + doc.name + '.', 'error');
                      submitBtn.disabled = false;
                      submitBtn.textContent = 'Submit Company Vehicle Request';
                      return;
                  }
  
                  const fileValidation = validateFileSize(fileInput, 5, doc.name);
                  if (!fileValidation.valid) {
                      showSignupMessage(fileValidation.message, 'error');
                      submitBtn.disabled = false;
                      submitBtn.textContent = 'Submit Company Vehicle Request';
                      return;
                  }
  
                  const dateValidation = validateDates(issueDate, expirationDate, doc.name, doc.checkOneMonth);
                  if (!dateValidation.valid) {
                      showSignupMessage(dateValidation.message, 'error');
                      submitBtn.disabled = false;
                      submitBtn.textContent = 'Submit Company Vehicle Request';
                      return;
                  }
              }
          }
  
          console.log('Creating FormData...');
          const formData = new FormData(form); 
      
          formData.append('vehicle_id', vehicleId);
          console.log('Vehicle ID added to FormData:', vehicleId);
  
          console.log('FormData contents:');
          for (let pair of formData.entries()) {
              console.log(pair[0] + ': ' + pair[1]);
          }
  
          submitBtn.textContent = 'Uploading...';

          fetch('company_request_doc.php', {
              method: 'POST',
              body: formData
          })
          .then(function(response) {
              console.log('Response received:', response.status);
              if (!response.ok) {
                  return response.text().then(function(text) {
                      console.error('Error response:', text);
                      throw new Error('HTTP error! status: ' + response.status);
                  });
              }
              return response.text();
          })
          .then(function(text) {
              console.log('Server response:', text);
          
              if (text.includes('<b>') || text.includes('Fatal error') || text.includes('Warning') || text.includes('Parse error')) {
                  console.error('PHP Error:', text);
                  showSignupMessage('Server error. Please check console for details.', 'error');
                  submitBtn.disabled = false;
                  submitBtn.textContent = 'Submit Company Vehicle Request';
                  return;
              }
  
              let data;
              try {
                  data = JSON.parse(text);
              } catch (e) {
                  console.error('Invalid JSON:', text);
                  showSignupMessage('Server returned invalid response.', 'error');
                  submitBtn.disabled = false;
                  submitBtn.textContent = 'Submit Company Vehicle Request';
                  return;
              }
  
              if (data.status === 'success') {
                  showSignupMessage(data.message || 'Vehicle documents submitted successfully!', 'success');
                  setTimeout(function() {
                      window.location.href = 'homepage_pas.html';
                  }, 1500);
              } else {
                  showSignupMessage(data.message || 'Submission failed.', 'error');
                  if (data.debug) {
                      console.log('Debug info:', data.debug);
                  }
                  submitBtn.disabled = false;
                  submitBtn.textContent = 'Submit Company Vehicle Request';
              }
          })
          .catch(function(error) {
              console.error('Fetch error details:', error);
              showSignupMessage('Upload error: ' + error.message, 'error');
              submitBtn.disabled = false;
              submitBtn.textContent = 'Submit Company Vehicle Request';
          });
  
      } catch (error) {
          console.error('Validation error:', error);
          showSignupMessage('Form validation error: ' + error.message, 'error');
          submitBtn.disabled = false;
          submitBtn.textContent = 'Submit Company Vehicle Request';
      }
  }

  // Setup event listener
  const submitBtn = document.getElementById('submitBtn');
  if (submitBtn) {
      submitBtn.addEventListener('click', function(e) {
          e.preventDefault();
          console.log('Submit button clicked');
          submitDriverVehicleRequest(); 
      });
  } else {
      console.error('Submit button not found!');
  }

});