// Fichier : tracking.js
// Script autonome gérant le cycle de vie du tracking comportemental

document.addEventListener('DOMContentLoaded', async () => {
    // 1. Lecture de l'ID injecté par WordPress (Zéro paramètre URL nécessaire)
    const aireId = typeof cycloData !== 'undefined' ? cycloData.aire_id : null;
    // Détection de la langue du navigateur (ex: retourne 'fr' depuis 'fr-FR')
    const userLang = navigator.language.slice(0,2);

    // Variables de session
    let currentVisitId = null;
    const sessionStartTime = Date.now();

    // Arrêt de l'exécution si le paramètre 'aire' est manquant
    if (!aireId) return;

    // 2. Appel initial : Création de l'entrée dans la base de données WordPress
    try{
        const response = await fetch('/wp-json/aire-repos/v1/scan', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                id_aire: parseInt(aireId, 10),
                code_langue: userLang
            })
        });

        const data = await response.json();

        // Enregistrement de l'identifiant unique retourné par le serveur
        if (data.status === 'success') {
            currentVisitId = data.id_visite;
        }
    } catch (error) {
        // Capture des erreurs réseau ou de parsing JSON
        console.error("Erreur d'initialisation du suivi: ", error);
    }

    // 3. Capture de la fin de session via l'événement 'visibilitychange'
    document.addEventListener('visibilitychange', function() {

        // Si l'application passe en arrière-plan et que la visite a bien été initiée
        if (document.visibilityState === 'hidden' && currentVisitId) {
            // Calcul du temps passé en secondes
            const timeSpentSeconds = Math.floor((Date.now() - sessionStartTime) / 1000);

            // Préparation des données pour sendBeacon()
            const payloadData = JSON.stringify({
                id_visite: currentVisitId,
                temps_total: timeSpentSeconds
            });

            // Ajout de [payloadData] dans le Blob
            const blob = new Blob([payloadData], { type: 'application/json' });

            // Expédition de la requête en arrière-plan vers WordPress
            navigator.sendBeacon('/wp-json/aire-repos/v1/update-time', blob);
        }
    });
});