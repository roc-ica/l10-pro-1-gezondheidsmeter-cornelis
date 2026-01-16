/**
 * Modern Popup System
 * Replaces standard alert() with beautiful popups
 */

class Popup {
    constructor() {
        this.overlay = null;
        this.container = null;
        this.currentCallback = null;
        this.init();
    }

    init() {
        // Create popup structure if it doesn't exist
        if (!document.getElementById('custom-popup-overlay')) {
            this.createPopupStructure();
        }
        this.overlay = document.getElementById('custom-popup-overlay');
        this.container = this.overlay.querySelector('.popup-container');
    }

    createPopupStructure() {
        const popupHTML = `
            <div id="custom-popup-overlay" class="popup-overlay">
                <div class="popup-container">
                    <div class="popup-header">
                        <div class="popup-icon" id="popup-icon"></div>
                        <h2 class="popup-title" id="popup-title"></h2>
                    </div>
                    <div class="popup-body">
                        <p class="popup-message" id="popup-message"></p>
                    </div>
                    <div class="popup-footer" id="popup-footer"></div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', popupHTML);
    }

    show(options) {
        const {
            type = 'info',
            title = '',
            message = '',
            confirmText = 'OK',
            cancelText = 'Annuleren',
            showCancel = false,
            onConfirm = null,
            onCancel = null
        } = options;

        // Set icon
        const iconElement = document.getElementById('popup-icon');
        iconElement.className = `popup-icon ${type}`;
        iconElement.innerHTML = this.getIcon(type);

        // Set title and message
        document.getElementById('popup-title').textContent = title;
        document.getElementById('popup-message').textContent = message;

        // Set buttons
        const footer = document.getElementById('popup-footer');
        footer.innerHTML = '';

        if (showCancel) {
            const cancelBtn = document.createElement('button');
            cancelBtn.className = 'popup-btn popup-btn-secondary';
            cancelBtn.textContent = cancelText;
            cancelBtn.onclick = () => {
                this.hide();
                if (onCancel) onCancel();
            };
            footer.appendChild(cancelBtn);
        }

        const confirmBtn = document.createElement('button');
        confirmBtn.className = `popup-btn popup-btn-${type === 'success' ? 'success' : 'primary'}`;
        confirmBtn.textContent = confirmText;
        confirmBtn.onclick = () => {
            this.hide();
            if (onConfirm) onConfirm();
        };
        footer.appendChild(confirmBtn);

        // Show overlay
        this.overlay.classList.add('active');

        // Auto-focus confirm button
        setTimeout(() => confirmBtn.focus(), 100);

        // Close on overlay click
        this.overlay.onclick = (e) => {
            if (e.target === this.overlay) {
                this.hide();
                if (onCancel) onCancel();
            }
        };

        // Close on Escape key
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                this.hide();
                if (onCancel) onCancel();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
    }

    hide() {
        this.overlay.classList.remove('active');
        this.overlay.onclick = null;
    }

    getIcon(type) {
        const icons = {
            success: `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            `,
            error: `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            `,
            warning: `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            `,
            info: `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
            `
        };
        return icons[type] || icons.info;
    }
}

// Initialize popup system
let popupInstance = null;

// Wait for DOM to be ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        popupInstance = new Popup();
    });
} else {
    popupInstance = new Popup();
}

// Helper functions for easy usage
window.showSuccess = function (message, title = 'Gelukt!', onConfirm = null) {
    if (!popupInstance) popupInstance = new Popup();
    popupInstance.show({
        type: 'success',
        title: title,
        message: message,
        confirmText: 'OK',
        onConfirm: onConfirm
    });
};

window.showError = function (message, title = 'Fout', onConfirm = null) {
    if (!popupInstance) popupInstance = new Popup();
    popupInstance.show({
        type: 'error',
        title: title,
        message: message,
        confirmText: 'OK',
        onConfirm: onConfirm
    });
};

window.showWarning = function (message, title = 'Let op', onConfirm = null) {
    if (!popupInstance) popupInstance = new Popup();
    popupInstance.show({
        type: 'warning',
        title: title,
        message: message,
        confirmText: 'OK',
        onConfirm: onConfirm
    });
};

window.showInfo = function (message, title = 'Info', onConfirm = null) {
    if (!popupInstance) popupInstance = new Popup();
    popupInstance.show({
        type: 'info',
        title: title,
        message: message,
        confirmText: 'OK',
        onConfirm: onConfirm
    });
};

window.showConfirm = function (message, title = 'Bevestigen', onConfirm = null, onCancel = null) {
    if (!popupInstance) popupInstance = new Popup();
    popupInstance.show({
        type: 'warning',
        title: title,
        message: message,
        confirmText: 'Ja',
        cancelText: 'Annuleren',
        showCancel: true,
        onConfirm: onConfirm,
        onCancel: onCancel
    });
};

// Override default alert (optional - can be removed if you prefer to keep default alert as backup)
// window.alert = function(message) {
//     showInfo(message);
// };
