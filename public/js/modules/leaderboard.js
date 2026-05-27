/**
 * Belajaryuk - Leaderboard Module
 */

import { API, Config } from './api.js';
import { UI } from './ui.js';

export const Leaderboard = {
    async load() {
        const container = document.getElementById('leaderboard-body-full');
        if (!container) return;

        container.innerHTML = '<tr><td colspan="4" class="text-center"><div class="skeleton-box" style="height: 100px; width: 100%;"></div></td></tr>';

        try {
            const response = await API.getLeaderboard(50);
            const data = response.data || [];
            
            const currentUserStr = localStorage.getItem(Config.STORAGE_KEYS.USER_DATA);
            const currentUser = currentUserStr ? JSON.parse(currentUserStr) : null;

            this.render(data, currentUser);
        } catch (error) {
            console.error('Leaderboard load error:', error);
            container.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Gagal memuat papan peringkat.</td></tr>';
        }
    },

    render(data, currentUser) {
        const container = document.getElementById('leaderboard-body-full');
        if (!container) return;

        if (data.length === 0) {
            container.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Belum ada data peringkat.</td></tr>';
            return;
        }

        let html = '';
        data.forEach((item, index) => {
            const rank = index + 1;
            const isMe = currentUser && item.id === currentUser.id;
            
            let rankClass = '';
            let rankDisplay = rank;
            if (rank === 1) {
                rankClass = 'rank-gold';
                rankDisplay = '<i data-lucide="award"></i>';
            } else if (rank === 2) {
                rankClass = 'rank-silver';
                rankDisplay = '<i data-lucide="award"></i>';
            } else if (rank === 3) {
                rankClass = 'rank-bronze';
                rankDisplay = '<i data-lucide="award"></i>';
            }

            const initials = (item.full_name || item.username || '?')[0].toUpperCase();
            const avatarUrl = item.avatar ? (item.avatar.startsWith('http') ? item.avatar : UI.getAssetPath(`uploads/${item.avatar}`)) : '';
            const avatarHtml = item.avatar 
                ? `<img src="${UI.escapeHtml(avatarUrl)}" alt="Avatar" class="user-avatar-small" loading="lazy" decoding="async">`
                : `<div class="user-avatar-small">${initials}</div>`;

            html += `
                <tr class="${isMe ? 'is-me' : ''}">
                    <td>
                        <div class="rank-badge ${rankClass}">${rankDisplay}</div>
                    </td>
                    <td>
                        <div class="user-cell">
                            ${avatarHtml}
                            <div class="user-info-cell">
                                <span class="user-name">${UI.escapeHtml(item.full_name || item.username)}</span>
                                <span class="user-username">${UI.escapeHtml(item.username)}</span>
                            </div>
                        </div>
                    </td>
                    <td>${item.materials_completed || 0} Modul</td>
                    <td class="text-right">
                        <span class="total-points-cell">${Math.round(item.total_points || 0)}</span>
                    </td>
                </tr>
            `;
        });

        container.innerHTML = html;
        if (window.lucide) window.lucide.createIcons();
    }
};
