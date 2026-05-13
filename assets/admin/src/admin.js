import './admin.css';

const ready = (callback) => {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', callback, { once: true });
    return;
  }

  callback();
};

ready(() => {
  document.querySelectorAll('[data-aicore-admin-app]').forEach((element) => {
    element.classList.add('aicore-admin-app');
  });
});
