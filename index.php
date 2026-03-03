<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./frontend/assets/css/login.css">
</head>
<body>

<div class="login-box">
    <h2>Login</h2>

    <form id="loginForm">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>

    <div class="message" id="message"></div>
</div>

<script src="./frontend/assets/js/login.js"></script>
</body>
</html>
