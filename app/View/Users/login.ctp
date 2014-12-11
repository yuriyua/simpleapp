<div class="users">
<?php echo $this->Session->flash('auth'); ?>
	<a href="<?php echo $client->createAuthUrl(); ?>">Log in using Google</a>
</div>
