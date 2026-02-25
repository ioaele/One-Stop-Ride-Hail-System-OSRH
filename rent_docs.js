document.addEventListener('DOMContentLoaded', () => {
    const storedUsername = localStorage.getItem('username') || 'User';
  
    const navbarUsernameEl = document.getElementById('navbar-username');
    const heroUsernameEl   = document.getElementById('hero-username');
  
    if (navbarUsernameEl) navbarUsernameEl.textContent = storedUsername;
    if (heroUsernameEl) heroUsernameEl.textContent = storedUsername;
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
    
    // Validate file size
    function validateFileSize(fileInput, maxSizeMB, fileName) {
      if (!fileInput || !fileInput.files || !fileInput.files[0]) {
        return { valid: false, message: `${fileName}: No file selected` };
      }
      
      const file = fileInput.files[0];
      const maxSizeBytes = maxSizeMB * 1024 * 1024;
      const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
      
      if (file.size > maxSizeBytes) {
        return {
          valid: false,
          message: `${fileName} is ${fileSizeMB}MB. Maximum allowed is ${maxSizeMB}MB.`
        };
      }
      
      return { valid: true };
    }
    
    // Validate dates
    function validateDates(issueDate, expirationDate, docName, checkOneMonth = false) {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      
      if (!issueDate) {
        return { valid: false, message: `${docName}: Issue date is required` };
      }
      
      const issue = new Date(issueDate);
      issue.setHours(0, 0, 0, 0);
      
      if (issue > today) {
        return {
          valid: false,
          message: `${docName}: Invalid issue date.`
        };
      }
      
      if (checkOneMonth) {
        const oneMonthAgo = new Date(today);
        oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);
        
        if (issue < oneMonthAgo) {
          return {
            valid: false,
            message: `${docName}: Issue date must not be older than 1 month.`
          };
        }
      }
      
      if (expirationDate) {
        const exp = new Date(expirationDate);
        exp.setHours(0, 0, 0, 0);
        
        if (exp <= today) {
          return {
            valid: false,
            message: `${docName}: Invalid expiration date.`
          };
        }
        
        if (exp <= issue) {
          return {
            valid: false,
            message: `${docName}: Invalid expiration date.`
          };
        }
      }
      
      return { valid: true };
    }
  
    function submitDriverDocsRequest() {
      const submitBtn = document.getElementById('submitBtn');
      const form = document.getElementById('driverForm');
    
      if (!submitBtn || !form) {
        console.error('submitBtn or form not found');
        return;
      }
    
      submitBtn.disabled = true;
      submitBtn.textContent = 'Checking...';
    
      try {
        
      
    
        const docMappings = [
          { 
            name: 'ID/Passport',
            codeId: 'id_doc_code',
            dateId: 'id_doc_publish',
            expId: 'id_doc_exp',
            fileId: 'id_doc_file',
            checkOneMonth: false,
            required: true
          },
          { 
            name: 'Driving License',
            codeId: 'dl_code',
            dateId: 'dl_publish',
            expId: 'dl_exp',
            fileId: 'dl_file',
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
              showSignupMessage(`Please fill in document code for ${doc.name}.`, 'error');
              submitBtn.disabled = false;
              submitBtn.textContent = 'Continue';
              return;
            }
    
            const fileValidation = validateFileSize(fileInput, 2, doc.name);
            if (!fileValidation.valid) {
              showSignupMessage(fileValidation.message, 'error');
              submitBtn.disabled = false;
              submitBtn.textContent = 'Continue';
              return;
            }
    
            const dateValidation = validateDates(issueDate, expirationDate, doc.name, doc.checkOneMonth);
            if (!dateValidation.valid) {
              showSignupMessage(dateValidation.message, 'error');
              submitBtn.disabled = false;
              submitBtn.textContent = 'Continue';
              return;
            }
          }
        }
    
        console.log('Creating FormData...');
        const formData = new FormData(form); 
    
        console.log('FormData contents:');
        for (let pair of formData.entries()) {
          console.log(pair[0] + ': ' + pair[1]);
        }
    
        fetch('rent_docs.php', {
          method: 'POST',
          body: formData
        })
          .then(response => {
           
            console.log('Response received:', response.status);
            if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
          })
          .then(text => {
            
            console.log('Server response:', text);
            
            if (text.includes('<b>') || text.includes('Fatal error') || text.includes('Warning')) {
              console.error('PHP Error:', text);
              showSignupMessage('Server error. Please check console for details.', 'error');
              submitBtn.disabled = false;
              submitBtn.textContent = 'Continue';
              return;
            }
    
            let data;
            try {
              data = JSON.parse(text);
            } catch (e) {
              console.error('Invalid JSON:', text);
              showSignupMessage('Server returned invalid response.', 'error');
              submitBtn.disabled = false;
              submitBtn.textContent = 'Continue';
              return;
            }
    
            if (data.status === 'success') {
              showSignupMessage(data.message || 'Driver documents submitted successfully!', 'success');
              setTimeout(() => {
                window.location.href = 'homepage_pas.html';
              }, 1500);
            } else {
              showSignupMessage(data.message || 'Submission failed.', 'error');
              if (data.debug) {
                console.log('Debug info:', data.debug);
              }
            }
    
            submitBtn.disabled = false;
            submitBtn.textContent = 'Continue';
          })
          .catch(error => {
            console.error('Fetch error details:', error);
            showSignupMessage('Upload error: ' + error.message, 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Continue';
          });
    
      } catch (error) {
        console.error('Validation error:', error);
        showSignupMessage('Form validation error: ' + error.message, 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Continue';
      }
    }
  
    // Setup event listener
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
      submitBtn.addEventListener('click', function (e) {
        e.preventDefault();
        console.log('Submit button clicked');
        submitDriverDocsRequest();
      });
    } else {
      console.error('Submit button not found!');
    }
    
  });