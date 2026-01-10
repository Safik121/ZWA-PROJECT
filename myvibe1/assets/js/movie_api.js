// TMDb API integration for MyVibe — interactive version with rating/comment form

document.addEventListener('DOMContentLoaded', () => {
    // --- Modal open/close ---
    const addFromApiCardMovies = document.getElementById('addFromApiCardMovies');
    const apiAddModal = document.getElementById('apiAddModalMovies');
    const closeBtn = apiAddModal?.querySelector('.close');
  
    if (addFromApiCardMovies && apiAddModal) {
      addFromApiCardMovies.addEventListener('click', () => {
        apiAddModal.style.display = 'block';
      });
  
      if (closeBtn) {
        closeBtn.addEventListener('click', () => {
          apiAddModal.style.display = 'none';
        });
      }
  
      window.addEventListener('click', (e) => {
        if (e.target === apiAddModal) apiAddModal.style.display = 'none';
      });
    }
  
    // --- Search logic ---
    const form = document.getElementById('movieSearchForm');
    const queryInput = document.getElementById('movieQuery');
    const resultsDiv = document.getElementById('movieResults');
    const collectionId = document.getElementById('collectionId')?.value;
  
    if (!form || !queryInput || !resultsDiv || !collectionId) return;
  
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const q = queryInput.value.trim();
      if (!q) return;
  
      resultsDiv.innerHTML = '<p>Searching movies…</p>';
  
      try {
        const resp = await fetch(`api/tmdb_search.php?query=${encodeURIComponent(q)}`, {
          headers: { 'Accept': 'application/json' }
        });
        const data = await resp.json();
  
        if (!Array.isArray(data) || data.length === 0) {
          resultsDiv.innerHTML = '<p>No movies found.</p>';
          return;
        }
  
        // --- Show results ---
        const frag = document.createDocumentFragment();
        data.forEach(movie => {
          const card = document.createElement('div');
          card.className = 'api-item selectable';
          card.style.cursor = 'pointer';
  
          const img = document.createElement('img');
          img.className = 'api-thumb';
          img.src = movie.image || 'default/item_default.png';
          img.alt = movie.name || 'Movie';
  
          const title = document.createElement('h4');
          title.textContent = movie.name || 'Unknown';
  
          const release = document.createElement('p');
          release.textContent = `Released: ${movie.released || 'Unknown'}`;
  
          const rating = document.createElement('p');
          rating.textContent = `TMDb Rating: ${movie.rating?.toFixed?.(1) || 'N/A'}`;
  
          card.append(img, title, release, rating);
          card.addEventListener('click', () => showMovieForm(movie));
          frag.append(card);
        });
  
        resultsDiv.innerHTML = '';
        resultsDiv.appendChild(frag);
      } catch (err) {
        console.error('TMDb fetch error:', err);
        resultsDiv.innerHTML = '<p>Error fetching data from TMDb API.</p>';
      }
    });
  
    // --- Show Add Form for selected movie ---
    function showMovieForm(movie) {
      const resultsDiv = document.getElementById('movieResults');
      resultsDiv.innerHTML = '';
  
      const wrapper = document.createElement('div');
      wrapper.className = 'api-form';
  
      const title = document.createElement('h3');
      title.textContent = `Add "${movie.name}"`;
  
      const img = document.createElement('img');
      img.className = 'api-thumb-large';
      img.src = movie.image || 'default/item_default.png';
      img.alt = movie.name;
  
      // Form for adding movie
      const formAdd = document.createElement('form');
      formAdd.method = 'post';
      formAdd.action = `${window.location.pathname}${window.location.search}`;
  
      // Hidden fields
      const hiddenAction = makeHidden('action', 'add');
      const titleField = makeHidden('title', movie.name || 'Unknown');
      const imageField = makeHidden('image_url', movie.image || '');
      const apiIdField = makeHidden('api_id', movie.id || '');
      const collectionField = makeHidden('collection_id', collectionId);
      const ratingField = makeHidden('rating', '3'); // default
  
      // Comment input
      const labelComment = document.createElement('label');
      labelComment.textContent = 'Your comment:';
      const commentArea = document.createElement('textarea');
      commentArea.name = 'comment';
      commentArea.rows = 3;
      commentArea.value = movie.overview || 'Added from TMDb API';
  
      // Rating select
      const labelRating = document.createElement('label');
      labelRating.textContent = 'Your rating (1–5):';
      const ratingSelect = document.createElement('select');
      ratingSelect.name = 'rating';
      for (let i = 1; i <= 5; i++) {
        const opt = document.createElement('option');
        opt.value = i;
        opt.textContent = '⭐'.repeat(i);
        if (i === 3) opt.selected = true;
        ratingSelect.append(opt);
      }
  
      const submit = document.createElement('button');
      submit.type = 'submit';
      submit.textContent = 'Add Movie';
      submit.className = 'button-create';
  
      formAdd.append(
        hiddenAction,
        titleField,
        imageField,
        apiIdField,
        collectionField,
        labelComment,
        commentArea,
        labelRating,
        ratingSelect,
        submit
      );
  
      wrapper.append(title, img, formAdd);
      resultsDiv.append(wrapper);
    }
  
    function makeHidden(name, value) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = value;
      return input;
    }
  });
  