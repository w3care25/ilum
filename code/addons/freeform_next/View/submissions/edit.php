<link rel="stylesheet" href="<?php echo URL_THIRD_THEMES ?>freeform_next/css/builder.css" />
<link rel="stylesheet" href="<?php echo URL_THIRD_THEMES ?>freeform_next/css/submission-edit.css"/>

<div class="<?php echo version_compare(APP_VER, '4.0.0', '<') ? 'box' : '' ?>">
    <?php $this->embed('ee:_shared/form')?>
</div>
