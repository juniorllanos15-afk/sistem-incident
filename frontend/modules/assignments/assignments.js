// assignments.js
window.initAssignments = function() {
    const GATEWAY_URL = 'http://localhost:8000/gateway.php';
    
    const containers = {
        list: document.getElementById('assignment-list-view'),
        form: document.getElementById('assignment-form-view')
    };

    const elements = {
        form: document.getElementById('assignment-form'),
        incidentSelect: document.getElementById('asig-incident'),
        prioritySelect: document.getElementById('asig-priority'),
        techSelect: document.getElementById('asig-tech-select'),
        selectedTechsTable: document.getElementById('selected-techs-list'),
        mainList: document.getElementById('assignment-list')
    };

    const buttons = {
        add: document.getElementById('btn-add-assignment'),
        back: document.getElementById('btn-back-assignments'),
        cancel: document.getElementById('btn-cancel-asig'),
        addTech: document.getElementById('btn-add-tech-to-list')
    };

    let selectedTechs = []; // Arreglo de IDs de técnicos seleccionados temporalmente
    let cachedTechs = []; // Guardar nombres de técnicos para visualización
    let allUsers = [];
    let technicalRolIds = [];

    // --- CARGA DE DATOS SELECTORES ---

    async function loadInitialData() {
        try {
            // 1. Obtener Roles para filtrar dinámicamente "tecnico"
            const rolRes = await fetch(`${GATEWAY_URL}?service=rol&endpoint=list`);
            const rolData = await rolRes.json();
            if (rolData.success) {
                technicalRolIds = rolData.data
                    .filter(r => r.name.toLowerCase().includes('tecnico'))
                    .map(r => r.id);
            }

            // 2. Obtener Usuarios
            const userRes = await fetch(`${GATEWAY_URL}?service=user&endpoint=list`);
            const userData = await userRes.json();
            if (userData.success) {
                allUsers = userData.data;
                populateTechSelect();
            }

            // 3. Obtener Incidencias
            const incRes = await fetch(`${GATEWAY_URL}?service=incident&endpoint=list`);
            const incData = await incRes.json();
            if (incData.success) {
                populateIncidentSelect(incData.data);
            }
        } catch (error) {
            console.error('Error loading assignment data:', error);
        }
    }

    function populateTechSelect() {
        elements.techSelect.innerHTML = '<option value="">-- Seleccionar Técnico --</option>';
        const techUsers = allUsers.filter(u => technicalRolIds.includes(u.rol_id));
        techUsers.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = u.user_name;
            elements.techSelect.appendChild(opt);
        });
    }

    function populateIncidentSelect(incidents) {
        elements.incidentSelect.innerHTML = '<option value="">-- Seleccionar Incidencia --</option>';
        incidents.forEach(i => {
            const statusLabel = i.state == 3 ? '[FINALIZADO]' : '[PENDIENTE]';
            const opt = document.createElement('option');
            opt.value = i.id;
            opt.textContent = `${statusLabel} #${i.id} - ${i.title}`;
            elements.incidentSelect.appendChild(opt);
        });
    }

    // --- GESTIÓN DE TÉCNICOS EN TABLA TEMPORAL ---

    function addTechToList() {
        const techId = parseInt(elements.techSelect.value);
        if (!techId) return;

        if (selectedTechs.includes(techId)) {
            alert('Este técnico ya ha sido añadido');
            return;
        }

        const user = allUsers.find(u => u.id === techId);
        selectedTechs.push(techId);
        renderSelectedTechs();
    }

    function renderSelectedTechs() {
        elements.selectedTechsTable.innerHTML = '';
        selectedTechs.forEach(id => {
            const user = allUsers.find(u => u.id === id);
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${user ? user.user_name : 'ID: ' + id}</td>
                <td><button type="button" class="btn btn-delete" onclick="removeTechFromList(${id})">&times;</button></td>
            `;
            elements.selectedTechsTable.appendChild(tr);
        });
    }

    window.removeTechFromList = (id) => {
        selectedTechs = selectedTechs.filter(t => t !== id);
        renderSelectedTechs();
    };

    // --- CRUD Y NAVEGACIÓN ---

    async function fetchAssignments() {
        try {
            const res = await fetch(`${GATEWAY_URL}?service=assignment&endpoint=list`);
            const data = await res.json();
            if (data.success) {
                renderAssignments(data.data);
            }
        } catch (error) {
            console.error('Error fetching assignments:', error);
        }
    }

    function renderAssignments(list) {
        elements.mainList.innerHTML = '';
        list.forEach(asig => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${asig.id}</td>
                <td>Incidencia #${asig.incident_id}</td>
                <td><span class="status-badge" style="background: rgba(255,255,255,0.1);">${asig.priority}</span></td>
                <td>${new Date(asig.assignment_date).toLocaleString()}</td>
                <td><button class="btn btn-edit" onclick="viewAsigDetails(${asig.id})">Ver Técnicos</button></td>
                <td><span class="status-badge" style="color: #4ade80;">${asig.state_assignments == 1 ? 'Activo' : 'Inactivo'}</span></td>
            `;
            elements.mainList.appendChild(tr);
        });
    }

    async function handleFormSubmit(e) {
        e.preventDefault();

        if (selectedTechs.length === 0) {
            alert('Debe asignar al menos un técnico');
            return;
        }

        const authUser = JSON.parse(localStorage.getItem('auth_user'));
        
        const payload = {
            incident_id: elements.incidentSelect.value,
            priority: elements.prioritySelect.value,
            assigned_by: authUser ? authUser.id : 1,
            technicians: selectedTechs
        };

        try {
            const res = await fetch(`${GATEWAY_URL}?service=assignment&endpoint=create`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await res.json();
            if (res.ok && result.success) {
                showToast('Asignación registrada con éxito');
                toggleView(true);
            } else {
                alert('Error: ' + (result.error || 'No se pudo guardar'));
            }
        } catch (error) {
            console.error('Error saving assignment:', error);
        }
    }

    function toggleView(showList) {
        if (showList) {
            containers.form.classList.add('hidden');
            containers.list.classList.remove('hidden');
            fetchAssignments();
        } else {
            containers.list.classList.add('hidden');
            containers.form.classList.remove('hidden');
            selectedTechs = [];
            renderSelectedTechs();
            loadInitialData();
        }
    }

    function showToast(msg) {
        const t = document.getElementById('toast');
        if (t) {
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }
    }

    window.viewAsigDetails = async (id) => {
        try {
            const res = await fetch(`${GATEWAY_URL}?service=assignment&endpoint=details&id=${id}`);
            const data = await res.json();
            if (data.success) {
                const names = data.data.map(d => {
                    const u = allUsers.find(user => user.id === d.technician_id);
                    return u ? u.user_name : `ID:${d.technician_id}`;
                }).join(', ');
                alert(`Técnicos Asignados: ${names}`);
            }
        } catch (error) {
            console.error(error);
        }
    };

    // --- EVENT LISTENERS ---
    buttons.add.onclick = () => toggleView(false);
    buttons.back.onclick = () => toggleView(true);
    buttons.cancel.onclick = () => toggleView(true);
    buttons.addTech.onclick = addTechToList;
    elements.form.onsubmit = handleFormSubmit;

    // --- INICIALIZACIÓN ---
    fetchAssignments();
};
