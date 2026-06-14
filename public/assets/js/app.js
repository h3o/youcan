/* YouCan — app.js */

// Module-level so every function (including initAnnotations) can access it
const BASE = (document.querySelector('meta[name="base-url"]') || {}).content || '';

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
                window.location.href = BASE + '/login';
                return;
            }

            btn.disabled = true;

            try {
                const slug = btn.dataset.slug;
                const res  = await fetch(BASE + `/stories/${slug}/like`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token':     csrfToken,
                        'Content-Type':     'application/json',
                    },
                    body: JSON.stringify({}),
                });

                if (res.status === 401) {
                    window.location.href = BASE + '/login';
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

    // ── Inline annotation system ──────────────────────────────────────────────
    if (document.getElementById('story-body') && window.YOUCAN) {
        initAnnotations();
    }

});

// ── Annotation system ─────────────────────────────────────────────────────────
function initAnnotations() {
    const YC        = window.YOUCAN;
    const storyBody = document.getElementById('story-body');
    const panel     = document.getElementById('annotation-panel');
    const bubble    = document.getElementById('annotation-bubble');
    if (!storyBody || !panel || !bubble) return;

    let pendingAnchor = null;   // anchor data captured on mouseup
    let activeMark    = null;   // currently highlighted annotation-mark element

    // ── Apply highlights via mark.js ─────────────────────────────────────────
    function applyHighlights() {
        if (!YC.comments || !YC.comments.length || typeof Mark === 'undefined') return;

        YC.comments.forEach(c => {
            if (+c.anchor_detached || !c.anchor_text) return;

            let matchCount = 0;
            const target = parseInt(c.occurrence_idx, 10) || 0;
            const instance = new Mark(storyBody);

            instance.mark(c.anchor_text, {
                accuracy:           'exactly',
                separateWordSearch: false,
                acrossElements:     true,
                className:          'annotation-mark',
                filter: () => {
                    return matchCount++ === target;
                },
                each: el => {
                    el.dataset.commentId = c.id;
                    el.dataset.anchorText = c.anchor_text;
                },
            });
        });

        positionBadges();
        initHighlightClick();
    }

    // ── Position margin badges alongside highlighted text ─────────────────────
    function positionBadges() {
        const margin = document.getElementById('annotation-margin');
        if (!margin) return;
        margin.innerHTML = '';

        // Group by comment id — take the first mark element per comment
        const seen = new Map();
        storyBody.querySelectorAll('.annotation-mark[data-comment-id]').forEach(el => {
            const id = el.dataset.commentId;
            if (!seen.has(id)) seen.set(id, el);
        });

        seen.forEach((el, id) => {
            const elTop     = el.getBoundingClientRect().top;
            const bodyTop   = storyBody.getBoundingClientRect().top;
            const relTop    = el.offsetTop;

            const badge = document.createElement('span');
            badge.className         = 'annotation-badge';
            badge.style.top         = relTop + 'px';
            badge.dataset.commentId = id;
            badge.textContent       = '💬';
            badge.title             = 'View annotation';
            badge.addEventListener('click', () => openPanelForComment(parseInt(id, 10)));
            margin.appendChild(badge);
        });
    }

    // ── Click on a highlight → open panel ────────────────────────────────────
    function initHighlightClick() {
        storyBody.querySelectorAll('.annotation-mark').forEach(el => {
            el.addEventListener('click', e => {
                e.stopPropagation();
                const id = parseInt(el.dataset.commentId, 10);
                // Deactivate previously active mark
                storyBody.querySelectorAll('.annotation-mark--active')
                         .forEach(m => m.classList.remove('annotation-mark--active'));
                // Activate this group
                storyBody.querySelectorAll(`.annotation-mark[data-comment-id="${id}"]`)
                         .forEach(m => m.classList.add('annotation-mark--active'));
                openPanelForComment(id);
            });
        });
    }

    // ── Text selection → show bubble ─────────────────────────────────────────
    storyBody.addEventListener('mouseup', e => {
        setTimeout(() => {  // let browser finalize selection
            const sel = window.getSelection();
            if (!sel || sel.isCollapsed || !sel.toString().trim()) {
                hideBubble();
                return;
            }
            // Verify selection is within story body
            const range = sel.getRangeAt(0);
            if (!storyBody.contains(range.commonAncestorContainer)) {
                hideBubble();
                return;
            }

            const text = sel.toString();
            const rect = range.getBoundingClientRect();
            const bodyPlain = storyBody.textContent;

            // Compute context
            const fullText = bodyPlain;
            const start    = getSelectionOffset(storyBody, range);
            const prefix   = fullText.slice(Math.max(0, start - 100), start);
            const suffix   = fullText.slice(start + text.length, start + text.length + 100);

            pendingAnchor = { anchorText: text, anchorPrefix: prefix, anchorSuffix: suffix, anchorStart: start };

            // Position bubble above the selection end
            bubble.style.left = (rect.left + rect.width / 2 + window.scrollX) + 'px';
            bubble.style.top  = (rect.top + window.scrollY - 44) + 'px';
            bubble.setAttribute('aria-hidden', 'false');
        }, 10);
    });

    document.addEventListener('mousedown', e => {
        if (!bubble.contains(e.target) && !panel.contains(e.target)) {
            hideBubble();
        }
    });

    function hideBubble() {
        bubble.setAttribute('aria-hidden', 'true');
    }

    // ── Bubble click → open panel for new annotation ──────────────────────────
    document.getElementById('bubble-btn')?.addEventListener('click', () => {
        hideBubble();
        if (!pendingAnchor) return;
        if (!YC.isLoggedIn) { window.location.href = BASE + '/login'; return; }
        openPanelNew(pendingAnchor);
    });

    // ── Panel: open in "new annotation" mode ──────────────────────────────────
    function openPanelNew(anchor) {
        const quoteEl  = document.getElementById('panel-quote');
        const threadEl = document.getElementById('panel-thread');
        const form     = document.getElementById('panel-form');

        if (quoteEl)  quoteEl.textContent = '"' + anchor.anchorText + '"';
        if (threadEl) threadEl.innerHTML  = '';

        // Fill hidden fields
        setField('f-anchor-text',   anchor.anchorText);
        setField('f-anchor-prefix', anchor.anchorPrefix);
        setField('f-anchor-suffix', anchor.anchorSuffix);
        setField('f-anchor-start',  anchor.anchorStart);
        setField('f-parent-id',     '');

        // Reset body
        const bodyEl = document.getElementById('panel-body');
        if (bodyEl) bodyEl.value = '';

        if (form) {
            // Switch form type to inline
            const typeInput = form.querySelector('input[name="type"]');
            if (typeInput) typeInput.value = 'inline';
            form.style.display = '';
        }

        openPanel();
        document.getElementById('panel-body')?.focus();
    }

    // ── Panel: open for existing comment ─────────────────────────────────────
    function openPanelForComment(commentId) {
        const c = YC.comments.find(x => parseInt(x.id, 10) === commentId);
        if (!c) return;

        const quoteEl  = document.getElementById('panel-quote');
        const threadEl = document.getElementById('panel-thread');
        const form     = document.getElementById('panel-form');

        if (quoteEl) quoteEl.textContent = '"' + (c.anchor_text || '') + '"';

        // Render thread (comment + replies)
        if (threadEl) {
            threadEl.innerHTML = renderCommentHtml(c, false) +
                (c.replies || []).map(r => renderCommentHtml(r, true)).join('');
        }

        // Show reply form for top-level
        if (form) {
            const typeInput = form.querySelector('input[name="type"]');
            if (typeInput) typeInput.value = 'inline';
            setField('f-anchor-text',   c.anchor_text || '');
            setField('f-anchor-prefix', c.anchor_prefix || '');
            setField('f-anchor-suffix', c.anchor_suffix || '');
            setField('f-anchor-start',  c.anchor_start || '');
            setField('f-parent-id',     c.id);
            const bodyEl = document.getElementById('panel-body');
            if (bodyEl) {
                bodyEl.placeholder = YC.i18n.replyPh;
                bodyEl.value = '';
            }
            form.style.display = '';
        }

        openPanel();
    }

    function renderCommentHtml(c, isReply) {
        const cls = 'comment panel-comment' + (isReply ? ' comment--reply' : '');
        const canDelete = YC.isLoggedIn &&
            (parseInt(c.user_id, 10) === parseInt(YC.currentUserId, 10) || YC.isOwner);
        const deleteBtn = canDelete
            ? `<form method="post" action="${BASE}/comments/${c.id}/delete" class="comment__delete-form"
                    onsubmit="return confirm(${JSON.stringify(YC.i18n.deleteConfirm)})">
                 <input type="hidden" name="_csrf" value="${YC.csrfToken}">
                 <button type="submit" class="comment__delete">×</button>
               </form>` : '';
        return `<div class="${cls}" id="panel-comment-${c.id}">
            <div class="comment__header">
                <span class="avatar avatar--xs">${c.username[0].toUpperCase()}</span>
                <span class="comment__author">${esc(c.username)}</span>
                <span class="comment__date">${esc(c.created_at?.slice(0, 10) || '')}</span>
                ${deleteBtn}
            </div>
            <div class="comment__body">${esc(c.body)}</div>
        </div>`;
    }

    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function openPanel() {
        panel.classList.add('annotation-panel--open');
        panel.setAttribute('aria-hidden', 'false');
    }

    function closePanel() {
        panel.classList.remove('annotation-panel--open');
        panel.setAttribute('aria-hidden', 'true');
        storyBody.querySelectorAll('.annotation-mark--active')
                 .forEach(m => m.classList.remove('annotation-mark--active'));
        pendingAnchor = null;
    }

    document.getElementById('panel-close')?.addEventListener('click',  closePanel);
    document.getElementById('panel-cancel')?.addEventListener('click', closePanel);

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closePanel();
    });

    // ── Helpers ───────────────────────────────────────────────────────────────
    function setField(id, value) {
        const el = document.getElementById(id);
        if (el) el.value = value;
    }

    function getSelectionOffset(container, range) {
        const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT);
        let offset = 0;
        let node;
        while ((node = walker.nextNode())) {
            if (node === range.startContainer) {
                return offset + range.startOffset;
            }
            offset += node.textContent.length;
        }
        return 0;
    }

    // Apply highlights on load
    applyHighlights();
}
