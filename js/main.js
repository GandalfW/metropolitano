/**
 * Lógica de Interfaz de Usuario para Centro Comercial Metropolitano
 */

document.addEventListener('DOMContentLoaded', () => {
    // Elementos del DOM
    const hamburger = document.getElementById('hamburger');
    const navbar = document.querySelector('.navbar');
    const header = document.getElementById('header');
    const navLinks = document.querySelectorAll('.nav-links a');
    
    // 1. Toggle del Menú Móvil
    const toggleMenu = () => {
        hamburger.classList.toggle('active');
        navbar.classList.toggle('active');
        
        // Accesibilidad: Notificar a los lectores de pantalla si el menú está desplegado
        const isExpanded = hamburger.classList.contains('active');
        hamburger.setAttribute('aria-expanded', isExpanded);
    };

    hamburger.addEventListener('click', toggleMenu);

    // 2. Cerrar menú móvil al hacer click en cualquier enlace de la navegación
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (navbar.classList.contains('active')) {
                toggleMenu();
            }
        });
    });

    // 3. Efecto visual en la barra de navegación en Scroll (Sombra y reducción de padding)
    window.addEventListener('scroll', () => {
        const scrollPosition = window.scrollY;
        header.classList.toggle('scrolled', scrollPosition > 50);
    });

    // 4. Animación de revelado (Fade-in Up) para las tarjetas al hacer scroll
    const revealElements = document.querySelectorAll('.feature-card');
    
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                // Remover el delay después de la animación para no afectar el :hover
                setTimeout(() => {
                    entry.target.style.transitionDelay = '0s';
                }, 600);
                observer.unobserve(entry.target); // Animar solo la primera vez
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: "0px 0px -50px 0px"
    });

    revealElements.forEach((el, index) => {
        el.classList.add('hidden');
        el.style.transitionDelay = `${index * 0.15}s`; // Efecto cascada
        revealObserver.observe(el);
    });
});