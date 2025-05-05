document.addEventListener('DOMContentLoaded', function() {
    // Gérer les notifications
    const notificationsMenu = document.querySelector('.notifications-menu');
    const notificationsToggle = notificationsMenu?.querySelector('.nav-link');
    
    if (notificationsToggle) {
        notificationsToggle.addEventListener('click', function(e) {
            e.preventDefault();
            notificationsMenu.classList.toggle('show');
        });
        
        // Fermer le menu si on clique en dehors
        document.addEventListener('click', function(e) {
            if (!notificationsMenu.contains(e.target)) {
                notificationsMenu.classList.remove('show');
            }
        });
    }
    
    // Marquer les alertes comme lues
    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const notificationItem = this.closest('.notification-item');
            const alertId = notificationItem.dataset.alertId;
            
            fetch('index.php?uri=mark-alert-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ alert_id: alertId })
            })
            .then(response => {
                if (!response.ok) throw new Error('Erreur réseau');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Supprimer la notification
                    notificationItem.remove();
                    
                    // Mettre à jour le compteur
                    const badge = document.querySelector('.notifications-menu .badge');
                    if (badge) {
                        const count = parseInt(badge.textContent) - 1;
                        if (count > 0) {
                            badge.textContent = count;
                        } else {
                            badge.remove();
                        }
                    }
                    
                    // Afficher "aucune notification" si c'était la dernière
                    const notificationsList = document.getElementById('notifications-list');
                    if (notificationsList.children.length === 0) {
                        notificationsList.innerHTML = '<div class="no-notifications">Aucune nouvelle notification</div>';
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        });
    }

    // Marquer une alerte comme lue
    document.querySelectorAll('.mark-read').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const alertItem = this.closest('.alert-item');
            const alertId = alertItem.dataset.alertId;
            
            fetch('index.php?uri=mark-alert-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ alert_id: alertId })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alertItem.remove();
                    updateAlertCount();
                } else {
                    throw new Error(data.message || 'Erreur lors du traitement');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Impossible de marquer l\'alerte comme lue. Veuillez réessayer.');
            });
        });
    });

    // Mettre à jour le compteur d'alertes
    function updateAlertCount() {
        const alertsList = document.querySelector('.alerts-list');
        const remainingAlerts = alertsList.querySelectorAll('.alert-item').length;
        const badge = document.querySelector('.alerts-badge');
        const container = document.querySelector('.alerts-container');
        
        if (remainingAlerts > 0) {
            badge.dataset.count = remainingAlerts;
        } else if (container) {
            container.remove();
        }
    }
});