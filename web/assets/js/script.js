// Main JavaScript for all pages

// Wait for document to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Add event listener for stop operation buttons
    document.querySelectorAll('.stop-operation').forEach(function(button) {
        button.addEventListener('click', function() {
            var operationId = this.dataset.operationId;
            stopOperation(operationId);
        });
    });
    
    // Refresh button
    const refreshBtn = document.querySelector('.refresh-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            window.location.reload();
        });
    }
    
    // Check for mobile view to handle sidebar
    if (window.innerWidth < 768) {
        // Collapse sidebar after clicking a link on mobile
        document.querySelectorAll('#sidebar .nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                const sidebar = document.getElementById('sidebar');
                if (sidebar.classList.contains('show')) {
                    const bsCollapse = new bootstrap.Collapse(sidebar);
                    bsCollapse.hide();
                }
            });
        });
    }
});

/**
 * Stop an operation
 * 
 * @param {number} operationId Operation ID
 */
function stopOperation(operationId) {
    if (confirm('Are you sure you want to stop this operation?')) {
        // Send API request to stop operation
        fetch(`api/operations/${operationId}/stop`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getAccessToken()}`
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.message) {
                alert(data.message);
                window.location.reload();
            } else if (data.error) {
                alert(`Error: ${data.error}`);
            }
        })
        .catch(error => {
            console.error('Error stopping operation:', error);
            alert('An error occurred while stopping the operation. Please try again.');
        });
    }
}

/**
 * Get access token from session storage
 * 
 * @returns {string} Access token
 */
function getAccessToken() {
    // In a real application, this would get the token from session storage or cookies
    // For demo purposes, we'll return a placeholder
    return 'access_token';
}

/**
 * Format date and time
 * 
 * @param {string} datetime Date/time string
 * @param {boolean} includeTime Whether to include time
 * @returns {string} Formatted date/time
 */
function formatDateTime(datetime, includeTime = true) {
    if (!datetime) return '-';
    
    const date = new Date(datetime);
    const options = {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    };
    
    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
        options.second = '2-digit';
    }
    
    return date.toLocaleDateString('en-US', options);
}

/**
 * Generate status badge HTML
 * 
 * @param {string} status Status string
 * @returns {string} HTML for status badge
 */
function statusBadge(status) {
    let colorClass = 'secondary';
    
    switch (status) {
        case 'active':
            colorClass = 'success';
            break;
        case 'restricted':
            colorClass = 'warning';
            break;
        case 'banned':
            colorClass = 'danger';
            break;
        case 'pending':
            colorClass = 'info';
            break;
        case 'running':
            colorClass = 'primary';
            break;
        case 'completed':
            colorClass = 'success';
            break;
        case 'failed':
            colorClass = 'danger';
            break;
        case 'stopped':
            colorClass = 'warning';
            break;
    }
    
    return `<span class="badge bg-${colorClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
}
