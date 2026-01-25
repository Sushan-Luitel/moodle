window.addEventListener('DOMContentLoaded', () => {
    const returnLink = document.querySelector('a.returnattemptlink');
    if (returnLink) {
        returnLink.style.pointerEvents = 'none';
        returnLink.style.opacity = '0.4';
        returnLink.style.cursor = 'not-allowed';
        returnLink.removeAttribute('href');
        returnLink.setAttribute('aria-disabled', 'true');
    }

    const returnBtn = document.querySelector('button[name="return"]');
    if (returnBtn) {
        returnBtn.disabled = true;
        returnBtn.style.opacity = '0.4';
        returnBtn.style.cursor = 'not-allowed';
    }

    document.addEventListener('click', function(e) {
        if (e.target.closest('.returnattemptlink')) {
            e.preventDefault();
            e.stopPropagation();
            alert('You are not allowed to return to the attempt.');
        }
    }, true);
});
