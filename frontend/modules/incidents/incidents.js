window.initIncidents = function () {
    const GATEWAY_URL = 'http://localhost:8000/gateway.php?service=incident&endpoint=';
    const CAT_GATEWAY_URL = 'http://localhost:8000/gateway.php?service=category&endpoint=';

    // --- 1. ELEMENTOS DEL DOM ---
    const containers = {
        list: document.getElementById('incident-list-view'),
        form: document.getElementById('incident-form-view'),
        mapModal: document.getElementById('map-modal')
    };

    const formElements = {
        form: document.getElementById('incident-form'),
        title: document.getElementById('view-title'),
        id: document.getElementById('inc-id'),
        name: document.getElementById('inc-name'),
        date: document.getElementById('inc-date'),
        lat: document.getElementById('inc-lat'),
        lng: document.getElementById('inc-lng'),
        category: document.getElementById('inc-category'),
        ubication: document.getElementById('inc-ubication'),
        status: document.getElementById('inc-status-select'),
        desc: document.getElementById('inc-desc')
    };

    const listBody = document.getElementById('incident-list');
    const toast = document.getElementById('toast');

    // Botones
    const btnAdd = document.getElementById('btn-add-incident');
    const btnBack = document.getElementById('btn-back-list');
    const btnCancel = document.getElementById('btn-cancel-form');
    const btnOpenMap = document.getElementById('btn-open-map');
    const btnCloseMap = document.getElementById('close-map-modal');
    const btnConfirmLoc = document.getElementById('btn-confirm-location');

    // Detalles
    const detailListBody = document.getElementById('detail-items-list');
    const detailInput = document.getElementById('inc-detail-desc');
    const btnAddDetail = document.getElementById('btn-add-detail');

    let isEditMode = false;
    let map = null;
    let marker = null;
    let tempCoords = { lat: null, lng: null };
    let tempDetails = [];

    // --- 2. LÓGICA DEL MAPA (Leaflet) ---

    function initMap() {
        if (map) return;
        const initialPos = [-17.775822, -63.197291];
        map = L.map('map-container').setView(initialPos, 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);
        map.on('click', function (e) {
            placeMarker(e.latlng.lat, e.latlng.lng);
        });
    }

    function placeMarker(lat, lng) {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function (e) {
                const pos = marker.getLatLng();
                tempCoords = { lat: pos.lat, lng: pos.lng };
            });
        }
        tempCoords = { lat, lng };
    }

    function openMapModal() {
        containers.mapModal.classList.add('show');
        initMap();
        setTimeout(() => {
            map.invalidateSize();
            if (formElements.lat.value && formElements.lng.value) {
                const l = parseFloat(formElements.lat.value);
                const g = parseFloat(formElements.lng.value);
                placeMarker(l, g);
                map.setView([l, g], 15);
            }
        }, 200);
    }

    function closeMapModal() {
        containers.mapModal.classList.remove('show');
    }

    function confirmLocation() {
        if (tempCoords.lat && tempCoords.lng) {
            formElements.lat.value = tempCoords.lat.toFixed(6);
            formElements.lng.value = tempCoords.lng.toFixed(6);
            closeMapModal();
        } else {
            alert('Por favor selecciona un punto en el mapa');
        }
    }

    // --- 3. GESTIÓN DE DETALLES TEMPORALES ---

    function renderDetailList() {
        if (!detailListBody) return;
        detailListBody.innerHTML = '';
        tempDetails.forEach((detail, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${detail.description}</td>
                <td style="text-align: right; width: 50px;">
                    <button type="button" class="btn btn-delete" style="padding: 0.2rem 0.5rem;" onclick="removeTempDetail(${index})">&times;</button>
                </td>
            `;
            detailListBody.appendChild(tr);
        });
    }

    function addDetail() {
        const desc = detailInput.value.trim();
        if (!desc) return;
        tempDetails.push({ description: desc });
        detailInput.value = '';
        renderDetailList();
    }

    window.removeTempDetail = (index) => {
        tempDetails.splice(index, 1);
        renderDetailList();
    };

    // --- 4. GESTIÓN DE CATEGORÍAS ---

    async function fetchCategoriesForSelect() {
        try {
            const response = await fetch(CAT_GATEWAY_URL + 'list');
            const data = await response.json();
            if (response.ok && data.success) {
                populateCategorySelect(data.data);
            }
        } catch (error) {
            console.error('Error al cargar categorías:', error);
        }
    }

    function populateCategorySelect(categories) {
        if (!formElements.category) return;
        formElements.category.innerHTML = '<option value="">-- Seleccione una categoría --</option>';
        categories.forEach(cat => {
            const opt = document.createElement('option');
            opt.value = cat.id;
            opt.textContent = cat.name;
            formElements.category.appendChild(opt);
        });
    }

    // --- 5. NAVEGACIÓN ---

    function toggleView(showList = true) {
        if (showList) {
            containers.form.classList.add('hidden');
            containers.list.classList.remove('hidden');
            fetchIncidents();
        } else {
            containers.list.classList.add('hidden');
            containers.form.classList.remove('hidden');
            renderDetailList();
        }
    }

    // --- 6. CRUD ---

    function prepareCreate() {
        isEditMode = false;
        formElements.form.reset();
        formElements.id.value = '';
        formElements.lat.value = '';
        formElements.lng.value = '';
        formElements.category.value = '';
        formElements.title.textContent = 'Nueva Incidencia';
        tempCoords = { lat: null, lng: null };
        tempDetails = [];

        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        formElements.date.value = now.toISOString().slice(0, 16);

        toggleView(false);
    }

    async function handleFormSubmit(e) {
        e.preventDefault();

        const authUser = JSON.parse(localStorage.getItem('auth_user'));
        const userId = authUser ? authUser.id : 1;

        const payload = {
            id: formElements.id.value,
            name: formElements.name.value,
            description: formElements.desc.value,
            date_incident: formElements.date.value,
            latitude: formElements.lat.value,
            longitude: formElements.lng.value,
            category_id: formElements.category.value,
            ubication: formElements.ubication.value,
            state: formElements.status.value,
            details: tempDetails,
            user_id: userId
        };

        const endpoint = isEditMode ? 'update' : 'create';

        try {
            const response = await fetch(GATEWAY_URL + endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                showToast(data.message || 'Éxito');
                toggleView(true);
            } else {
                alert('Error: ' + (data.error || 'Desconocido'));
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    async function fetchIncidents() {
        try {
            const response = await fetch(GATEWAY_URL + 'list');
            const data = await response.json();
            if (response.ok && data.success) renderTable(data.data);
        } catch (error) {
            console.error('Network Error:', error);
        }
    }

    const INCIDENT_STATES = {
        PENDING: 1,
        IN_PROCESS: 2,
        FINISHED: 3
    };

    function renderTable(incidents) {
        if (!listBody) return;
        listBody.innerHTML = '';
        incidents.forEach(inc => {
            const title = inc.title || inc.name || 'Sin título';
            
            // Lógica de estados dinámica
            const state = parseInt(inc.state);
            let stateLabel = 'Pendiente';
            let stateStyle = 'background: rgba(59, 130, 246, 0.2); color: #60a5fa;';

            if (state === INCIDENT_STATES.IN_PROCESS) {
                stateLabel = 'En Proceso';
                stateStyle = 'background: rgba(245, 158, 11, 0.2); color: #fbbf24;';
            } else if (state === INCIDENT_STATES.FINISHED) {
                stateLabel = 'Finalizado';
                stateStyle = 'background: rgba(16, 185, 129, 0.2); color: #34d399;';
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${inc.id}</td>
                <td><strong>${title}</strong></td>
                <td>${inc.description || ''}</td>
                <td><span class="status-badge" style="${stateStyle}">${stateLabel}</span></td>
                <td>${new Date(inc.created_at).toLocaleDateString()}</td>
                <td>
                    <button class="btn btn-edit" onclick="editIncident(${inc.id}, '${title.replace(/'/g, "\\'")}', '${(inc.description || '').replace(/'/g, "\\'")}', '${inc.date_incident || ''}', '${inc.latitude || ''}', '${inc.longitude || ''}', '${(inc.ubication || '').replace(/'/g, "\\'")}', '${inc.state || '1'}', '${inc.category_id || ''}')">Editar</button>
                    <button class="btn btn-delete" onclick="disableIncident(${inc.id})">Eliminar</button>
                </td>
            `;
            listBody.appendChild(tr);
        });
    }

    window.editIncident = async (id, name, description, date, lat, lng, ubication, state, category_id) => {
        isEditMode = true;
        formElements.id.value = id;
        formElements.name.value = name;
        formElements.desc.value = description || '';
        formElements.ubication.value = ubication || '';
        formElements.lat.value = lat || '';
        formElements.lng.value = lng || '';
        formElements.status.value = state || '1';
        formElements.category.value = category_id || '';

        // Cargar detalles desde el servidor
        try {
            const resp = await fetch(GATEWAY_URL + 'details&incident_id=' + id);
            const data = await resp.json();
            if (resp.ok && data.success) {
                tempDetails = data.data.map(d => ({ description: d.description }));
            } else {
                tempDetails = [];
            }
        } catch (err) {
            console.error('Error al cargar detalles:', err);
            tempDetails = [];
        }

        renderDetailList();

        if (date) {
            const d = new Date(date);
            d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
            formElements.date.value = d.toISOString().slice(0, 16);
        }

        formElements.title.textContent = 'Editar Incidencia';
        toggleView(false);
    };

    window.disableIncident = async (id) => {
        if (!confirm('¿Eliminar incidencia?')) return;
        try {
            const response = await fetch(GATEWAY_URL + 'disable', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const data = await response.json();
            if (response.ok && data.success) { fetchIncidents(); showToast('Desactivada'); }
        } catch (error) {
            console.error(error);
        }
    };

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // --- EVENTOS ---
    if (btnAdd) btnAdd.addEventListener('click', prepareCreate);
    if (btnBack) btnBack.addEventListener('click', () => toggleView(true));
    if (btnCancel) btnCancel.addEventListener('click', () => toggleView(true));
    if (btnOpenMap) btnOpenMap.addEventListener('click', openMapModal);
    if (btnCloseMap) btnCloseMap.addEventListener('click', closeMapModal);
    if (btnConfirmLoc) btnConfirmLoc.addEventListener('click', confirmLocation);
    if (btnAddDetail) btnAddDetail.addEventListener('click', addDetail);
    if (formElements.form) formElements.form.addEventListener('submit', handleFormSubmit);

    // --- INICIO ---
    fetchCategoriesForSelect();
    fetchIncidents();
};
