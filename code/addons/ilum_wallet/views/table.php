<style>
    .tbl-wrap { margin-bottom: 0; padding-bottom: 0; }
    th { min-width: 100px; }
</style>

<script>
    function copyToClipboard(key) {
        navigator.clipboard.writeText(key);
    }
</script>

<div class="box">
    <?php //echo"<pre>";print_r($table); die; ?>
    <?=$this->embed('ee:_shared/table', $table);?>
</div>