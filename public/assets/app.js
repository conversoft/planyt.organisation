document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy-prompt]');

    if (!button) {
        return;
    }

    const prompt = document.querySelector('#compiledPrompt');

    if (!prompt) {
        return;
    }

    await navigator.clipboard.writeText(prompt.value);
    button.textContent = 'Kopiert';
});


document.querySelectorAll('[data-mail-body]').forEach((body) => {
    const wrapper = body.closest('.mail-body-wrapper');
    const button = wrapper?.querySelector('[data-mail-more]');

    if (!button || body.scrollHeight <= 800) {
        return;
    }

    body.classList.add('is-collapsed');
    button.hidden = false;

    button.addEventListener('click', () => {
        const collapsed = body.classList.toggle('is-collapsed');
        button.textContent = collapsed ? 'Mehr anzeigen' : 'Weniger anzeigen';
    });
});
