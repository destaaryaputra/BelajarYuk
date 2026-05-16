/**
 * Belajaryuk - Main Application Entry Point
 * Implements Frontend Routing & Modular Loading
 */

import { Router } from './modules/router.js';
import { UI } from './modules/ui.js';
import { Auth } from './modules/auth.js';
import { Dashboard } from './modules/dashboard.js';
import { Materials } from './modules/materials.js';
import { Admin } from './modules/admin.js';
import { AIChat } from './modules/chat.js';

document.addEventListener('DOMContentLoaded', async () => {
    // 1. Initialize UI
    UI.init();

    // 2. Initialize Router
    Router.init();
    
    // 3. Initialize AI Chat
    AIChat.init();
    
    // 4. Setup Global Page Event Handlers
    window.addEventListener('page-loaded', (e) => {
        const pageId = e.detail.pageId;
        
        // Auth Handlers
        if (pageId === 'login-page') {
            document.getElementById('login-form')?.addEventListener('submit', Auth.handleLogin);
        }
        if (pageId === 'register-page') {
            document.getElementById('register-form')?.addEventListener('submit', Auth.handleRegister);
        }

        // Student Handlers
        if (pageId === 'dashboard-page') {
            Dashboard.load();
        }
        if (pageId === 'materials-page') {
            Materials.load();
        }

        // Admin Handlers
        if (pageId === 'admin-page') {
            Admin.load();
            
            // Attach Admin Sidebar Listeners
            document.querySelectorAll('.admin-sidebar-nav button').forEach(btn => {
                btn.onclick = () => {
                    const tab = btn.id.replace('btn-tab-', '');
                    Admin.switchTab(tab);
                };
            });
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
