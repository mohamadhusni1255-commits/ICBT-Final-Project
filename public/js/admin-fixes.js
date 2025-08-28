/**
 * Admin Panel Fixes
 * Connects all admin panel buttons to backend functionality without modifying HTML/CSS
 */

class AdminFixes {
    constructor() {
        this.baseUrl = window.location.origin;
        this.csrfToken = null;
        this.init();
    }

    init() {
        this.loadCSRFToken();
        this.connectAdminButtons();
        this.setupUserCreation();
        this.setupVideoManagement();
        this.setupCompetitionManagement();
        this.setupJudgeManagement();
    }

    async loadCSRFToken() {
        try {
            const response = await fetch(`${this.baseUrl}/api/auth/csrf-token`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.csrfToken = data.token;
                    console.log('Admin CSRF token loaded');
                }
            }
        } catch (error) {
            console.error('Failed to load admin CSRF token:', error);
        }
    }

    connectAdminButtons() {
        // Connect Create User button
        this.connectCreateUserButton();
        
        // Connect all action buttons in user table
        this.connectUserActionButtons();
        
        // Connect tab functionality
        this.connectTabButtons();
        
        // Connect bulk action buttons
        this.connectBulkActionButtons();
    }

    connectCreateUserButton() {
        // Find Create User button by text content
        const createUserBtn = this.findButtonByText('Create User');
        if (createUserBtn) {
            createUserBtn.addEventListener('click', (e) => this.handleCreateUser(e));
        }

        // Also connect any button with "Add User" text
        const addUserBtn = this.findButtonByText('Add User');
        if (addUserBtn) {
            addUserBtn.addEventListener('click', (e) => this.handleCreateUser(e));
        }
    }

    connectUserActionButtons() {
        // Connect view, edit, delete buttons for users
        document.addEventListener('click', (e) => {
            if (e.target.closest('.action-btn')) {
                const actionBtn = e.target.closest('.action-btn');
                const action = this.getActionType(actionBtn);
                const userId = this.getUserIdFromRow(actionBtn);
                
                if (userId && action) {
                    this.handleUserAction(action, userId);
                }
            }
        });
    }

    connectTabButtons() {
        // Connect tab switching
        document.querySelectorAll('.tab-btn').forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchTab(tab);
            });
        });
    }

    connectBulkActionButtons() {
        // Connect bulk approve/reject/delete buttons
        const bulkApproveBtn = this.findButtonByText('Bulk Approve');
        if (bulkApproveBtn) {
            bulkApproveBtn.addEventListener('click', () => this.handleBulkAction('approve'));
        }

        const bulkRejectBtn = this.findButtonByText('Bulk Reject');
        if (bulkRejectBtn) {
            bulkRejectBtn.addEventListener('click', () => this.handleBulkAction('reject'));
        }

        const bulkDeleteBtn = this.findButtonByText('Bulk Delete');
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', () => this.handleBulkAction('delete'));
        }
    }

    setupUserCreation() {
        // Create user creation modal if it doesn't exist
        this.createUserCreationModal();
        
        // Connect form submission
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'createUserForm') {
                e.preventDefault();
                this.submitCreateUser(e.target);
            }
        });
    }

    createUserCreationModal() {
        if (document.getElementById('createUserModal')) return;

        const modal = document.createElement('div');
        modal.id = 'createUserModal';
        modal.className = 'modal';
        modal.style.cssText = `
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        `;

        modal.innerHTML = `
            <div class="modal-content" style="
                background-color: #fefefe;
                margin: 15% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
                max-width: 500px;
                border-radius: 8px;
            ">
                <span class="close" style="
                    color: #aaa;
                    float: right;
                    font-size: 28px;
                    font-weight: bold;
                    cursor: pointer;
                ">&times;</span>
                <h2>Create New User</h2>
                <form id="createUserForm">
                    <div style="margin-bottom: 15px;">
                        <label>Username:</label>
                        <input type="text" name="username" required style="width: 100%; padding: 8px; margin-top: 5px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>Email:</label>
                        <input type="email" name="email" required style="width: 100%; padding: 8px; margin-top: 5px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>Password:</label>
                        <input type="password" name="password" required style="width: 100%; padding: 8px; margin-top: 5px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>Role:</label>
                        <select name="role" required style="width: 100%; padding: 8px; margin-top: 5px;">
                            <option value="user">User</option>
                            <option value="judge">Judge</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label>Age Group:</label>
                        <select name="age_group" required style="width: 100%; padding: 8px; margin-top: 5px;">
                            <option value="13-17">13-17</option>
                            <option value="18-25">18-25</option>
                            <option value="26-35">26-35</option>
                            <option value="36+">36+</option>
                        </select>
                    </div>
                    <button type="submit" style="
                        background-color: #4361ee;
                        color: white;
                        padding: 10px 20px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                    ">Create User</button>
                </form>
            </div>
        `;

        document.body.appendChild(modal);

        // Add close functionality
        const closeBtn = modal.querySelector('.close');
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        // Close modal when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    }

    setupVideoManagement() {
        // Connect video management buttons
        this.connectVideoActionButtons();
    }

    setupCompetitionManagement() {
        // Connect competition management buttons
        this.connectCompetitionActionButtons();
    }

    setupJudgeManagement() {
        // Connect judge management buttons
        this.connectJudgeActionButtons();
    }

    async handleCreateUser(event) {
        event.preventDefault();
        
        // Show the modal
        const modal = document.getElementById('createUserModal');
        if (modal) {
            modal.style.display = 'block';
        }
    }

    async submitCreateUser(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Creating...';

            const userData = {
                username: formData.get('username'),
                email: formData.get('email'),
                password: formData.get('password'),
                role: formData.get('role'),
                age_group: formData.get('age_group')
            };

            const response = await fetch(`${this.baseUrl}/api/admin/create_user`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify(userData)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.showSuccess('User created successfully!');
                form.reset();
                
                // Close modal
                const modal = document.getElementById('createUserModal');
                if (modal) {
                    modal.style.display = 'none';
                }

                // Refresh user list
                this.loadAdminUsers();
            } else {
                this.showError(data.error || 'Failed to create user');
            }
        } catch (error) {
            console.error('Create user error:', error);
            this.showError('Network error. Please try again.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    async handleUserAction(action, userId) {
        switch (action) {
            case 'view':
                await this.viewUser(userId);
                break;
            case 'edit':
                await this.editUser(userId);
                break;
            case 'delete':
                await this.deleteUser(userId);
                break;
        }
    }

    async viewUser(userId) {
        try {
            const response = await fetch(`${this.baseUrl}/api/admin/users/${userId}`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.showUserDetails(data.user);
                }
            }
        } catch (error) {
            console.error('View user error:', error);
        }
    }

    async editUser(userId) {
        // Implement user editing functionality
        console.log('Edit user:', userId);
        this.showError('User editing not yet implemented');
    }

    async deleteUser(userId) {
        if (!confirm('Are you sure you want to delete this user?')) {
            return;
        }

        try {
            const response = await fetch(`${this.baseUrl}/api/admin/users/${userId}`, {
                method: 'DELETE',
                credentials: 'include',
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.showSuccess('User deleted successfully!');
                    this.loadAdminUsers(); // Refresh list
                } else {
                    this.showError(data.error || 'Failed to delete user');
                }
            }
        } catch (error) {
            console.error('Delete user error:', error);
            this.showError('Network error. Please try again.');
        }
    }

    async handleBulkAction(action) {
        const selectedUsers = this.getSelectedUsers();
        
        if (selectedUsers.length === 0) {
            this.showError('Please select users first');
            return;
        }

        try {
            const response = await fetch(`${this.baseUrl}/api/admin/users/bulk_action`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({
                    action: action,
                    user_ids: selectedUsers
                })
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.showSuccess(`Bulk ${action} completed successfully!`);
                    this.loadAdminUsers(); // Refresh list
                } else {
                    this.showError(data.error || `Bulk ${action} failed`);
                }
            }
        } catch (error) {
            console.error(`Bulk ${action} error:`, error);
            this.showError('Network error. Please try again.');
        }
    }

    switchTab(clickedTab) {
        // Remove active class from all tabs
        document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
        
        // Add active class to clicked tab
        clickedTab.classList.add('active');
        
        // Load content based on the tab
        const tabName = clickedTab.textContent.toLowerCase();
        this.loadTabContent(tabName);
    }

    async loadTabContent(tabName) {
        switch (tabName) {
            case 'users':
                await this.loadAdminUsers();
                break;
            case 'videos':
                await this.loadAdminVideos();
                break;
            case 'competitions':
                await this.loadAdminCompetitions();
                break;
            case 'judges':
                await this.loadAdminJudges();
                break;
            case 'settings':
                this.loadAdminSettings();
                break;
        }
    }

    async loadAdminUsers() {
        try {
            const response = await fetch(`${this.baseUrl}/api/admin/users`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.displayAdminUsers(data.users);
                }
            }
        } catch (error) {
            console.error('Failed to load admin users:', error);
        }
    }

    async loadAdminVideos() {
        try {
            const response = await fetch(`${this.baseUrl}/api/admin/videos`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.displayAdminVideos(data.videos);
                }
            }
        } catch (error) {
            console.error('Failed to load admin videos:', error);
        }
    }

    async loadAdminCompetitions() {
        try {
            const response = await fetch(`${this.baseUrl}/api/admin/competitions`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.displayAdminCompetitions(data.competitions);
                }
            }
        } catch (error) {
            console.error('Failed to load admin competitions:', error);
        }
    }

    async loadAdminJudges() {
        try {
            const response = await fetch(`${this.baseUrl}/api/admin/users?role=judge`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.displayAdminUsers(data.users);
                }
            }
        } catch (error) {
            console.error('Failed to load admin judges:', error);
        }
    }

    loadAdminSettings() {
        // Load admin settings
        console.log('Loading admin settings');
    }

    displayAdminUsers(users) {
        const tbody = document.getElementById('usersTableBody');
        if (!tbody) return;

        if (!users || users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px;">
                        <div style="color: var(--gray-500);">
                            <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 10px;"></i>
                            <p>No users found</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = users.map(user => `
            <tr>
                <td>
                    <div class="user-info">
                        <div class="user-avatar">${this.getUserInitials(user.full_name || user.email)}</div>
                        ${user.full_name || 'Unknown User'}
                    </div>
                </td>
                <td>${user.email}</td>
                <td>${user.role || 'User'}</td>
                <td><span class="status ${user.status || 'active'}">${user.status || 'Active'}</span></td>
                <td>${this.formatDate(user.created_at)}</td>
                <td>
                    <button class="action-btn view" data-user-id="${user.id}"><i class="fas fa-eye"></i></button>
                    <button class="action-btn edit" data-user-id="${user.id}"><i class="fas fa-edit"></i></button>
                    <button class="action-btn delete" data-user-id="${user.id}"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    }

    displayAdminVideos(videos) {
        // Implement video display logic
        console.log('Displaying admin videos:', videos);
    }

    displayAdminCompetitions(competitions) {
        // Implement competition display logic
        console.log('Displaying admin competitions:', competitions);
    }

    // Utility methods
    findButtonByText(text) {
        const buttons = Array.from(document.querySelectorAll('button'));
        return buttons.find(btn => btn.textContent.trim() === text);
    }

    getActionType(button) {
        if (button.classList.contains('view')) return 'view';
        if (button.classList.contains('edit')) return 'edit';
        if (button.classList.contains('delete')) return 'delete';
        return null;
    }

    getUserIdFromRow(button) {
        const row = button.closest('tr');
        if (row) {
            const actionBtn = row.querySelector('.action-btn');
            return actionBtn?.getAttribute('data-user-id');
        }
        return null;
    }

    getSelectedUsers() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    }

    getUserInitials(name) {
        if (!name) return 'U';
        return name.split(' ').map(n => n.charAt(0)).join('').toUpperCase().substring(0, 2);
    }

    formatDate(dateString) {
        if (!dateString) return 'Unknown';
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    showUserDetails(user) {
        // Show user details in a modal or alert
        alert(`User: ${user.full_name || user.email}\nRole: ${user.role}\nStatus: ${user.status}`);
    }

    showSuccess(message) {
        // Show success message
        alert(`Success: ${message}`);
    }

    showError(message) {
        // Show error message
        alert(`Error: ${message}`);
    }
}

// Initialize admin fixes when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (window.location.pathname.includes('admin_panel')) {
        window.adminFixes = new AdminFixes();
    }
});
