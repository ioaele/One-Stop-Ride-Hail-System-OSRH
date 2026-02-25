 
  const API_BASE_URL='api';
    
// Global variables
let safetyData = [];
let filteredData = [];

// DOM Elements
const loadBtn = document.getElementById('loadBtn');
const refreshBtn = document.getElementById('refreshBtn');
const searchInput = document.getElementById('searchInput');
const tableBody = document.getElementById('tableBody');
const errorMessage = document.getElementById('errorMessage');
const successMessage = document.getElementById('successMessage');
const statsBar = document.getElementById('statsBar');
const totalRecords = document.getElementById('totalRecords');
const lastUpdated = document.getElementById('lastUpdated');

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    loadBtn.addEventListener('click', loadSafetyData);
    refreshBtn.addEventListener('click', loadSafetyData);
    searchInput.addEventListener('input', handleSearch);
});

// Load safety data from API
async function loadSafetyData() {
    try {
        // Show loading state
        setLoadingState(true);
        hideMessages();
      
const response = await fetch(`${API_BASE_URL}/printSafeChar.php`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            safetyData = result.data;
            filteredData = [...safetyData];
            renderTable(filteredData);
            updateStats(result.count);
            showSuccess(`Successfully loaded ${result.count} records`);
        } else {
            throw new Error(result.error || 'Failed to load data');
        }
        
    } catch (error) {
        console.error('Error loading data:', error);
        showError(`Error: ${error.message}`);
        renderEmptyState('Error loading data. Please try again.');
    } finally {
        setLoadingState(false);
    }
}

// Render table with data
function renderTable(data) {
    if (!data || data.length === 0) {
        renderEmptyState('No records found');
        return;
    }
    
    tableBody.innerHTML = data.map((item, index) => `
        <tr class="fade-in" style="animation-delay: ${index * 0.05}s">
            <td>${formatDate(item.date)}</td>
            <td>${escapeHtml(item.official || 'N/A')}</td>
            <td>${escapeHtml(item.comments || 'No comments')}</td>
        </tr>
    `).join('');
}

// Render empty state
function renderEmptyState(message) {
    tableBody.innerHTML = `
        <tr>
            <td colspan="3" class="no-data">${message}</td>
        </tr>
    `;
}

// Handle search/filter
function handleSearch(e) {
    const searchTerm = e.target.value.toLowerCase().trim();
    
    if (!searchTerm) {
        filteredData = [...safetyData];
    } else {
        filteredData = safetyData.filter(item => {
            const official = (item.official || '').toLowerCase();
            const comments = (item.comments || '').toLowerCase();
            return official.includes(searchTerm) || comments.includes(searchTerm);
        });
    }
    
    renderTable(filteredData);
    updateStats(filteredData.length);
}

// Update statistics bar
function updateStats(count) {
    totalRecords.textContent = count;
    lastUpdated.textContent = new Date().toLocaleString();
    statsBar.style.display = 'flex';
}

// Set loading state
function setLoadingState(isLoading) {
    const btnText = loadBtn.querySelector('.btn-text');
    const loader = loadBtn.querySelector('.loader');
    
    loadBtn.disabled = isLoading;
    refreshBtn.disabled = isLoading;
    
    if (isLoading) {
        btnText.textContent = 'Loading...';
        loader.style.display = 'inline-block';
    } else {
        btnText.textContent = 'Load Safety Data';
        loader.style.display = 'none';
    }
}

// Show error message
function showError(message) {
    errorMessage.textContent = message;
    errorMessage.style.display = 'block';
    successMessage.style.display = 'none';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        errorMessage.style.display = 'none';
    }, 5000);
}

// Show success message
function showSuccess(message) {
    successMessage.textContent = message;
    successMessage.style.display = 'block';
    errorMessage.style.display = 'none';
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        successMessage.style.display = 'none';
    }, 3000);
}

// Hide all messages
function hideMessages() {
    errorMessage.style.display = 'none';
    successMessage.style.display = 'none';
}

// Format date
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } catch (error) {
        return dateString;
    }
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Export data to CSV (bonus feature)
function exportToCSV() {
    if (!filteredData || filteredData.length === 0) {
        showError('No data to export');
        return;
    }
    
    const headers = ['Check Date', 'Official', 'Comments'];
    const csvContent = [
        headers.join(','),
        ...filteredData.map(item => [
            formatDate(item.date),
            `"${(item.official || '').replace(/"/g, '""')}"`,
            `"${(item.comments || '').replace(/"/g, '""')}"`
        ].join(','))
    ].join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `safety_characteristics_${Date.now()}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showSuccess('Data exported successfully');
}
