// ================= DESAININ JAVASCRIPT =================

// ================= INITIALIZATION =================
document.addEventListener('DOMContentLoaded', function() {
    // Core initialization
    initializeParticles();
    initializeNavigation();
    initializePortfolio();
    initializeTestimonials();
    initializePriceCalculator();
    initializeAnimations();
    
    // Additional features
    revealSections();
    lazyLoad();
    preloadResources();
    improveAccessibility();
    
    // Service card click handlers
    document.querySelectorAll('.service-card').forEach(card => {
        card.addEventListener('click', () => {
            const serviceType = card.dataset.service;
            if (serviceType) {
                selectService(serviceType);
            }
        });
    });
});

// ================= PARTICLE EFFECTS =================

// Initialize floating particles
function initializeParticles() {
    const particlesContainer = document.getElementById('particles');
    const particleCount = 20;

    for (let i = 0; i < particleCount; i++) {
        createParticle(particlesContainer);
    }

    // Create new particle every 3 seconds
    setInterval(() => {
        createParticle(particlesContainer);
    }, 3000);
}

function createParticle(container) {
    const particle = document.createElement('div');
    particle.className = 'particle';
    
    // Random size and position
    const size = Math.random() * 6 + 2;
    const startX = Math.random() * window.innerWidth;
    const animationDuration = Math.random() * 10 + 15;
    
    particle.style.cssText = `
        width: ${size}px;
        height: ${size}px;
        left: ${startX}px;
        animation-duration: ${animationDuration}s;
    `;
    
    container.appendChild(particle);
    
    // Remove particle after animation
    setTimeout(() => {
        if (particle.parentNode) {
            particle.parentNode.removeChild(particle);
        }
    }, animationDuration * 1000);
}

// ================= SIDEBAR NAVIGATION =================

// Sidebar Navigation functionality
function initializeNavigation() {
    const sidebar = document.getElementById('sidebar');
    const openSidebar = document.getElementById('openSidebar');
    const closeSidebar = document.getElementById('closeSidebar');
    const toggleSidebar = document.getElementById('toggleSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainContent = document.querySelector('.main-content');
    const navItems = document.querySelectorAll('.nav-item');

    if (!sidebar) {
        console.error('Sidebar element not found!');
        return;
    }

    // Toggle sidebar (desktop)
    if (toggleSidebar) {
        toggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            if (mainContent) {
                mainContent.classList.toggle('sidebar-collapsed');
            }
            
            // Update footer margin with class toggle for smooth animation
            const footer = document.getElementById('footer');
            if (footer) {
                footer.classList.toggle('sidebar-collapsed');
            }
            
            // Update toggle icon
            const icon = toggleSidebar.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.className = 'fas fa-chevron-right';
                toggleSidebar.title = 'Expand Sidebar';
            } else {
                icon.className = 'fas fa-chevron-left';
                toggleSidebar.title = 'Collapse Sidebar';
            }
        });
    }

    // Open sidebar (mobile)
    if (openSidebar) {
        openSidebar.addEventListener('click', () => {
            sidebar.classList.add('active');
            if (sidebarOverlay) sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }

    // Close sidebar (mobile)
    if (closeSidebar) {
        closeSidebar.addEventListener('click', () => {
            sidebar.classList.remove('active');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        });
    }

    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        });
    }

    // Close sidebar on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    });

    // Active nav item highlighting
    function setActiveNavItem() {
        const currentHash = window.location.hash || '#home';
        navItems.forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('href') === currentHash) {
                item.classList.add('active');
            }
        });
    }

    // Set active on load
    setActiveNavItem();

    // Update active on hash change
    window.addEventListener('hashchange', setActiveNavItem);

    // Close mobile sidebar when clicking nav items
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });
    });

    // Header scroll effect
    window.addEventListener('scroll', () => {
        if (window.scrollY > 100) {
            header.style.background = 'rgba(0, 0, 0, 0.95)';
        } else {
            header.style.background = 'rgba(0, 0, 0, 0.9)';
        }
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// ================= PORTFOLIO SECTION =================
function initializePortfolio() {
    const portfolioGrid = document.getElementById('portfolioGrid');
    const filterBtns = document.querySelectorAll('.filter-btn');
    
    if (!portfolioGrid) return;

    // Portfolio items data
    const portfolioItems = [
        {
            title: 'Video TikTok Viral',
            category: 'video',
            icon: 'fas fa-video',
            description: 'Edit video TikTok dengan transisi smooth dan efek trending'
        },
        {
            title: 'Logo Minimalis',
            category: 'design',
            icon: 'fas fa-paint-brush',
            description: 'Desain logo modern dan memorable untuk startup teknologi'
        },
        {
            title: 'Feed Instagram',
            category: 'social',
            icon: 'fab fa-instagram',
            description: 'Desain feed aesthetic dengan konsep visual yang konsisten'
        },
        {
            title: 'Video YouTube',
            category: 'video',
            icon: 'fab fa-youtube',
            description: 'Edit video gaming dengan highlight dan sound design'
        },
        {
            title: 'Poster Event',
            category: 'design',
            icon: 'fas fa-image',
            description: 'Desain poster acara musik festival dengan tipografi bold'
        },
        {
            title: 'Story Template',
            category: 'social',
            icon: 'fas fa-mobile-alt',
            description: 'Template story Instagram untuk promosi bisnis F&B'
        }
    ];

    // Render portfolio items
    function renderPortfolio(items) {
        portfolioGrid.innerHTML = '';
        
        items.forEach((item, index) => {
            const div = document.createElement('div');
            div.className = 'portfolio-item';
            div.dataset.category = item.category;
            
            div.innerHTML = `
                <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8 h-full 
                            transition-all duration-300 hover:-translate-y-2 hover:bg-white/10 
                            hover:shadow-xl hover:shadow-primary/20 cursor-pointer group relative overflow-hidden">
                    
                    <div class="w-16 h-16 mx-auto mb-6 bg-gradient-to-r from-amber-500 to-yellow-600 rounded-full 
                                flex items-center justify-center text-white text-2xl
                                group-hover:scale-110 transition-transform duration-300 relative z-10">
                        <i class="${item.icon}"></i>
                    </div>
                    
                    <div class="text-center relative z-10">
                        <h4 class="text-xl font-semibold text-white mb-3 group-hover:text-amber-400 transition-colors duration-300">
                            ${item.title}
                        </h4>
                        <p class="text-gray-300 text-sm leading-relaxed opacity-80">
                            ${item.description}
                        </p>
                    </div>

                    <div class="absolute inset-0 bg-gradient-to-r from-amber-500/10 to-yellow-600/10 opacity-0 rounded-2xl 
                                group-hover:opacity-100 transition-opacity duration-300"></div>
                </div>
            `;
            
            portfolioGrid.appendChild(div);
            
            // Animate in
            setTimeout(() => {
                div.style.animation = 'slideUp 0.6s ease-out both';
            }, index * 100);
        });
    }

    // Initial render
    renderPortfolio(portfolioItems);

    // Filter functionality
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Update active button
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Filter items
            const filter = btn.dataset.filter || 'all';
            const filteredItems = filter === 'all' 
                ? portfolioItems 
                : portfolioItems.filter(item => item.category === filter);

            renderPortfolio(filteredItems);
        });
    });
}

