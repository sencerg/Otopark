<script>
(function () {
    var tema = localStorage.getItem('tema') === 'vuexy' ? 'vuexy' : 'klasik';
    var mod = localStorage.getItem('mod') || 'light';
    if (mod === 'system') mod = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    document.documentElement.setAttribute('data-tema', tema);
    document.documentElement.setAttribute('data-bs-theme', mod === 'dark' ? 'dark' : 'light');
})();
</script>
