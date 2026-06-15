window.initUsers = function () {
    const USER_GATEWAY = 'http://localhost:8000/gateway.php?service=user&endpoint=';
    const ROL_GATEWAY = 'http://localhost:8000/gateway.php?service=rol&endpoint=';

    // Elementos del DOM
    const modal = document.getElementById('user-modal');
    const btnAdd = document.getElementById('btn-add-user');
    const closeModalBtn = document.getElementById('close-user-modal');
    const form = document.getElementById('user-form');
    const listBody = document.getElementById('user-list');
    const toast = document.getElementById('toast');
    const modalTitle = document.getElementById('user-modal-title');
    const roleSelect = document.getElementById('user-role');
    const pwdInput = document.getElementById('user-password');
    const pwdHint = document.getElementById('password-hint');

    let isEditMode = false;

    // --- FUNCIONES DE ACCIÓN ---

    /**
     * Prepara y abre el modal para crear un nuevo usuario
     */
    function openCreateModal() {
        isEditMode = false;
        modalTitle.textContent = 'Create User';
        if (form) form.reset();
        const userIdInput = document.getElementById('user-id');
        if (userIdInput) userIdInput.value = '';

        if (pwdInput) pwdInput.required = true;
        if (pwdHint) pwdHint.style.display = 'none';
        showModal();
    }

    /**
     * Muestra el modal
     */
    function showModal() {
        if (modal) modal.classList.add('show');
    }

    /**
     * Oculta el modal
     */
    function hideModal() {
        if (modal) modal.classList.remove('show');
    }

    /**
     * Maneja el clic fuera del modal para cerrarlo
     */
    function handleOutsideClick(e) {
        if (e.target === modal) {
            hideModal();
        }
    }

    /**
     * Procesa el envío del formulario (Crear o Editar)
     */
    async function handleFormSubmit(e) {
        e.preventDefault();

        const payload = {
            id: document.getElementById('user-id').value,
            user_name: document.getElementById('user-name').value,
            email: document.getElementById('user-email').value,
            password: pwdInput.value,
            rol_id: roleSelect.value
        };

        if (isEditMode && !payload.password) {
            delete payload.password;
        }

        const endpoint = isEditMode ? 'update' : 'create';

        try {
            const response = await fetch(USER_GATEWAY + endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                hideModal();
                showToast(data.message || 'Action completed successfully');
                fetchUsers();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error saving user:', error);
        }
    }

    // --- EVENT LISTENERS ---

    if (btnAdd) btnAdd.addEventListener('click', openCreateModal);
    if (closeModalBtn) closeModalBtn.addEventListener('click', hideModal);
    window.onclick = handleOutsideClick;
    if (form) form.addEventListener('submit', handleFormSubmit);

    // --- CARGA INICIAL ---
    fetchRolesForSelect();
    fetchUsers();

    // --- FUNCIONES DE DATOS Y RENDERIZADO ---

    async function fetchRolesForSelect() {
        if (!roleSelect) return;
        try {
            const response = await fetch(ROL_GATEWAY + 'list');
            const data = await response.json();

            if (response.ok && data.success) {
                roleSelect.innerHTML = '<option value="" disabled selected>Select a Role</option>';
                data.data.forEach(rol => {
                    const opt = document.createElement('option');
                    opt.value = rol.id;
                    opt.textContent = rol.name;
                    roleSelect.appendChild(opt);
                });
            } else {
                roleSelect.innerHTML = '<option disabled>Error loading roles</option>';
            }
        } catch (error) {
            console.error('Network Error fetching roles:', error);
        }
    }

    async function fetchUsers() {
        try {
            const response = await fetch(USER_GATEWAY + 'list');
            const data = await response.json();

            if (response.ok && data.success) {
                renderTable(data.data);
            } else {
                console.error('Failed to fetch users', data);
            }
        } catch (error) {
            console.error('Network Error fetching users:', error);
        }
    }

    function renderTable(users) {
        if (!listBody) return;
        listBody.innerHTML = '';
        users.forEach(user => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${user.id}</td>
                <td>${user.user_name}</td>
                <td>${user.email}</td>
                <td><span class="status-badge">Role ID: ${user.rol_id}</span></td>
                <td><span class="status-badge">Active</span></td>
                <td>${new Date(user.created_at).toLocaleDateString()}</td>
                <td>
                    <button class="btn btn-edit" onclick="editUser(${user.id}, '${user.user_name.replace(/'/g, "\\'")}', '${user.email.replace(/'/g, "\\'")}', ${user.rol_id})">Edit</button>
                    <button class="btn btn-delete" onclick="disableUser(${user.id})">Disable</button>
                </td>
            `;
            listBody.appendChild(tr);
        });
    }

    window.editUser = (id, userName, email, rolId) => {
        isEditMode = true;
        modalTitle.textContent = 'Edit User';
        if (form) form.reset();
        document.getElementById('user-id').value = id;
        document.getElementById('user-name').value = userName;
        document.getElementById('user-email').value = email;
        roleSelect.value = rolId;

        if (pwdInput) pwdInput.required = false;
        if (pwdHint) pwdHint.style.display = 'block';

        showModal();
    };

    window.disableUser = async (id) => {
        if (!confirm('Are you sure you want to disable this user?')) return;

        try {
            const response = await fetch(USER_GATEWAY + 'disable', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });

            const data = await response.json();
            if (response.ok && data.success) {
                showToast('User disabled');
                fetchUsers();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error disabling user:', error);
        }
    };

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }
};
