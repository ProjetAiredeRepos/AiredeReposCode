// Fichier : wp-content/themes/theme-aire-repos/scripts/dashboard-app.js

document.addEventListener('DOMContentLoaded', () => {
    const appLayout = document.querySelector('.cyclo-app-layout');
    if (!appLayout) return;

    const apiNonce = appLayout.getAttribute('data-nonce');
    const root = document.getElementById('dashboard-root');
    const breadcrumbs = document.querySelector('.breadcrumbs');

    // --- 1. THÈME ET VARIABLES ---
    const THEME = {
        primary: '#1A9E66',
        primaryLight: 'rgba(26, 158, 102, 0.1)',
        textMain: '#2D3748',
        textMuted: '#718096',
        gridLine: '#F0F4F8'
    };

    // --- 2. GESTION DE L'ÉTAT (State Machine) ---
    let appState = {
        module: 'discovery', // 'discovery' | 'survey'
        level: 'global',     // 'global' | 'aire'
        entityId: 'all',
        dateStart: '2026-01-01',
        dateEnd: '2026-12-31'
    };

    // --- 3. MOTEUR API SÉCURISÉ ---
    async function fetchApiData() {
        const endpoint = appState.module === 'discovery' ? 'discovery-overview' : 'survey-overview';

        const params = new URLSearchParams({
            level: appState.level,
            id: appState.entityId,
            start_date: appState.dateStart,
            end_date: appState.dateEnd
        });

        root.innerHTML = '<div class="loading-state">Analyse des données en cours...</div>';

        try {
            const response = await fetch(`/wp-json/cyclo-data/v1/${endpoint}?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': apiNonce,
                    'Content-Type': 'application/json'
                }
            });

            // SÉCURITÉ : Vérifie si WordPress renvoie une erreur PHP (HTML) au lieu du JSON
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                const textError = await response.text();
                console.error("Erreur Serveur (Non-JSON) :", textError);
                throw new Error("Erreur serveur : L'API a renvoyé du HTML au lieu de JSON. Vérifiez vos routes PHP.");
            }

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Erreur lors de la récupération des données.');
            }

            // Aiguillage vers la bonne vue de rendu (en passant les données)
            if (appState.module === 'discovery') {
                renderDiscoveryOverview(result.data);
            } else if (appState.module === 'survey') {
                renderSurveyOverview(result.data);
            }

        } catch (error) {
            console.error('API Fetch Error:', error);
            root.innerHTML = `<div class="error-state">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom:10px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg><br>
                ${error.message}
            </div>`;
        }
    }

    // --- 4. VUES DE RENDU (N'appellent plus fetchApi, elles reçoivent 'data') ---
    function renderDiscoveryOverview(data) {
        // Mise à jour du fil d'ariane
        if(breadcrumbs) breadcrumbs.innerHTML = `Administration <span class="sep">></span> Outils de découverte <span class="sep">></span> <strong>Vue d'ensemble</strong>`;

        root.innerHTML = `
            <div class="dash-card card-full">
                <div class="card-header">
                    <h3>Évolution des consultations</h3>
                    <p>Consultations mensuelles</p>
                </div>
                <div class="chart-container main-chart">
                    <canvas id="chart-evolution"></canvas>
                </div>
            </div>

            <div class="dash-card card-half">
                <div class="card-header">
                    <h3>Consultations totales</h3>
                    <p>Sur la période sélectionnée</p>
                </div>
                <div class="kpi-value">${data.total_consultations ? data.total_consultations.toLocaleString('fr-FR') : 0}</div>
            </div>

            <div class="dash-card card-half">
                <div class="card-header">
                    <h3>Origine géographique</h3>
                    <p>Répartition par nationalité des visiteurs</p>
                </div>
                <div class="chart-container" style="height: 250px;">
                    <canvas id="chart-geo"></canvas>
                </div>
            </div>
        `;
        initDiscoveryCharts(data);
    }

    function renderSurveyOverview(data) {
        // Mise à jour du fil d'ariane
        if(breadcrumbs) breadcrumbs.innerHTML = `Administration <span class="sep">></span> Questionnaires <span class="sep">></span> <strong>Vue d'ensemble</strong>`;

        root.innerHTML = `
            <div class="dash-card card-half">
                <div class="card-header">
                    <h3>Réponses totales</h3>
                    <p>Sur la période sélectionnée</p>
                </div>
                <div class="kpi-value">${data.total_reponses || 0}</div>
            </div>

            <div class="dash-card card-half">
                <div class="card-header">
                    <h3>Indice de satisfaction</h3>
                    <p>Moyenne pondérée</p>
                </div>
                <div class="kpi-value">${data.indice_satisfaction || 0}/5</div>
            </div>

            <div class="dash-card card-half">
                <div class="card-header">
                    <h3>Évolution du nombre de réponses</h3>
                </div>
                <div class="chart-container" style="height: 300px;">
                    <canvas id="chart-survey-evolution"></canvas>
                </div>
            </div>

            <div class="dash-card card-half">
                <div class="card-header">
                    <h3>Score global par thème</h3>
                    <p>Analyse radar (sur 100)</p>
                </div>
                <div class="chart-container" style="height: 300px;">
                    <canvas id="chart-radar"></canvas>
                </div>
            </div>
        `;
        initSurveyCharts(data);
    }

    // --- 5. INITIALISATION DES GRAPHIQUES ---
    function initDiscoveryCharts(data) {
        // Courbe
        const ctxEvol = document.getElementById('chart-evolution');
        if (ctxEvol && data.monthly_evolution) {
            const labels = ['Jan.', 'Fév.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sep.', 'Oct.', 'Nov.', 'Déc.'];
            const chartData = new Array(12).fill(0);
            data.monthly_evolution.forEach(row => { chartData[row.mois - 1] = parseInt(row.total, 10); });

            new Chart(ctxEvol, {
                type: 'line',
                data: { labels: labels, datasets: [{ data: chartData, borderColor: THEME.primary, backgroundColor: THEME.primaryLight, fill: true, tension: 0.4 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });
        }

        // Donut
        const ctxGeo = document.getElementById('chart-geo');
        if (ctxGeo && data.geo_stats) {
            const geoLabels = data.geo_stats.map(item => item.code_langue.toUpperCase());
            const geoValues = data.geo_stats.map(item => parseInt(item.total, 10));
            new Chart(ctxGeo, {
                type: 'doughnut',
                data: { labels: geoLabels, datasets: [{ data: geoValues, backgroundColor: ['#1A9E66', '#41C48F', '#8EEABF'] }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '65%' }
            });
        }
    }

    function initSurveyCharts(data) {
        const ctxRadar = document.getElementById('chart-radar');
        if (ctxRadar && data.radar_scores) {
            new Chart(ctxRadar, {
                type: 'radar',
                data: { labels: ['Aire de repos', 'Sécurité', 'Services'], datasets: [{ data: data.radar_scores, backgroundColor: THEME.primaryLight, borderColor: THEME.primary }] },
                options: { responsive: true, maintainAspectRatio: false, scales: { r: { min: 0, max: 100 } }, plugins: { legend: { display: false } } }
            });
        }
    }

    // --- 6. ÉCOUTEURS D'ÉVÉNEMENTS (Routage et Filtres) ---

    // Filtres
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            e.currentTarget.classList.add('active');

            const filterValue = e.currentTarget.innerText.toLowerCase();
            const today = new Date();

            if (filterValue.includes('jour')) {
                // Aujourd'hui
                appState.dateStart = today.toISOString().split('T')[0];
                appState.dateEnd = appState.dateStart;
            } else if (filterValue.includes('semaine')) {
                // Semaine en cours (Lundi à Dimanche)
                const firstDay = new Date(today);
                const day = firstDay.getDay() || 7; // Dimanche devient 7
                if(day !== 1) firstDay.setHours(-24 * (day - 1)); // On recule jusqu'au lundi
                appState.dateStart = firstDay.toISOString().split('T')[0];
                appState.dateEnd = today.toISOString().split('T')[0];
            } else if (filterValue.includes('mois')) {
                // Mois en cours (du 1er à aujourd'hui)
                appState.dateStart = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                appState.dateEnd = today.toISOString().split('T')[0];
            } else {
                // Année entière
                appState.dateStart = `${today.getFullYear()}-01-01`;
                appState.dateEnd = `${today.getFullYear()}-12-31`;
            }

            fetchApiData(); // L'état est à jour, on relance le cerveau !
        });
    });

    // Menu principal et Accordéons (Fusionnés)
    document.querySelectorAll('.nav-item[data-action="route"], .nav-item[data-action="route-and-toggle"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Mise à jour visuelle (menu actif)
            document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
            e.currentTarget.classList.add('active');

            // Mise à jour de l'état (Cerveau)
            appState.module = e.currentTarget.getAttribute('data-module');
            appState.level = e.currentTarget.getAttribute('data-level');
            appState.entityId = e.currentTarget.getAttribute('data-id');

            // Ouverture/Fermeture du sous-menu si c'est un bouton "route-and-toggle"
            if (e.currentTarget.getAttribute('data-action') === 'route-and-toggle') {
                const targetId = e.currentTarget.getAttribute('data-target');
                const submenu = document.getElementById(targetId);
                if (submenu) {
                    const isHidden = submenu.style.display === 'none';
                    submenu.style.display = isHidden ? 'block' : 'none';
                    const chevron = e.currentTarget.querySelector('.chevron');
                    if(chevron) chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            }

            // Sur mobile, ferme la sidebar après un clic
            if(window.innerWidth <= 1024) document.querySelector('.cyclo-sidebar').classList.remove('is-open');

            fetchApiData();
        });
    });

    // Bouton Mobile
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.cyclo-sidebar');
    if (mobileBtn && sidebar) {
        mobileBtn.addEventListener('click', () => { sidebar.classList.toggle('is-open'); });
    }

    // --- 7. LANCEMENT ---
    fetchApiData();
});