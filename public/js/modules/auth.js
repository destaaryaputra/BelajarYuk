/**
 * Belajaryuk - Authentication Module
 */

import { API, Config } from './api.js';
import { UI } from './ui.js';

export const Auth = {
    async handleLogin(event) {
        event.preventDefault();
        const form = event.target;
        const username = form.querySelector('#login-username').value;
        const password = form.querySelector('#login-password').value;

        UI.showLoading();
        try {
            const response = await API.login(username, password);
            
            if (response.success && response.data) {
                API.setToken(response.data.token);
                localStorage.setItem(Config.STORAGE_KEYS.USER_DATA, JSON.stringify(response.data.user));
                
                UI.showNotification('Berhasil masuk! Selamat belajar.', 'success');
                
                // Redirect based on role
                if (response.data.user.role === 'admin') {
                    window.location.hash = 'admin-page';
                } else {
                    window.location.hash = 'dashboard-page';
                }
            }
        } catch (error) {
            UI.showNotification(error.message, 'error');
        } finally {
            UI.hideLoading();
        }
    },

    async handleRegister(event) {
        event.preventDefault();
        const form = event.target;
        
        const fullname = form.querySelector('#register-fullname').value.trim();
        const username = form.querySelector('#register-username').value.replace(/\s+/g, '').toLowerCase();
        const email = form.querySelector('#register-email').value.trim();
        const password = form.querySelector('#register-password').value;
        const passwordConfirm = form.querySelector('#register-password-confirm').value;

        if (password.length < 8) {
            UI.showNotification('Kata sandi minimal 8 karakter.', 'warning');
            return;
        }

        if (password !== passwordConfirm) {
            UI.showNotification('Konfirmasi kata sandi tidak cocok.', 'error');
            return;
        }

        UI.showLoading();
        try {
            const response = await API.register({
                full_name: fullname,
                username, email, password
            });
            UI.showNotification(response.message, 'success');
            window.location.hash = 'login-page';
        } catch (error) {
            UI.showNotification(error.message, 'error');
        } finally {
            UI.hideLoading();
        }
    },

    async handleLogout() {
        UI.showLoading();
        try {
            await API.logout();
        } catch (error) {
            console.error('Logout failed:', error);
        } finally {
            API.clearAuth();
            UI.showNotification('Berhasil keluar.', 'info');
            setTimeout(() => {
                window.location.hash = 'landing-page';
            }, 800);
        }
    },

    getUserData() {
        const data = localStorage.getItem(Config.STORAGE_KEYS.USER_DATA);
        return data ? JSON.parse(data) : null;
    },

    isLoggedIn() {
        return !!localStorage.getItem(Config.STORAGE_KEYS.AUTH_TOKEN);
    }
};

// Global Exposure
window.Auth = Auth;
window.handleLogin = (e) => Auth.handleLogin(e);
window.handleRegister = (e) => Auth.handleRegister(e);
window.handleLogout = () => Auth.handleLogout();
