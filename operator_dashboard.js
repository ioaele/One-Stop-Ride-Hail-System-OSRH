document.addEventListener('DOMContentLoaded', () => {
    
    loadPendingDocuments();

    // Filter event listeners
    const categoryFilter = document.getElementById('filter-category');
    const usernameFilter = document.getElementById('filter-username');
    
    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterDocuments);
    }
    
    if (usernameFilter) {
        usernameFilter.addEventListener('input', filterDocuments);
    }

    // Image modal setup
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const span = document.getElementsByClassName('close')[0];

    if (span) {
        span.onclick = function() {
            modal.style.display = 'none';
        }
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    // Make openImageModal available globally
    window.openImageModal = function(imageSrc) {
        if (modal && modalImg) {
            modal.style.display = 'block';
            modalImg.src = imageSrc;
        }
    }
});

let allDocuments = [];

function loadPendingDocuments() {
    const loading = document.getElementById('loading');
    const noDocsMsg = document.getElementById('no-documents');
    const container = document.getElementById('documents-container');

    if (loading) loading.style.display = 'block';
    if (noDocsMsg) noDocsMsg.style.display = 'none';
    if (container) container.innerHTML = '';

    fetch('operator_get_pending_docs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (loading) loading.style.display = 'none';

        if (data.status === 'success') {
            allDocuments = data.documents || [];

            if (allDocuments.length === 0) {
                if (noDocsMsg) noDocsMsg.style.display = 'block';
            } else {
                displayDocuments(allDocuments);
            }
        } else {
            showMessage(data.message || 'Error loading documents', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (loading) loading.style.display = 'none';
        showMessage('Failed to load documents: ' + error.message, 'error');
    });
}

function displayDocuments(documents) {
    const container = document.getElementById('documents-container');
    if (!container) return;
    
    container.innerHTML = '';

    if (!documents || documents.length === 0) {
        const noDocsMsg = document.getElementById('no-documents');
        if (noDocsMsg) noDocsMsg.style.display = 'block';
        return;
    }

    documents.forEach(doc => {
        const docCard = createDocumentCard(doc);
        container.appendChild(docCard);
    });
}

function createDocumentCard(doc) {
    const card = document.createElement('div');
    card.className = `doc-card ${doc.doc_category.toLowerCase()}`;
    card.setAttribute('data-category', doc.doc_category);
    card.setAttribute('data-username', (doc.username || '').toLowerCase());

    const isImage = doc.image_pdf && (doc.image_pdf.endsWith('.jpg') || 
                                      doc.image_pdf.endsWith('.jpeg') || 
                                      doc.image_pdf.endsWith('.png') || 
                                      doc.image_pdf.endsWith('.gif'));
    const isPDF = doc.image_pdf && doc.image_pdf.endsWith('.pdf');

    card.innerHTML = `
        <div class="doc-info">
            <span class="badge ${doc.doc_category.toLowerCase()}">${doc.doc_category}</span>
            <h3>${doc.doc_type_name || 'Unknown Document'}</h3>
            <div class="doc-details">
                <p><strong>User:</strong> <span>${doc.username || 'N/A'} (${doc.user_name || 'N/A'})</span></p>
                <p><strong>Email:</strong> <span>${doc.email || 'N/A'}</span></p>
                <p><strong>Document Code:</strong> <span>${doc.doc_code || 'N/A'}</span></p>
                <p><strong>Issue Date:</strong> <span>${formatDate(doc.publish_date)}</span></p>
                <p><strong>Expiry Date:</strong> <span>${doc.exp_date ? formatDate(doc.exp_date) : 'N/A'}</span></p>
                ${doc.updated_at ? `<p><strong>Updated:</strong> <span>${formatDateTime(doc.updated_at)}</span></p>` : ''}
            </div>
        </div>

        <div class="doc-preview">
            ${isImage ? 
                `<img src="${doc.image_pdf}" alt="Document" onclick="openImageModal('${doc.image_pdf}')">` : 
                isPDF ? 
                `<p class="pdf-icon">PDF Document</p>` : 
                `<p class="pdf-icon">File</p>`
            }
            <a href="${doc.image_pdf}" target="_blank">View Full Document</a>
        </div>

        <div class="doc-actions">
            <button class="btn-approve" onclick="approveDocument('${doc.doc_category}', ${doc.doc_id}, this)">
                ✓ Approve
            </button>
            <button class="btn-disapprove" onclick="toggleReasonInput(this)">
                ✗ Disapprove
            </button>
            <textarea class="rejection-reason" 
                      id="reason-${doc.doc_category}-${doc.doc_id}" 
                      placeholder="Reason for disapproval (optional)..."
                      rows="3"></textarea>
            <button class="btn-disapprove" 
                    style="display: none;" 
                    onclick="disapproveDocument('${doc.doc_category}', ${doc.doc_id}, this)">
                Confirm Disapproval
            </button>
        </div>
    `;

    return card;
}

function toggleReasonInput(button) {
    const actions = button.parentElement;
    const reasonTextarea = actions.querySelector('.rejection-reason');
    const confirmBtn = actions.querySelectorAll('.btn-disapprove')[1];

    if (reasonTextarea && confirmBtn) {
        reasonTextarea.classList.toggle('show');
        confirmBtn.style.display = reasonTextarea.classList.contains('show') ? 'block' : 'none';
        button.style.display = reasonTextarea.classList.contains('show') ? 'none' : 'block';
    }
}

function approveDocument(category, docId, button) {
    if (!confirm('Are you sure you want to APPROVE this document?')) {
        return;
    }

    const card = button.closest('.doc-card');
    const actions = card.querySelector('.doc-actions');
    const buttons = actions.querySelectorAll('button');
    
    buttons.forEach(btn => btn.disabled = true);
    button.textContent = 'Approving...';

    fetch('operator_approve_document.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            doc_category: category,
            doc_id: docId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            let message = data.message;
            
            // Check if driver was created
            if (data.driver_created) {
                message += ` 🎉 Driver ID ${data.driver_id} has been created!`;
            }
            
            showMessage(message, 'success');
            
            // Remove the card with animation
            card.style.transition = 'opacity 0.5s, transform 0.5s';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            
            setTimeout(() => {
                card.remove();
                
                // Check if no more documents
                const remaining = document.querySelectorAll('.doc-card').length;
                if (remaining === 0) {
                    const noDocsMsg = document.getElementById('no-documents');
                    if (noDocsMsg) noDocsMsg.style.display = 'block';
                }
            }, 500);

         
        } else {
            showMessage(data.message || 'Failed to approve document', 'error');
            buttons.forEach(btn => btn.disabled = false);
            button.textContent = '✓ Approve';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error approving document: ' + error.message, 'error');
        buttons.forEach(btn => btn.disabled = false);
        button.textContent = '✓ Approve';
    });
}
function disapproveDocument(category, docId, button) {
    const reasonTextarea = document.getElementById(`reason-${category}-${docId}`);
    const reason = reasonTextarea ? reasonTextarea.value.trim() : '';

    if (!reason) {
        if (!confirm('Are you sure you want to DISAPPROVE this document without providing a reason?')) {
            return;
        }
    }

    const card = button.closest('.doc-card');
    const actions = card.querySelector('.doc-actions');
    const buttons = actions.querySelectorAll('button');
    
    buttons.forEach(btn => btn.disabled = true);
    button.textContent = 'Processing...';

    console.log('Disapproving document:', { category, docId, reason });

    fetch('operator_disapprove_document.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            doc_category: category,
            doc_id: docId,
            rejection_reason: reason
        })
    })
    .then(response => response.text().then(text => {
        console.log('Raw response:', text);

        // Αν ο server γύρισε HTML (π.χ. 404/500 σελίδα)
        if (text.trim().startsWith('<!') || text.trim().startsWith('<html')) {
            throw new Error('Server returned HTML instead of JSON. Check PHP error log.');
        }

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error('Invalid JSON response: ' + text.substring(0, 100));
        }

        // Αν HTTP status δεν είναι 2xx, ρίχνουμε error με μήνυμα από server
        if (!response.ok) {
            const serverMsg = data && data.message ? data.message : 'Server error';
            throw new Error(`HTTP ${response.status}: ${serverMsg}`);
        }

        return data;   // εδώ περνάμε ΜΟΝΟ το JSON στο επόμενο then
    }))
    .then(data => {
        // εδώ data είναι το JSON από το PHP (ΟΧΙ result.data)
        if (data.status === 'success') {
            showMessage(data.message || 'Document marked for correction!', 'success');
            
            // Αφαιρούμε την κάρτα με animation όπως στο approve
            card.style.transition = 'opacity 0.5s, transform 0.5s';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';

            setTimeout(() => {
                card.remove();

                // Αν δεν έμειναν άλλες κάρτες, δείξε το μήνυμα "no documents"
                const remaining = document.querySelectorAll('.doc-card').length;
                if (remaining === 0) {
                    const noDocsMsg = document.getElementById('no-documents');
                    if (noDocsMsg) noDocsMsg.style.display = 'block';
                }
            }, 500);

        } else {
            // Business error από backend
            showMessage(data.message || 'Failed to disapprove document', 'error');
            buttons.forEach(btn => btn.disabled = false);
            button.textContent = 'Confirm Disapproval';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error disapproving document: ' + error.message, 'error');
        buttons.forEach(btn => btn.disabled = false);
        button.textContent = 'Confirm Disapproval';
    });
}

