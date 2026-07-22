document.addEventListener('DOMContentLoaded', () => {
    // Check if there are any PHP flash messages stored in session to show as toast
    // This part can be integrated with PHP by printing a script block

    // Example Toast Function
    window.showToast = function(message, type = 'success') {
        const container = document.getElementById('toast-container') || createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        let icon = 'fa-check-circle';
        if(type === 'error') icon = 'fa-exclamation-circle';
        if(type === 'warning') icon = 'fa-exclamation-triangle';

        toast.innerHTML = `
            <i class="fa-solid ${icon}" style="color: var(--${type === 'error' ? 'danger' : (type === 'warning' ? 'accent' : 'success')}); font-size: 1.5rem;"></i>
            <div>
                <strong>${type.charAt(0).toUpperCase() + type.slice(1)}</strong><br>
                <span style="font-size: 0.9rem; color: var(--text-muted);">${message}</span>
            </div>
        `;
        
        container.appendChild(toast);

        // Remove toast from DOM after animation
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 3300);
    };

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
        return container;
    }

    // Live search functionality
    const searchInput = document.getElementById('live-search');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const products = document.querySelectorAll('.product-card');
            
            products.forEach(product => {
                const title = product.querySelector('.product-title').innerText.toLowerCase();
                if (title.includes(query)) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        });
    }

    // Dark Mode Logic
    const toggleBtn = document.getElementById('theme-toggle');
    const htmlTag = document.documentElement;

    // Check stored preference
    const storedTheme = localStorage.getItem('theme');
    if (storedTheme) {
        htmlTag.setAttribute('data-theme', storedTheme);
        updateThemeIcon(storedTheme);
    } else {
        // Check system preference
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        if(prefersDark) {
            htmlTag.setAttribute('data-theme', 'dark');
            updateThemeIcon('dark');
        }
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const currentTheme = htmlTag.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            htmlTag.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });
    }

    function updateThemeIcon(theme) {
        if(toggleBtn) {
            if(theme === 'dark') {
                toggleBtn.innerHTML = '<i class="fa-solid fa-sun"></i>';
            } else {
                toggleBtn.innerHTML = '<i class="fa-solid fa-moon"></i>';
            }
        }
    }
});

// Modal Functions
window.openModal = function(modalId) {
    const m = document.getElementById(modalId);
    if(m) m.classList.add('active');
};
window.closeModal = function(modalId) {
    const m = document.getElementById(modalId);
    if(m) m.classList.remove('active');
};
document.addEventListener('click', (e) => {
    if(e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});
