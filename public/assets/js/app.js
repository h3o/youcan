/* YouCan — app.js */

document.addEventListener('DOMContentLoaded', () => {

    // ── CSRF token ────────────────────────────────────────────────────────────
    const csrfMeta  = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';

    // ── Mobile nav toggle ─────────────────────────────────────────────────────
    const navToggle = document.querySelector('.nav__toggle');
    const navLinks  = document.querySelector('.nav__links');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', () => {
            const open = navLinks.classList.toggle('nav__links--open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // ── Quill rich text editor ────────────────────────────────────────────────
    const editorEl = document.getElementById('quill-editor');
    const contentInput = document.getElementById('content-input');
    const storyForm    = document.getElementById('story-form');

    if (editorEl && contentInput) {
        const quill = new Quill(editorEl, {
            theme: 'snow',
            placeholder: editorEl.dataset.placeholder || 'Tell your story…',
            modules: {
                toolbar: [
                    [{ header: [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link'],
                    ['clean'],
                ],
            },
        });

        // Pre-fill on edit
        if (contentInput.value) {
            quill.root.innerHTML = contentInput.value;
        }

        // Sync to hidden input before submit
        if (storyForm) {
            storyForm.addEventListener('formdata', (e) => {
                e.formData.set('content', quill.root.innerHTML);
            });

            storyForm.addEventListener('submit', () => {
                contentInput.value = quill.root.innerHTML;
            });
        }
    }

    // ── Like / heart button ───────────────────────────────────────────────────
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (btn.dataset.loginRequired) {
                window.location.href = '/login';
                return;
            }

            btn.disabled = true;

            try {
                const slug = btn.dataset.slug;
                const res  = await fetch(`/stories/${slug}/like`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token':     csrfToken,
                        'Content-Type':     'application/json',
                    },
                    body: JSON.stringify({}),
                });

                if (res.status === 401) {
                    window.location.href = '/login';
                    return;
                }
                if (!res.ok) return;

                const data = await res.json();

                const countEl = btn.querySelector('.like-btn__count');
                if (countEl) countEl.textContent = data.count;

                btn.dataset.liked = data.liked ? 'true' : 'false';
                btn.setAttribute('aria-pressed', data.liked ? 'true' : 'false');
                btn.classList.toggle('like-btn--liked', data.liked);
            } finally {
                btn.disabled = false;
            }
        });
    });

    // ── Auto-dismiss flash messages ───────────────────────────────────────────
    document.querySelectorAll('.flash').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .4s';
            el.style.opacity    = '0';
            setTimeout(() => el.remove(), 400);
        }, 4000);
    });

});
