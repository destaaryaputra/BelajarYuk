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
import { Profile } from './modules/profile.js';
import { Progress } from './modules/progress.js';
import { Leaderboard } from './modules/leaderboard.js';
import { AIChat } from './modules/chat.js';
import { MaterialDetail } from './modules/materialDetail.js';
import { Quiz } from './modules/quiz.js';

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

        // Ensure theme icons are correct for the new page
        UI.updateThemeIcons();

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
        if (pageId === 'material-detail-page') {
            MaterialDetail.load();
        }
        if (pageId === 'quiz-page') {
            Quiz.load();
        }
        if (pageId === 'profile-page') {
            Profile.load();
        }
        if (pageId === 'progress-page') {
            Progress.load();
        }
        if (pageId === 'leaderboard-page') {
            Leaderboard.load();
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
            btn.onclick = () => Auth.handleLogout();
        });
    });

    // 5. Initial Load
    try {
        await Router.handleInitialRoute();
    } catch (err) {
        console.error('❌ Failed to load initial route:', err);
    } finally {
        // 6. Hide splash screen after initial route is loaded
        // We use a small delay to let the initial page render before fading out
        setTimeout(() => {
            UI.hideSplash();
        }, 600);
    }
});
