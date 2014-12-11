<div class="users">
	<?php if (count($emails)): ?>
	<table>
		<tr>
			<th>Email</th>
		</tr>
	<?php foreach ($emails as $email): ?>
		<tr>
			<td>
				<?php echo $email; ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
	<div class="search">
	<?php echo $this->Form->create('Users', array('action' => 'meetings'));?>
		<fieldset>
			<legend><?php echo __('Filter');?></legend>
		<?php
			echo $this->Form->input('Users.keywords');
			echo $this->Form->input('Users.sort', array(
				'options' => array(
					'' => 'Recently',
					'frequently' => 'Frequently'
				)
			));
			echo $this->Form->submit('Search');
		?>
		</fieldset>
	<?php echo $this->Form->end();?>
	</div>
<?php else: ?>
	<p>empty</p>
<?php endif; ?>
</div>
