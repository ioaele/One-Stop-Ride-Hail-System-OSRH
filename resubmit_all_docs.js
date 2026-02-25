document.addEventListener('DOMContentLoaded', () => {
    const storedUsername = localStorage.getItem('username') || 'User';
    const navbarUsernameEl = document.getElementById('navbar-username');
    if (navbarUsernameEl) navbarUsernameEl.textContent = storedUsername;
  
    loadDocumentsForResubmit();
});

// Document type mappings
const driverDocTypeMapping = {
    7: 'ID / Passport',
    8: 'Residence Permit',
    9: 'Driving License',
    10:'Criminal Record Certificate',
    11:'Medical Certificate',
    12:'Psychological Certificate'
};


const vehicleDocTypeMapping = {
    10: 'Vehicle Registration',
    11: 'MOT Certificate',
    12: 'Vehicle Classification Certificate'
};
function loadDocumentsForResubmit() {
    const loadingMsg = document.getElementById('loading-message');
    const noDocsMsg = document.getElementById('no-docs-message');
    const form = document.getElementById('resubmitForm');

    console.log('Loading documents...');

    fetch('checkUsersDocs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({})
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response:', data);
        loadingMsg.style.display = 'none';

        if (data.status === 'no_docs' || data.status === 'pending') {
            noDocsMsg.textContent = data.message;
            noDocsMsg.style.display = 'block';
            return;
        }

        if (data.status === 'error') {
            showMessage(data.message || 'Error loading documents', 'error');
            return;
        }

        if (data.status === 'has_issues') {
            // Set hidden fields - CRITICAL FIX HERE
            const usersIdInput = document.getElementById('users_id');
            const vehicleIdInput = document.getElementById('vehicle_id');
            
            if (usersIdInput) {
                usersIdInput.value = data.users_id || '';
                console.log('Set users_id to:', data.users_id);
            }
            
            if (vehicleIdInput) {
                vehicleIdInput.value = data.vehicle_id || '';
                console.log('Set vehicle_id to:', data.vehicle_id);
            }

            // Build driver docs form
            if (data.driverDocs && data.driverDocs.length > 0) {
                console.log('Building driver docs form with', data.driverDocs.length, 'documents');
                buildDriverDocsForm(data.driverDocs);
                document.getElementById('driver-docs-section').style.display = 'block';
            }

            // Build vehicle docs form
            if (data.vehicleDocs && data.vehicleDocs.length > 0) {
                console.log('Building vehicle docs form with', data.vehicleDocs.length, 'documents');
                buildVehicleDocsForm(data.vehicleDocs);
                document.getElementById('vehicle-docs-section').style.display = 'block';
            }

            form.style.display = 'block';
        } else {
            noDocsMsg.style.display = 'block';
        }
    })
    .catch(err => {
        console.error('Error:', err);
        loadingMsg.style.display = 'none';
        showMessage('Failed to load documents: ' + err.message, 'error');
    });
}

function buildDriverDocsForm(documents) {
    const container = document.getElementById('driver-docs-container');
    container.innerHTML = '';

    documents.forEach((doc, index) => {
        const docType = parseInt(doc.d_doc_type_id);
        const docTypeName = driverDocTypeMapping[docType] || 'Unknown Document';

        const docBlock = createDocBlock(
            docTypeName,
            'driver',
            index,
            docType,
            doc.doc_code,
            doc.d_doc_publish_date,
            doc.d_doc_ex_date
        );

        container.appendChild(docBlock);
    });
}

function buildVehicleDocsForm(documents) {
    const container = document.getElementById('vehicle-docs-container');
    container.innerHTML = '';

    documents.forEach((doc, index) => {
        const docType = parseInt(doc.v_doc_type_id);
        const docTypeName = vehicleDocTypeMapping[docType] || 'Unknown Document';

        const docBlock = createDocBlock(
            docTypeName,
            'vehicle',
            index,
            docType,
            doc.doc_code,
            doc.v_doc_publish_date,
            doc.v_doc_exp_date
        );

        container.appendChild(docBlock);
    });
}

function createDocBlock(docTypeName, docCategory, index, docTypeId, docCode, publishDate, expDate) {
    const docBlock = document.createElement('div');
    docBlock.className = 'doc-block';
    docBlock.style.cssText = 'border: 2px solid #dc3545; background-color: #fff5f5; padding: 20px; margin-bottom: 20px; border-radius: 8px;';
    
    const prefix = docCategory === 'driver' ? 'driver_docs' : 'vehicle_docs';
    
    docBlock.innerHTML = `
        <h3 style="color: #dc3545;">🔴 ${docTypeName}</h3>
        <p style="color: #666; margin-bottom: 15px;">
            <em>Status: Needs Correction</em>
        </p>
        
        <input type="hidden" name="${prefix}[${index}][doc_type_id]" value="${docTypeId}">
        
        <div class="form-row">
            <label for="${prefix}_code_${index}">Document Code *</label>
            <input 
                type="text" 
                id="${prefix}_code_${index}" 
                name="${prefix}[${index}][doc_code]" 
                value="${docCode || ''}" 
                required>
        </div>

        <div class="form-row">
            <label for="${prefix}_publish_${index}">Issue Date *</label>
            <input 
                type="date" 
                id="${prefix}_publish_${index}" 
                name="${prefix}[${index}][publish_date]" 
                value="${publishDate || ''}" 
                max="${new Date().toISOString().split('T')[0]}"
                required>
            <small style="color: #666; display: block; margin-top: 5px;">
                Issue date cannot be in the future
            </small>
        </div>

        <div class="form-row">
            <label for="${prefix}_exp_${index}">Expiration Date</label>
            <input 
                type="date" 
                id="${prefix}_exp_${index}" 
                name="${prefix}[${index}][exp_date]" 
                value="${expDate || ''}"
                min="${new Date().toISOString().split('T')[0]}">
            <small style="color: #666; display: block; margin-top: 5px;">
                Expiration date must be in the future (leave empty if doesn't expire)
            </small>
        </div>

        <div class="form-row">
            <label for="${prefix}_file_${index}">
                Upload New File (image/pdf) 
                <span style="color: red;">*</span>
            </label>
            <input 
                type="file" 
                id="${prefix}_file_${index}" 
                name="${prefix}_${index}_file" 
                accept=".pdf,.jpg,.jpeg,.png,.gif" 
                required>
            <small style="color: red; display: block; margin-top: 5px;">
                You must upload a new file. Maximum 5MB.
            </small>
        </div>
    `;

    return docBlock;
}

function handleResubmit(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('resubmitForm');

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

    console.log('Submitting form data...');

    fetch('resubmit_all_docs.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
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
        
        if (text.includes('<b>') || text.includes('Fatal error')) {
            console.error('PHP Error:', text);
            showMessage('Server error. Check console.', 'error');
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
        const publishDate = new Date(publishInput.value);
        publishDate.setHours(0, 0, 0, 0);

        if (publishDate > today) {
            isValid = false;
            publishInput.style.border = '2px solid red';
            errorMessages.push(`Issue date for document ${index + 1} cannot be in the future`);
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

// Setup submit button
document.getElementById('submitBtn')?.addEventListener('click', handleResubmit);