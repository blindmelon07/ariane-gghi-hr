// Dark mode toggle - persist preference in localStorage
function initDarkMode() {
    const stored = localStorage.getItem('darkMode');
    if (stored === 'true' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

// Run immediately to prevent flash
initDarkMode();

window.toggleDarkMode = function () {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('darkMode', isDark);
};

window.isDarkMode = function () {
    return document.documentElement.classList.contains('dark');
};
