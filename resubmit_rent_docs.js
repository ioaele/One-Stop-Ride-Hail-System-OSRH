document.addEventListener('DOMContentLoaded', () => {
    const storedUsername = localStorage.getItem('username') || 'User';
    const navbarUsernameEl = document.getElementById('navbar-username');
    if (navbarUsernameEl) navbarUsernameEl.textContent = storedUsername;
  
    loadDocumentsForResubmit();
});

// Document type mappings
const driverDocTypeMapping = {
    7: 'ID / Passport',
    9: 'Driving License',
};

function loadDocumentsForResubmit() {
    const loadingMsg = document.getElementById('loading-message');
    const noDocsMsg = document.getElementById('no-docs-message');
    const form = document.getElementById('resubmitForm');
    const driverDocsSection = document.getElementById('driver-docs-section');

    console.log('Loading documents...');

    fetch('checkRentDocs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        console.log('Document Status Response:', data);
        
        // Hide loading message
        loadingMsg.style.display = 'none';
    
        switch (data.status) {
            case 'no_docs':
                window.location.href = 'rent.html';
                break;
    
            case 'has_issues':
                // Show the form
                form.style.display = 'block';
                
                // Check for driver docs
                if (data.driverDocs && data.driverDocs.length > 0) {
                    driverDocsSection.style.display = 'block';
                    buildDriverDocsForm(data.driverDocs);
                }
                break;
    
            case 'pending':
                noDocsMsg.style.display = 'block';
                noDocsMsg.innerHTML = '<p style="color: orange; font-weight: bold;">' + 
                    (data.message || 'All documents are pending approval.') + 
                    '</p><button onclick="window.location.href=\'homepage_pas.html\'">Return to Homepage</button>';
                break;
    
            case 'approved':
                noDocsMsg.style.display = 'block';
                noDocsMsg.innerHTML = '<p style="color: green; font-weight: bold;">' + 
                    (data.message || 'All documents have been approved!') + 
                    '</p><button onclick="window.location.href=\'homepage_pas.html\'">Return to Homepage</button>';
                break;
    
            case 'error':
                noDocsMsg.style.display = 'block';
                noDocsMsg.innerHTML = '<p style="color: red; font-weight: bold;">' + 
                    (data.message || 'Something went wrong.') + 
                    '</p><button onclick="window.location.href=\'homepage_pas.html\'">Return to Homepage</button>';
                break;
    
            default:
                noDocsMsg.style.display = 'block';
                noDocsMsg.innerHTML = '<p style="color: red; font-weight: bold;">Unexpected response. Please try again later.</p>' +
                    '<button onclick="window.location.href=\'homepage_pas.html\'">Return to Homepage</button>';
                console.error('Unknown status:', data.status, data);
        }
    })
    .catch(err => {
        console.error(err);
        loadingMsg.style.display = 'none';
        noDocsMsg.style.display = 'block';
        noDocsMsg.innerHTML = '<p style="color: red; font-weight: bold;">Something went wrong. Please try again later.</p>' +
            '<button onclick="window.location.href=\'homepage_pas.html\'">Return to Homepage</button>';
    });
}

function buildDriverDocsForm(documents) {
    const container = document.getElementById('driver-docs-container');
    container.innerHTML = '';

    documents.forEach((doc, index) => {
        console.log('=== DOCUMENT ' + index + ' ===');
        console.log('Full doc:', JSON.stringify(doc, null, 2));
        
        const docType = parseInt(doc.d_doc_type_id);
        // Use the d_doc_type from response if available
        const docTypeName = doc.d_doc_type || driverDocTypeMapping[docType] || 'Unknown Document';

        const docBlock = createDocBlock(
            docTypeName,             // 1: Document type name
            'driver',                // 2: Category
            index,                   // 3: Index
            docType,                 // 4: Document type ID
            doc.doc_code,            // 5: Document code
            doc.d_doc_publish_date,  // 6: Issue date
            doc.d_doc_ex_date        // 7: Expiration date
        );

        container.appendChild(docBlock);
    });
}

