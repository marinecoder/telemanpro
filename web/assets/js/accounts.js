// JavaScript for accounts page

document.addEventListener('DOMContentLoaded', function() {
    // Save account button
    const saveAccountBtn = document.getElementById('saveAccountBtn');
    if (saveAccountBtn) {
        saveAccountBtn.addEventListener('click', addAccount);
    }
    
    // View account buttons
    document.querySelectorAll('.view-account-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const accountId = this.dataset.accountId;
            const phone = this.dataset.phone;
            const apiId = this.dataset.apiId;
            const apiHash = this.dataset.apiHash;
            const status = this.dataset.status;
            
            // Fill modal with account data
            document.getElementById('viewPhone').textContent = phone;
            document.getElementById('viewApiId').textContent = apiId;
            document.getElementById('viewApiHash').textContent = apiHash;
            document.getElementById('viewStatus').innerHTML = statusBadge(status);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('viewAccountModal'));
            modal.show();
        });
    });
    
    // Authorize account buttons
    document.querySelectorAll('.authorize-account-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const accountId = this.dataset.accountId;
            const phone = this.dataset.phone;
            
            // Fill modal with account data
            document.getElementById('authAccountId').value = accountId;
            document.getElementById('authPhone').textContent = phone;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('authorizeAccountModal'));
            modal.show();
        });
    });
    
    // Authorize button
    const authorizeBtn = document.getElementById('authorizeBtn');
    if (authorizeBtn) {
        authorizeBtn.addEventListener('click', authorizeAccount);
    }
    
    // Delete account buttons
    document.querySelectorAll('.delete-account-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const accountId = this.dataset.accountId;
            const phone = this.dataset.phone;
            
            // Fill modal with account data
            document.getElementById('deleteAccountId').value = accountId;
            document.getElementById('deletePhone').textContent = phone;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('deleteAccountModal'));
            modal.show();
        });
    });
    
    // Confirm delete button
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', deleteAccount);
    }
});

/**
 * Add a new account
 */
function addAccount() {
    // Get form data
    const form = document.getElementById('addAccountForm');
    const phone = form.querySelector('#phone').value;
    const apiId = form.querySelector('#api_id').value;
    const apiHash = form.querySelector('#api_hash').value;
    
    // Validate form
    if (!phone || !apiId || !apiHash) {
        alert('Please fill in all required fields.');
        return;
    }
    
    // Send API request to add account
    fetch('api/accounts', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${getAccessToken()}`
        },
        body: JSON.stringify({
            phone: phone,
            api_id: apiId,
            api_hash: apiHash
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            alert(data.message);
            
            // Hide modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addAccountModal'));
            modal.hide();
            
            // Reload page
            window.location.reload();
        } else if (data.error) {
            alert(`Error: ${data.error}`);
        }
    })
    .catch(error => {
        console.error('Error adding account:', error);
        alert('An error occurred while adding the account. Please try again.');
    });
}

/**
 * Authorize an account
 */
function authorizeAccount() {
    // Get form data
    const form = document.getElementById('authorizeAccountForm');
    const accountId = form.querySelector('#authAccountId').value;
    const code = form.querySelector('#code').value;
    const password = form.querySelector('#password').value;
    
    // Validate form
    if (!accountId || !code) {
        alert('Please enter the verification code.');
        return;
    }
    
    // Prepare request data
    const requestData = {
        account_id: accountId,
        code: code
    };
    
    // Add password if provided
    if (password) {
        requestData.password = password;
    }
    
    // Send API request to authorize account
    fetch(`api/accounts/${accountId}/authorize`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${getAccessToken()}`
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            alert(data.message);
            
            // Hide modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('authorizeAccountModal'));
            modal.hide();
            
            // Reload page
            window.location.reload();
        } else if (data.error) {
            alert(`Error: ${data.error}`);
            
            // Check if 2FA is required
            if (data.requires_password) {
                // Show password field
                form.querySelector('.password-form').style.display = 'block';
            }
        }
    })
    .catch(error => {
        console.error('Error authorizing account:', error);
        alert('An error occurred while authorizing the account. Please try again.');
    });
}

/**
 * Delete an account
 */
function deleteAccount() {
    // Get account ID
    const accountId = document.getElementById('deleteAccountId').value;
    
    // Send API request to delete account
    fetch(`api/accounts/${accountId}`, {
        method: 'DELETE',
        headers: {
            'Authorization': `Bearer ${getAccessToken()}`
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            alert(data.message);
            
            // Hide modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteAccountModal'));
            modal.hide();
            
            // Reload page
            window.location.reload();
        } else if (data.error) {
            alert(`Error: ${data.error}`);
        }
    })
    .catch(error => {
        console.error('Error deleting account:', error);
        alert('An error occurred while deleting the account. Please try again.');
    });
}
