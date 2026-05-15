/** Profile page logic. */
function loadProfile() {
    logger.info('Loading profile...');
    const userDataStr = localStorage.getItem(CONFIG.STORAGE_KEYS.USER_DATA);
    const container = document.getElementById('profile-content');
    
    if (userDataStr && container) {
        const user = JSON.parse(userDataStr);
        const avatarUrl = user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.full_name || user.username)}&background=10b981&color=fff&size=128`;
        const fullName = escapeHtml(user.full_name || 'Nama Pengguna');
        const username = escapeHtml(user.username || 'username');
        const email = escapeHtml(user.email || '-');
        const bio = escapeHtml(user.bio || 'Belum ada bio. Yuk, ceritakan sedikit tentang dirimu!');
        const editFullName = escapeHtml(user.full_name || '');
        const editUsername = escapeHtml(user.username || '');
        const editBio = escapeHtml(user.bio || '');

        container.innerHTML = `
            <div id="profile-view" class="profile-view-compact">
                <div class="profile-header profile-header-compact">
                    <img src="${avatarUrl}" alt="Avatar Pengguna" class="avatar">
                    <div class="profile-header-info">
                        <h2>${fullName}</h2>
                        <p>@${username}</p>
                    </div>
                    <div class="profile-actions profile-actions-top">
                        <button class="btn-outline btn-small" onclick="toggleEditProfile()">Edit Profil</button>
                        <button class="btn-outline btn-danger btn-small" onclick="handleLogout()">Logout</button>
                    </div>
                </div>

                <div class="profile-section profile-section-compact">
                    <div class="profile-grid">
                        <div class="profile-field">
                            <label>Nama Lengkap</label>
                            <span>${fullName}</span>
                        </div>
                        <div class="profile-field">
                            <label>Username</label>
                            <span>@${username}</span>
                        </div>
                        <div class="profile-field">
                            <label>Alamat Email</label>
                            <span>${email}</span>
                        </div>
                        <div class="profile-field profile-field-full">
                            <label>Bio</label>
                            <span class="bio-text">${bio}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div id="profile-edit" style="display: none;">
                <h3 class="section-divider">Edit Profil</h3>
                <form id="edit-profile-form" class="profile-edit-form" onsubmit="handleUpdateProfile(event)">
                    <div class="profile-grid">
                        <div>
                            <label>Nama Lengkap</label>
                            <input type="text" id="edit-fullname" value="${editFullName}" required>
                        </div>
                        <div>
                            <label>Username</label>
                            <input type="text" id="edit-username" value="${editUsername}" readonly title="Username tidak dapat diubah demi keamanan akun">
                        </div>
                        <div class="profile-field-full">
                            <label>Bio Singkat</label>
                            <textarea id="edit-bio" placeholder="Ceritakan sedikit tentang diri Anda...">${editBio}</textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-12 mt-8">
                        <button type="button" class="btn-outline flex-1" onclick="toggleEditProfile()">Batal</button>
                        <button type="submit" class="flex-1">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        `;
    }
}

function toggleEditProfile() {
    const viewEl = document.getElementById('profile-view');
    const editEl = document.getElementById('profile-edit');
    if (viewEl.style.display === 'none') {
        viewEl.style.display = 'block';
        editEl.style.display = 'none';
    } else {
        viewEl.style.display = 'none';
        editEl.style.display = 'block';
    }
}

async function handleUpdateProfile(event) {
    event.preventDefault();
    const form = event.target;
    
    const updatedData = {
        full_name: form.querySelector('#edit-fullname').value.trim(),
        bio: form.querySelector('#edit-bio').value.trim()
    };

    showLoading(true);
    try {
        // Kirim data ke API Backend
        const response = await API.updateProfile(updatedData);
        
        localStorage.setItem(CONFIG.STORAGE_KEYS.USER_DATA, JSON.stringify(response.data.user));
        
        showNotification('Sip! Profil kamu berhasil diperbarui.', 'success');
        loadProfile();
        loadDashboard();
    } catch (error) {
        handleAPIError(error);
    } finally {
        showLoading(false);
    }
}

