/**
 * Belajaryuk - Authentication Module
 * Standard: Senior Frontend Engineer (Namespaced & State-aware)
 */

window.App = window.App || {};

App.Auth = {
    async handleRegister(event) {
        event.preventDefault();
        const form = event.target;
        
        const fullname = form.querySelector('#register-fullname').value.trim();
        const username = form.querySelector('#register-username').value.replace(/\s+/g, '').toLowerCase();
        const email = form.querySelector('#register-email').value.trim();
        const password = form.querySelector('#register-password').value;
        const passwordConfirm = form.querySelector('#register-password-confirm').value;

        if (password.length < 8) {
            App.Utils.showNotification('Kata sandi minimal 8 karakter agar lebih aman.', 'warning');
            return;
        }

        if (password !== passwordConfirm) {
            App.Utils.showNotification('Konfirmasi kata sandi tidak cocok.', 'error');
            return;
        }

        App.Utils.showLoading(true);
        try {
            const response = await App.Service.API.register({
                full_name: fullname,
                username, email, password
            });
            App.Utils.showNotification(response.message, 'success');
            form.reset();
            App.Router.showPage('login-page');
        } catch (error) {
            App.Utils.showNotification(error.message, 'error');
        } finally {
            App.Utils.showLoading(false);
        }
    },

    async handleLogin(event) {
        event.preventDefault();
        const form = event.target;
        const username = form.querySelector('#login-username').value;
        const password = form.querySelector('#login-password').value;

        console.log("Proses login dimulai untuk user:", username);
        App.Utils.showLoading(true);
        try {
            const response = await App.Service.API.login(username, password);
            console.log("Respon API diterima:", response);
            
            if (response.success && response.data) {
                App.Service.API.setToken(response.data.token);
                localStorage.setItem(App.Config.STORAGE_KEYS.USER_DATA, JSON.stringify(response.data.user));
                
                App.Utils.showNotification('Berhasil masuk! Selamat belajar.', 'success');
                form.reset();
                
                App.UI.setupRoleAccess();

                if (response.data.user.role === 'admin') {
                    App.Router.showPage('admin-page');
                } else {
                    App.Router.showPage('dashboard-page');
                }
            } else {
                throw new Error(response.message || 'Login gagal tanpa pesan error.');
            }
        } catch (error) {
            console.error("Login Error Detail:", error);
            App.Utils.showNotification(error.message, 'error');
        } finally {
            App.Utils.showLoading(false);
        }
    },

    async handleLogout() {
        App.Utils.showLoading(true);
        try {
            await App.Service.API.logout();
        } catch (error) {
            console.error('Logout failed:', error);
        } finally {
            App.Service.API.clearAuth();
            App.Utils.showNotification('Berhasil keluar.', 'info');
            setTimeout(() => {
                window.location.href = '/';
            }, 800);
        }
    },

    checkAuth() {
        const token = localStorage.getItem(App.Config.STORAGE_KEYS.AUTH_TOKEN);
        const userData = localStorage.getItem(App.Config.STORAGE_KEYS.USER_DATA);
        return !!(token && userData);
    }
};

// Global polyfill for compatibility while refactoring other files
window.handleLogout = () => App.Auth.handleLogout();
window.handleLogin = (e) => App.Auth.handleLogin(e);
window.handleRegister = (e) => App.Auth.handleRegister(e);
