document.addEventListener('DOMContentLoaded', function() {
    // Menu responsive toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navbar = document.querySelector('.navbar');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            navbar.classList.toggle('active');
        });
    }
    
    // Fermer le menu lors d'un clic à l'extérieur
    document.addEventListener('click', function(event) {
        if (!navbar.contains(event.target) && navbar.classList.contains('active')) {
            navbar.classList.remove('active');
        }
    });
    
    // Ajouter une classe active au lien courant
    const currentPage = window.location.href;
    const navLinks = document.querySelectorAll('.navbar a');
    
    navLinks.forEach(link => {
        if (currentPage.includes(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });
});