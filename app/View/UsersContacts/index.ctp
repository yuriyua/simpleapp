<?php if (count($contacts)): ?>
<table>
    <tr>
        <th>Name</th>
        <th>Phone Number</th>
        <th>Email</th>
        <th>Google ID</th>
    </tr>
<?php foreach ($contacts as $contact): ?>
    <tr>
        <td>
			<?php echo $contact['UsersContact']['name']; ?>
		</td>
        <td>
			<?php echo $contact['UsersContact']['phone']; ?>
        </td>
        <td>
			<?php echo $contact['UsersContact']['email']; ?>
        </td>
        <td>
            <?php echo $contact['UsersContact']['google_id']; ?>
        </td>
    </tr>
<?php endforeach; ?>
</table>
<div class="paging">
<?php
	echo $this->Paginator->first("First");

	if ($this->Paginator->hasPrev()) {
		echo $this->Paginator->prev("Prev");
	}

	echo $this->Paginator->numbers(array('modulus' => 2));

	if ($this->Paginator->hasNext()) {
		echo $this->Paginator->next("Next");
	}

	echo $this->Paginator->last("Last");
?>
</div>
<div class="search">
<?php echo $this->Form->create('UsersContact', array('action' => 'index'));?>
	<fieldset>
		<legend><?php echo __('Filter');?></legend>
    <?php
        echo $this->Form->input('UsersContact.keywords');
        echo $this->Form->input('UsersContact.name');
        echo $this->Form->input('UsersContact.email');
        echo $this->Form->input('UsersContact.phone');
        echo $this->Form->input('UsersContact.google_id', array('type' => 'text', 'label' => 'Google ID'));
		echo $this->Form->input('UsersContact.sort', array(
			'options' => array(
				'count' => 'Frequently',
				'date' => 'Recently'
			),
			'empty' => ''
		));
		echo $this->Form->input('UsersContact.direction', array(
			'options' => array(
				'asc' => 'ASC',
				'desc' => 'DESC'
			)
		));
        echo $this->Form->submit('Search');
    ?>
	</fieldset>
<?php echo $this->Form->end();?>
</div>
<?php else: ?>
<p>empty</p>
<?php
endif;
