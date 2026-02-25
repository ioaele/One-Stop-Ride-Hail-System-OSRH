// Configuration
const API_URL = 'get_driver_requests.php'; 

// Driver Requests Manager
class DriverRequestsManager {
    constructor() {
        this.requests = [];
        this.refreshInterval = null;
    }

    // Initialize the manager
    init() {
        this.setupEventListeners();
        // Auto-fetch requests on page load
        this.fetchRequests();
    }

    // Setup event listeners
    setupEventListeners() {
        const fetchBtn = document.getElementById('fetchRequestsBtn');
        const refreshBtn = document.getElementById('refreshBtn');
        const autoRefreshCheckbox = document.getElementById('autoRefresh');

        if (fetchBtn) {
            fetchBtn.addEventListener('click', () => this.fetchRequests());
        }

        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.fetchRequests());
        }

        if (autoRefreshCheckbox) {
            autoRefreshCheckbox.addEventListener('change', (e) => {
                if (e.target.checked) {
                    this.startAutoRefresh();
                } else {
                    this.stopAutoRefresh();
                }
            });
        }
    }

    // Fetch driver requests from the API
    async fetchRequests() {
        this.showLoading(true);

        try {
            const response = await fetch(API_URL, {
                method: 'GET',
                credentials: 'same-origin', // Include session cookies
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const text = await response.text();
            let data;
            
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('Invalid JSON response:', text);
                throw new Error('Server returned invalid JSON. Check PHP error log.');
            }

            if (data.success) {
                this.requests = data.data;
                this.displayRequests(data.data);
                this.updateStats(data.count);
                this.showSuccess(`Found ${data.count} ride request(s) within radius`);
            } else {
                this.showError(data.error || 'Failed to fetch requests');
                if (data.details) {
                    console.error('Error details:', data.details);
                }
            }
        } catch (error) {
            this.showError(`Error: ${error.message}`);
            console.error('Fetch error:', error);
        } finally {
            this.showLoading(false);
        }
    }

    // Display requests in the table
    displayRequests(requests) {
        const tbody = document.getElementById('requestsTableBody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (requests.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="no-data">No ride requests found within 5km radius</td>
                </tr>
            `;
            return;
        }

        requests.forEach((request, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${this.escapeHtml(request.username)}</td>
                <td>${this.formatPhoneNumber(request.phone_number)}</td>
                <td>
                    <button class="action-btn" onclick="driverRequestsManager.viewLocation('${this.escapeHtml(request.username)}')">
                        View Location
                    </button>
                    <button class="action-btn accept-btn" onclick="driverRequestsManager.acceptRequest('${this.escapeHtml(request.username)}', '${this.formatPhoneNumber(request.phone_number)}')">
                        Accept
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }


    // Accept a ride request
    acceptRequest(username, phoneNumber) {
        if (confirm(`Accept ride request from ${username}?\n\nYou can contact them at ${phoneNumber}`)) {
            this.showSuccess(`Ride request accepted! Contact ${username} at ${phoneNumber}`);
            // TODO: Add logic to update the database that the ride was accepted
        }
    }

    // Update statistics
    updateStats(count) {
        const statsElement = document.getElementById('requestCount');
        if (statsElement) {
            statsElement.textContent = count;
        }

        const lastUpdateElement = document.getElementById('lastUpdate');
        if (lastUpdateElement) {
            lastUpdateElement.textContent = new Date().toLocaleTimeString();
        }
    }

    // Auto-refresh functionality
    startAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        this.refreshInterval = setInterval(() => {
            this.fetchRequests();
        }, 30000); // Refresh every 30 seconds
        this.showSuccess('Auto-refresh enabled (every 30 seconds)');
    }

    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
        this.showSuccess('Auto-refresh disabled');
    }

    // Show loading state
    showLoading(isLoading) {
        const loadingElement = document.getElementById('loading');
        if (loadingElement) {
            loadingElement.style.display = isLoading ? 'flex' : 'none';
        }

        const fetchBtn = document.getElementById('fetchRequestsBtn');
        if (fetchBtn) {
            fetchBtn.disabled = isLoading;
            fetchBtn.textContent = isLoading ? 'Loading...' : 'Fetch Requests';
        }
    }

    // Show success message
    showSuccess(message) {
        this.showMessage(message, 'success');
    }

    // Show error message
    showError(message) {
        this.showMessage(message, 'error');
    }

    // Show message
    showMessage(message, type) {
        const messageElement = document.getElementById('message');
        if (messageElement) {
            messageElement.textContent = message;
            messageElement.className = `message ${type}`;
            messageElement.style.display = 'block';

            setTimeout(() => {
                messageElement.style.display = 'none';
            }, 5000);
        }
    }

    // Format phone number
    formatPhoneNumber(phone) {
        if (!phone) return 'N/A';
        return phone;
    }

    // Escape HTML to prevent XSS
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize the manager when DOM is ready
let driverRequestsManager;
document.addEventListener('DOMContentLoaded', () => {
    driverRequestsManager = new DriverRequestsManager();
    driverRequestsManager.init();
    
    // Clean up any existing location on page load to prevent primary key conflicts
    initializeDriverLocation();
});

// Initialize driver location by cleaning up any existing records
function initializeDriverLocation() {
    fetch('offline.php', {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        // Location initialized successfully
    })
    .catch(error => {
        console.warn('Location initialization:', error);
    });
}


const locationElement = document.getElementById("location");
const offlineElement = document.getElementById("offline");

// Event listeners
locationElement.addEventListener('click', function(e) {
    e.preventDefault();
    getLocation();
});

offlineElement.addEventListener('click', function(e) {
    e.preventDefault();
    removeLocation();
});

// Get and update location
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(showPosition, showError);
    } else {
        alert("Geolocation is not supported by this browser.");
    }   
}

function showPosition(position) {
    const latitude = position.coords.latitude;
    const longitude = position.coords.longitude;
    const data = {
        longitude: longitude,
        latitude: latitude
    };
    
    const xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;

        if (xhr.status >= 200 && xhr.status < 300) {
            let resp;
            try {
                resp = JSON.parse(xhr.responseText);
            } catch (e) {
                console.error('Invalid JSON from server:', xhr.responseText);
                showSignupMessage('Unexpected response from server.', 'error');
                return;
            }

            if (resp.status === 'success') {
                showSignupMessage(resp.message || 'Location updated successfully.', 'success');
            } else {
                showSignupMessage(resp.message || 'Location update failed.', 'error');
            }
        } else {
            console.error('Location error:', xhr.status, xhr.responseText);
            showSignupMessage('Network/server error. Please try again.', 'error');
        }
    };

    xhr.open('POST', 'location.php');
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.send(JSON.stringify(data));
}

function showError(error) {
    alert("Unable to retrieve your location: " + error.message);
}
function showSignupMessage(message, type) {
    // Option 1: Simple alert (basic solution)
    alert(message);
}
// Remove location (go offline)
function removeLocation() {
    const xhr = new XMLHttpRequest();

    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;

        if (xhr.status >= 200 && xhr.status < 300) {
            let resp;
            try {
                resp = JSON.parse(xhr.responseText);
            } catch (e) {
                console.error('Invalid JSON from server:', xhr.responseText);
                showSignupMessage('Unexpected response from server.', 'error');
                return;
            }

            if (resp.status === 'success') {
                showSignupMessage(resp.message || 'You are now offline.', 'success');
            } else {
                showSignupMessage(resp.message || 'Failed to go offline.', 'error');
            }
        } else {
            console.error('Offline error:', xhr.status, xhr.responseText);
            showSignupMessage('Network/server error. Please try again.', 'error');
        }
    };

    xhr.open('GET', 'offline.php');
    xhr.send();
}