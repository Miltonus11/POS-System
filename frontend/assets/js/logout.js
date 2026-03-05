// send logout request then redirect
const logoutLink = document.getElementById('logoutLink');
logoutLink.addEventListener('click', function(e) {
    e.preventDefault();
    fetch('../../../backend/api/auth.php?action=logout')
        .then(r => r.json())
        .then(data => {
            // always navigate back to login/index
            window.location.href = '../../../index.php';
        })
        .catch(err => {
            console.error('Logout failed', err);
            window.location.href = '../../../index.php';
        });
});