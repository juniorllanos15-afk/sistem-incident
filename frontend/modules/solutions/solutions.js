// solutions.js
window.initSolutions = function() {
    const GATEWAY_URL = 'http://localhost:8000/gateway.php';
    const authUser = JSON.parse(localStorage.getItem('auth_user'));

    const views = {
        denied: document.getElementById('solution-access-denied'),
        main: document.getElementById('solution-main-view'),
        form: document.getElementById('solution-form-view')
    };

    const elements = {
        assignedList: document.getElementById('assigned-incidents-list'),
        headerInfo: document.getElementById('incident-header-info'),
        detailsContainer: document.getElementById('incident-details-solutions-container'),
        form: document.getElementById('solution-form'),
        solIncidentId: document.getElementById('sol-incident-id'),
        solAsigDetailId: document.getElementById('sol-asig-detail-id')
    };

    const buttons = {
        back: document.getElementById('btn-back-solutions'),
        cancel: document.getElementById('btn-cancel-solutions')
    };

    let technicalRolIds = [];
    let currentIncidentDetails = [];
    let existingSolutions = [];

    // --- SEGURIDAD Y ACCESO ---

    async function checkAccess() {
        try {
            // Obtener roles para validar si es técnico
            const res = await fetch(`${GATEWAY_URL}?service=rol&endpoint=list`);
            const data = await res.json();
            if (data.success) {
                technicalRolIds = data.data
                    .filter(r => r.name.toLowerCase().includes('tecnico'))
                    .map(r => r.id);
                
                if (technicalRolIds.includes(authUser.rol_id)) {
                    views.denied.classList.add('hidden');
                    views.main.classList.remove('hidden');
                    loadMyAssignments();
                } else {
                    views.main.classList.add('hidden');
                    views.denied.classList.remove('hidden');
                }
            }
        } catch (error) {
            console.error('Error checking access:', error);
        }
    }

    // --- CARGA DE DATOS ---

    async function loadMyAssignments() {
        try {
            const res = await fetch(`${GATEWAY_URL}?service=assignment&endpoint=technician-assignments&technician_id=${authUser.id}`);
            const data = await res.json();
            if (data.success) {
                renderAssignments(data.data);
            }
        } catch (error) {
            console.error('Error loading assignments:', error);
        }
    }

    async function renderAssignments(list) {
        elements.assignedList.innerHTML = '';
        if (list.length === 0) {
            elements.assignedList.innerHTML = '<tr><td colspan="4">No tienes incidencias asignadas.</td></tr>';
            return;
        }

        // Obtener títulos de incidencias para mejor visualización
        const incRes = await fetch(`${GATEWAY_URL}?service=incident&endpoint=list`);
        const incData = await incRes.json();
        const incidentsMap = {};
        if (incData.success) {
            incData.data.forEach(i => incidentsMap[i.id] = i);
        }

        list.forEach(asig => {
            const inc = incidentsMap[asig.incident_id] || { title: 'Desconocida' };
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${asig.assignments_id}</td>
                <td>#${asig.incident_id} - ${inc.title}</td>
                <td>${new Date(asig.created_at).toLocaleDateString()}</td>
                <td><button class="btn btn-edit" onclick="openSolutionForm(${asig.incident_id}, ${asig.id})">Resolver</button></td>
            `;
            elements.assignedList.appendChild(tr);
        });
    }

    window.openSolutionForm = async (incidentId, asigDetailId) => {
        views.main.classList.add('hidden');
        views.form.classList.remove('hidden');
        
        elements.solIncidentId.value = incidentId;
        elements.solAsigDetailId.value = asigDetailId;

        try {
            // 1. Cargar cabecera de incidencia
            const incRes = await fetch(`${GATEWAY_URL}?service=incident&endpoint=list`);
            const incData = await incRes.json();
            const incident = incData.data.find(i => i.id == incidentId);
            if (incident) {
                elements.headerInfo.innerHTML = `
                    <h3>${incident.title}</h3>
                    <p>${incident.description}</p>
                    <small>Ubicación: ${incident.ubication} | Fecha: ${new Date(incident.date_incident).toLocaleString()}</small>
                `;
            }

            // 2. Cargar detalles
            const detRes = await fetch(`${GATEWAY_URL}?service=incident&endpoint=details&incident_id=${incidentId}`);
            const detData = await detRes.json();
            currentIncidentDetails = detData.data;

            // 3. Cargar soluciones existentes para marcar qué falta
            const solRes = await fetch(`${GATEWAY_URL}?service=solution&endpoint=list&incident_id=${incidentId}`);
            const solData = await solRes.json();
            existingSolutions = solData.success ? solData.data : [];

            renderFormFields();
        } catch (error) {
            console.error('Error opening solution form:', error);
        }
    };

    function renderFormFields() {
        elements.detailsContainer.innerHTML = '';
        currentIncidentDetails.forEach(detail => {
            const existing = existingSolutions.find(s => s.incident_detail_id == detail.id);
            const div = document.createElement('div');
            div.className = 'form-group card';
            div.style.marginBottom = '1rem';
            div.style.background = existing ? 'rgba(74, 222, 128, 0.05)' : 'rgba(255, 255, 255, 0.03)';
            
            div.innerHTML = `
                <label style="color: var(--primary-color);">Detalle #${detail.id}</label>
                <p style="font-size: 0.9rem; margin-bottom: 0.5rem;">${detail.description}</p>
                <textarea 
                    name="sol_${detail.id}" 
                    class="form-control" 
                    placeholder="Escribe la solución..." 
                    ${existing ? 'disabled' : ''}
                >${existing ? existing.solution : ''}</textarea>
                ${existing ? '<small style="color: #4ade80;">Ya solucionado</small>' : ''}
            `;
            elements.detailsContainer.appendChild(div);
        });
    }

    async function handleSolutionSubmit(e) {
        e.preventDefault();
        
        const incidentId = elements.solIncidentId.value;
        const asigDetailId = elements.solAsigDetailId.value;
        const formData = new FormData(elements.form);
        const solutionsToSend = [];

        currentIncidentDetails.forEach(detail => {
            const solText = formData.get(`sol_${detail.id}`);
            const alreadySolved = existingSolutions.find(s => s.incident_detail_id == detail.id);
            
            if (solText && !alreadySolved) {
                solutionsToSend.push({
                    incident_id: incidentId,
                    incident_detail_id: detail.id,
                    assignments_detail_id: asigDetailId,
                    solution: solText
                });
            }
        });

        if (solutionsToSend.length === 0) {
            alert('No hay nuevas soluciones para guardar');
            return;
        }

        try {
            const res = await fetch(`${GATEWAY_URL}?service=solution&endpoint=create`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    incident_id: incidentId,
                    solutions: solutionsToSend
                })
            });

            const result = await res.json();
            if (result.success) {
                showToast('Soluciones guardadas exitosamente');
                
                // Verificar si se completó la incidencia
                const totalDetails = currentIncidentDetails.length;
                const totalSolved = (result.solved_details ? result.solved_details.length : 0);
                
                if (totalSolved >= totalDetails) {
                    await closeIncident(incidentId);
                }
                
                toggleView(true);
            }
        } catch (error) {
            console.error('Error submitting solutions:', error);
        }
    }

    async function closeIncident(id) {
        try {
            await fetch(`${GATEWAY_URL}?service=incident&endpoint=changeState`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, state: 3 })
            });
            showToast('¡Incidencia Finalizada automáticamente!');
        } catch (error) {
            console.error('Error closing incident:', error);
        }
    }

    function toggleView(showList) {
        if (showList) {
            views.form.classList.add('hidden');
            views.main.classList.remove('hidden');
            loadMyAssignments();
        } else {
            views.main.classList.add('hidden');
            views.form.classList.remove('hidden');
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

    // --- EVENT LISTENERS ---
    buttons.back.onclick = () => toggleView(true);
    buttons.cancel.onclick = () => toggleView(true);
    elements.form.onsubmit = handleSolutionSubmit;

    // --- INICIALIZACIÓN ---
    checkAccess();
};