// Add portfolio CSS styles
const portfolioStyle = document.createElement('style');
portfolioStyle.textContent = `
    .portfolio-item {
        opacity: 0;
        transform: translateY(30px);
        position: relative;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }

    .filter-btn {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 12px 24px;
        border-radius: 25px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .filter-btn:hover {
        background: rgba(102, 126, 234, 0.2);
        border-color: rgba(102, 126, 234, 0.5);
        transform: translateY(-2px);
    }

    .filter-btn.active {
        background: linear-gradient(45deg, #667eea, #764ba2);
        border-color: transparent;
        color: white;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }

    .portfolio-grid {
        min-height: 400px;
    }

    /* Mobile Navigation Styles */
    .nav-links {
        transition: all 0.3s ease;
    }

    /* Hamburger Menu Styles */
    .hamburger {
        display: none;
        flex-direction: column;
        cursor: pointer;
        padding: 4px;
        z-index: 1001;
    }

    .hamburger span {
        width: 25px;
        height: 3px;
        background-color: white;
        margin: 3px 0;
        transition: 0.3s;
        border-radius: 2px;
    }

    @media (max-width: 768px) {
        .hamburger {
            display: flex;
        }

        .nav-links {
            position: fixed;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100vh;
            background: rgba(0, 0, 0, 0.98);
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            backdrop-filter: blur(20px);
            z-index: 1000;
        }

        .nav-links.active {
            left: 0;
        }

        .nav-links li {
            margin: 1rem 0;
        }

        .nav-links a {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .hamburger.active span:nth-child(1) {
            transform: rotate(-45deg) translate(-5px, 6px);
        }

        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active span:nth-child(3) {
            transform: rotate(45deg) translate(-5px, -6px);
        }

        .portfolio-item {
            margin-bottom: 1rem;
        }
        
        .filter-btn {
            padding: 8px 16px;
            font-size: 14px;
        }
    }

    /* Responsive grid improvements */
    @media (max-width: 640px) {
        .portfolio-grid {
            grid-template-columns: 1fr;
        }
    }
`;
document.head.appendChild(portfolioStyle);

