<?php $form_attrs = (isset($form_attrs)) ? $form_attrs: array(); ?>
<div class="box table-list-wrap">
	<div class="tbl-ctrls">
<?php if (isset($form_url)):?>
	<?=form_open($form_url, $form_attrs)?>
<?php endif;?>
	<?php if ( ! empty($form_right_links)):?>
		<fieldset class="tbl-search right">
		<?php foreach ($form_right_links as $link_data):?>
		<a class="btn tn action" href="<?=$link_data['link']?>"><?=$link_data['title']?></a>
		<?php endforeach;?>
		</fieldset>
	<?php endif;?>
	<?php if (isset($cp_page_title)):?>
		<h1><?=$cp_page_title?></h1>
	<?php elseif (isset($wrapper_header)):?>
		<h1><?=$wrapper_header?></h1>
	<?php endif;?>
		<?=ee('CP/Alert')->getAllInlines()?>
		<?php if (isset($filters)) echo $filters; ?>
		<?=$child_view?>
	<?php if (isset($pagination)):?>
		<div class="ss_clearfix"><?=$pagination?></div>
	<?php endif;?>
<?php if (isset($footer)):?>
	<?php if ($footer['type'] == 'form'):?>
		<fieldset class="form-ctrls">
		<?php if (isset($footer['submit_lang'])):?>
			<input class="btn submit" type="submit" value="<?=$footer['submit_lang']?>" />
		<?php endif;?>
		</fieldset>
	<?php else: ?>

	<?php endif;?>
<?php endif;?>
<?php if (isset($form_url)):?>
		</form>
<?php endif;?>
	</div>
</div>
