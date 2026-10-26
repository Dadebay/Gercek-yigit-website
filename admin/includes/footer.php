
    </main>
  </div>
</div>
<footer class="admin-footer py-3 mt-4 border-top bg-white shadow-sm">
    <div class="container-fluid d-flex justify-content-between align-items-center small text-muted">
        <span>© 2025 Gerçek Ýigit</span>
        <span>Gerçek Ýigit Admin</span>
    </div>
</footer>
<script src="./assets/js/bootstrap.bundle.min.js"></script>
<script>
    (function(){
        if (typeof jQuery === 'undefined' || typeof $.fn.DataTable === 'undefined') return;
        $(function(){
            // Initialize DataTables on all Bootstrap tables in admin area
            $('table').each(function(){
                // skip if already initialized
                if ($.fn.dataTable.isDataTable(this)) return;
                try {
                    // remove inline attributes that may interfere
                    $(this).removeAttr('style').removeAttr('width');
                    $(this).addClass('table table-hover table-striped table-bordered');
                    $(this).DataTable({
                        paging: true,
                        searching: true,
                        info: true,
                        lengthChange: true,
                        autoWidth: false,
                        order: [],
                        responsive: true
                    });
                } catch (e) {
                    console.warn('DataTable init failed for table', e);
                }
            });
        });
    })();
</script>
</body>
</html>
