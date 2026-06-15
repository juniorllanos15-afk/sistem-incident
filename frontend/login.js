const GATEWAY_URL = '../api-gateway/gateway.php?service=user&endpoint=login';

document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;
    const errorBox = document.getElementById('login-error');

    errorBox.style.display = 'none';

    try {
        const response = await fetch(GATEWAY_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Guardamos al usuario de forma segura en local storage
            localStorage.setItem('auth_user', JSON.stringify(data.data));
            // Redirigimos a la aplicación principal
            window.location.href = 'index.html';
        } else {
            errorBox.textContent = data.error || 'Credenciales incorrectas';
            errorBox.style.display = 'block';
        }
    } catch (error) {
        console.error('Error in login:', error);
        errorBox.textContent = 'Error de comunicación con el servicio de Usuarios';
        errorBox.style.display = 'block';
    }
});
