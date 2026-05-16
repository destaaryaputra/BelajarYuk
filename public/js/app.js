/**
 * Belajaryuk - Main Application Entry Point
 * Implements Frontend Routing & Modular Loading
 */

import { Router } from './modules/router.js';
import { UI } from './modules/ui.js';

document.addEventListener('DOMContentLoaded', async () => {
    // 1. Initialize UI (Splash, etc)
    UI.init();

    // 2. Initialize Router
    Router.init();
    
    // 3. Initial Load
    await Router.handleInitialRoute();
    
    // 4. Hide splash after app ready
    setTimeout(() => UI.hideSplash(), 500);
});
