<?php
/**
 * ADMIN MODAL VIEW
 * ----------------
 * Zobrazuje administrační panel dostupný pouze pro uživatele s rolí "admin".
 *
 * Funkce:
 *  - vyhledávání uživatelů
 *  - stránkování seznamu
 *  - editace uživatele (email, avatar, heslo)
 *  - vytvoření kolekce pro vybraného uživatele
 *
 * Bezpečnost:
 *  - modal pouze pro role === 'admin'
 *  - žádný inline JavaScript
 *  - veškeré API volání jde přes admin_actions.php
 *  - žádné neescapované uživatelské hodnoty v HTML
 *
 * @package MyVibe\Views\Partials
 * @author  Safik
 */

// ======================================================
// 1. OCHRANA PRO ADMINA
// ======================================================
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    return;
}
?>
<script>
    window.currentAdminId = <?= json_encode($_SESSION['user_id'] ?? 0) ?>;
</script>

<!-- ====================================================== -->
<!-- 2. ADMIN PANEL BUTTON -->
<!-- ====================================================== -->
<button id="adminPanelBtn" class="admin-btn">👑</button>

<!-- ====================================================== -->
<!-- 3. MAIN ADMIN MODAL -->
<!-- ====================================================== -->
<div id="adminModal" class="modal">
    <div class="modal-content admin-modal">
        <span class="close">&times;</span>
        <h2>Admin Panel – User Management</h2>

        <!-- 3.1 SEARCH FIELD -->
        <div class="admin-search">
            <input type="text" id="searchUserInput" placeholder="Search users...">
        </div>

        <!-- 3.2 USER LIST -->
        <div id="adminUserList"></div>

        <!-- 3.3 PAGINATION -->
        <div class="pagination-controls">
            <button id="prevPage" disabled>← Previous</button>
            <span id="pageInfo">Page 1</span>
            <button id="nextPage">Next →</button>
        </div>
    </div>
</div>

<!-- ====================================================== -->
<!-- 4. EDIT USER MODAL -->
<!-- ====================================================== -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeEdit">&times;</span>
        <h2>Edit User</h2>

        <form id="editUserForm" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="id" id="editUserId">

            <label>Email:</label>
            <input type="email" name="email" id="editUserEmail" required>

            <label>New Password (leave empty to keep current):</label>
            <input type="password" name="password" id="editUserPassword" minlength="6">

            <label>Avatar:</label>
            <input type="file" name="avatarFile" id="editUserAvatarFile" accept=".jpg,.jpeg,.png,.webp">

            <button type="submit">Save Changes</button>
        </form>
    </div>
</div>

<!-- ====================================================== -->
<!-- 5. CREATE COLLECTION FOR USER MODAL -->
<!-- ====================================================== -->
<div id="createCollectionModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeCreateCollection">&times;</span>
        <h2>Create Collection for User</h2>

        <form id="createCollectionForm" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="action" value="create_collection">
            <input type="hidden" name="username" id="targetUsername">

            <label>Title:</label>
            <input type="text" name="title" required>

            <label>Theme:</label>
            <select name="theme_type" required>
                <option value="games">Games</option>
                <option value="music">Music</option>
                <option value="movies">Movies</option>
                <option value="books">Books</option>
                <option value="anime">Anime/Manga</option>
                <option value="recipes">Recipes</option>
                <option value="custom">Custom</option>
            </select>

            <label>Custom Theme Name (optional):</label>
            <input type="text" name="custom_theme_name">

            <label>Cover Image (optional):</label>
            <input type="file" name="cover" accept=".jpg,.jpeg,.png,.webp">

            <button type="submit">Create Collection</button>
        </form>
    </div>
</div>

<!-- ====================================================== -->