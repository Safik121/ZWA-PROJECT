/**
 * SEARCH AUTOCOMPLETE
 * -------------------
 * Handles real-time user search suggestions in the header.
 */

document.addEventListener('DOMContentLoaded', () => {
    const searchForm = document.querySelector('.search-form');
    if (!searchForm) return;

    const input = searchForm.querySelector('input[name="q"]');
    if (!input) return;

    // Create dropdown container
    const dropdown = document.createElement('div');
    dropdown.className = 'search-dropdown';
    dropdown.style.display = 'none';
    searchForm.appendChild(dropdown);

    let debounceTimer;

    input.addEventListener('input', (e) => {
        const query = e.target.value.trim();

        clearTimeout(debounceTimer);

        if (query.length < 2) {
            dropdown.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetchSuggestions(query);
        }, 300);
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!searchForm.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // Handle keyboard navigation (optional but good for UX)
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            dropdown.style.display = 'none';
        }
    });

    async function fetchSuggestions(query) {
        try {
            // Get base URL from a meta tag or deduce it? 
            // We can use relative path 'api/search_users_hint.php' if we are in root, 
            // but we might be in subfolders.
            // Best to use the form action to determine base path.

            // Form action is usually "BASE_URL/search_users.php"
            // We want "BASE_URL/api/search_users_hint.php"

            const formAction = searchForm.getAttribute('action');
            const baseUrl = formAction.substring(0, formAction.lastIndexOf('/'));
            const apiUrl = `${baseUrl}/api/search_users_hint.php?q=${encodeURIComponent(query)}`;

            const response = await fetch(apiUrl);
            if (!response.ok) throw new Error('API error');

            const users = await response.json();
            renderDropdown(users);

        } catch (error) {
            console.error('Search hint error:', error);
        }
    }

    function renderDropdown(users) {
        dropdown.innerHTML = '';

        if (users.length === 0) {
            dropdown.style.display = 'none';
            return;
        }

        const ul = document.createElement('ul');
        ul.className = 'search-results-list';

        users.forEach(user => {
            const li = document.createElement('li');
            li.className = 'search-result-item';

            // Highlight matching part? Maybe later.

            li.innerHTML = `
                <a href="profile.php?id=${user.id}" class="search-result-link">
                    <img src="${user.avatar}" alt="${user.username}" class="search-result-avatar">
                    <div class="search-result-info">
                        <span class="search-result-name">${escapeHtml(user.username)}</span>
                        ${user.display_name ? `<span class="search-result-displayname">${escapeHtml(user.display_name)}</span>` : ''}
                    </div>
                </a>
            `;
            ul.appendChild(li);
        });

        dropdown.appendChild(ul);
        dropdown.style.display = 'block';
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
