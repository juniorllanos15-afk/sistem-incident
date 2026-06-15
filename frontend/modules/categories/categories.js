window.initCategories = function() {
    const GATEWAY_URL = 'http://localhost:8000/gateway.php?service=category&endpoint=';

    // Elementos del DOM
    const modal = document.getElementById('category-modal');
    const btnAdd = document.getElementById('btn-add-category');
    const closeModalBtn = document.getElementById('close-modal');
    const form = document.getElementById('category-form');
    const listBody = document.getElementById('category-list');
    const toast = document.getElementById('toast');
    const modalTitle = document.getElementById('modal-title');

    let isEditMode = false;

    // --- FUNCIONES DE ACCIÓN ---

    /**
     * Prepara y abre el modal para crear una nueva categoría
     */
    function openCreateModal() {
        isEditMode = false;
        modalTitle.textContent = 'Create Category';
        if (form) form.reset();
        const catIdInput = document.getElementById('cat-id');
        if (catIdInput) catIdInput.value = '';
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
        const id = document.getElementById('cat-id').value;
        const name = document.getElementById('cat-name').value;
        const description = document.getElementById('cat-desc').value;

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
                fetchCategories();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error saving category:', error);
        }
    }

    // --- EVENT LISTENERS ---

    if (btnAdd) btnAdd.addEventListener('click', openCreateModal);
    if (closeModalBtn) closeModalBtn.addEventListener('click', hideModal);
    window.onclick = handleOutsideClick;
    if (form) form.addEventListener('submit', handleFormSubmit);

    // --- CARGA INICIAL ---
    fetchCategories();

    // --- FUNCIONES DE DATOS Y RENDERIZADO ---

    async function fetchCategories() {
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

    function renderTable(categories) {
        if (!listBody) return;
        listBody.innerHTML = '';
        categories.forEach(cat => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${cat.id}</td>
                <td>${cat.name}</td>
                <td>${cat.description || ''}</td>
                <td><span class="status-badge">Active</span></td>
                <td>${new Date(cat.created_at).toLocaleDateString()}</td>
                <td>
                    <button class="btn btn-edit" onclick="editCategory(${cat.id}, '${cat.name.replace(/'/g, "\\'")}', '${(cat.description || '').replace(/'/g, "\\'")}')">Edit</button>
                    <button class="btn btn-delete" onclick="disableCategory(${cat.id})">Disable</button>
                </td>
            `;
            listBody.appendChild(tr);
        });
    }

    window.editCategory = (id, name, description) => {
        isEditMode = true;
        modalTitle.textContent = 'Edit Category';
        document.getElementById('cat-id').value = id;
        document.getElementById('cat-name').value = name;
        document.getElementById('cat-desc').value = description || '';
        showModal();
    };

    window.disableCategory = async (id) => {
        if (!confirm('Are you sure you want to disable this category?')) return;

        try {
            const response = await fetch(GATEWAY_URL + 'disable', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });

            const data = await response.json();
            if (response.ok && data.success) {
                showToast('Category disabled');
                fetchCategories();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error disabling category:', error);
        }
    };

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }
};
