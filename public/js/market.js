document.querySelectorAll('form[data-price]').forEach((form) => {
    const weight = form.querySelector('[data-weight]');
    const total = form.querySelector('[data-total]');
    function updateEstimate() {
        const transport =
            Number(form.dataset.price) * Math.max(0, Number(weight.value));
        const fee = Math.round((transport * Number(form.dataset.fee)) / 100);
        total.textContent = ((transport + fee) / 100).toLocaleString('fr-FR', {
            style: 'currency',
            currency: 'EUR',
        });
    }
    weight.addEventListener('input', updateEstimate);
    updateEstimate();
});

const cookieDialog = document.getElementById('cookie-settings');
document.querySelectorAll('[data-cookie-open]').forEach((button) => {
    button.addEventListener('click', () => cookieDialog?.showModal());
});
document.querySelectorAll('[data-cookie-close]').forEach((button) => {
    button.addEventListener('click', () => cookieDialog?.close());
});
