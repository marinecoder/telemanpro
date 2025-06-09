/**
 * User Management JavaScript
 * Handles AJAX interactions for user management functions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Load users when the page loads
    loadUsers();

    // Add event listeners for user actions
    document.getElementById('saveUserBtn').addEventListener('click', addUser);
    document.getElementById('updateUserBtn').addEventListener('click', updateUser);
    document.getElementById('confirmDeleteBtn').addEventListener('click', deleteUser);

    // Password validation for the add user form
    document.getElementById('confirmPassword').addEventListener('input', validatePassword);
});

/**
 * Load users from the API and populate the table
 */
function loadUsers() {
    fetch('/api/users')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                populateUserTable(data.users);
            } else {
                showAlert('error', 'Failed to load users: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
            showAlert('error', 'Failed to load users: ' + error.message);
        });
}

/**
 * Populate the user table with data
 */
function populateUserTable(users) {
    const tableBody = document.getElementById('userTable');
    tableBody.innerHTML = '';

    if (users.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="9" class="text-center">No users found</td></tr>';
        return;
    }

    users.forEach(user => {
        const row = document.createElement('tr');
        
        // Format dates
        const createdDate = new Date(user.created_at).toLocaleString();
        const lastLoginDate = user.last_login ? new Date(user.last_login).toLocaleString() : 'Never';
        
        // Create user row
        row.innerHTML = `
            <td>${user.id}</td>
            <td>${user.username}</td>
            <td>${user.email}</td>
            <td>${user.first_name || ''} ${user.last_name || ''}</td>
            <td><span class="badge ${user.role === 'admin' ? 'bg-danger' : 'bg-primary'}">${user.role}</span></td>
            <td>${user.two_factor_enabled ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'}</td>
            <td>${lastLoginDate}</td>
            <td>${createdDate}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary edit-user" data-id="${user.id}">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger delete-user" data-id="${user.id}">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        tableBody.appendChild(row);
    });

    // Add event listeners to the edit and delete buttons
    document.querySelectorAll('.edit-user').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            loadUserForEdit(userId);
        });
    });

    document.querySelectorAll('.delete-user').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            document.getElementById('deleteUserId').value = userId;
            const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
            modal.show();
        });
    });
}

/**
 * Load user data for editing
 */
function loadUserForEdit(userId) {
    fetch(`/api/users/${userId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Populate the edit form with user data
                document.getElementById('editUserId').value = data.user.id;
                document.getElementById('editUsername').value = data.user.username;
                document.getElementById('editEmail').value = data.user.email;
                document.getElementById('editFirstName').value = data.user.first_name || '';
                document.getElementById('editLastName').value = data.user.last_name || '';
                document.getElementById('editRole').value = data.user.role;
                document.getElementById('editTwoFactorEnabled').checked = data.user.two_factor_enabled;
                
                // Show the edit modal
                const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                modal.show();
            } else {
                showAlert('error', 'Failed to load user data: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error loading user data:', error);
            showAlert('error', 'Failed to load user data: ' + error.message);
        });
}

/**
 * Add a new user
 */
function addUser() {
    // Get form values
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const firstName = document.getElementById('firstName').value;
    const lastName = document.getElementById('lastName').value;
    const role = document.getElementById('role').value;
    const twoFactorEnabled = document.getElementById('twoFactorEnabled').checked;

    // Validate passwords match
    if (password !== confirmPassword) {
        showAlert('error', 'Passwords do not match');
        return;
    }

    // Create user data object
    const userData = {
        username,
        email,
        password,
        first_name: firstName,
        last_name: lastName,
        role,
        two_factor_enabled: twoFactorEnabled
    };

    // Send data to API
    fetch('/api/users', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(userData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
            modal.hide();
            
            // Reset the form
            document.getElementById('addUserForm').reset();
            
            // Reload the user list
            loadUsers();
            
            // Show success message
            showAlert('success', 'User added successfully');
        } else {
            showAlert('error', 'Failed to add user: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error adding user:', error);
        showAlert('error', 'Failed to add user: ' + error.message);
    });
}

/**
 * Update an existing user
 */
function updateUser() {
    // Get form values
    const userId = document.getElementById('editUserId').value;
    const username = document.getElementById('editUsername').value;
    const email = document.getElementById('editEmail').value;
    const password = document.getElementById('editPassword').value;
    const firstName = document.getElementById('editFirstName').value;
    const lastName = document.getElementById('editLastName').value;
    const role = document.getElementById('editRole').value;
    const twoFactorEnabled = document.getElementById('editTwoFactorEnabled').checked;

    // Create user data object
    const userData = {
        username,
        email,
        first_name: firstName,
        last_name: lastName,
        role,
        two_factor_enabled: twoFactorEnabled
    };

    // Only include password if it was changed
    if (password) {
        userData.password = password;
    }

    // Send data to API
    fetch(`/api/users/${userId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(userData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
            modal.hide();
            
            // Reload the user list
            loadUsers();
            
            // Show success message
            showAlert('success', 'User updated successfully');
        } else {
            showAlert('error', 'Failed to update user: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error updating user:', error);
        showAlert('error', 'Failed to update user: ' + error.message);
    });
}

/**
 * Delete a user
 */
function deleteUser() {
    const userId = document.getElementById('deleteUserId').value;

    fetch(`/api/users/${userId}`, {
        method: 'DELETE'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteUserModal'));
            modal.hide();
            
            // Reload the user list
            loadUsers();
            
            // Show success message
            showAlert('success', 'User deleted successfully');
        } else {
            showAlert('error', 'Failed to delete user: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error deleting user:', error);
        showAlert('error', 'Failed to delete user: ' + error.message);
    });
}

/**
 * Validate that passwords match
 */
function validatePassword() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (password !== confirmPassword) {
        document.getElementById('confirmPassword').setCustomValidity('Passwords do not match');
    } else {
        document.getElementById('confirmPassword').setCustomValidity('');
    }
}

/**
 * Show an alert message
 */
function showAlert(type, message) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add to the page
    const main = document.querySelector('main');
    main.insertBefore(alertDiv, main.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alertDiv);
        bsAlert.close();
    }, 5000);
}
