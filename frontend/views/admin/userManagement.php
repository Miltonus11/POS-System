<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
    <link rel="stylesheet" href="../../assets/css/userManagement.css">
</head>
<body>
    <div class="container">
        <h1>User Management</h1>
        <a href="admin-dashboard.php">Back to Dashboard</a>
        <a href="#" id="logoutLink">Logout</a>

        <div class="message success-message">Success message will be shown here</div>
        <div class="message error-message">Error message will be shown here</div>

        <h2>View Users</h2>
        <form>
            <input type="text" name="search" placeholder="Search by username or role">
            <button type="submit">Search</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>SampleUser</td>
                    <td>manager</td>
                    <td>
                        <form class="inline-form">
                            <input type="text" name="username" value="SampleUser">
                            <input type="text" name="role" value="manager">
                            <button type="submit">Update</button>
                        </form>
                        <form class="inline-form">
                            <button type="submit" onclick="return confirm('Are you sure you want to archive this user?');">Archive</button>
                        </form>
                    </td>
                </tr>
                <tr>
                    <td>AnotherUser</td>
                    <td>staff</td>
                    <td>
                        <form class="inline-form">
                            <input type="text" name="username" value="AnotherUser">
                            <input type="text" name="role" value="staff">
                            <button type="submit">Update</button>
                        </form>
                        <form class="inline-form">
                            <button type="submit" onclick="return confirm('Are you sure you want to archive this user?');">Archive</button>
                        </form>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="add-user-form">
            <h2>Add User</h2>
            <form>
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="role">
                    <option value="manager">Manager</option>
                    <option value="staff">Staff</option>
                </select>
                <button type="submit">Add User</button>
            </form>
        </div>
    </div>

<script src="../../assets/js/logout.js"></script>
</body>
</html>
