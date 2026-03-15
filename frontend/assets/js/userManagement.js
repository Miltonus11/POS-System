document.addEventListener('DOMContentLoaded', function () {
    const userTableBody = document.querySelector('table tbody');
    const searchInput = document.querySelector('.search-input');
    const addUserBtn = document.querySelector('.add-user-btn');

    //custom notification
    function showNotification(message, isSuccess = true) {
        const notification = document.createElement('div');
        notification.classList.add('custom-notification', isSuccess ? 'success' : 'error');
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                if(document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 500);
        }, 3000);
    }

    //confirmation
    function showConfirmation(message, onConfirm) {
        const confirmationModal = document.createElement('div');
        confirmationModal.classList.add('modal');
        confirmationModal.innerHTML = `
            <div class="modal-content">
                <p>${message}</p>
                <div class="confirmation-buttons">
                    <button id="confirmBtn">Yes</button>
                    <button id="cancelBtn">No</button>
                </div>
            </div>
        `;
        document.body.appendChild(confirmationModal);

        document.getElementById('confirmBtn').addEventListener('click', () => {
            onConfirm();
            document.body.removeChild(confirmationModal);
        });

        document.getElementById('cancelBtn').addEventListener('click', () => {
            document.body.removeChild(confirmationModal);
        });
    }

    //fetch
    function fetchUsers() {
        fetch(`${apiBasePath}/users/get-user.php`, { credentials: 'include' })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    userTableBody.innerHTML = ''; // Clear existing rows
                    data.users.forEach(user => {
                        const row = `
                            <tr>
                                <td>${user.id}</td>
                                <td>${user.username}</td>
                                <td class="password-cell">
                                    <span>********</span>
                                    <i class="fas fa-eye-slash reveal-pass-icon"></i>
                                </td>
                                <td>${user.full_name}</td>
                                <td>${user.role}</td>
                                <td>${user.status}</td>
                                <td>${new Date(user.created_at).toLocaleDateString()}</td>
                                <td>${user.last_login ? new Date(user.last_login).toLocaleString() : 'N/A'}</td>
                                <td class="action-buttons">
                                    <button class="edit-btn" data-id="${user.id}"><i class="fas fa-edit"></i></button>
                                    <button class="archive-btn" data-id="${user.id}"><i class="fas fa-archive"></i></button>
                                </td>
                            </tr>
                        `;
                        userTableBody.innerHTML += row;
                    });
                } else {
                    showNotification('Failed to fetch users: ' + data.message, false);
                }
            })
            .catch(error => {
                showNotification('Error fetching users: ' + error.message, false);
                console.error('Error fetching users:', error)
            });
    }
    fetchUsers();
    //searrch function
    searchInput.addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase();
        const tableRows = userTableBody.querySelectorAll('tr');
        tableRows.forEach(row => {
            const rowData = row.textContent.toLowerCase();
            if (rowData.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    //handle add users
    addUserBtn.addEventListener('click', function () {
        showModal('Add New User');
    });

    //delete and edit buttons
    userTableBody.addEventListener('click', function (e) {
        const target = e.target.closest('button');
        if (!target) return;

        const id = target.dataset.id;
        if (target.classList.contains('edit-btn')) {
            fetch(`${apiBasePath}/users/get-user.php?id=${id}`, { credentials: 'include' })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.users.length > 0) {
                        const user = data.users[0];
                        showModal('Edit User', user);
                    } else {
                        showNotification('User not found.', false);
                    }
                })
                .catch(err => showNotification('Error: ' + err.message, false));
        } else if (target.classList.contains('archive-btn')) {
            showConfirmation('Are you sure you want to delete this user?', () => {
                fetch(`${apiBasePath}/users/delete-users.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id }),
                    credentials: 'include'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message || 'User archived successfully.');
                        fetchUsers();
                    } else {
                        showNotification('Error archiving user: ' + data.message, false);
                    }
                })
                .catch(err => showNotification('Error: ' + err.message, false));
            });
        }
    });

    //modal for adding/editing users
    function showModal(title, user = null) {
        const id = user ? user.id : null;
        const username = user ? user.username : '';
        const fullName = user ? user.full_name : '';
        const role = user ? user.role : 'cashier';
        const status = user ? user.status : 'active'; // Add status

        const modal = document.createElement('div');
        modal.classList.add('modal');
        modal.innerHTML = `
            <div class="modal-content">
                <span class="close-btn">&times;</span>
                <h2>${title}</h2>
                <form id="userForm">
                    <input type="hidden" name="id" value="${id || ''}">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" placeholder="Username" value="${username}" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" placeholder="${id ? 'New Password (optional)' : 'Password'}" ${id ? '' : 'required'}>
                    </div>
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" name="full_name" placeholder="Full Name" value="${fullName}" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select name="role" required>
                            <option value="admin" ${role === 'admin' ? 'selected' : ''}>Admin</option>
                            <option value="manager" ${role === 'manager' ? 'selected' : ''}>Manager</option>
                            <option value="cashier" ${role === 'cashier' ? 'selected' : ''}>Cashier</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" required>
                            <option value="active" ${status === 'active' ? 'selected' : ''}>Active</option>
                            <option value="inactive" ${status === 'inactive' ? 'selected' : ''}>Inactive</option>
                        </select>
                    </div>
                    <button type="submit">Save User</button>
                </form>
            </div>
        `;
        document.body.appendChild(modal);

        modal.querySelector('.close-btn').addEventListener('click', () => {
            document.body.removeChild(modal);
        });
        
        //close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == modal) {
                if(document.body.contains(modal)){
                    document.body.removeChild(modal);
                }
            }
        }

        modal.querySelector('#userForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            const url = id ? `${apiBasePath}/users/update-users.php` : `${apiBasePath}/users/add-users.php`;
            
            fetch(url, {
                method: 'POST',
                 headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data),
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if(document.body.contains(modal)){
                        document.body.removeChild(modal);
                    }
                    showNotification(data.message || 'Operation successful!');
                    fetchUsers();
                } else {
                    showNotification('Error: ' + data.message, false);
                }
            })
            .catch(error => showNotification('Error: ' + error.message, false));
        });
    }
});