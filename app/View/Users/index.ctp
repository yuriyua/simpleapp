<div class="users">
<?php if ($user): ?>
	<div class="row">
		<a href="<?php echo h(Router::url(array('controller' => 'users', 'action' => 'logout'))); ?>">Log out</a>
	</div>
	<div class="row">
		<a href="#">Contacts List</a>
	</div>
	<div class="row">
		<a href="#">Meeting List</a>
	</div>
<?php else: ?>
	<div class="row">
		<a href="<?php echo h(Router::url(array('controller' => 'users', 'action' => 'signup'))); ?>">Sign Up</a>
	</div>
	<div class="row">
		<a href="<?php echo h(Router::url(array('controller' => 'users', 'action' => 'login'))); ?>">Log In</a>
	</div>
<?php endif; ?>
</div>
