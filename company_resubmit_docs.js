document.addEventListener('DOMContentLoaded', () => {
  const storedUsername = localStorage.getItem('username') || 'User';
  const navbarUsernameEl = document.getElementById('navbar-username');
  if (navbarUsernameEl) navbarUsernameEl.textContent = storedUsername;

  loadDocumentsForResubmit();
});

// Document type ID to field mapping
const docTypeMapping = {
  10: 'Vehicle Registration',
  11: 'MOT Certificate',
  12: 'Vehicle Classification Certificate'
};

function loadDocumentsForResubmit() {
  const loadingMsg = document.getElementById('loading-message');
  const noDocsMsg = document.getElementById('no-docs-message');
  const form = document.getElementById('resubmitForm');

  console.log('Starting to load documents...');

  fetch('getDocsForResubmit.php', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/json'
      },
      body: JSON.stringify({})
  })
  .then(response => {
      console.log('Response status:', response.status);
      
      if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      return response.text();
  })
  .then(text => {
      console.log('Raw response:', text);
      
      let data;
      try {
          data = JSON.parse(text);
      } catch (e) {
          console.error('Failed to parse JSON:', e);
          throw new Error('Invalid JSON response from server');
      }
      
      return data;
  })
  .then(data => {
      console.log('Parsed data:', data);
      loadingMsg.style.display = 'none';

      if (data.status === 'no_issues' || data.code === 'NO_ISSUES') {
          console.log('No issues found');
          noDocsMsg.textContent = data.message || 'All documents are approved or pending!';
          noDocsMsg.style.display = 'block';
          return;
      }

      if (data.status === 'error') {
          console.error('Error from server:', data.message);
          showMessage(data.message || 'Error loading documents', 'error');
          return;
      }

      if ((data.status === 'success' || data.code === 'SUCCESS') && 
          data.documents && data.documents.length > 0) {
          
          console.log('Documents to resubmit:', data.documents);
          
          const vehicleId = data.documents[0].vehicle_id || data.vehicle_id;
          console.log('Vehicle ID:', vehicleId);
          
          if (!vehicleId) {
              showMessage('Vehicle ID not found in response', 'error');
              return;
          }
          
          document.getElementById('vehicle_id').value = vehicleId;
          buildResubmitForm(data.documents);
          form.style.display = 'block';
      } else {
          console.log('No documents found');
          noDocsMsg.style.display = 'block';
      }
  })
  .catch(err => {
      console.error('Fetch error:', err);
      loadingMsg.style.display = 'none';
      showMessage('Failed to load documents: ' + err.message, 'error');
  });
}

function buildResubmitForm(documents) {
  const container = document.getElementById('docs-container');
  container.innerHTML = '';

  console.log('Building form for', documents.length, 'documents');

  documents.forEach((doc, index) => {
      const docType = parseInt(doc.v_doc_type_id);
      const docTypeName = docTypeMapping[docType] || 'Unknown Document';

      const docBlock = document.createElement('div');
      docBlock.className = 'doc-block';
      docBlock.style.border = '2px solid #dc3545';
      docBlock.style.backgroundColor = '#fff5f5';
      docBlock.style.padding = '20px';
      docBlock.style.marginBottom = '20px';
      docBlock.style.borderRadius = '8px';
      
      docBlock.innerHTML = `
          <h3 style="color: #dc3545;">🔴 ${docTypeName}</h3>
          <p style="color: #666; margin-bottom: 15px;">
              <em>Status: Needs Correction</em>
          </p>
          
          <input type="hidden" name="vehicle_docs[${index}][v_doc_type_id]" value="${docType}">
          
          <div class="form-row">
              <label for="doc_code_${index}">Document Code *</label>
              <input 
                  type="text" 
                  id="doc_code_${index}" 
                  name="vehicle_docs[${index}][doc_code]" 
                  value="${doc.doc_code || ''}" 
                  required>
          </div>

          <div class="form-row">
              <label for="publish_date_${index}">Issue Date *</label>
              <input 
                  type="date" 
                  id="publish_date_${index}" 
                  name="vehicle_docs[${index}][v_doc_publish_date]" 
                  value="${doc.v_doc_publish_date || ''}" 
                  max="${new Date().toISOString().split('T')[0]}"
                  required>
              <small style="color: #666; display: block; margin-top: 5px;">
                  Issue date cannot be in the future
              </small>
          </div>

          <div class="form-row">
              <label for="exp_date_${index}">Expiration Date</label>
              <input 
                  type="date" 
                  id="exp_date_${index}" 
                  name="vehicle_docs[${index}][v_doc_exp_date]" 
                  value="${doc.v_doc_exp_date || ''}"
                  min="${new Date().toISOString().split('T')[0]}">
              <small style="color: #666; display: block; margin-top: 5px;">
                  Expiration date must be in the future (leave empty if document doesn't expire)
              </small>
          </div>

          <div class="form-row">
              <label for="file_${index}">
                  Upload New File (image/pdf) 
                  <span style="color: red;">*</span>
              </label>
              <input 
                  type="file" 
                  id="file_${index}" 
                  name="vehicle_docs_${index}_file" 
                  accept=".pdf,.jpg,.jpeg,.png,.gif" 
                  required>
              <small style="color: red; display: block; margin-top: 5px;">
                  You must upload a new file. Maximum 5MB.
              </small>
          </div>
      `;

      container.appendChild(docBlock);
  });

  const submitBtn = document.getElementById('submitBtn');
  const newSubmitBtn = submitBtn.cloneNode(true);
  submitBtn.parentNode.replaceChild(newSubmitBtn, submitBtn);
  newSubmitBtn.addEventListener('click', handleResubmit);

  console.log('Form built successfully');
}