// Testimonials slider
function initializeTestimonials() {
    const testimonialCards = document.querySelectorAll('.testimonial-card');
    const navDots = document.querySelectorAll('.nav-dot');
    let currentTestimonial = 0;

    if (testimonialCards.length > 0) {
        function showTestimonial(index) {
            // Hide all testimonials
            testimonialCards.forEach(card => card.classList.remove('active'));
            if (navDots.length > 0) {
                navDots.forEach(dot => dot.classList.remove('active'));
            }

            // Show selected testimonial
            testimonialCards[index].classList.add('active');
            if (navDots[index]) {
                navDots[index].classList.add('active');
            }

            currentTestimonial = index;
        }

        // Auto-rotate testimonials
        setInterval(() => {
            const nextIndex = (currentTestimonial + 1) % testimonialCards.length;
            showTestimonial(nextIndex);
        }, 5000);

        // Make showTestimonial globally accessible
        window.showTestimonial = showTestimonial;
    }
}

// Price calculator
function initializePriceCalculator() {
    const serviceSelect = document.getElementById('service');
    const packageSelect = document.getElementById('package');
    const estimatedPriceElement = document.getElementById('estimatedPrice');

    function updatePrice() {
        const serviceOption = serviceSelect.options[serviceSelect.selectedIndex];
        const packageOption = packageSelect.options[packageSelect.selectedIndex];

        if (serviceOption.dataset.price && packageOption.dataset.multiplier) {
            const basePrice = parseInt(serviceOption.dataset.price);
            const multiplier = parseFloat(packageOption.dataset.multiplier);
            const finalPrice = Math.round(basePrice * multiplier);

            estimatedPriceElement.textContent = `Rp${finalPrice.toLocaleString('id-ID')}`;
        } else {
            estimatedPriceElement.textContent = 'Rp0';
        }
    }

    serviceSelect.addEventListener('change', updatePrice);
    packageSelect.addEventListener('change', updatePrice);

    // Make updatePrice globally accessible
    window.updatePrice = updatePrice;
}

// WhatsApp order function
function orderWhatsApp(packageType = null) {
    const form = document.getElementById('orderForm');
    const formData = new FormData(form);
    
    // Get form values
    const name = formData.get('name') || 'Tidak diisi';
    const phone = formData.get('phone') || 'Tidak diisi';
    const service = document.getElementById('service');
    const serviceText = service.options[service.selectedIndex]?.text || 'Tidak dipilih';
    const packageSelect = document.getElementById('package');
    const packageText = packageType || (packageSelect.options[packageSelect.selectedIndex]?.text || 'Tidak dipilih');
    const description = formData.get('description') || 'Tidak ada deskripsi';
    const deadline = formData.get('deadline') || 'Tidak ditentukan';
    const estimatedPrice = document.getElementById('estimatedPrice').textContent;

    // Create WhatsApp message
    const message = `*PEMESANAN DESAININ*

*Informasi Pelanggan:*
- Nama: ${name}
- WhatsApp: ${phone}

*Detail Pesanan:*
- Layanan: ${serviceText}
- Paket: ${packageText}
- Deadline: ${deadline}
- Estimasi Harga: ${estimatedPrice}

*Catatan:*
Mohon konfirmasi detail pesanan dan lanjutkan pembayaran untuk memulai pengerjaan.

Terima kasih!`;

    // WhatsApp number (format internasional)
    const phoneNumber = '6288299154725'; // Nomor WA admin

    // Create WhatsApp URL
    const whatsappURL = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;

    // Open WhatsApp
    window.open(whatsappURL, '_blank');
}

// WhatsApp contact function for footer
function openWhatsApp() {
    const phoneNumber = '6288299154725'; // Format internasional tanpa +
    const message = 'Halo! Saya tertarik dengan layanan Desainin. Mohon informasi lebih lanjut. Terima kasih!';
    const whatsappURL = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;
    window.open(whatsappURL, '_blank');
}

// Make functions globally accessible
window.orderWhatsApp = orderWhatsApp;
window.openWhatsApp = openWhatsApp;

// Form submission
document.addEventListener('submit', function(e) {
    if (e.target.id === 'orderForm') {
        e.preventDefault();
        if (validateForm()) {
            orderWhatsApp();
            showNotification('Mengarahkan ke WhatsApp...', 'info');
        }
    }
});

