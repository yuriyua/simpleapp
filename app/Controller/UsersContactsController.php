<?php
App::uses('AppController', 'Controller');

class UsersContactsController extends AppController {

	public $components = array('Paginator');

	public function index() {
		if (!$this->Auth->loggedIn()) {
			return $this->redirect('/');
		}

		if ($this->request->is('post')) {
			$filter_url = array('controller' => 'users_contacts', 'action' => 'index');

			foreach ($this->request->data['UsersContact'] as $name => $value) {
				if (trim($value) !== '') {
					$filter_url[$name] = urlencode($value);
				}
			}

			return $this->redirect($filter_url);
		}

		$conditions = array(
			'UsersContact.user_id' => $this->Auth->user('id')
		);

		if (count($this->params['named'])) {
			foreach($this->params['named'] as $param_name => $value){
				if (!in_array($param_name, array('page','sort','direction'))) {
					$value = urldecode($value);

					if ($param_name == 'keywords') {
						$conditions['OR'] = array(
							array('UsersContact.name LIKE' => '%' . $value . '%'),
							array('UsersContact.phone LIKE' => '%' . $value . '%'),
							array('UsersContact.email LIKE' => '%' . $value . '%'),
							array('UsersContact.google_id LIKE' => '%' . $value . '%')
						);
					} else {
						$conditions['UsersContact.' . $param_name] = $value;
					}

					$this->request->data['UsersContact'][$param_name] = $value;
				}
			}
		}

		$order = array();

		if (!empty($this->params['named']['sort']))
		{
			$this->request->data['UsersContact']['sort'] = $this->params['named']['sort'];
			
			if (!empty($this->params['named']['direction']))
			{
				$order[$this->params['named']['sort']] = $this->params['named']['direction'];
			}
		}

		if (!empty($this->params['named']['direction']))
		{
			$this->request->data['UsersContact']['direction'] = $this->params['named']['direction'];
		}

		$this->paginate = array(
			'conditions' => $conditions,
			'order' => $order,
			'limit' => 20
		);

		$this->set('contacts', $this->paginate('UsersContact'));
	}

}
