<div class="users">
<?php echo $this->Session->flash('auth'); ?>
	<a href="<?php echo $client->createAuthUrl(); ?>">Sign up using Google</a>
</div>
