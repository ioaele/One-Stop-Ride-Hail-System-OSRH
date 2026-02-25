// driver.js - Complete implementation with proper API integration

// Display username immediately when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Get username from localStorage (set by login.js)
    const username = localStorage.getItem('username');
    
    // Display it in the navbar
    const navbarUsername = document.getElementById('navbar-username');
    if (navbarUsername) {
        if (username) {
            navbarUsername.textContent = username;
        } else {
            navbarUsername.textContent = 'Driver';
        }
    }
    
    console.log('Username loaded:', username);
    
    // Initialize dashboard
    loadPendingRides();
    loadStats();
    loadFeedback();
    startPolling();
});

// ===== POLLING FOR PENDING RIDES =====
let pollInterval = null;

function startPolling() {
    const autoPollCheckbox = document.getElementById('autoPoll');
    if (autoPollCheckbox && autoPollCheckbox.checked) {
        pollInterval = setInterval(() => {
            loadPendingRides();
        }, 5000); // Poll every 5 seconds
    }
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

// Listen to checkbox changes
document.addEventListener('DOMContentLoaded', function() {
    const autoPollCheckbox = document.getElementById('autoPoll');
    if (autoPollCheckbox) {
        autoPollCheckbox.addEventListener('change', function() {
            if (this.checked) {
                startPolling();
            } else {
                stopPolling();
            }
        });
    }
});

// ===== LOAD PENDING RIDES =====
async function loadPendingRides() {
    const container = document.getElementById('incoming-list');
    container.innerHTML = '<div class="loading">Loading requests...</div>';
    
    try {
        const response = await fetch('driver.php?action=get_pending_rides', {
            method: 'GET',
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch rides');
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Unknown error');
        }
        
        if (data.pending && data.pending.length > 0) {
            container.innerHTML = '';
            data.pending.forEach(ride => {
                const rideDiv = document.createElement('div');
                rideDiv.classList.add('ride');
                rideDiv.innerHTML = `
                    <div style="margin-bottom: 12px;">
                        <strong style="font-size: 1.05rem; color: #0b63ff;">
                            ${ride.customer_name}
                        </strong>
                    </div>
                    <div style="margin-bottom: 8px; font-size: 0.9rem; color: #666;">
                        📞 ${ride.customer_phone}
                    </div>
                    <div style="margin-bottom: 8px;">
                        <strong>Pickup:</strong> Lat ${ride.pickup_lat}
                    </div>
                    <div style="margin-bottom: 8px;">
                        <strong>Dropoff:</strong> Lat ${ride.dropoff_lat}
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin: 12px 0; font-size: 0.85rem;">
                        <div><strong>Fare:</strong> ${ride.fare}</div>
                        <div><strong>Distance:</strong> ${ride.distance}</div>
                        <div><strong>ETA:</strong> ${ride.eta}</div>                    </div>
                    <div style="display: flex; gap: 8px; margin-top: 12px;">
                        <button class="btn btn-accept" onclick="acceptRide(${ride.ride_id})" style="flex: 1;">
                            ✅ Accept
                        </button>
                        <button class="btn btn-decline" onclick="declineRide(${ride.ride_id})" style="flex: 1;">
                            ❌ Decline
                        </button>
                    </div>
                `;
                container.appendChild(rideDiv);
            });
        } else {
            container.innerHTML = '<div class="loading">No pending requests at the moment.</div>';
        }
    } catch (error) {
        console.error('Error loading pending rides:', error);
        container.innerHTML = `<div class="loading" style="color: #ef4444;">Error: ${error.message}</div>`;
    }
}

// // ===== ACCEPT/DECLINE RIDES =====
// async function acceptRide(rideId) {
//     if (!confirm('Accept this ride request?')) return;
    
//     try {
//         const response = await fetch('driver_api.php?action=accept_ride', {
//             method: 'POST',
//             credentials: 'include',
//             headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
//             body: `ride_id=${rideId}`
//         });
        
//         const data = await response.json();
        
//         if (data.success) {
//             logActivity(`✅ Accepted ride #${rideId}`);
//             loadPendingRides();
//         } else {
//             alert('Error: ' + (data.error || 'Could not accept ride'));
//         }
//     } catch (error) {
//         console.error('Error accepting ride:', error);
//         alert('Error accepting ride');
//     }
// }

// async function declineRide(rideId) {
//     if (!confirm('Decline this ride request?')) return;
    
//     try {
//         const response = await fetch('driver_api.php?action=decline_ride', {
//             method: 'POST',
//             credentials: 'include',
//             headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
//             body: `ride_id=${rideId}`
//         });
        
//         const data = await response.json();
        
//         if (data.success) {
//             logActivity(`❌ Declined ride #${rideId}`);
//             loadPendingRides();
//         } else {
//             alert('Error: ' + (data.error || 'Could not decline ride'));
//         }
//     } catch (error) {
//         console.error('Error declining ride:', error);
//         alert('Error declining ride');
//     }
// }

// // ===== LOAD STATISTICS =====
// async function loadStats() {
//     const period = document.getElementById('statsPeriod').value;
    
//     try {
//         const response = await fetch(`driver_api.php?action=get_stats&period=${period}`, {
//             method: 'GET',
//             credentials: 'include'
//         });
        
//         const data = await response.json();
        
//         document.getElementById('stat-rides').textContent = data.rides || 0;
//         document.getElementById('stat-earnings').textContent = '$' + (data.earnings || '0.00');
//         document.getElementById('stat-rating').textContent = data.rating || '0.0';
        
//     } catch (error) {
//         console.error('Error loading stats:', error);
//         document.getElementById('stat-rides').textContent = '--';
//         document.getElementById('stat-earnings').textContent = '--';
//         document.getElementById('stat-rating').textContent = '--';
//     }
// }

// // ===== LOAD FEEDBACK =====
// async function loadFeedback() {
//     const container = document.getElementById('feedbackList');
//     container.innerHTML = '<div class="loading">Loading feedback...</div>';
    
//     try {
//         const response = await fetch('driver_api.php?action=get_feedback', {
//             method: 'GET',
//             credentials: 'include'
//         });
        
//         const data = await response.json();
        
//         if (data.success && data.feedback && data.feedback.length > 0) {
//             container.innerHTML = '';
//             data.feedback.forEach(fb => {
//                 const fbDiv = document.createElement('div');
//                 fbDiv.classList.add('feedback');
//                 fbDiv.innerHTML = `
//                     <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
//                         <strong>${fb.customer_name}</strong>
//                         <span style="color: #f59e0b;">⭐ ${fb.rating}/5</span>
//                     </div>
//                     <div style="font-size: 0.9rem; color: #666; margin-bottom: 4px;">
//                         ${fb.comment || 'No comment'}
//                     </div>
//                     <div style="font-size: 0.8rem; color: #999;">
//                         ${fb.date}
//                     </div>
//                 `;
//                 container.appendChild(fbDiv);
//             });
//         } else {
//             container.innerHTML = '<div class="loading">No feedback yet.</div>';
//         }
//     } catch (error) {
//         console.error('Error loading feedback:', error);
//         container.innerHTML = '<div class="loading" style="color: #ef4444;">Error loading feedback.</div>';
//     }
// }

// // ===== ACTIVITY LOG =====
// function logActivity(message) {
//     const log = document.getElementById('activityLog');
//     const timestamp = new Date().toLocaleTimeString();
    
//     const activityDiv = document.createElement('div');
//     activityDiv.classList.add('activity-item');
//     activityDiv.innerHTML = `<strong>${timestamp}</strong> - ${message}`;
    
//     log.prepend(activityDiv);
    
//     // Keep only last 20 items
//     while (log.children.length > 20) {
//         log.removeChild(log.lastChild);
//     }
// }

// // ===== AVAILABILITY =====
// async function setAvailability(status) {
//     const statusText = status === 1 ? 'online' : 'offline';
    
//     try {
//         const response = await fetch('driver_api.php?action=set_availability', {
//             method: 'POST',
//             credentials: 'include',
//             headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
//             body: `status=${status}`
//         });
        
//         const data = await response.json();
        
//         if (data.success) {
//             logActivity(`🎛️ Status changed to ${statusText}`);
//             alert(`You are now ${statusText}`);
//         } else {
//             alert('Error: ' + (data.error || 'Could not update status'));
//         }
//     } catch (error) {
//         console.error('Error setting availability:', error);
//         alert('Error updating availability');
//     }
// }

// // ===== CHAT FUNCTIONS =====
// let currentChatCustomerId = null;

// function startChat() {
//     const custId = document.getElementById('chatCustId').value.trim();
//     if (!custId) {
//         alert('Please enter a customer ID');
//         return;
//     }
    
//     currentChatCustomerId = custId;
//     document.getElementById('messages').innerHTML = '<div class="loading">Loading chat...</div>';
//     loadChatMessages();
    
//     logActivity(`💬 Started chat with customer #${custId}`);
// }

// async function loadChatMessages() {
//     if (!currentChatCustomerId) return;
    
//     try {
//         const response = await fetch(`driver_api.php?action=chat_get&customer_id=${currentChatCustomerId}`, {
//             method: 'GET',
//             credentials: 'include'
//         });
        
//         const data = await response.json();
        
//         const container = document.getElementById('messages');
        
//         if (data.success && data.messages && data.messages.length > 0) {
//             container.innerHTML = '';
//             data.messages.forEach(msg => {
//                 const msgDiv = document.createElement('div');
//                 msgDiv.classList.add('message', msg.from_driver ? 'me' : 'them');
//                 msgDiv.textContent = msg.message;
//                 container.appendChild(msgDiv);
//             });
//             container.scrollTop = container.scrollHeight;
//         } else {
//             container.innerHTML = '<div style="text-align: center; color: #999; padding: 20px;">No messages yet. Start the conversation!</div>';
//         }
//     } catch (error) {
//         console.error('Error loading chat:', error);
//     }
// }

// async function sendMessage() {
//     if (!currentChatCustomerId) {
//         alert('Please start a chat first');
//         return;
//     }
    
//     const input = document.getElementById('msgInput');
//     const message = input.value.trim();
    
//     if (!message) return;
    
    try {
        const response = await fetch('driver.php?action=chat_send', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `customer_id=${currentChatCustomerId}&message=${encodeURIComponent(message)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            input.value = '';
            loadChatMessages();
        } else {
            alert('Error: ' + (data.error || 'Could not send message'));
        }
    } catch (error) {
        console.error('Error sending message:', error);
        alert('Error sending message');
    }


// ===== MANUAL REFRESH =====
function pollNow() {
    logActivity('🔄 Manual refresh triggered');
    loadPendingRides();
    loadStats();
    loadFeedback();
}