function createDocBlock(docTypeName, docCategory, index, docTypeId, docCode, publishDate, expDate) {
    const docBlock = document.createElement('div');
    docBlock.className = 'doc-block';
    docBlock.style.cssText = 'border: 2px solid #dc3545; background-color: #fff5f5; padding: 20px; margin-bottom: 20px; border-radius: 8px;';
    
    const prefix = docCategory === 'driver' ? 'driver_docs' : 'vehicle_docs';
    const today = new Date().toISOString().split('T')[0];
    
    // Validate dates - use empty string if invalid
    function isValidDate(dateStr) {
        if (!dateStr) return false;
        const regex = /^\d{4}-\d{2}-\d{2}$/;
        return regex.test(dateStr);
    }
    
    const safePublishDate = isValidDate(publishDate) ? publishDate : '';
    const safeExpDate = isValidDate(expDate) ? expDate : '';
    const safeDocCode = docCode || '';
    
    let html = '';
    html += '<h3 style="color: #dc3545;">' + docTypeName + '</h3>';
    html += '<p style="color: #666; margin-bottom: 15px;"><em>Status: Needs Correction</em></p>';
    
    html += '<input type="hidden" name="' + prefix + '[' + index + '][doc_type_id]" value="' + docTypeId + '">';
    
    html += '<div class="form-row">';
    html += '<label for="' + prefix + '_code_' + index + '">Document Code *</label>';
    html += '<input type="text" id="' + prefix + '_code_' + index + '" name="' + prefix + '[' + index + '][doc_code]" value="' + safeDocCode + '" required>';
    html += '</div>';

    html += '<div class="form-row">';
    html += '<label for="' + prefix + '_publish_' + index + '">Issue Date *</label>';
    html += '<input type="date" id="' + prefix + '_publish_' + index + '" name="' + prefix + '[' + index + '][publish_date]" value="' + safePublishDate + '" max="' + today + '" required>';
    html += '<small style="color: #666; display: block; margin-top: 5px;">Issue date cannot be in the future</small>';
    html += '</div>';

    html += '<div class="form-row">';
    html += '<label for="' + prefix + '_exp_' + index + '">Expiration Date</label>';
    html += '<input type="date" id="' + prefix + '_exp_' + index + '" name="' + prefix + '[' + index + '][exp_date]" value="' + safeExpDate + '" min="' + today + '">';
    html += '<small style="color: #666; display: block; margin-top: 5px;">Expiration date must be in the future (leave empty if doesn\'t expire)</small>';
    html += '</div>';

    html += '<div class="form-row">';
    html += '<label for="' + prefix + '_file_' + index + '">Upload New File (image/pdf) <span style="color: red;">*</span></label>';
    html += '<input type="file" id="' + prefix + '_file_' + index + '" name="' + prefix + '_' + index + '_file" accept=".pdf,.jpg,.jpeg,.png,.gif" required>';
    html += '<small style="color: red; display: block; margin-top: 5px;">You must upload a new file. Maximum 5MB.</small>';
    html += '</div>';

    docBlock.innerHTML = html;

    return docBlock;
}

function handleResubmit(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('resubmitForm');

    // Add enctype if missing
    if (!form.hasAttribute('enctype')) {
        form.setAttribute('enctype', 'multipart/form-data');
    }

    // Validate dates
    if (!validateDates(form)) {
        return;
    }

    // Validate files
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

    // Debug: Log form data
    console.log('Submitting form data...');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }

    fetch('resubmit_rent_docs.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Error response:', text);
                throw new Error('HTTP error! status: ' + response.status);
            });
        }
        return response.text();
    })
    .then(text => {
        console.log('Server response:', text);
        
        if (text.includes('<b>') || text.includes('Fatal error')) {
            console.error('PHP Error:', text);
            showMessage('Server error. Check console.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Resubmit Documents';
            return;
        }

        if (!text || text.trim() === '') {
            console.error('Empty response from server');
            showMessage('Empty response from server. Check PHP error logs.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Resubmit Documents';
            return;
        }

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Invalid JSON:', text);
            showMessage('Invalid server response.', 'error');
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

    const publishInputs = form.querySelectorAll('input[name*="publish_date"]');
    const expInputs = form.querySelectorAll('input[name*="exp_date"]');

    publishInputs.forEach((publishInput, index) => {
        if (!publishInput.value) {
            isValid = false;
            publishInput.style.border = '2px solid red';
            errorMessages.push('Issue date is required for document ' + (index + 1));
            return;
        }

        const publishDate = new Date(publishInput.value);
        publishDate.setHours(0, 0, 0, 0);

        if (publishDate > today) {
            isValid = false;
            publishInput.style.border = '2px solid red';
            errorMessages.push('Issue date for document ' + (index + 1) + ' cannot be in the future');
        } else {
            publishInput.style.border = '';
        }

        const expInput = expInputs[index];
        if (expInput && expInput.value) {
            const expDate = new Date(expInput.value);
            expDate.setHours(0, 0, 0, 0);

            if (expDate <= publishDate) {
                isValid = false;
                expInput.style.border = '2px solid red';
                errorMessages.push('Expiration date for document ' + (index + 1) + ' must be after issue date');
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

// Setup submit button
document.getElementById('submitBtn')?.addEventListener('click', handleResubmit);