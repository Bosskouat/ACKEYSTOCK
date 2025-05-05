// animations.js

// Animation simple au scroll (apparition progressive)
window.addEventListener('scroll', () => {
    const elements = document.querySelectorAll('.feature, .hero h2, .hero p, .btn-primary');
    elements.forEach(el => {
      if (el.getBoundingClientRect().top < window.innerHeight - 100) {
        el.style.opacity = 1;
        el.style.transform = 'translateY(0)';
      } else {
        el.style.opacity = 0;
        el.style.transform = 'translateY(20px)';
      }
    });
  });
  
  // Apparence initiale
  document.addEventListener('DOMContentLoaded', () => {
    const elements = document.querySelectorAll('.feature, .hero h2, .hero p, .btn-primary');
    elements.forEach(el => {
      el.style.transition = 'all 0.6s ease-out';
      el.style.opacity = 0;
      el.style.transform = 'translateY(20px)';
    });
  });
  