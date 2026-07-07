// Fichier : wp-content/themes/theme-aire-repos/scripts/dashboard-app.js

// Plugin réutilisable pour les graphiques circulaires et barres
const percentageTooltip = {
    callbacks: {
        label: function(context) {
            let dataset = context.dataset.data;
            let total = dataset.reduce((previousValue, currentValue) => previousValue + currentValue, 0);
            let currentValue = dataset[context.dataIndex];
            let percentage = Math.round((currentValue / total) * 100);
            return ` ${context.label} : ${currentValue} (${percentage}%)`;
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const appLayout = document.querySelector('.cyclo-app-layout');
    if (!appLayout) return;

    const apiNonce = appLayout.getAttribute('data-nonce');
    const root = document.getElementById('dashboard-root');

    const THEME = { primary: '#1A9E66', primaryLight: '#E7F5EF', textMain: '#2D3748', textMuted: '#718096' };

    // Enregistrement global du plugin Datalabels pour afficher les %
    Chart.register(ChartDataLabels);
    Chart.defaults.set('plugins.datalabels', {
        color: '#fff',
        font: { weight: 'bold', size: 12 },
        formatter: (value, ctx) => {
            let sum = 0;
            let dataArr = ctx.chart.data.datasets[0].data;
            dataArr.map(data => { sum += Number(data); });
            let percentage = (value * 100 / sum).toFixed(0) + "%";
            return percentage;
        }
    });

    let appState = {
        module: 'discovery', level: 'global', entityId: 'all', entityName: "Vue d'ensemble",
        filterType: 'annee', dateStart: `${new Date().getFullYear()}-01-01`, dateEnd: `${new Date().getFullYear()}-12-31`,
        compare: false,
        compareStart: '', // Sera défini dynamiquement
        compareEnd: ''
    };

    // --- 1. MOTEUR API ---
    async function fetchApiData() {
        // Routage de l'endpoint selon le module
        let endpoint = 'discovery-overview';
        if (appState.module === 'survey') endpoint = 'survey-overview';
        if (appState.module === 'users') endpoint = 'users';

        // INTERCEPTION POUR LES PARAMÈTRES
        if (appState.module === 'settings') {
            root.innerHTML = '<div class="loading-state">Chargement des paramètres...</div>';
            updateHeaderTitles();
            try {
                const res = await fetch(`/wp-json/cyclo-data/v1/settings`, { headers: { 'X-WP-Nonce': apiNonce } });

                // 1. On vérifie d'abord si le serveur a planté (Erreur 500 ou 404)
                if (!res.ok) {
                    const textError = await res.text();
                    console.error("Réponse brute du serveur :", textError);
                    throw new Error(`Erreur HTTP ${res.status}`);
                }

                const result = await res.json();

                // 2. On vérifie si l'API a renvoyé un statut d'erreur WordPress
                if (result.status !== 'success' && !result.data) {
                    throw new Error(result.message || "Endpoint introuvable ou accès refusé.");
                }

                renderSettingsView(result.data);
            } catch(e) {
                console.error("Erreur critique Paramètres :", e);
                root.innerHTML = `<div class="error-state" style="color: #E53E3E; border: 1px solid #FC8181; background: #FFF5F5;">Détail de l'erreur : ${e.message}<br><small>Ouvre la console (F12) pour plus d'infos.</small></div>`;
            }
            return; // On stoppe la fonction ici pour ce module
        }
        const params = new URLSearchParams({
            level: appState.level,
            id: appState.entityId,
            start_date: appState.dateStart,
            end_date: appState.dateEnd,
            filter_type: appState.filterType,
            compare: appState.compare ? '1' : '0',
            compare_start: appState.compareStart, // Nouveau
            compare_end: appState.compareEnd, // Nouveau
        });

        root.innerHTML = '<div class="loading-state">Analyse des données en cours...</div>';
        updateHeaderTitles();

        try {
            const res = await fetch(`/wp-json/cyclo-data/v1/${endpoint}?${params.toString()}`, { headers: { 'X-WP-Nonce': apiNonce } });
            if(!res.ok) throw new Error("Erreur Serveur ou Accès Refusé");

            // C'est cette ligne qui manquait dans ton code !
            const result = await res.json();

            // Affichage de la bonne vue
            if (appState.module === 'discovery') renderDiscoveryOverview(result.data);
            else if (appState.module === 'survey') renderSurveyOverview(result.data);
            else if (appState.module === 'users') renderUsersList(result.data);
            else if (appState.module === 'permissions') renderPermissionsView();
            else if (appState.module === 'settings') renderSettingsView(); // <-- NOUVELLE LIGNE

        } catch (error) {
            root.innerHTML = `<div class="error-state">${error.message}</div>`;
        }
    }

    // --- 2. TITRES DYNAMIQUES ---
    function updateHeaderTitles() {
        let modName = "Vue d'ensemble";
        if (appState.module === 'discovery') modName = "Outils de découverte";
        else if (appState.module === 'survey') modName = "Questionnaires";
        else if (appState.module === 'users') modName = "Utilisateurs";
        else if (appState.module === 'permissions') modName = "Utilisateurs"; // Les permissions sont dans la section Utilisateurs

        let lvlName = appState.level === 'route' ? ` <span class="sep">></span> ${appState.entityName}` : (appState.level === 'aire' ? ` <span class="sep">></span> Itinéraire <span class="sep">></span> ${appState.entityName}` : "");

        const breadcrumbsEl = document.querySelector('.breadcrumbs');
        if (breadcrumbsEl) {
            // Ajustement du fil d'ariane
            if (appState.module === 'users' || appState.module === 'permissions') {
                breadcrumbsEl.innerHTML = `Administration <span class="sep">></span> Utilisateurs`;
            } else {
                breadcrumbsEl.innerHTML = `Administration <span class="sep">></span> ${modName}${lvlName}`;
            }
        }

        const titleH1 = document.getElementById('dash-main-title');
        if(titleH1) {
            // Si on est sur une vue globale (comme les permissions ou les utilisateurs), on utilise le nom de l'entité passé par le bouton du menu
            titleH1.innerText = appState.level === 'global' ? appState.entityName : appState.entityName;
        }
    }

    // --- 3. RENDU DÉCOUVERTE ---
    function renderDiscoveryOverview(data) {
        // Réafficher les filtres s'ils ont été masqués par la vue Utilisateurs
        const headerActions = document.querySelector('.header-actions');
        if (headerActions) headerActions.style.display = 'flex';

        // Calcul des minutes et secondes
        const min = Math.floor(data.temps_moyen / 60);
        const sec = data.temps_moyen % 60;
        const timeString = data.temps_moyen > 0 ? `${min} min ${sec} s` : "0 min 0 s";

        let linksHtml = '<div style="padding:10px 0; color:#888;">Aucun lien cliqué sur la période.</div>';
        if(data.top_links && data.top_links.length > 0) {
            linksHtml = data.top_links.map((link, idx) => `
                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid #eee;">
                    <span style="font-weight:500;">${idx + 1}. ${link.texte_lien}</span><strong>${link.total}</strong>
                </div>
            `).join('');
        }

        root.innerHTML = `
            <div class="dash-card card-full"><div class="card-header"><h3>Évolution des consultations</h3><p>Période: ${appState.filterType}</p></div><div class="chart-container main-chart"><canvas id="chart-evo"></canvas></div></div>
            <div class="dash-card card-half"><div class="card-header"><h3>Temps moyen passé</h3><p>Par session</p></div><div class="kpi-value">${timeString}</div></div>
            <div class="dash-card card-half"><div class="card-header"><h3>Consultations totales</h3></div><div class="kpi-value">${data.total_consultations.toLocaleString('fr-FR')}</div></div>
            <div class="dash-card card-half" style="display: flex; flex-direction: column;">
                <div class="card-header"><h3>Répartition par tranche horaire</h3></div>
                <div class="chart-container" style="height: 200px;"><canvas id="chart-hour"></canvas></div>
                <div id="legend-hour" style="margin-top: 15px; padding-top: 10px; border-top: 1px dashed #E2E8F0; max-height: 140px; overflow-y: auto; font-size: 0.85rem; color: #4A5568;"></div>
            </div>
            <div class="dash-card card-half"><div class="card-header"><h3>Liens les plus cliqués</h3></div><div style="max-height:250px; overflow-y:auto;">${linksHtml}</div></div>
        `;
        initTimelineChart(document.getElementById('chart-evo'), data.timeline, data.timeline_compare);

        if(document.getElementById('chart-hour') && data.hourly_stats) {
            const hData = new Array(24).fill(0);
            data.hourly_stats.forEach(r => hData[parseInt(r.heure)] = parseInt(r.total));

            // 1. Génération du graphique à barres
            new Chart(document.getElementById('chart-hour'), {
                type: 'bar',
                data: { labels: Array.from({length:24}, (_,i)=>`${i}h`), datasets: [{ data: hData, backgroundColor: THEME.primary, borderRadius:4 }] },
                options: {
                    responsive:true,
                    maintainAspectRatio:false,
                    plugins:{
                        legend:{display:false},
                        datalabels: { display: false }
                    }
                }
            });

            // 2. NOUVEAU : Génération du détail textuel en dessous
            const legendHourDiv = document.getElementById('legend-hour');
            if (legendHourDiv) {
                let html = '<div style="font-weight: 600; margin-bottom: 8px; color: #2D3748;">Détail des consultations :</div>';
                let hasData = false;

                // On boucle sur les 24 heures
                hData.forEach((total, hour) => {
                    if (total > 0) { // On n'affiche que les heures où il y a eu de l'activité
                        hasData = true;
                        html += `
                            <div style="display: flex; justify-content: space-between; padding: 4px 8px; margin-bottom: 4px; background: #F7FAFC; border-radius: 4px; border: 1px solid #EDF2F7;">
                                <span style="font-weight: 600; color: #4A5568;">${hour}h00 - ${hour+1}h00</span>
                                <span><strong style="color: #1A9E66;">${total}</strong> consultation${total > 1 ? 's' : ''}</span>
                            </div>
                        `;
                    }
                });

                if (!hasData) {
                    html += '<div style="text-align:center; color:#A0AEC0; padding: 10px;">Aucune donnée sur cette période</div>';
                }

                legendHourDiv.innerHTML = html;
            }
        }
    }

    // --- 4. RENDU QUESTIONNAIRES ---
    function renderSurveyOverview(data) {
        const headerActions = document.querySelector('.header-actions');
        if (headerActions) headerActions.style.display = 'flex';

        // Calcul des sources de découverte (Q4)
        const sCount = {};
        if (data.q4_raw) {
            data.q4_raw.forEach(r => {
                if(r.q4_connaissance) {
                    r.q4_connaissance.split(',').forEach(s => {
                        const src = s.trim();
                        sCount[src] = (sCount[src]||0) + 1;
                    });
                }
            });
        }

        root.innerHTML = `
            <div class="dash-card card-third"><div class="card-header"><h3>Réponses</h3></div><div class="kpi-value">${data.total_reponses}</div></div>
            <div class="dash-card card-third"><div class="card-header"><h3>Satisfaction</h3></div><div class="kpi-value">${data.indice_satisfaction}/5</div></div>
            <div class="dash-card card-third"><div class="card-header"><h3>Radar des Scores</h3></div><div class="chart-container" style="height: 150px;"><canvas id="chart-radar"></canvas></div></div>
            
            <div class="dash-card card-full"><div class="card-header"><h3>Évolution des réponses</h3><p>Période: ${appState.filterType}</p></div><div class="chart-container main-chart"><canvas id="chart-survey-timeline"></canvas></div></div>
            
            <div class="dash-card card-third">
                <div class="card-header" style="display:flex; justify-content:space-between;">
                    <h3>1. Note Aire</h3><span style="font-weight:bold; color:var(--primary);">${data.averages.aire} / 4</span>
                </div>
                <div class="chart-container" style="height: 180px;"><canvas id="chart-q1"></canvas></div>
                <div id="legend-q1"></div>
            </div>
            <div class="dash-card card-third">
                <div class="card-header" style="display:flex; justify-content:space-between;">
                    <h3>2. Note Sécurité</h3><span style="font-weight:bold; color:var(--primary);">${data.averages.securite} / 4</span>
                </div>
                <div class="chart-container" style="height: 180px;"><canvas id="chart-q2"></canvas></div>
                <div id="legend-q2"></div>
            </div>
            <div class="dash-card card-third">
                <div class="card-header" style="display:flex; justify-content:space-between;">
                    <h3>3. Note Services</h3><span style="font-weight:bold; color:var(--primary);">${data.averages.services} / 4</span>
                </div>
                <div class="chart-container" style="height: 180px;"><canvas id="chart-q3"></canvas></div>
                <div id="legend-q3"></div>
            </div>
            
            <div class="dash-card card-third">
                <div class="card-header"><h3>4. Sources de découverte</h3></div>
                <div class="chart-container" style="height: 180px;"><canvas id="chart-q4"></canvas></div>
                <div id="legend-q4"></div>
            </div>
            <div class="dash-card card-third">
                <div class="card-header"><h3>Pays</h3></div> <div class="chart-container" style="height: 180px;"><canvas id="chart-pays"></canvas></div>
                <div id="legend-pays"></div>
            </div>
            <div class="dash-card card-third">
                <div class="card-header"><h3>Accompagnement</h3></div>
                <div class="chart-container" style="height: 180px;"><canvas id="chart-acc"></canvas></div>
                <div id="legend-acc"></div>
            </div>
        `;

        initTimelineChart(document.getElementById('chart-survey-timeline'), data.timeline, data.timeline_compare);

        if(document.getElementById('chart-radar') && data.total_reponses > 0) {
            new Chart(document.getElementById('chart-radar'), { type: 'radar', data: { labels: ['Aire', 'Sécurité', 'Services'], datasets: [{ label: 'Score', data: data.radar_scores, backgroundColor: THEME.primaryLight, borderColor: THEME.primary }] }, options:{responsive:true, maintainAspectRatio:false, scales: { r: { min: 0, max: 100, ticks:{display:false} } }, plugins: { legend: { display: false } }} });
        }

        // NOUVELLE PALETTE ÉTENDUE : Harmonieuse et contrastée pour la DataViz
        const colorPalette = [
            '#1A9E66', // Vert (Couleur de marque principale)
            '#2B6CB0', // Bleu profond
            '#ED8936', // Orange dynamique
            '#9F7AEA', // Violet doux
            '#38B2AC', // Sarcelle / Turquoise
            '#D69E2E', // Jaune moutarde
            '#F56565', // Corail / Rouge doux
            '#0BC5EA', // Cyan clair
            '#ED64A6', // Rose
            '#4A5568'  // Gris ardoise (au cas où il y a plus de 10 variables)
        ];

        function renderCustomDoughnut(canvasId, legendId, originalLabels, originalValues) {
            // CORRECTION : Filtrage strict pour éliminer les zéros
            const labels = [];
            const values = [];
            const colors = [];

            originalValues.forEach((val, i) => {
                if (val > 0) {
                    labels.push(originalLabels[i]);
                    values.push(val);
                    // On conserve la couleur d'origine associée à l'index pour rester cohérent
                    colors.push(colorPalette[i % colorPalette.length]);
                }
            });

            if(!document.getElementById(canvasId) || values.length === 0) {
                if(document.getElementById(canvasId)) document.getElementById(canvasId).parentElement.innerHTML = '<div style="text-align:center; color:#999; padding-top:20px;">Aucune donnée</div>';
                return;
            }

            new Chart(document.getElementById(canvasId), {
                type: 'doughnut',
                data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 2 }] },
                options: {
                    responsive:true,
                    maintainAspectRatio:false,
                    cutout:'60%',
                    plugins: { legend: { display: false } }
                }
            });

            const legendDiv = document.getElementById(legendId);
            if (legendDiv) {
                let html = '<div style="margin-top: 20px; font-size: 0.9rem; color: #4A5568; max-height: 120px; overflow-y: auto; padding-right: 5px;">';
                labels.forEach((label, i) => {
                    html += `<div style="display: flex; align-items: center; margin-bottom: 8px;">
                                <span style="width: 12px; height: 12px; background-color: ${colors[i]}; border-radius: 50%; margin-right: 10px; flex-shrink: 0;"></span>
                                <strong style="margin-right: 6px;">${label}</strong> : sélectionné ${values[i]} fois
                             </div>`;
                });
                html += '</div>';
                legendDiv.innerHTML = html;
            }
        }

        // Notes
        const parseNotes = (distData) => {
            const vals = [0,0,0,0];
            if(distData) distData.forEach(r => { if(r.note>=1 && r.note<=4) vals[parseInt(r.note)-1] = parseInt(r.total); });
            return vals;
        };
        renderCustomDoughnut('chart-q1', 'legend-q1', ['1/4', '2/4', '3/4', '4/4'], parseNotes(data.q1_dist));
        renderCustomDoughnut('chart-q2', 'legend-q2', ['1/4', '2/4', '3/4', '4/4'], parseNotes(data.q2_dist));
        renderCustomDoughnut('chart-q3', 'legend-q3', ['1/4', '2/4', '3/4', '4/4'], parseNotes(data.q3_dist));

        // Sources
        renderCustomDoughnut('chart-q4', 'legend-q4', Object.keys(sCount), Object.values(sCount));

        // CORRECTION : Fusionner la France avec les Pays
        let paysLabels = [];
        let paysValues = [];

        // 1. On injecte la France d'abord (en extrayant l'information de data.origine)
        if (data.origine) {
            const frData = data.origine.find(o => o.profil_origine === 'fr');
            if (frData && parseInt(frData.total) > 0) {
                paysLabels.push('FRANCE');
                paysValues.push(parseInt(frData.total));
            }
        }

        // 2. On ajoute les autres pays
        if (data.pays) {
            data.pays.forEach(p => {
                if (p.profil_pays && p.profil_pays.trim() !== '') {
                    paysLabels.push(p.profil_pays.toUpperCase());
                    paysValues.push(parseInt(p.total));
                }
            });
        }
        renderCustomDoughnut('chart-pays', 'legend-pays', paysLabels, paysValues);

        // NOUVEAU BLOC : Injection du détail des régions françaises sous la légende Pays
        const paysLegendDiv = document.getElementById('legend-pays');
        if (paysLegendDiv && data.region_dist && data.region_dist.length > 0) {

            // On crée une sous-section stylisée avec une bordure en pointillés
            let subLegendHtml = `
                <div style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #E2E8F0; font-size: 0.85rem; color: #718096;">
                    <div style="font-weight: 600; margin-bottom: 6px; color: #4A5568;">Détail des répondants de France :</div>
            `;

            // On boucle sur les données renvoyées par l'API (Département 41, Région Centre, etc.)
            data.region_dist.forEach(r => {
                subLegendHtml += `
                    <div style="margin-bottom: 4px; padding-left: 8px; display: flex; justify-content: space-between;">
                        <span>• ${r.profil_region}</span>
                        <span style="font-weight: 500; color: #2D3748;">sélectionné ${r.total} fois</span>
                    </div>
                `;
            });

            subLegendHtml += `</div>`;

            // On insère ce HTML à la fin de la légende existante sans écraser le reste
            paysLegendDiv.insertAdjacentHTML('beforeend', subLegendHtml);
        }

        // Accompagnement
        if(data.accompagnement && data.accompagnement.length > 0) {
            renderCustomDoughnut('chart-acc', 'legend-acc', data.accompagnement.map(a => a.profil_accompagnement), data.accompagnement.map(a => parseInt(a.total)));
        } else {
            renderCustomDoughnut('chart-acc', 'legend-acc', [], []);
        }
    }

    // --- 5. RENDU UTILISATEURS ---
    function renderUsersList(data) {
        // Masque les filtres de date et d'export (inutiles sur cette vue)
        const headerActions = document.querySelector('.header-actions');
        if (headerActions) headerActions.style.display = 'none';

        let usersHtml = '<div style="padding:1.5rem; text-align:center;">Aucun utilisateur trouvé.</div>';
        if(data.users && data.users.length > 0) {
            usersHtml = data.users.map(u => `
                <div class="user-row">
                    <div class="u-avatar bg-${u.role_color}">${u.initials}</div>
                    <div class="u-info">
                        <strong>${u.name}</strong>
                        <span>${u.email}</span>
                    </div>
                    <div class="u-role">
                        <span class="badge badge-${u.role_color}">${u.role}</span>
                    </div>
                    <div class="u-date">📅 ${u.date}</div>
                </div>
            `).join('');
        }

        root.innerHTML = `
            <div class="dash-card card-third"><div class="card-header"><h3>Total utilisateurs</h3></div><div class="kpi-value">${data.total}</div></div>
            <div class="dash-card card-third"><div class="card-header"><h3>Actifs</h3></div><div class="kpi-value" style="color:#1A9E66;">${data.total}</div></div>
            <div class="dash-card card-third"><div class="card-header"><h3>Inactifs</h3></div><div class="kpi-value" style="color:#E53E3E;">0</div></div>

            <div class="dash-card card-full" style="padding:0;">
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--dash-border); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin:0; font-size:1.1rem;">Comptes & Permissions</h3>
                    
                    <button id="btn-open-create-user" class="btn-primary" style="display: flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14M5 12h14"></path>
                        </svg>
                        Nouvel utilisateur
                    </button>
                </div>
                <div class="users-list-container">
                    ${usersHtml}
                </div>
            </div>
        `;

        // On attache l'événement pour ouvrir la modale juste après l'injection dans le DOM
        const btnCreateUser = document.getElementById('btn-open-create-user');
        if (btnCreateUser) {
            btnCreateUser.addEventListener('click', openCreateUserModal);
        }
    }

    // --- 5B. RENDU GESTION DES PERMISSIONS ---
    async function renderPermissionsView() {
        const headerActions = document.querySelector('.header-actions');
        if (headerActions) headerActions.style.display = 'none';

        // L'interface complète fidèle aux maquettes Figma
        root.innerHTML = `
            <div class="dash-card card-full" style="padding: 2rem;">
                <h3 style="margin-top: 0; font-size: 1.25rem;">Rôles disponibles</h3>
                <p style="color: var(--text-muted); margin-bottom: 24px;">Sélectionnez un rôle pour visualiser et modifier ses permissions</p>
                
                <div style="display: flex; gap: 16px; margin-bottom: 32px;" id="role-selector">
                    <button class="btn-secondary role-select-btn" data-role="cyclo_user" style="display: flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg> Utilisateur
                    </button>
                    <button class="btn-secondary role-select-btn" data-role="cyclo_editeur" style="display: flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Éditeur
                    </button>
                    <button class="btn-secondary role-select-btn" data-role="administrator" style="display: flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg> Admin
                    </button>
                </div>

                <div style="display: flex; gap: 24px; margin-bottom: 32px;">
                    <div style="flex: 1; background: #F7FAFC; padding: 20px; border-radius: 8px; border: 1px solid #E2E8F0; display: flex; align-items: center; gap: 16px;">
                        <div style="background: #E2E8F0; padding: 12px; border-radius: 8px; color: #4A5568;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg></div>
                        <div>
                            <span style="font-size: 0.85rem; color: #718096; text-transform: uppercase; font-weight: bold;">Rôle sélectionné</span>
                            <div style="font-size: 1.25rem; font-weight: bold; color: #2D3748; margin-top: 4px;" id="perm-role-name">...</div>
                        </div>
                    </div>
                    <div style="flex: 1; background: #E7F5EF; padding: 20px; border-radius: 8px; border: 1px solid #C6F6D5; display: flex; align-items: center; gap: 16px;">
                        <div style="background: #C6F6D5; padding: 12px; border-radius: 8px; color: #1A9E66;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
                        <div>
                            <span style="font-size: 0.85rem; color: #2F855A; text-transform: uppercase; font-weight: bold;">Permissions actives</span>
                            <div style="font-size: 1.25rem; font-weight: bold; color: #1A9E66; margin-top: 4px;" id="perm-active-count">0 / 11</div>
                        </div>
                    </div>
                    <div style="flex: 1; background: #F7FAFC; padding: 20px; border-radius: 8px; border: 1px solid #E2E8F0; display: flex; align-items: center; gap: 16px;">
                        <div style="background: #E2E8F0; padding: 12px; border-radius: 8px; color: #4A5568;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg></div>
                        <div>
                            <span style="font-size: 0.85rem; color: #718096; text-transform: uppercase; font-weight: bold;">Catégories concernées</span>
                            <div style="font-size: 1.25rem; font-weight: bold; color: #2D3748; margin-top: 4px;">4</div>
                        </div>
                    </div>
                </div>

                <div class="permissions-matrix" style="opacity: 0.5; pointer-events: none; transition: opacity 0.3s;" id="permissions-container">
                    
                    <div style="border: 1px solid #E2E8F0; border-radius: 8px; margin-bottom: 16px; overflow: hidden;">
                        <div style="background: #F7FAFC; padding: 12px 20px; border-bottom: 1px solid #E2E8F0; font-weight: 600;">Gestion des permissions</div>
                        <div style="padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <label style="display: flex; gap: 12px; align-items: start; cursor: pointer;"><input type="checkbox" class="cap-checkbox" data-cap="cyclo_view_permissions" style="margin-top: 4px; accent-color: #1A9E66;"> <div><strong>Voir les permissions</strong><br><span style="font-size:0.85rem; color:#718096;">Permet de voir la page Gestion des Permissions.</span></div></label>
                            <label style="display: flex; gap: 12px; align-items: start; cursor: pointer;"><input type="checkbox" class="cap-checkbox" data-cap="cyclo_edit_permissions" style="margin-top: 4px; accent-color: #1A9E66;"> <div><strong>Modifier les permissions</strong><br><span style="font-size:0.85rem; color:#718096;">Permet de modifier les permissions.</span></div></label>
                        </div>
                    </div>

                    <div style="border: 1px solid #E2E8F0; border-radius: 8px; margin-bottom: 16px; overflow: hidden;">
                        <div style="background: #F7FAFC; padding: 12px 20px; border-bottom: 1px solid #E2E8F0; font-weight: 600;">Gestion des utilisateurs</div>
                        <div style="padding: 20px; display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px;">
                            <label style="display: flex; gap: 12px; align-items: start; cursor: pointer;"><input type="checkbox" class="cap-checkbox" data-cap="cyclo_view_users" style="margin-top: 4px; accent-color: #1A9E66;"> <div><strong>Voir les utilisateurs</strong><br><span style="font-size:0.85rem; color:#718096;">Permet d'afficher la liste.</span></div></label>
                            <label style="display: flex; gap: 12px; align-items: start; cursor: pointer;"><input type="checkbox" class="cap-checkbox" data-cap="cyclo_create_users" style="margin-top: 4px; accent-color: #1A9E66;"> <div><strong>Créer un utilisateur</strong><br><span style="font-size:0.85rem; color:#718096;">Permet d'ajouter des comptes.</span></div></label>
                            <label style="display: flex; gap: 12px; align-items: start; cursor: pointer;"><input type="checkbox" class="cap-checkbox" data-cap="cyclo_edit_users" style="margin-top: 4px; accent-color: #1A9E66;"> <div><strong>Modifier un utilisateur</strong><br><span style="font-size:0.85rem; color:#718096;">Permet de changer les profils.</span></div></label>
                            <label style="display: flex; gap: 12px; align-items: start; cursor: pointer;"><input type="checkbox" class="cap-checkbox" data-cap="cyclo_delete_users" style="margin-top: 4px; accent-color: #1A9E66;"> <div><strong>Supprimer un utilisateur</strong><br><span style="font-size:0.85rem; color:#718096;">Action irréversible.</span></div></label>
                        </div>
                    </div>
                    
                    <div style="border: 1px solid #E2E8F0; border-radius: 8px; margin-bottom: 16px; overflow: hidden;">
                        <div style="background: #F7FAFC; padding: 12px 20px; border-bottom: 1px solid #E2E8F0; font-weight: 600;">Accès aux statistiques</div>
                        <div style="padding: 20px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                            <label style="display: flex; gap: 12px; align-items: start; cursor: pointer;"><input type="checkbox" class="cap-checkbox" data-cap="cyclo_view_stats" style="margin-top: 4px; accent-color: #1A9E66;"> <div><strong>Voir les statistiques</strong><br><span style="font-size:0.85rem; color:#718096;">Permet de voir les tableaux de bord.</span></div></label>
                            <label style="display: flex; gap: 12px; align-items: start; cursor: pointer;"><input type="checkbox" class="cap-checkbox" data-cap="cyclo_export_stats" style="margin-top: 4px; accent-color: #1A9E66;"> <div><strong>Exporter les données</strong><br><span style="font-size:0.85rem; color:#718096;">Permet d'exporter les données en CSV/Excel.</span></div></label>
                            <label style="display: flex; gap: 12px; align-items: start; cursor: pointer;"><input type="checkbox" class="cap-checkbox" data-cap="cyclo_compare_stats" style="margin-top: 4px; accent-color: #1A9E66;"> <div><strong>Comparaison de périodes</strong><br><span style="font-size:0.85rem; color:#718096;">Permet d'activer la comparaison temporelle.</span></div></label>
                        </div>
                    </div>

                    <div style="border: 1px solid #E2E8F0; border-radius: 8px; margin-bottom: 16px; overflow: hidden;">
                        <div style="background: #F7FAFC; padding: 12px 20px; border-bottom: 1px solid #E2E8F0; font-weight: 600;">Gestion des questionnaires</div>
                        <div style="padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <label style="display: flex; gap: 12px; align-items: start; cursor: pointer;"><input type="checkbox" class="cap-checkbox" data-cap="cyclo_view_surveys" style="margin-top: 4px; accent-color: #1A9E66;"> <div><strong>Voir les réponses</strong><br><span style="font-size:0.85rem; color:#718096;">Permet de consulter les réponses aux questionnaires.</span></div></label>
                            <label style="display: flex; gap: 12px; align-items: start; cursor: pointer;"><input type="checkbox" class="cap-checkbox" data-cap="cyclo_manage_surveys" style="margin-top: 4px; accent-color: #1A9E66;"> <div><strong>Gérer les questionnaires</strong><br><span style="font-size:0.85rem; color:#718096;">Permet de modifier les questionnaires existants.</span></div></label>
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 24px;">
                        <button id="btn-save-permissions" class="btn-primary" style="padding: 12px 24px;">Sauvegarder les modifications</button>
                    </div>
                </div>
            </div>
        `;

        const container = document.getElementById('permissions-container');
        const checkboxes = document.querySelectorAll('.cap-checkbox');
        let currentRole = null;

        // Met à jour les compteurs dynamiquement
        function updateCounters(roleName) {
            const checkedCount = document.querySelectorAll('.cap-checkbox:checked').length;
            document.getElementById('perm-active-count').innerText = `${checkedCount} / 11`;

            let roleDisplay = "Utilisateur";
            if (roleName === 'cyclo_editeur') roleDisplay = "Éditeur";
            if (roleName === 'administrator') roleDisplay = "Admin";
            document.getElementById('perm-role-name').innerText = roleDisplay;
        }

        // Écouteur pour actualiser le compteur dès qu'on coche/décoche une case
        checkboxes.forEach(chk => {
            chk.addEventListener('change', () => updateCounters(currentRole));
        });

        async function loadRolePermissions(roleName) {
            container.style.opacity = '0.5';
            container.style.pointerEvents = 'none';
            currentRole = roleName;

            try {
                const res = await fetch(`/wp-json/cyclo-data/v1/permissions/${roleName}`, { headers: { 'X-WP-Nonce': apiNonce } });
                const result = await res.json();

                if (result.status === 'success') {
                    checkboxes.forEach(chk => {
                        const cap = chk.getAttribute('data-cap');
                        chk.checked = result.data[cap] === true;
                        chk.disabled = (roleName === 'administrator');
                    });

                    updateCounters(roleName);

                    container.style.opacity = '1';
                    container.style.pointerEvents = (roleName === 'administrator') ? 'none' : 'auto';
                    document.getElementById('btn-save-permissions').style.display = (roleName === 'administrator') ? 'none' : 'inline-block';
                }
            } catch (error) { console.error("Erreur chargement permissions", error); }
        }

        document.querySelectorAll('.role-select-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.role-select-btn').forEach(b => {
                    b.classList.remove('btn-primary');
                    b.classList.add('btn-secondary');
                });
                const target = e.currentTarget;
                target.classList.remove('btn-secondary');
                target.classList.add('btn-primary');

                loadRolePermissions(target.getAttribute('data-role'));
            });
        });

        document.getElementById('btn-save-permissions').addEventListener('click', async () => {
            if (!currentRole) return;
            const btn = document.getElementById('btn-save-permissions');
            btn.innerText = "Sauvegarde..."; btn.disabled = true;

            const payload = {};
            checkboxes.forEach(chk => { payload[chk.getAttribute('data-cap')] = chk.checked; });

            try {
                const res = await fetch(`/wp-json/cyclo-data/v1/permissions/${currentRole}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': apiNonce },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();
                if (result.status === 'success') alert("Permissions mises à jour avec succès.");
                else alert("Erreur : " + result.message);
            } catch (error) { alert("Erreur réseau."); }

            btn.innerText = "Sauvegarder les modifications"; btn.disabled = false;
        });

        // Simule le premier clic pour charger l'Éditeur par défaut
        document.querySelector('[data-role="cyclo_editeur"]').click();
    }

    // --- 6. TIMELINE INTELLIGENTE AVEC COMPARAISON ---
    function initTimelineChart(ctx, timelineData, compareData = null) {
        if (!ctx) return;
        let labels = [], chartData = [], compData = [];

        if (appState.filterType === 'annee') {
            labels = ['Jan.', 'Fév.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sep.', 'Oct.', 'Nov.', 'Déc.'];
            chartData = new Array(12).fill(0); compData = new Array(12).fill(0);
            if(timelineData) timelineData.forEach(r => chartData[parseInt(r.label) - 1] = parseInt(r.total));
            if(compareData) compareData.forEach(r => compData[parseInt(r.label) - 1] = parseInt(r.total));
        } else if (appState.filterType === 'jour') {
            labels = Array.from({length: 24}, (_, i) => `${i}h`);
            chartData = new Array(24).fill(0); compData = new Array(24).fill(0);
            if(timelineData) timelineData.forEach(r => chartData[parseInt(r.label)] = parseInt(r.total));
            if(compareData) compareData.forEach(r => compData[parseInt(r.label)] = parseInt(r.total));
        } else if (appState.filterType === 'semaine') {
            labels = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
            chartData = new Array(7).fill(0); compData = new Array(7).fill(0);
            if(timelineData) timelineData.forEach(r => chartData[parseInt(r.label)] = parseInt(r.total));
            if(compareData) compareData.forEach(r => compData[parseInt(r.label)] = parseInt(r.total));
        } else {
            labels = Array.from({length: 31}, (_, i) => `${i+1}`);
            chartData = new Array(31).fill(0); compData = new Array(31).fill(0);
            if(timelineData) timelineData.forEach(r => chartData[parseInt(r.label) - 1] = parseInt(r.total));
            if(compareData) compareData.forEach(r => compData[parseInt(r.label) - 1] = parseInt(r.total));
        }

        const datasets = [{
            label: 'Période actuelle',
            data: chartData,
            borderColor: '#1A9E66',
            backgroundColor: 'rgba(26, 158, 102, 0.1)',
            borderWidth: 3, // Ligne plus épaisse
            pointBackgroundColor: '#1A9E66', // Points verts
            pointBorderColor: '#ffffff',
            pointRadius: 4, // Taille des points
            pointHoverRadius: 6,
            fill: true,
            tension: 0.4
        }];

        if (appState.compare && compareData) {
            datasets.push({
                label: 'Période de comparaison',
                data: compData,
                borderColor: '#ED8936', // Orange dynamique BI
                borderDash: [6, 6], // Tirets plus marqués et espacés
                borderWidth: 3, // Ligne de même épaisseur pour équilibrer
                backgroundColor: 'transparent',
                pointBackgroundColor: '#ED8936', // Points oranges
                pointBorderColor: '#ffffff',
                pointRadius: 4, // Points visibles sur les intersections
                pointHoverRadius: 6,
                fill: false,
                tension: 0.4
            });
        }

        new Chart(ctx, {
            type: 'line',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: appState.compare },
                    datalabels: { display: false } // Force la désactivation des % ici
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                },
                interaction: {
                    mode: 'index', // Affiche les deux valeurs en même temps au survol
                    intersect: false
                }
            }
        });
    }

    // --- 7. ÉCOUTEURS D'ÉVÉNEMENTS ---
    document.querySelectorAll('.nav-item[data-action="route"], .nav-item[data-action="route-and-toggle"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active')); e.currentTarget.classList.add('active');

            appState.module = e.currentTarget.getAttribute('data-module');
            appState.level = e.currentTarget.getAttribute('data-level');
            appState.entityId = e.currentTarget.getAttribute('data-id');
            appState.entityName = e.currentTarget.getAttribute('data-name') || "Vue d'ensemble";

            if (e.currentTarget.getAttribute('data-action') === 'route-and-toggle') {
                const sub = document.getElementById(e.currentTarget.getAttribute('data-target'));
                if(sub) {
                    const isHidden = sub.style.display === 'none';
                    sub.style.display = isHidden ? 'block' : 'none';
                    const chevron = e.currentTarget.querySelector('.chevron');
                    if(chevron) chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            }
            if(window.innerWidth <= 1024) closeMobileMenu();
            fetchApiData();
        });
    });

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active')); e.currentTarget.classList.add('active');
            const f = e.currentTarget.innerText.toLowerCase(); const t = new Date();
            if (f.includes('jour')) { appState.filterType = 'jour'; appState.dateStart = t.toISOString().split('T')[0]; appState.dateEnd = appState.dateStart; }
            else if (f.includes('semaine')) { appState.filterType = 'semaine'; const d = new Date(t); d.setDate(d.getDate() - (d.getDay()||7) + 1); appState.dateStart = d.toISOString().split('T')[0]; appState.dateEnd = t.toISOString().split('T')[0]; }
            else if (f.includes('mois')) { appState.filterType = 'mois'; appState.dateStart = new Date(t.getFullYear(), t.getMonth(), 1).toISOString().split('T')[0]; appState.dateEnd = t.toISOString().split('T')[0]; }
            else { appState.filterType = 'annee'; appState.dateStart = `${t.getFullYear()}-01-01`; appState.dateEnd = `${t.getFullYear()}-12-31`; }
            fetchApiData();
        });
    });

    const mobileBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.cyclo-sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    function closeMobileMenu() {
        if(sidebar) sidebar.classList.remove('is-open');
        if(overlay) overlay.classList.remove('is-active');
    }

    if (mobileBtn && sidebar) {
        mobileBtn.addEventListener('click', (e) => {
            e.preventDefault();
            sidebar.classList.toggle('is-open');
            if(overlay) overlay.classList.toggle('is-active');
        });
    }

    if (overlay) overlay.addEventListener('click', closeMobileMenu);

    const btnCsv = document.getElementById('export-csv-btn');
    if (btnCsv) btnCsv.addEventListener('click', () => { window.location.href = `/wp-json/cyclo-data/v1/export-csv?${new URLSearchParams({...appState, _wpnonce: apiNonce}).toString()}`; });

    const btnPdf = document.getElementById('export-pdf-btn');
    if (btnPdf) btnPdf.addEventListener('click', () => {
        const element = document.getElementById('dashboard-root');
        const originalText = btnPdf.innerHTML;
        btnPdf.innerHTML = "Génération..."; btnPdf.disabled = true;
        html2pdf().set({ margin: 10, filename: `Export_${appState.module}.pdf`, image: { type: 'jpeg', quality: 0.98 }, html2canvas: { scale: 2 }, jsPDF: { format: 'a4', orientation: 'landscape' } }).from(element).save().then(() => { btnPdf.innerHTML = originalText; btnPdf.disabled = false; });
    });

    // Logique de la Modale "Nouvel utilisateur"
    const modalCreateUser = document.getElementById('modal-create-user');
    const formCreateUser = document.getElementById('form-create-user');
    const successScreen = document.getElementById('create-user-success');

    // Ouverture de la Modale "Nouvel Utilisateur"
    function openCreateUserModal() {
        formCreateUser.style.display = 'block';
        successScreen.style.display = 'none';
        formCreateUser.reset();
        modalCreateUser.style.display = 'flex'; // Affiche la modale
    }

    // Fermeture de la modale
    document.querySelectorAll('.close-modal, .cancel-modal').forEach(btn => {
        btn.addEventListener('click', () => { modalCreateUser.style.display = 'none'; });
    });

    // Gérer la soumission vers l'API REST
    formCreateUser.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Récupération des données
        const payload = {
            first_name: document.getElementById('new_user_firstname').value,
            last_name: document.getElementById('new_user_lastname').value,
            email: document.getElementById('new_user_email').value,
            role: document.getElementById('new_user_role').value
        };

        try {
            // Envoi à l'API (nécessite le nonce de sécurité WP)
            const response = await fetch('/wp-json/cyclo-data/v1/users/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': apiNonce // CORRECTION : On utilise la variable apiNonce
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                // Basculer l'interface sur l'écran de succès
                formCreateUser.style.display = 'none';
                successScreen.style.display = 'block';

                // Afficher le mot de passe généré
                document.getElementById('generated_password').value = result.temp_password;

                // Recharger la liste des utilisateurs en arrière-plan
                // loadUsersList(); // Appelle ta fonction existante ici
            } else {
                alert("Erreur : " + (result.message || "Impossible de créer l'utilisateur"));
            }
        } catch (error) {
            console.error("Erreur réseau :", error);
            alert("Erreur de connexion à l'API.");
        }
    });

    // Bouton copier le mot de passe
    document.getElementById('copy-password').addEventListener('click', () => {
        const pwdField = document.getElementById('generated_password');
        pwdField.select();
        document.execCommand('copy');
        alert('Mot de passe copié !'); // À remplacer par un Toast UI si tu en as un
    });

    // --- 8. RENDU DES PARAMÈTRES ---
    function renderSettingsView(data) {
        const headerActions = document.querySelector('.header-actions');
        if (headerActions) headerActions.style.display = 'none';

        // Bloc Notifications (Uniquement si Admin)
        let notificationsHtml = '';
        if (data.is_admin) {
            notificationsHtml = `
                <div class="settings-section" style="background: white; border: 1px solid #E2E8F0; border-radius: 8px; margin-bottom: 24px; padding: 24px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px; border-bottom: 1px solid #EDF2F7; padding-bottom: 12px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#718096" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                        <h3 style="margin: 0; font-size: 1.1rem; color: #2D3748;">Notifications</h3>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <div>
                            <strong style="display: block; color: #2D3748;">Alertes par email</strong>
                            <span style="font-size: 0.85rem; color: #718096;">Recevoir un email lors d'un pic de consultations</span>
                        </div>
                        <label class="toggle-switch" style="position: relative; display: inline-block; width: 44px; height: 24px;">
                            <input type="checkbox" id="opt_alert_email" ${data.global.alert_email === '1' ? 'checked' : ''} style="opacity: 0; width: 0; height: 0;">
                            <span class="slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #CBD5E0; border-radius: 24px; transition: .4s;"></span>
                        </label>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="display: block; color: #2D3748;">Rapport hebdomadaire</strong>
                            <span style="font-size: 0.85rem; color: #718096;">Envoi automatique chaque lundi matin</span>
                        </div>
                        <label class="toggle-switch" style="position: relative; display: inline-block; width: 44px; height: 24px;">
                            <input type="checkbox" id="opt_weekly_report" ${data.global.weekly_report === '1' ? 'checked' : ''} style="opacity: 0; width: 0; height: 0;">
                            <span class="slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #CBD5E0; border-radius: 24px; transition: .4s;"></span>
                        </label>
                    </div>
                </div>
            `;
        }

        root.innerHTML = `
            <div style="grid-column: 1 / -1; width: 100%; max-width: 800px; margin: 0 auto; padding-bottom: 40px;">
            
            ${notificationsHtml}

            <div class="settings-section" style="background: white; border: 1px solid #E2E8F0; border-radius: 8px; margin-bottom: 24px; padding: 24px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid #EDF2F7; padding-bottom: 12px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#718096" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                    <h3 style="margin: 0; font-size: 1.1rem; color: #2D3748;">Langue & Région</h3>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <strong style="display: block; color: #2D3748;">Langue de l'interface</strong>
                        <span style="font-size: 0.85rem; color: #718096;">Langue affichée dans le back-office</span>
                    </div>
                    <select id="opt_locale" class="cyclo-select" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #E2E8F0; min-width: 200px;">
                        <option value="fr_FR" ${data.user.locale === 'fr_FR' ? 'selected' : ''}>Français</option>
                        <option value="en_US" ${data.user.locale === 'en_US' ? 'selected' : ''}>English</option>
                    </select>
                </div>
            </div>

            <div class="settings-section" style="background: white; border: 1px solid #E2E8F0; border-radius: 8px; margin-bottom: 24px; padding: 24px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid #EDF2F7; padding-bottom: 12px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#718096" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    <h3 style="margin: 0; font-size: 1.1rem; color: #2D3748;">Apparence</h3>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <strong style="display: block; color: #2D3748;">Densité d'affichage</strong>
                        <span style="font-size: 0.85rem; color: #718096;">Compacité de l'interface</span>
                    </div>
                    <select id="opt_density" class="cyclo-select" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #E2E8F0; min-width: 200px;">
                        <option value="confortable" ${data.user.density === 'confortable' ? 'selected' : ''}>Confortable</option>
                        <option value="compact" ${data.user.density === 'compact' ? 'selected' : ''}>Compacte</option>
                    </select>
                </div>
            </div>

            <div style="text-align: right;">
                <button id="btn-save-settings" class="btn-primary" style="padding: 10px 24px; font-size: 1rem; display: flex; align-items: center; gap: 8px; margin-left: auto;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    Sauvegarder
                </button>
            </div>
        </div>
        `;

        // Écouteur pour la sauvegarde
        document.getElementById('btn-save-settings').addEventListener('click', async () => {
            const btn = document.getElementById('btn-save-settings');
            const originalText = btn.innerHTML;
            btn.innerText = "Enregistrement..."; btn.disabled = true;

            const payload = {
                density: document.getElementById('opt_density').value,
                locale: document.getElementById('opt_locale').value
            };

            // Ajoute les données globales si elles existent dans le DOM (Admin uniquement)
            const alertEmail = document.getElementById('opt_alert_email');
            if (alertEmail) payload.alert_email = alertEmail.checked ? '1' : '0';

            const weeklyReport = document.getElementById('opt_weekly_report');
            if (weeklyReport) payload.weekly_report = weeklyReport.checked ? '1' : '0';

            try {
                const res = await fetch('/wp-json/cyclo-data/v1/settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': apiNonce },
                    body: JSON.stringify(payload)
                });

                const result = await res.json();
                if (result.status === 'success') {
                    btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Enregistré`;
                    btn.style.backgroundColor = '#148354';
                    setTimeout(() => { btn.innerHTML = originalText; btn.style.backgroundColor = ''; btn.disabled = false; }, 2000);
                }
            } catch (error) {
                alert("Erreur de sauvegarde.");
                btn.innerHTML = originalText; btn.disabled = false;
            }
        });



        // Petit script intégré pour le style CSS des "Toggles" dynamiques
        const style = document.createElement('style');
        style.innerHTML = `
            .toggle-switch input:checked + .slider { background-color: #1A9E66; }
            .toggle-switch .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: .4s; }
            .toggle-switch input:checked + .slider:before { transform: translateX(20px); }
        `;
        document.head.appendChild(style);
    }

    // --- 9. SÉLECTEUR DE DATES (FLATPICKR) ---
    const datePickerBtn = document.getElementById('date-picker-btn');
    const datePickerLabel = document.getElementById('date-picker-label');

    if (datePickerBtn && typeof flatpickr !== 'undefined') {

        // Initialisation de la date affichée au chargement
        const currentYear = new Date().getFullYear();
        datePickerLabel.innerText = `1 jan. - 31 déc. ${currentYear}`;

        // On stocke l'instance dans une variable
        const fpInstance = flatpickr(datePickerBtn, {
            mode: "range",
            locale: "fr",
            dateFormat: "Y-m-d",
            defaultDate: [appState.dateStart, appState.dateEnd],
            maxDate: "today",

            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const formatter = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short' });
                    const yearFormatter = new Intl.DateTimeFormat('fr-FR', { year: 'numeric' });

                    const startStr = formatter.format(selectedDates[0]);
                    const endStr = formatter.format(selectedDates[1]);
                    const yearStr = yearFormatter.format(selectedDates[1]);

                    datePickerLabel.innerText = `${startStr} - ${endStr} ${yearStr}`;

                    appState.dateStart = instance.formatDate(selectedDates[0], "Y-m-d");
                    appState.dateEnd = instance.formatDate(selectedDates[1], "Y-m-d");
                    appState.filterType = 'custom';

                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    fetchApiData();
                }
            }
        });

        // FORCE l'ouverture du calendrier au clic sur le bouton
        datePickerBtn.addEventListener('click', (e) => {
            e.preventDefault();
            fpInstance.open();
        });

    } else {
        console.error("Flatpickr n'est pas chargé ou le bouton date-picker-btn est introuvable.");
    }

    // --- GESTION DE LA COMPARAISON ET DU 2EME CALENDRIER ---
    const compareCheckbox = document.getElementById('compare-checkbox');
    const comparePickerBtn = document.getElementById('compare-picker-btn');
    const comparePickerLabel = document.getElementById('compare-picker-label');
    let fpCompareInstance = null;

    if (comparePickerBtn && typeof flatpickr !== 'undefined') {
        // Initialisation du 2ème Flatpickr
        fpCompareInstance = flatpickr(comparePickerBtn, {
            mode: "range",
            locale: "fr",
            dateFormat: "Y-m-d",
            maxDate: "today",
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const formatter = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short' });
                    const yearFormatter = new Intl.DateTimeFormat('fr-FR', { year: 'numeric' });
                    comparePickerLabel.innerText = `${formatter.format(selectedDates[0])} - ${formatter.format(selectedDates[1])} ${yearFormatter.format(selectedDates[1])}`;

                    appState.compareStart = instance.formatDate(selectedDates[0], "Y-m-d");
                    appState.compareEnd = instance.formatDate(selectedDates[1], "Y-m-d");

                    fetchApiData(); // Recharge les graphiques avec les nouvelles dates !
                }
            }
        });

        comparePickerBtn.addEventListener('click', (e) => {
            e.preventDefault();
            fpCompareInstance.open();
        });
    }

    if (compareCheckbox) {
        compareCheckbox.addEventListener('change', (e) => {
            appState.compare = e.target.checked;

            if (appState.compare) {
                comparePickerBtn.style.display = 'flex'; // Affiche le bouton

                // Si aucune date de comparaison n'a encore été choisie, on met N-1 par défaut
                if (!appState.compareStart) {
                    let ds = new Date(appState.dateStart);
                    let de = new Date(appState.dateEnd);
                    ds.setFullYear(ds.getFullYear() - 1);
                    de.setFullYear(de.getFullYear() - 1);

                    appState.compareStart = ds.toISOString().split('T')[0];
                    appState.compareEnd = de.toISOString().split('T')[0];

                    const formatter = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short' });
                    const yearFormatter = new Intl.DateTimeFormat('fr-FR', { year: 'numeric' });
                    comparePickerLabel.innerText = `${formatter.format(ds)} - ${formatter.format(de)} ${yearFormatter.format(de)}`;

                    if (fpCompareInstance) fpCompareInstance.setDate([ds, de]);
                }
            } else {
                comparePickerBtn.style.display = 'none'; // Cache le bouton
            }
            fetchApiData();
        });
    }
    // Lancement
    fetchApiData();
});

