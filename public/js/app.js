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
    console.log('🚀 Belajaryuk Bootstrapping...');
    
    // 1. Initialize UI
    UI.init();

    // 2. Initialize Router
    Router.init();

    // 3. Setup Global Page Event Handlers
    window.addEventListener('page-loaded', (e) => {
        console.log(`📄 Page Loaded: ${e.detail.pageId}`);
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
    try {
        await Router.handleInitialRoute();
    } catch (err) {
        console.error('❌ Failed to load initial route:', err);
    } finally {
        // 5. Always hide splash to prevent white screen lock
        setTimeout(() => {
            UI.hideSplash();
            console.log('✅ Bootstrapping Finished');
        }, 800);
    }
});

