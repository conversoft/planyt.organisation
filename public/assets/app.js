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
