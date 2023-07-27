<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?>

<?php if (version_compare(APP_VER, '4', '<')) { ?>
<div class="box">
<?php } ?>
	<?php $this->embed('ee:_shared/form')?>
<?php if (version_compare(APP_VER, '4', '<')) { ?>
</div>
<?php } ?>

<div class="box" style="margin-top: 15px;">
	<div class="md-wrap">
	<h2>Help</h2>
	<h3>Twitter</h3>
	<p>To get Twitter API credentials, go to <a href="https://apps.twitter.com/" target="_blank">https://apps.twitter.com/</a>, login to your account and create a new app.</p>
	<h3>Facebook</h3>
	<p>To get Facebook API credentials, go to <a href="https://developers.facebook.com/apps/" target="_blank">https://developers.facebook.com/apps/</a>, login to your account and add a new app.</p>
	<h3>Instagram</h3>
	<p>To get Instagram API credentials, go to <a href="https://www.instagram.com/developer/" target="_blank">https://www.instagram.com/developer/</a>, login to your account and register a new application. Don't forget to enter your EE site URL (<em><?=$site_url?></em>) into the valid redirect URIs. Uncheck the <em>Disable implicit OAuth</em> option.</p>
	<p>Once you have a client id, copy this URL <code><?=$instagram_token_url?></code>, replace <code>CLIENTID</code> with your Instagram app client id, paste it into your browser. Authorize your app to access your account, you'll get redirected to your EE site.</p>
	<p>Check the URL which should be <code><?=$site_url?>#access_token=xxxxxxxxx.xxxxxxxxxx.xxxxxxxxxxxxxxxxxxxxxx</code>. Copy the access token part and save it into the add-on settings.</p>
	<p><strong>NOTE:</strong> In sandbox mode, you can only access your own posts.</p>
	</div>
</div>
