function showRequestError(message) {
    const error = document.getElementById('request-error');
    if (!error) return;
    error.textContent = message;
    error.classList.remove('d-none');
    error.focus();
}

function initializeForms() {
    document.querySelectorAll('[data-anon-token]').forEach(input => {
        if (input.value) return;
        try {
            const key = 'voluntrack.anon_token';
            let token = localStorage.getItem(key);
            if (!token || !/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(token)) {
                const bytes = crypto.getRandomValues(new Uint8Array(16));
                bytes[6] = (bytes[6] & 15) | 64;
                bytes[8] = (bytes[8] & 63) | 128;
                const hex = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
                token = `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
                localStorage.setItem(key, token);
            }
            input.value = token;
        } catch (error) {
            const notice = document.createElement('p');
            notice.className = 'form-text';
            notice.setAttribute('role', 'status');
            notice.textContent = 'This browser cannot remember anonymous contributions across visits. You can still submit hours without an account.';
            input.after(notice);
        }
    });
    const summary = document.querySelector('[data-validation-summary]');
    if (summary) summary.focus();
}

document.addEventListener('click', async event => {
    const button = event.target.closest('[data-copy-target]');
    if (!button) return;
    const input = document.getElementById(button.dataset.copyTarget);
    const status = button.closest('.share-panel').querySelector('[data-copy-status]');
    try {
        await navigator.clipboard.writeText(input.value);
        status.textContent = 'Link copied. Ready to share with your community.';
    } catch (error) {
        input.focus();
        input.select();
        status.textContent = "Automatic copying is unavailable. The link is selected; use your browser's Copy action.";
    }
});

document.addEventListener('htmx:configRequest', event => {
    event.detail.headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
});

document.addEventListener('htmx:beforeRequest', () => {
    const error = document.getElementById('request-error');
    if (error) error.classList.add('d-none');
});

document.addEventListener('htmx:afterSwap', event => {
    initializeForms();
    if (event.detail.target.id === 'form-container') {
        const confirmation = event.detail.target.querySelector('h2');
        if (confirmation && !event.detail.target.querySelector('form')) {
            confirmation.tabIndex = -1;
            confirmation.focus();
        }
    }
});

document.addEventListener('htmx:responseError', event => {
    let message = 'Your change could not be saved. Please try again.';
    if (event.detail.xhr.status === 422) {
        try {
            const response = JSON.parse(event.detail.xhr.responseText);
            message = Object.values(response.errors ?? {}).flat().join(' ') || message;
        } catch (error) {
            message = 'Please check your values and try again. Hours must be between 0.01 and 24.';
        }
    } else if ([401, 419].includes(event.detail.xhr.status)) {
        message = 'Your session has expired. Reload the page and sign in again before retrying.';
    } else if (event.detail.xhr.status === 403) {
        message = 'You do not have permission to make this change.';
    }
    showRequestError(message);
});
document.addEventListener('htmx:sendError', () => showRequestError('Connection lost. Your change was not confirmed. Check your connection before trying again.'));
document.addEventListener('DOMContentLoaded', initializeForms);