function filterDocuments() {
    const categoryFilter = document.getElementById('filter-category');
    const usernameFilter = document.getElementById('filter-username');
    const noDocsMsg = document.getElementById('no-documents');
    
    // Safety checks
    if (!categoryFilter || !usernameFilter) {
        console.error('Filter elements not found');
        return;
    }
    
    if (!allDocuments || allDocuments.length === 0) {
        console.log('No documents to filter');
        if (noDocsMsg) {
            noDocsMsg.style.display = 'block';
            noDocsMsg.querySelector('p').textContent = '🔍 No documents to filter.';
        }
        return;
    }

    const categoryValue = categoryFilter.value;
    const usernameValue = usernameFilter.value.toLowerCase();

    const filteredDocs = allDocuments.filter(doc => {
        const matchCategory = categoryValue === 'all' || doc.doc_category === categoryValue;
        const matchUsername = !usernameValue || (doc.username && doc.username.toLowerCase().includes(usernameValue));
        return matchCategory && matchUsername;
    });

    displayDocuments(filteredDocs);

    if (filteredDocs.length === 0) {
        if (noDocsMsg) {
            noDocsMsg.style.display = 'block';
            noDocsMsg.querySelector('p').textContent = '🔍 No documents match your filters.';
        }
    } else {
        if (noDocsMsg) noDocsMsg.style.display = 'none';
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB');
    } catch (e) {
        return 'N/A';
    }
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleString('en-GB');
    } catch (e) {
        return 'N/A';
    }
}

function showMessage(message, type) {
    const alertBox = document.getElementById('alert');
    const alertText = document.getElementById('alert-text');

    if (!alertBox || !alertText) {
        console.error('Alert elements not found');
        alert(message);
        return;
    }

    alertText.textContent = message;
    alertBox.className = `alert ${type}`;
    alertBox.style.display = 'block';

    alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    setTimeout(() => {
        alertBox.style.display = 'none';
    }, 5000);
}