// Scroll animations
function initializeAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'slideUp 0.8s ease-out both';
            }
        });
    }, observerOptions);

    // Observe elements for animation
    document.querySelectorAll('.service-card, .pricing-card, .testimonial-card, .contact-form, .info-card').forEach(el => {
        observer.observe(el);
    });
}

// Service card interactions
document.addEventListener('click', function(e) {
    if (e.target.closest('.service-card')) {
        const serviceCard = e.target.closest('.service-card');
        const serviceType = serviceCard.dataset.service;
        
        // Add ripple effect
        const ripple = document.createElement('div');
        ripple.style.cssText = `
            position: absolute;
            background: rgba(102, 126, 234, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        `;
        
        const rect = serviceCard.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        
        serviceCard.style.position = 'relative';
        serviceCard.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }
});

// Add ripple animation CSS
const rippleStyle = document.createElement('style');
rippleStyle.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(rippleStyle);

// Parallax effect for floating cards
window.addEventListener('scroll', () => {
    const scrolled = window.pageYOffset;
    const floatingCards = document.querySelectorAll('.floating-card');
    
    floatingCards.forEach((card, index) => {
        const speed = 0.3 + (index * 0.1);
        card.style.transform = `translateY(${scrolled * speed}px)`;
    });
});

// Loading animation
window.addEventListener('load', () => {
    // Add loading complete class to body
    document.body.classList.add('loaded');
    
    // Animate hero elements
    const heroElements = document.querySelectorAll('.hero-content h1, .hero-content p, .hero-buttons');
    heroElements.forEach((el, index) => {
        el.style.animationDelay = `${index * 0.2}s`;
    });
});

// Smooth reveal animations for sections
const revealSections = () => {
    const sections = document.querySelectorAll('section');
    const windowHeight = window.innerHeight;
    
    sections.forEach(section => {
        const sectionTop = section.getBoundingClientRect().top;
        const revealPoint = 150;
        
        if (sectionTop < windowHeight - revealPoint) {
            section.classList.add('revealed');
        }
    });
};

window.addEventListener('scroll', revealSections);

// Add CSS for section reveals
const revealStyle = document.createElement('style');
revealStyle.textContent = `
    section {
        opacity: 0;
        transform: translateY(50px);
        transition: all 0.8s ease-out;
    }
    
    section.revealed {
        opacity: 1;
        transform: translateY(0);
    }
    
    #home {
        opacity: 1;
        transform: none;
    }
`;
document.head.appendChild(revealStyle);

// Initialize reveal on load
document.addEventListener('DOMContentLoaded', revealSections);

// Service selection helper
function selectService(serviceType) {
    const serviceSelect = document.getElementById('service');
    const options = serviceSelect.options;
    
    for (let i = 0; i < options.length; i++) {
        if (options[i].value.includes(serviceType)) {
            serviceSelect.selectedIndex = i;
            updatePrice();
            break;
        }
    }
    
    // Scroll to contact form
    document.getElementById('contact').scrollIntoView({
        behavior: 'smooth'
    });
}

// Make selectService globally accessible
window.selectService = selectService;

// Add click handlers for service cards to auto-select service
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.service-card').forEach(card => {
        card.addEventListener('click', () => {
            const serviceType = card.dataset.service;
            if (serviceType) {
                selectService(serviceType);
            }
        });
    });
});

// Form validation
function validateForm() {
    const form = document.getElementById('orderForm');
    const inputs = form.querySelectorAll('input[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#ff4757';
            isValid = false;
            
            // Reset border color after 3 seconds
            setTimeout(() => {
                input.style.borderColor = '';
            }, 3000);
        }
    });
    
    if (!isValid) {
        // Show error message
        showNotification('Mohon lengkapi semua field yang wajib diisi!', 'error');
    }
    
    return isValid;
}

