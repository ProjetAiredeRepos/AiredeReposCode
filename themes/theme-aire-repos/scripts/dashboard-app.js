// Fichier : wp-content/themes/theme-aire-repos/scripts/dashboard-app.js

document.addEventListener('DOMContentLoaded', () => {
    const appLayout = document.querySelector('.cyclo-app-layout');
    if (!appLayout) return;

    const apiNonce = appLayout.getAttribute('data-nonce');
    const root = document.getElementById('dashboard-root');

    // Couleurs issues de tes maquettes Figma
    const THEME = {
        primary: '#1A9E66',
        primaryLight: 'rgba(26, 158, 102, 0.1)',
        textMain: '#2D3748',
        textMuted: '#718096',
        gridLine: '#F0F4F8'
    };

    // --- GESTION DU MENU MOBILE ---
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.cyclo-sidebar');

    if (mobileBtn && sidebar) {
        mobileBtn.addEventListener('click', () => {
            sidebar.classList.toggle('is-open');
        });

        // Ferme le menu si on clique sur un lien (pratique sur mobile)
        const navItems = sidebar.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.addEventListener('click', () => {
                if(window.innerWidth <= 1024) {
                    sidebar.classList.remove('is-open');
                }
            });
        });
    }

    // --- 1. MOTEUR DE REQUÊTES SÉCURISÉ ---
    async function fetchApi(endpoint) {
        try {
            const response = await fetch(`/wp-json/cyclo-data/v1/${endpoint}`, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': apiNonce, // Authentification stricte
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Erreur réseau');
            return result.data;
        } catch (error) {
            console.error('API Error:', error);
            root.innerHTML = `<div class="error-state">Erreur lors du chargement des données : ${error.message}</div>`;
            throw error;
        }
    }

    // --- 2. CONSTRUCTION DE LA VUE (DÉCOUVERTE) ---
    async function renderDiscoveryOverview() {
        root.innerHTML = '<div class="loading-state">Analyse des données en cours...</div>';

        // Appel API
        const data = await fetchApi('discovery-overview');

        // Injection de la grille CSS (Figma Handoff)
        root.innerHTML = `
            <div class="dash-card card-full">
                <div class="card-header">
                    <h3>Évolution des consultations</h3>
                    <p>Consultations mensuelles - 2026</p>
                </div>
                <div class="chart-container main-chart">
                    <canvas id="chart-evolution"></canvas>
                </div>
            </div>

            <div class="dash-card card-half">
                <div class="card-header">
                    <h3>Consultations totales</h3>
                    <p>Sur l'année 2026</p>
                </div>
                <div class="kpi-value">${data.total_consultations.toLocaleString('fr-FR')}</div>
                <div class="kpi-trend positive">+12% vs année précédente</div>
            </div>

            <div class="dash-card card-half">
                <div class="card-header">
                    <h3>Temps moyen passé</h3>
                    <p>Par session</p>
                </div>
                <div class="kpi-value">3 min 42 s</div>
                <div class="kpi-trend positive">+8% vs année précédente</div>
            </div>
        `;

        initCharts(data);
    }

    // --- 3. INITIALISATION DES GRAPHIQUES (Chart.js) ---
    function initCharts(data) {
        const ctxEvolution = document.getElementById('chart-evolution');
        if (!ctxEvolution) return;

        // Préparation des données pour le graphique depuis l'API
        const labels = ['Jan.', 'Fév.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sep.', 'Oct.', 'Nov.', 'Déc.'];
        const chartData = new Array(12).fill(0); // Tableau de 12 zéros par défaut

        // On remplit avec les vraies données
        if(data.monthly_evolution) {
            data.monthly_evolution.forEach(row => {
                // row.mois va de 1 à 12, on ajuste l'index (0 à 11)
                chartData[row.mois - 1] = parseInt(row.total, 10);
            });
        }

        // Configuration du graphique "Évolution des consultations"
        new Chart(ctxEvolution, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Consultations',
                    data: chartData,
                    borderColor: THEME.primary,
                    backgroundColor: THEME.primaryLight,
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: THEME.primary,
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true, // Remplit sous la courbe (comme ta maquette)
                    tension: 0.4 // Courbe lissée
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1A202C',
                        padding: 12,
                        titleFont: { family: 'DM Sans', size: 13 },
                        bodyFont: { family: 'DM Sans', size: 14, weight: 'bold' },
                        displayColors: false,
                        callbacks: {
                            label: function(context) { return context.parsed.y + ' visites'; }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: THEME.textMuted, font: { family: 'DM Sans' } }
                    },
                    y: {
                        grid: { color: THEME.gridLine, drawBorder: false },
                        ticks: { color: THEME.textMuted, font: { family: 'DM Sans' }, maxTicksLimit: 5 }
                    }
                }
            }
        });
    }

    // --- 4. BOOTSTRAP DE L'APP ---
    renderDiscoveryOverview();

});