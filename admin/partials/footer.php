        </main>

    </div>
</div>

<script>
(function () {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('adminOverlay');
    const toggle  = document.getElementById('sidebarToggle');
    const profile = document.getElementById('profileDropdown');

    function closeSidebar() {
        sidebar?.classList.remove('is-open');
        overlay?.classList.remove('is-visible');
        overlay?.setAttribute('aria-hidden', 'true');
    }

    toggle?.addEventListener('click', function () {
        const open = !sidebar.classList.contains('is-open');
        sidebar.classList.toggle('is-open', open);
        overlay?.classList.toggle('is-visible', open);
        overlay?.setAttribute('aria-hidden', open ? 'false' : 'true');
    });

    overlay?.addEventListener('click', closeSidebar);

    profile?.addEventListener('click', function (e) {
        if (!e.target.closest('.admin-topbar__dropdown-item')) {
            profile.classList.toggle('is-open');
        }
    });

    document.addEventListener('click', function (e) {
        if (profile && !profile.contains(e.target)) {
            profile.classList.remove('is-open');
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1024) closeSidebar();
    });
})();
</script>

</body>
</html>