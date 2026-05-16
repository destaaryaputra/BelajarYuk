/**
 * Belajaryuk - Initial Script
 * Suppress console warnings and initial setup
 */

(function() {
    const originalWarn = console.warn;
    console.warn = function(...args) {
        const msg = args[0] && typeof args[0] === 'string' ? args[0] : '';
        // Suppress harmless deprecated warnings from external scripts (Vercel/Sentry/Zustand)
        if (msg.includes('Default export is deprecated') || msg.includes('zustand')) return;
        originalWarn.apply(console, args);
    };
})();
