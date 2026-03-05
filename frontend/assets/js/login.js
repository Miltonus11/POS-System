document.getElementById("loginForm").addEventListener("submit", function(e){
    e.preventDefault();

    const formData = new FormData(this);

    fetch("./backend/api/auth.php?action=login", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const msg = document.getElementById("message");

        if(data.success){
            // redirect based on user role
            const role = data.role || '';
            if(role === 'admin') {
                window.location.href = "./frontend/views/admin/admin-dashboard.php";
            } else if (role === 'manager') {
                window.location.href = "./frontend/views/manager/manager-dashboard.php";
            } else {
                window.location.href = "./index.php";
            }
        } else {
            msg.style.color = "red";
            msg.innerText = data.message;
        }
    })
    .catch(err => {
        const msg = document.getElementById("message");
        msg.style.color = "red";
        msg.innerText = "An unexpected error occurred. Please try again.";
        console.error(err);
    });
});
