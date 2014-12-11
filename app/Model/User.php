<?php

App::uses('AppModel', 'Model');

class User extends AppModel {
    public $validate = array(
		'email' => array(
			'rule' => 'email',
			'required' => true
		),
        'first_name' => array(
			'rule' => 'notEmpty',
			'required' => true
        ),
        'last_name' => array(
			'rule' => 'notEmpty',
			'required' => true
        )
    );
}
