/**
 * Belajaryuk - Main Application Entry Point
 * Implements Frontend Routing & Modular Loading
 */

import { Router } from './modules/router.js';
import { UI } from './modules/ui.js';
import { Auth } from './modules/auth.js';

document.addEventListener('DOMContentLoaded', async () => {
    // 1. Initialize UI
    UI.init();

    // 2. Initialize Router
    Router.init();
    
    // 3. Setup Global Page Event Handlers
    window.addEventListener('page-loaded', (e) => {
        const pageId = e.detail.pageId;
        
        // Login Page Handlers
        if (pageId === 'login-page') {
            document.getElementById('login-form')?.addEventListener('submit', Auth.handleLogin);
        }
        
        // Register Page Handlers
        if (pageId === 'register-page') {
            document.getElementById('register-form')?.addEventListener('submit', Auth.handleRegister);
        }

        // Global Logout Buttons
        document.querySelectorAll('[data-action="logout"]').forEach(btn => {
            btn.addEventListener('click', () => Auth.handleLogout());
        });
    });

    // 4. Initial Load
    await Router.handleInitialRoute();
    
    // 5. Hide splash after app ready
    setTimeout(() => UI.hideSplash(), 500);
});
