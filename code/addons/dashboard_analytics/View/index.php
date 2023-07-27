<?php if(!empty($no_access)) : ?>

		<h1><?=$heading;?></h1>
		<div class="settings">
			<?=ee('CP/Alert')->get('da-error')?>
		</div>

<?php else : ?>
	
	<?php if($token_status == 'empty' || $token_status == 'error') : ?>
		
		<div class="da-profile-intro">
			<?php $this->embed('ee:_shared/form', $form_vars)?>
		</div>	
	
	<?php else : ?>
		<?php if($ee_version == 3) : ?><div class="box"><?php endif; ?>
		<?php $this->embed('ee:_shared/form', $form_vars)?>
		<?php if($ee_version == 3) : ?></div><?php endif; ?>		
	<?php endif; ?>

<?php endif; ?>