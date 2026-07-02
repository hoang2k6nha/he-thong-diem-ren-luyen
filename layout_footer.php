<?php
// layout_footer.php
?>
    </div> <!-- .main-content -->
</div> <!-- .main-wrapper -->

<script>
document.getElementById('menuToggle').addEventListener('click', function() {
    document.getElementById('sidebar').classList.add('show');
    document.getElementById('sidebarOverlay').classList.add('show');
});

document.getElementById('sidebarOverlay').addEventListener('click', function() {
    document.getElementById('sidebar').classList.remove('show');
    this.classList.remove('show');
});

const closeSidebarBtn = document.getElementById('closeSidebar');
if (closeSidebarBtn) {
    closeSidebarBtn.addEventListener('click', function() {
        document.getElementById('sidebar').classList.remove('show');
        document.getElementById('sidebarOverlay').classList.remove('show');
    });
}
</script>
</body>
</html>