function handleResubmit(e) {
  e.preventDefault();
  
  console.log('Submit button clicked');
  
  const submitBtn = document.getElementById('submitBtn');
  const form = document.getElementById('resubmitForm');

  // Validate dates
  if (!validateDates(form)) {
      return;
  }

  // Validate all required files are selected
  const fileInputs = form.querySelectorAll('input[type="file"][required]');
  let allFilesSelected = true;
  
  fileInputs.forEach(input => {
      if (!input.files || input.files.length === 0) {
          allFilesSelected = false;
          input.style.border = '2px solid red';
      } else {
          input.style.border = '';
      }
  });

  if (!allFilesSelected) {
      showMessage('Please select files for all required documents', 'error');
      return;
  }

  submitBtn.disabled = true;
  submitBtn.textContent = 'Uploading...';

  const formData = new FormData(form);

  console.log('FormData contents:');
  for (let pair of formData.entries()) {
      console.log(pair[0] + ':', pair[1]);
  }

  fetch('company_resubmit_doc.php', {
      method: 'POST',
      body: formData
  })
  .then(response => {
      console.log('Response status:', response.status);
      
      if (!response.ok) {
          return response.text().then(text => {
              console.error('Error response:', text);
              throw new Error(`HTTP error! status: ${response.status}`);
          });
      }
      return response.text();
  })
  .then(text => {
      console.log('Server response:', text);
      
      if (text.includes('<b>') || text.includes('Fatal error') || 
          text.includes('Warning') || text.includes('Parse error')) {
          console.error('PHP Error:', text);
          showMessage('Server error. Please check console for details.', 'error');
          submitBtn.disabled = false;
          submitBtn.textContent = 'Resubmit Documents';
          return;
      }

      let data;
      try {
          data = JSON.parse(text);
      } catch (e) {
          console.error('Invalid JSON:', text);
          showMessage('Server returned invalid response.', 'error');
          submitBtn.disabled = false;
          submitBtn.textContent = 'Resubmit Documents';
          return;
      }

      if (data.status === 'success') {
          showMessage(data.message || 'Documents resubmitted successfully!', 'success');
          setTimeout(() => {
              window.location.href = 'homepage_pas.html';
          }, 1500);
      } else {
          showMessage(data.message || 'Resubmission failed.', 'error');
          if (data.debug) {
              console.log('Debug info:', data.debug);
          }
          submitBtn.disabled = false;
          submitBtn.textContent = 'Resubmit Documents';
      }
  })
  .catch(error => {
      console.error('Fetch error:', error);
      showMessage('Upload error: ' + error.message, 'error');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Resubmit Documents';
  });
}

function validateDates(form) {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  let isValid = true;
  let errorMessages = [];

  // Get all publish and expiration date inputs
  const publishInputs = form.querySelectorAll('input[name*="v_doc_publish_date"]');
  const expInputs = form.querySelectorAll('input[name*="v_doc_exp_date"]');

  publishInputs.forEach((publishInput, index) => {
      const publishDate = new Date(publishInput.value);
      publishDate.setHours(0, 0, 0, 0);

      // Check if publish date is in the future
      if (publishDate > today) {
          isValid = false;
          publishInput.style.border = '2px solid red';
          errorMessages.push(`Issue date for document ${index + 1} cannot be in the future`);
      } else {
          publishInput.style.border = '';
      }

      // Check expiration date if it exists
      const expInput = expInputs[index];
      if (expInput && expInput.value) {
          const expDate = new Date(expInput.value);
          expDate.setHours(0, 0, 0, 0);

          // Expiration date must be after issue date
          if (expDate <= publishDate) {
              isValid = false;
              expInput.style.border = '2px solid red';
              errorMessages.push(`Expiration date for document ${index + 1} must be after issue date`);
          } else {
              expInput.style.border = '';
          }
      }
  });

  if (!isValid) {
      showMessage(errorMessages.join('. '), 'error');
  }

  return isValid;
}

function showMessage(message, type) {
  const alertBox = document.getElementById('alert');
  const alertText = document.getElementById('alert-text');

  if (alertBox && alertText) {
      alertText.textContent = message;
      alertBox.style.display = 'block';
      alertBox.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
      alertBox.style.color = type === 'success' ? '#155724' : '#721c24';
      alertBox.style.border = type === 'success' ? '1px solid #c3e6cb' : '1px solid #f5c6cb';
      
      alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      
      setTimeout(() => {
          alertBox.style.display = 'none';
      }, 5000);
  } else {
      alert(message);
  }
}