// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('safetyForm');
    const submitBtn = document.getElementById('submitBtn');
    const loading = document.getElementById('loading');
    const messageDiv = document.getElementById('message');
    const commentsTextarea = document.getElementById('comments');
    const charCount = document.getElementById('charCount');

    // Character counter for comments
    commentsTextarea.addEventListener('input', function() {
        charCount.textContent = this.value.length;
    });

    // Form submission handler
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Get form values
        const licensePlate = document.getElementById('license_plate').value.trim();
        const comments = document.getElementById('comments').value.trim();
        const official = document.getElementById('official').value.trim();

        // Validate inputs
        if (!licensePlate || !comments || !official) {
            showMessage('Please fill in all required fields.', 'error');
            return;
        }

        // Disable submit button and show loading
        submitBtn.disabled = true;
        loading.classList.add('active');
        messageDiv.style.display = 'none';

        // Prepare data for submission
        const formData = {
            license_plate: licensePlate,
            comments: comments,
            official: official
        };

        try {
            // Send POST request to PHP backend
            const response = await fetch('safetyChar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            // Parse JSON response
            const data = await response.json();

            // Hide loading
            loading.classList.remove('active');
            submitBtn.disabled = false;

            // Handle response
            if (data.success) {
                showMessage(data.message || 'Safety characteristics recorded successfully!', 'success');
                // Clear form after successful submission
                form.reset();
                charCount.textContent = '0';
            } else {
                showMessage(data.message || 'An error occurred. Please try again.', 'error');
            }

        } catch (error) {
            // Hide loading
            loading.classList.remove('active');
            submitBtn.disabled = false;

            // Show error message
            showMessage('Connection error: Unable to reach the server. Please check your connection and try again.', 'error');
            console.error('Error:', error);
        }
    });

    // Function to display messages
    function showMessage(message, type) {
        messageDiv.textContent = message;
        messageDiv.className = 'message ' + type;
        messageDiv.style.display = 'block';

        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
    }

    // Input validation - prevent special characters in license plate
    document.getElementById('license_plate').addEventListener('input', function(e) {
        // Allow alphanumeric characters, dashes, and spaces
        this.value = this.value.replace(/[^a-zA-Z0-9\s\-]/g, '');
    });

    // Input validation - capitalize official name
    document.getElementById('official').addEventListener('blur', function(e) {
        // Capitalize first letter of each word
        this.value = this.value.replace(/\b\w/g, char => char.toUpperCase());
    });
});