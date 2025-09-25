// ================= PROMOTIONAL POPUP =================
// Simple promotional popup with session management

function createPromoPopup() {
    console.log('createPromoPopup called');
    
    // Check if popup was closed in this session
    if (sessionStorage.getItem('promoClosed')) {
        console.log('Popup was already closed in this session');
        return;
    }
    
    // Check if popup already exists
    if (document.getElementById('promoPopup')) {
        console.log('Popup already exists');
        return;
    }

    // Create premium popup HTML
    const popupHTML = `
        <div id="promoPopup" class="promo-popup-overlay">
            <div class="promo-popup-container">
                <!-- Close Button -->
                <button class="promo-close-btn" onclick="closePromoPopup()">
                    <i class="fas fa-times"></i>
                </button>
                
                <!-- Content -->
                <div class="promo-content">
                    <!-- Icon -->
                    <div class="promo-icon">
                        <i class="fas fa-gift"></i>
                    </div>
                    
                    <!-- Title & Subtitle -->
                    <h2 class="promo-title">Promo Spesial Hari Ini!</h2>
                    <p class="promo-subtitle">Jangan lewatkan kesempatan emas ini</p>
                    
                    <!-- Main Text -->
                    <p class="promo-text">
                        Dapatkan <strong>DISKON 30%</strong> untuk semua layanan desain grafis dan edit video profesional. 
                        Promo terbatas hanya untuk <strong>50 pelanggan pertama</strong> bulan ini!
                    </p>
                    
                    <!-- Call to Action -->
                    <div class="promo-buttons">
                        <button class="promo-btn-order" onclick="orderPromo()">
                            <i class="fas fa-rocket" style="margin-right: 8px;"></i>
                            Ambil Promo Sekarang
                        </button>
                        <button class="promo-btn-close" onclick="closePromoPopup()">
                            Nanti Saja
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    console.log('Adding popup to DOM...');
    // Add to page
    document.body.insertAdjacentHTML('beforeend', popupHTML);
    
    console.log('Popup added, showing with animation...');
    // Show popup with animation
    setTimeout(() => {
        const popup = document.getElementById('promoPopup');
        if (popup) {
            popup.classList.add('show');
            console.log('Popup shown with animation');
        } else {
            console.error('Popup element not found after insertion');
        }
    }, 100);
}

function closePromoPopup() {
    const popup = document.getElementById('promoPopup');
    if (popup) {
        popup.classList.add('hide');
        setTimeout(() => {
            popup.remove();
        }, 300);
        
        // Mark popup as closed for this session
        sessionStorage.setItem('promoClosed', 'true');
    }
}

function orderPromo() {
    // Check if user is logged in by looking for user dropdown or profile elements
    const isLoggedIn = document.querySelector('.user-avatar') !== null || 
                      document.querySelector('[data-user]') !== null ||
                      document.body.classList.contains('logged-in');
    
    if (isLoggedIn) {
        // Redirect to order creation page if logged in
        window.location.href = 'pages/orders/create.php';
    } else {
        // Redirect to login page if not logged in
        window.location.href = 'pages/auth/login.php';
    }
    
    closePromoPopup();
}

// Force clear session storage for testing
sessionStorage.removeItem('promoClosed');

// Auto-show popup after page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, creating popup immediately...');
    createPromoPopup();
});

// Backup method - show popup when window loads
window.addEventListener('load', function() {
    console.log('Window loaded, checking for popup...');
    if (!document.getElementById('promoPopup')) {
        console.log('No popup found, creating one...');
        createPromoPopup();
    }
});

// Also try to show popup immediately when script loads
console.log('Promo popup script loaded');
setTimeout(() => {
    console.log('Immediate popup attempt...');
    createPromoPopup();
}, 100);

// Also show popup when page becomes visible (for tab switching)
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Small delay to avoid conflicts
        setTimeout(() => {
            if (!document.getElementById('promoPopup')) {
                createPromoPopup();
            }
        }, 1000);
    }
});

// Make functions globally accessible
window.closePromoPopup = closePromoPopup;
window.orderPromo = orderPromo;