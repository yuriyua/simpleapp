<?php
App::uses('AppController', 'Controller');

class UsersController extends AppController {

	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('index', 'logout', 'login', 'signup', 'oauth2callback');
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
		$client->setRedirectUri(h(Router::url(array('controller' => 'users', 'action' => 'oauth2callback'), true)));
		$client->setAccessType('offline');
		$client->addScope(Google_Service_Plus::USERINFO_EMAIL);
		$client->addScope(Google_Service_Plus::USERINFO_PROFILE);
		$client->addScope('https://www.googleapis.com/auth/contacts.readonly');
		$client->addScope(Google_Service_Gmail::GMAIL_READONLY);
		$client->addScope(Google_Service_Calendar::CALENDAR_READONLY);

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
		$client->setRedirectUri(h(Router::url(array('controller' => 'users', 'action' => 'oauth2callback'), true)));
		$client->setAccessType('offline');
		$client->addScope(Google_Service_Plus::USERINFO_EMAIL);
		$client->addScope(Google_Service_Plus::USERINFO_PROFILE);
		$client->addScope('https://www.googleapis.com/auth/contacts.readonly');
		$client->addScope(Google_Service_Gmail::GMAIL_READONLY);
		$client->addScope(Google_Service_Calendar::CALENDAR_READONLY);

		$this->set('client', $client);
	}

	public function oauth2callback() {
		if ($this->Auth->loggedIn()) {
			return $this->redirect('/');
		}

		$code = $this->request->query('code');

		if ($code == null) {
			$this->Session->setFlash(__('Token cannot be blank.'));
			return $this->redirect('/');
		}

		$client = new Google_Client();
		$client->setClientId(Configure::read('GoogleAPI.client_id'));
		$client->setClientSecret(Configure::read('GoogleAPI.client_secret'));
		$client->setRedirectUri(h(Router::url(array('controller' => 'users', 'action' => 'oauth2callback'), true)));
		$client->setAccessType('offline');
		$client->authenticate($code);

		$gmail_service = new Google_Service_Gmail($client);
		$plus_service = new Google_Service_Plus($client);
		$profile = $plus_service->people->get('me');
		$profile_emails = $profile->getEmails();
		$profile_email = strtolower($profile_emails[0]->getValue());

		$user = $this->User->findByEmail($profile_email);

		if ($user == null) {
			$this->loadModel('UsersContact');

			$return = $this->User->save(array(
				'email' => $profile_email,
				'first_name' => $profile->getName()->givenName,
				'last_name' => $profile->getName()->familyName,
			));

			if (!$return) {
				$this->Session->setFlash(__('The user could not be saved. Please, try again.'));
				return $this->redirect('/');
			}

			$user = $return['User'];

			$link = 'https://www.google.com/m8/feeds/contacts/default/full?alt=json';

			do {
				$request = new Google_Http_Request($link);
				$return = $client->getAuth()->authenticatedRequest($request);
				$contacts_response = Google_Http_REST::decodeHttpResponse($return);

				if (count($contacts_response['feed']['entry'])) {
					foreach ($contacts_response['feed']['entry'] as $contact) {
						if (!empty($contact['gd$email'][0]['address']) && $contact['gd$email'][0]['address'] == $profile_email) {
							continue;
						}

						$data = array(	
							'user_id' => $user['id'],
							'google_id' => preg_replace('/^.+\//', '', $contact['id']['$t'])
						);

						if (!empty($contact['title']['$t'])) {
							$data['name'] = $contact['title']['$t'];
						}

						if (!empty($contact['gd$phoneNumber'][0]['$t'])) {
							$data['phone'] = $contact['gd$phoneNumber'][0]['$t'];
						}

						if (!empty($contact['gd$email'][0]['address'])) {
							$data['email'] = strtolower($contact['gd$email'][0]['address']);

							$response = $gmail_service->users_messages->listUsersMessages('me', array('q' => 'list:' . $data['email']));
							
							if ($response) {
								$data['count'] = $response->getResultSizeEstimate();

								$messages = $response->getMessages();

								if (count($messages)) {
									$__message = $gmail_service->users_messages->get('me', $messages[0]->getId(), array('format' => 'full'));
									$headers = $__message->getPayload()->getHeaders();

									if (count($headers)) {
										foreach ($headers as $header) {
											if ($header->getName() == 'Date') {
												$data['date'] = date('Y-m-d H:i:s', strtotime($header->getValue()));

												break;
											}
										}
									}
								}
							}
						}

						if (!$this->UsersContact->save($data)) {
							$this->Session->setFlash(__('The contact could not be saved.'));
							return $this->redirect('/');
						}
						
						$this->UsersContact->clear();
					}
				}

				if (!empty($contacts_response['feed']['link'][5]) && $contacts_response['feed']['link'][5]['rel'] == 'next') {
					$link = $contacts_response['feed']['link'][5]['href'];
				} elseif (!empty($contacts_response['feed']['link'][6]) && $contacts_response['feed']['link'][6]['rel'] == 'next') {
					$link = $contacts_response['feed']['link'][6]['href'];
				} else {
					break;
				}
			} while (true);
		}

		if (!$this->Auth->login($user)) {
			$this->Session->setFlash(__('Invalid user data.'));
			return $this->redirect('/');
		}

		return $this->redirect($this->Auth->redirectUrl());
	}

	public function logout() {
		return $this->redirect($this->Auth->logout());
	}

}
