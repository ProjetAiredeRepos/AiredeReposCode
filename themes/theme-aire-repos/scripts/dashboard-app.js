// Fichier : wp-content/themes/theme-aire-repos/scripts/dashboard-app.js

document.addEventListener('DOMContentLoaded', () => {
    const appLayout = document.querySelector('.cyclo-app-layout');
    if (!appLayout) return;

    const apiNonce = appLayout.getAttribute('data-nonce');
    const root = document.getElementById('dashboard-root');

    const THEME = { primary: '#1A9E66', primaryLight: '#E7F5EF', textMain: '#2D3748', textMuted: '#718096' };

    let appState = {
        module: 'discovery', level: 'global', entityId: 'all', entityName: "Vue d'ensemble",
        filterType: 'annee', dateStart: `${new Date().getFullYear()}-01-01`, dateEnd: `${new Date().getFullYear()}-12-31`
    };

    async function fetchApiData() {
        const endpoint = appState.module === 'discovery' ? 'discovery-overview' : 'survey-overview';
        const params = new URLSearchParams({ level: appState.level, id: appState.entityId, start_date: appState.dateStart, end_date: appState.dateEnd, filter_type: appState.filterType });

        root.innerHTML = '<div class="loading-state">Analyse des données en cours...</div>';
        updateHeaderTitles();

        try {
            const res = await fetch(`/wp-json/cyclo-data/v1/${endpoint}?${params.toString()}`, { headers: { 'X-WP-Nonce': apiNonce } });
            if(!res.ok) throw new Error("Erreur Serveur");
            const result = await res.json();

            if (appState.module === 'discovery') renderDiscoveryOverview(result.data);
            else renderSurveyOverview(result.data);
        } catch (error) {
            root.innerHTML = `<div class="error-state">${error.message}</div>`;
        }
    }

    function updateHeaderTitles() {
        const modName = appState.module === 'discovery' ? "Outils de découverte" : "Questionnaires";
        let lvlName = appState.level === 'route' ? ` <span class="sep">></span> ${appState.entityName}` : (appState.level === 'aire' ? ` <span class="sep">></span> Itinéraire <span class="sep">></span> ${appState.entityName}` : "");
        const title = appState.level === 'global' ? "Vue d'ensemble" : appState.entityName;

        document.querySelector('.breadcrumbs').innerHTML = `Administration <span class="sep">></span> ${modName}${lvlName}`;
        const titleH1 = document.getElementById('dash-main-title');
        if(titleH1) titleH1.innerText = title;
    }

    // --- RENDU DÉCOUVERTE ---
    function renderDiscoveryOverview(data) {
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
            <div class="dash-card card-half"><div class="card-header"><h3>Consultations totales</h3></div><div class="kpi-value">${data.total_consultations.toLocaleString('fr-FR')}</div></div>
            <div class="dash-card card-half"><div class="card-header"><h3>Origine géographique</h3></div><div class="chart-container" style="height: 250px;"><canvas id="chart-geo"></canvas></div></div>
            <div class="dash-card card-half"><div class="card-header"><h3>Répartition par tranche horaire</h3></div><div class="chart-container" style="height: 250px;"><canvas id="chart-hour"></canvas></div></div>
            <div class="dash-card card-half"><div class="card-header"><h3>Liens les plus cliqués</h3></div><div style="max-height:250px; overflow-y:auto;">${linksHtml}</div></div>
        `;
        initTimelineChart(document.getElementById('chart-evo'), data.timeline);
        if(document.getElementById('chart-geo') && data.geo_stats && data.geo_stats.length > 0) new Chart(document.getElementById('chart-geo'), { type: 'doughnut', data: { labels: data.geo_stats.map(i=>i.code_langue.toUpperCase()), datasets: [{ data: data.geo_stats.map(i=>i.total), backgroundColor: ['#1A9E66','#41C48F','#8EEABF'] }] }, options: { responsive:true, maintainAspectRatio:false, cutout:'65%' } });
        if(document.getElementById('chart-hour') && data.hourly_stats) {
            const hData = new Array(24).fill(0);
            data.hourly_stats.forEach(r => hData[parseInt(r.heure)] = parseInt(r.total));
            new Chart(document.getElementById('chart-hour'), { type: 'bar', data: { labels: Array.from({length:24}, (_,i)=>`${i}h`), datasets: [{ data: hData, backgroundColor: THEME.primary, borderRadius:4 }] }, options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }});
        }
    }

    // --- RENDU QUESTIONNAIRES ---
    function renderSurveyOverview(data) {
        const sCount = {};
        if (data.q4_raw) data.q4_raw.forEach(r => { if(r.q4_connaissance) r.q4_connaissance.split(',').forEach(s => { const src=s.trim(); sCount[src]=(sCount[src]||0)+1; }); });

        root.innerHTML = `
            <div class="dash-card card-third"><div class="card-header"><h3>Réponses</h3></div><div class="kpi-value">${data.total_reponses}</div></div>
            <div class="dash-card card-third"><div class="card-header"><h3>Satisfaction</h3></div><div class="kpi-value">${data.indice_satisfaction}/5</div></div>
            <div class="dash-card card-third"><div class="card-header"><h3>Radar des Scores</h3></div><div class="chart-container" style="height: 150px;"><canvas id="chart-radar"></canvas></div></div>
            
            <div class="dash-card card-full"><div class="card-header"><h3>Évolution des réponses</h3><p>Période: ${appState.filterType}</p></div><div class="chart-container main-chart"><canvas id="chart-survey-timeline"></canvas></div></div>
            
            <div class="dash-card card-third"><div class="card-header"><h3>1. Note Aire</h3></div><div class="chart-container" style="height: 200px;"><canvas id="chart-q1"></canvas></div></div>
            <div class="dash-card card-third"><div class="card-header"><h3>2. Note Sécurité</h3></div><div class="chart-container" style="height: 200px;"><canvas id="chart-q2"></canvas></div></div>
            <div class="dash-card card-third"><div class="card-header"><h3>3. Note Services</h3></div><div class="chart-container" style="height: 200px;"><canvas id="chart-q3"></canvas></div></div>
            
            <div class="dash-card card-full"><div class="card-header"><h3>4. Sources de découverte</h3></div><div class="chart-container" style="height: 250px;"><canvas id="chart-q4"></canvas></div></div>
            
            <div class="dash-card card-third"><div class="card-header"><h3>Origine</h3></div><div class="chart-container" style="height: 200px;"><canvas id="chart-orig"></canvas></div></div>
            <div class="dash-card card-third"><div class="card-header"><h3>Accompagnement</h3></div><div class="chart-container" style="height: 200px;"><canvas id="chart-acc"></canvas></div></div>
            <div class="dash-card card-third"><div class="card-header"><h3>Pays</h3></div><div class="chart-container" style="height: 200px;"><canvas id="chart-pays"></canvas></div></div>
        `;

        initTimelineChart(document.getElementById('chart-survey-timeline'), data.timeline);

        // FIX : Ajout du label "Score" pour retirer le "undefined"
        if(document.getElementById('chart-radar') && data.total_reponses > 0) {
            new Chart(document.getElementById('chart-radar'), { type: 'radar', data: { labels: ['Aire', 'Sécurité', 'Services'], datasets: [{ label: 'Score sur 100', data: data.radar_scores, backgroundColor: THEME.primaryLight, borderColor: THEME.primary }] }, options:{responsive:true, maintainAspectRatio:false, scales: { r: { min: 0, max: 100, ticks:{display:false} } }, plugins: { legend: { display: false } }} });
        }

        const renderMiniBar = (id, distData) => {
            if(!document.getElementById(id)) return;
            const vals = [0,0,0,0];
            if(distData) distData.forEach(r => { if(r.note>=1 && r.note<=4) vals[parseInt(r.note)-1] = parseInt(r.total); });
            new Chart(document.getElementById(id), { type: 'bar', data: { labels: ['1/4', '2/4', '3/4', '4/4'], datasets: [{ label: 'Votes', data: vals, backgroundColor: THEME.primary, borderRadius: 4 }] }, options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }});
        };
        renderMiniBar('chart-q1', data.q1_dist); renderMiniBar('chart-q2', data.q2_dist); renderMiniBar('chart-q3', data.q3_dist);

        if(document.getElementById('chart-q4') && Object.keys(sCount).length > 0) new Chart(document.getElementById('chart-q4'), { type: 'bar', data: { labels: Object.keys(sCount), datasets: [{ label: 'Mentions', data: Object.values(sCount), backgroundColor: THEME.primary, borderRadius: 4 }] }, options: { indexAxis: 'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }});
        if(document.getElementById('chart-orig') && data.origine && data.origine.length > 0) new Chart(document.getElementById('chart-orig'), { type: 'doughnut', data: { labels: data.origine.map(i=> i.profil_origine === 'fr' ? 'Français' : 'Étranger'), datasets: [{ data: data.origine.map(i=>i.total), backgroundColor:['#1A9E66', '#8EEABF'] }] }, options: { responsive:true, maintainAspectRatio:false, cutout:'65%' }});
        if(document.getElementById('chart-acc') && data.accompagnement && data.accompagnement.length > 0) new Chart(document.getElementById('chart-acc'), { type: 'doughnut', data: { labels: data.accompagnement.map(i=>i.profil_accompagnement), datasets: [{ data: data.accompagnement.map(i=>i.total), backgroundColor:['#1A9E66', '#F6AD55', '#E53E3E', '#2F855A'] }] }, options: { responsive:true, maintainAspectRatio:false, cutout:'65%' }});

        // FIX : Initialisation du graphique PAYS
        if(document.getElementById('chart-pays')) {
            if (data.pays && data.pays.length > 0) {
                new Chart(document.getElementById('chart-pays'), { type: 'bar', data: { labels: data.pays.map(i=>i.profil_pays.toUpperCase()), datasets: [{ label: 'Visiteurs', data: data.pays.map(i=>i.total), backgroundColor: THEME.primary, borderRadius: 4 }] }, options: { indexAxis: 'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }});
            } else {
                document.getElementById('chart-pays').parentElement.innerHTML = '<div style="text-align:center; color:#999; padding-top:20px;">Aucune donnée</div>';
            }
        }
    }

    // --- TIMELINE INTELLIGENTE ---
    function initTimelineChart(ctx, timelineData) {
        if (!ctx) return;
        let labels = [], chartData = [];

        if (appState.filterType === 'annee') {
            labels = ['Jan.', 'Fév.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sep.', 'Oct.', 'Nov.', 'Déc.'];
            chartData = new Array(12).fill(0);
            if(timelineData) timelineData.forEach(r => chartData[parseInt(r.label) - 1] = parseInt(r.total));
        } else if (appState.filterType === 'jour') {
            labels = Array.from({length: 24}, (_, i) => `${i}h`);
            chartData = new Array(24).fill(0);
            if(timelineData) timelineData.forEach(r => chartData[parseInt(r.label)] = parseInt(r.total));
        } else if (appState.filterType === 'semaine') {
            labels = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
            chartData = new Array(7).fill(0);
            if(timelineData) timelineData.forEach(r => chartData[parseInt(r.label)] = parseInt(r.total));
        } else {
            labels = Array.from({length: 31}, (_, i) => `${i+1}`);
            chartData = new Array(31).fill(0);
            if(timelineData) timelineData.forEach(r => chartData[parseInt(r.label) - 1] = parseInt(r.total));
        }

        new Chart(ctx, { type: 'line', data: { labels: labels, datasets: [{ label: 'Volume', data: chartData, borderColor: THEME.primary, backgroundColor: THEME.primaryLight, fill: true, tension: 0.4 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } } });
    }

    // --- ÉCOUTEURS D'ÉVÉNEMENTS (Navigation) ---
    document.querySelectorAll('.nav-item[data-action="route"], .nav-item[data-action="route-and-toggle"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active')); e.currentTarget.classList.add('active');
            appState.module = e.currentTarget.getAttribute('data-module'); appState.level = e.currentTarget.getAttribute('data-level'); appState.entityId = e.currentTarget.getAttribute('data-id'); appState.entityName = e.currentTarget.getAttribute('data-name') || "Vue d'ensemble";

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

    // --- GESTION DU MENU MOBILE ET OVERLAY (CORRIGÉ ET BLINDÉ) ---
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

    // Exports
    const btnCsv = document.getElementById('export-csv-btn');
    if (btnCsv) btnCsv.addEventListener('click', () => { window.location.href = `/wp-json/cyclo-data/v1/export-csv?${new URLSearchParams({...appState, _wpnonce: apiNonce}).toString()}`; });

    const btnPdf = document.getElementById('export-pdf-btn');
    if (btnPdf) btnPdf.addEventListener('click', () => {
        const element = document.getElementById('dashboard-root');
        const originalText = btnPdf.innerHTML;
        btnPdf.innerHTML = "Génération..."; btnPdf.disabled = true;
        html2pdf().set({ margin: 10, filename: `Export_${appState.module}.pdf`, image: { type: 'jpeg', quality: 0.98 }, html2canvas: { scale: 2 }, jsPDF: { format: 'a4', orientation: 'landscape' } }).from(element).save().then(() => { btnPdf.innerHTML = originalText; btnPdf.disabled = false; });
    });

    // Lancement
    fetchApiData();
});