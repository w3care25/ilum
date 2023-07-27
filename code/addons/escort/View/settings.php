<?php
	ee()->cp->load_package_css('settings');
	ee()->cp->load_package_js('settings');
?>

<!--
	I need to close out the existing .col,
	because otherwise the below columns will not 
	meet up with the outer edges of the breacrumbs (ugly).
	Note that I now need to leave the closing </div> out of this view.
-->
</div>

<div class="col-group align-right">
	
	<div class="col w-12">
		<?php if($ee_version == 3) : ?><div class="box"><?php endif; ?>
		<?php if(empty($current_service)) : ?>
			<div class="box">
				<h1><?= lang('escort_intro_heading'); ?></h1>
				<div class="txt-wrap">
					<?=lang('escort_intro_text')?>
				</div>
			</div>
		<?php else : ?>
			<?php $this->embed('ee:_shared/form', $form_vars)?>
		<?php endif; ?>
		<?php if($ee_version == 3) : ?></div><?php endif; ?>
	</div>
	
	<div class="col w-4">
		<div class="box sidebar">
			<h2<?php if(empty($current_service)) : ?> class="act"<?php endif; ?>><a href="<?=ee('CP/URL','addons/settings/escort');?>">Overview</a></h2>
			<h2><?= lang('escort_services'); ?></h2>
				<ul class="escort-service-list" data-action-url="<?=ee('CP/URL','addons/settings/escort');?>">
				<?php foreach($services as $service => $settings) : ?>
					<li data-escort-service="<?=$service;?>" class="<?=(!empty($current_settings[$service.'_active']) && $current_settings[$service.'_active'] == 'y') ? 'enabled-service' : 'disabled-service';?><?php if($current_service == $service) : ?> act<?php endif; ?>">
						<a href="<?=ee('CP/URL','addons/settings/escort/'.$service);?>"><?= lang('escort_'.$service.'_name'); ?></a>
					</li>
				<?php endforeach; ?>
				</ul>
		</div>
	</div>

<!-- Closing </div> intentionally absent! -->