// Notification system
function showNotification(message, type = 'info') {
    // Remove existing notification
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    // Add notification styles
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'error' ? '#ff4757' : '#2ed573'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        z-index: 10000;
        animation: slideInRight 0.3s ease-out;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    `;
    
    document.body.appendChild(notification);
    
    // Remove notification after 4 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 4000);
}

// Add notification animation styles
const notificationStyle = document.createElement('style');
notificationStyle.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(notificationStyle);

// Easter egg - Konami Code
let konamiCode = [];
const konamiSequence = [38, 38, 40, 40, 37, 39, 37, 39, 66, 65]; // ↑↑↓↓←→←→BA

document.addEventListener('keydown', (e) => {
    konamiCode.push(e.keyCode);
    
    if (konamiCode.length > konamiSequence.length) {
        konamiCode.shift();
    }
    
    if (konamiCode.join('') === konamiSequence.join('')) {
        // Easter egg activated
        document.body.style.animation = 'rainbow 2s infinite';
        showNotification('🎉 Easter egg activated! Special discount unlocked!', 'info');
        
        setTimeout(() => {
            document.body.style.animation = '';
        }, 4000);
    }
});

// Add rainbow animation
const rainbowStyle = document.createElement('style');
rainbowStyle.textContent = `
    @keyframes rainbow {
        0% { filter: hue-rotate(0deg); }
        100% { filter: hue-rotate(360deg); }
    }
`;
document.head.appendChild(rainbowStyle);

// Performance optimization - Lazy load images
const lazyLoad = () => {
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
};

// Initialize lazy loading
document.addEventListener('DOMContentLoaded', lazyLoad);

// Add loading states for interactive elements
function addLoadingState(element) {
    element.style.opacity = '0.6';
    element.style.pointerEvents = 'none';
    element.textContent = 'Loading...';
}

function removeLoadingState(element, originalText) {
    element.style.opacity = '1';
    element.style.pointerEvents = 'auto';
    element.textContent = originalText;
}

// Preload critical resources
function preloadResources() {
    // Preload font awesome icons
    const link = document.createElement('link');
    link.rel = 'preload';
    link.as = 'style';
    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
    document.head.appendChild(link);
}

// Initialize preloading
document.addEventListener('DOMContentLoaded', preloadResources);

// Add smooth transitions between sections
let currentSection = 0;
const sections = ['home', 'services', 'portfolio', 'pricing', 'contact'];

function navigateToSection(index) {
    if (index >= 0 && index < sections.length) {
        document.getElementById(sections[index]).scrollIntoView({
            behavior: 'smooth'
        });
        currentSection = index;
    }
}

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    if (e.ctrlKey) {
        switch(e.key) {
            case 'ArrowUp':
                e.preventDefault();
                navigateToSection(currentSection - 1);
                break;
            case 'ArrowDown':
                e.preventDefault();
                navigateToSection(currentSection + 1);
                break;
        }
    }
});

// Add accessibility improvements
function improveAccessibility() {
    // Add alt text to icons
    document.querySelectorAll('i.fas, i.fab').forEach(icon => {
        if (!icon.getAttribute('alt')) {
            const classes = icon.className.split(' ');
            const iconName = classes.find(cls => cls.startsWith('fa-'))?.replace('fa-', '');
            if (iconName) {
                icon.setAttribute('alt', iconName.replace('-', ' '));
                icon.setAttribute('role', 'img');
            }
        }
    });
    
    // Add skip links
    const skipLink = document.createElement('a');
    skipLink.href = '#home';
    skipLink.textContent = 'Skip to main content';
    skipLink.style.cssText = `
        position: absolute;
        left: -9999px;
        z-index: 10001;
        padding: 8px 16px;
        background: #667eea;
        color: white;
        text-decoration: none;
        border-radius: 0 0 8px 0;
    `;
    
    skipLink.addEventListener('focus', () => {
        skipLink.style.left = '0';
    });
    
    skipLink.addEventListener('blur', () => {
        skipLink.style.left = '-9999px';
    });
    
    document.body.insertBefore(skipLink, document.body.firstChild);
}

// Initialize accessibility improvements
document.addEventListener('DOMContentLoaded', improveAccessibility);

// Promo modal functions
function closePromo() {
    const overlay = document.getElementById('promoOverlay');
    if (overlay) {
        const modal = overlay.querySelector('.promo-modal');
        modal.style.animation = "slideOutDown 0.4s ease-out";
        overlay.style.animation = "fadeOut 0.4s ease-out";
        setTimeout(() => overlay.remove(), 400);
    }
}

function goToContact() {
    // Scroll ke kontak
    document.getElementById('contact').scrollIntoView({behavior:'smooth'});
    // Tutup popup
    closePromo();
}

// Make promo functions globally accessible
window.closePromo = closePromo;
window.goToContact = goToContact;

// Optional: tampilkan promo otomatis setelah 3 detik
window.addEventListener('load', () => {
    setTimeout(() => {
        const promoOverlay = document.getElementById('promoOverlay');
        if (promoOverlay) {
            promoOverlay.style.display = 'flex';
        }
    }, 3000);
});

console.log('🎨 Desainin website loaded successfully!');
console.log('✨ Created by Vylan Yoza Sinaga');
console.log('🎮 Try the Konami code for a surprise!');