<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#dataTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json"
        },
        "dom": 'rtip' // Hide default search box to use custom one
    });

    // Custom Search Box
    $('#searchBox').on('keyup', function() {
        table.search(this.value).draw();
    });
});
</script>
