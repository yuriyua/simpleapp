<?php
App::uses('AppController', 'Controller');

class UsersController extends AppController {

	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('index', 'logout', 'login', 'signup');
	}

    public function index() {
        $this->set('user', $this->Auth->user());
    }

	public function signup() {
		if ($this->Auth->loggedIn()) {
			return $this->redirect('/');
		}

		$error = $this->request->query('error');

		if ($error != null) {
			switch ($error) {
				default:
					$this->Session->setFlash(__('Access denied, try again'));
			}
		}

		$client = new Google_Client();
		$client->setClientId(Configure::read('GoogleAPI.client_id'));
		$client->setClientSecret(Configure::read('GoogleAPI.client_secret'));
		$client->setRedirectUri(h(Router::url(array('controller' => 'users', 'action' => 'signup'), true)));
		$client->setAccessType('offline');
		$client->addScope(Google_Service_Plus::USERINFO_EMAIL);
		$client->addScope(Google_Service_Plus::USERINFO_PROFILE);
		$client->addScope('https://www.googleapis.com/auth/contacts.readonly');
		$client->addScope(Google_Service_Gmail::GMAIL_READONLY);
		$client->addScope(Google_Service_Calendar::CALENDAR_READONLY);

		$code = $this->request->query('code');

		if ($code != null) {
			$plus_service = new Google_Service_Plus($client);
			$profile = $plus_service->people->get('me');
			$profile_emails = $profile->getEmails();
			$profile_email = strtolower($profile_emails[0]->getValue());

			$return = false;
			$user = $this->User->findByEmail($profile_email);

			if ($user) {
				$return = $this->Auth->login($user['User']);
			} else {
				$user = $this->User->save(array(
					'email' => $profile_email,
					'first_name' => $profile->getName()->givenName,
					'last_name' => $profile->getName()->familyName,
				));

				if ($user) {
					// TODO: check for expire
					$user['google_token'] = $code;

					$return = $this->Auth->login($user);

					// TODO: save user contacts
				} else {
					$this->Session->setFlash(__('The user could not be saved. Please, try again.'));
				}
			}

			if ($return) {
				return $this->redirect($this->Auth->redirectUrl());
			}
		}

		$this->set('client', $client);
	}

	public function login() {

		if ($this->Auth->loggedIn()) {
			return $this->redirect('/');
		}

		$error = $this->request->query('error');

		if ($error != null) {
			switch ($error) {
				default:
					$this->Session->setFlash(__('Access denied, try again'));
			}
		}

		$client = new Google_Client();
		$client->setClientId(Configure::read('GoogleAPI.client_id'));
		$client->setClientSecret(Configure::read('GoogleAPI.client_secret'));
		$client->setRedirectUri(h(Router::url(array('controller' => 'users', 'action' => 'login'), true)));
		$client->setAccessType('offline');
		$client->addScope(Google_Service_Plus::USERINFO_EMAIL);
		$client->addScope(Google_Service_Calendar::CALENDAR_READONLY);

		$code = $this->request->query('code');

		if ($code != null) {
			$plus_service = new Google_Service_Plus($client);
			$profile = $plus_service->people->get('me');
			$profile_emails = $profile->getEmails();
			$profile_email = strtolower($profile_emails[0]->getValue());

			$user = $this->User->findByEmail($profile_email);

			if (!$user)
			{
				$this->Session->setFlash(__('Incorrect email address.'));
				return $this->redirect(Router::url(array('controller' => 'users', 'action' => 'signup')));
			}

			// TODO: check for expire
			$user['google_token'] = $code;

			$return = $this->Auth->login($user);

			if ($return) {
				return $this->redirect($this->Auth->redirectUrl());
			}
		}

		$this->set('client', $client);
	}

	public function logout() {
		return $this->redirect($this->Auth->logout());
	}

}
