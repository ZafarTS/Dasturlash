(function () {
  const key = 'quiz-theme';
  const root = document.documentElement;
  const saved = localStorage.getItem(key);
  if (saved) root.setAttribute('data-theme', saved);

  window.toggleTheme = function toggleTheme() {
    const current = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', current);
    localStorage.setItem(key, current);
  };
})();
