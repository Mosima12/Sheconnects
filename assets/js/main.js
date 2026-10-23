// assets/js/main.js
// Minimal client-side niceties. Server-side validation is authoritative.

document.addEventListener('DOMContentLoaded', () => {
  // Auto-dismiss flash messages after 5 seconds.
  document.querySelectorAll('.flash').forEach(el => {
    setTimeout(() => { el.style.display = 'none'; }, 5000);
  });

  // Confirm dialog on any element with data-confirm="message".
  document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      if (!confirm(btn.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  // Light password-match check on the register page (server still validates).
  const pw  = document.querySelector('#password');
  const pw2 = document.querySelector('#password_confirm');
  if (pw && pw2) {
    const check = () => {
      pw2.setCustomValidity(pw.value !== pw2.value ? 'Passwords do not match.' : '');
    };
    pw.addEventListener('input', check);
    pw2.addEventListener('input', check);
  }
});