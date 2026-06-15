/**
 * ARCHIVO PRINCIPAL DE LA APLICACIÓN (app.js)
 * Gestiona el enrutamiento, la autenticación y la carga de módulos.
 */

// 1. Ejecución inmediata: Guardia de ruta
checkAuthentication();

// 2. Eventos iniciales
document.addEventListener('DOMContentLoaded', initializeApplication);

// --- FUNCIONES DE AUTENTICACIÓN ---

/**
 * Verifica si el usuario está logueado, sino lo redirige al login.
 */
function checkAuthentication() {
    const authUser = localStorage.getItem('auth_user');
    if (!authUser) {
        window.location.href = 'login.html';
    }
}

/**
 * Maneja el cierre de sesión del usuario.
 */
function handleLogout(e) {
    e.preventDefault();
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        localStorage.removeItem('auth_user');
        window.location.href = 'login.html';
    }
}

// --- FUNCIONES DE INICIALIZACIÓN ---

/**
 * Configura los listeners globales una vez que el DOM está listo.
 */
function initializeApplication() {
    // Listener para el botón de Logout
    const btnLogout = document.getElementById('nav-logout');
    if (btnLogout) {
        btnLogout.addEventListener('click', handleLogout);
    }

    // Listener para cambios en la URL (Hash)
    window.addEventListener('hashchange', handleRoute);

    // Disparador inicial de ruta
    setupInitialRoute();
}

/**
 * Establece la ruta por defecto o carga la actual al abrir la página.
 */
function setupInitialRoute() {
    let currentHash = window.location.hash;
    if (!currentHash) {
        window.location.hash = '#categories'; // Vista por defecto
    } else {
        handleRoute();
    }
}

// --- NAVEGACIÓN Y CARGA DE VISTAS ---

/**
 * Orquestador principal del enrutamiento.
 */
async function handleRoute() {
    const hash = window.location.hash.substring(1);
    if (!hash) return;

    updateSidebarActiveState(hash);
    await loadModuleView(hash);
}

/**
 * Actualiza visualmente el menú lateral para resaltar la opción activa.
 */
function updateSidebarActiveState(hash) {
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + hash) {
            link.classList.add('active');
        }
    });
}

/**
 * Carga el HTML del módulo y lo inicializa.
 */
async function loadModuleView(hash) {
    const contentDiv = document.getElementById('app-content');
    if (!contentDiv) return;

    try {
        const response = await fetch(`modules/${hash}/${hash}.html`);

        if (!response.ok) {
            renderErrorView(contentDiv, hash);
            return;
        }

        const html = await response.text();
        contentDiv.innerHTML = html;

        // Inicializamos el JS específico del módulo cargado
        initializeModuleJS(hash);

    } catch (error) {
        contentDiv.innerHTML = `
            <header><h2>Error loading view</h2></header>
            <div class="card glass-effect"><p>${error.message}</p></div>
        `;
    }
}

/**
 * Llama a la función de inicio de cada módulo (ej: initCategories).
 */
function initializeModuleJS(hash) {
    const initFunctions = {
        'categories': window.initCategories,
        'users': window.initUsers,
        'roles': window.initRoles,
        'incidents': window.initIncidents,
        'assignments': window.initAssignments,
        'solutions': window.initSolutions
    };

    const initFunc = initFunctions[hash];
    if (typeof initFunc === 'function') {
        initFunc();
    }
}

/**
 * Muestra una vista de error 404 si el módulo no existe.
 */
function renderErrorView(container, hash) {
    container.innerHTML = `
        <header><h2>404 - View Not Found</h2></header>
        <div class="card glass-effect">
            <p>Module <b>${hash}</b> is not specialized yet!</p>
        </div>
    `;
}

