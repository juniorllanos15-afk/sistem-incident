window.initRoles = function() {
    const GATEWAY_URL = 'http://localhost:8000/gateway.php?service=rol&endpoint=';

    // Elementos del DOM
    const modal = document.getElementById('role-modal');
    const btnAdd = document.getElementById('btn-add-role');
    const closeModalBtn = document.getElementById('close-role-modal');
    const form = document.getElementById('role-form');
    const listBody = document.getElementById('role-list');
    const toast = document.getElementById('toast');
    const modalTitle = document.getElementById('role-modal-title');

    let isEditMode = false;

    // --- FUNCIONES DE ACCIÓN ---

    /**
     * Prepara y abre el modal para crear un nuevo rol
     */
    function openCreateModal() {
        isEditMode = false;
        modalTitle.textContent = 'Create Role';
        if (form) form.reset();
        const roleIdInput = document.getElementById('role-id');
        if (roleIdInput) roleIdInput.value = '';
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
        const id = document.getElementById('role-id').value;
        const name = document.getElementById('role-name').value;
        const description = document.getElementById('role-desc').value;

        const payload = { name, description };
        if (isEditMode) payload.id = id;

        const endpoint = isEditMode ? 'update' : 'create';

        try {
            const response = await fetch(GATEWAY_URL + endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                hideModal();
                showToast(data.message || 'Action completed successfully');
                fetchRoles();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error saving role:', error);
        }
    }

    // --- EVENT LISTENERS ---

    if (btnAdd) btnAdd.addEventListener('click', openCreateModal);
    if (closeModalBtn) closeModalBtn.addEventListener('click', hideModal);
    window.onclick = handleOutsideClick;
    if (form) form.addEventListener('submit', handleFormSubmit);

    // --- CARGA INICIAL ---
    fetchRoles();

    // --- FUNCIONES DE DATOS Y RENDERIZADO ---

    async function fetchRoles() {
        try {
            const response = await fetch(GATEWAY_URL + 'list');
            const data = await response.json();

            if (response.ok && data.success) {
                renderTable(data.data);
            } else {
                console.error('Failed to fetch', data);
            }
        } catch (error) {
            console.error('Network Error:', error);
        }
    }

    function renderTable(roles) {
        if (!listBody) return;
        listBody.innerHTML = '';
        roles.forEach(rol => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${rol.id}</td>
                <td>${rol.name}</td>
                <td>${rol.description || ''}</td>
                <td><span class="status-badge">Active</span></td>
                <td>${new Date(rol.created_at).toLocaleDateString()}</td>
                <td>
                    <button class="btn btn-edit" onclick="editRole(${rol.id}, '${rol.name.replace(/'/g, "\\'")}', '${(rol.description || '').replace(/'/g, "\\'")}')">Edit</button>
                    <button class="btn btn-delete" onclick="disableRole(${rol.id})">Disable</button>
                </td>
            `;
            listBody.appendChild(tr);
        });
    }

    window.editRole = (id, name, description) => {
        isEditMode = true;
        modalTitle.textContent = 'Edit Role';
        document.getElementById('role-id').value = id;
        document.getElementById('role-name').value = name;
        document.getElementById('role-desc').value = description || '';
        showModal();
    };

    window.disableRole = async (id) => {
        if (!confirm('Are you sure you want to disable this role?')) return;

        try {
            const response = await fetch(GATEWAY_URL + 'disable', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });

            const data = await response.json();
            if (response.ok && data.success) {
                showToast('Role disabled');
                fetchRoles();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error disabling role:', error);
        }
    };

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }
};
