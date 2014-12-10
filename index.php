<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'vendor/autoload.php';

session_start();

$contacts_order = !empty($_GET['contacts_order']) ? $_GET['contacts_order'] : null;
$calendar_order = !empty($_GET['calendar_order']) ? $_GET['calendar_order'] : null;

$client = new Google_Client();
$client->setClientId('<YOUR_CLIENT_ID>');
$client->setClientSecret('<YOUR_CLIENT_SECRET>');
$client->setRedirectUri('http://php-yuriyua.rhcloud.com/index.php');
$client->setAccessType('offline');
$client->addScope(Google_Service_Plus::USERINFO_EMAIL);
$client->addScope(Google_Service_Plus::USERINFO_PROFILE);
$client->addScope('https://www.googleapis.com/auth/contacts.readonly');
$client->addScope(Google_Service_Gmail::GMAIL_READONLY);
$client->addScope(Google_Service_Calendar::CALENDAR_READONLY);

$plus_service = new Google_Service_Plus($client);
$gmail_service = new Google_Service_Gmail($client);
$calendar_service = new Google_Service_Calendar($client);

if (isset($_REQUEST['logout']))
{
	unset($_SESSION['access_token']);
}

if (isset($_GET['code']))
{
  $client->authenticate($_GET['code']);
  $_SESSION['access_token'] = $client->getAccessToken();
  $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
}

if (!empty($_SESSION['access_token']))
{
	$client->setAccessToken($_SESSION['access_token']);
}

if ($client->getAccessToken())
{
	$profile = $plus_service->people->get('me');
	$profile_emails = $profile->getEmails();

	if (!count($profile_emails))
	{
		throw new Exception('Authentication error');
	}

	$user_email = strtolower($profile_emails[0]->getValue());
	$user_name = $profile->getName()->givenName . ' ' . $profile->getName()->familyName;

	$contacts = array();

	$link = 'https://www.google.com/m8/feeds/contacts/default/full?alt=json&sortorder=ascending';

	do
	{
		$request = new Google_Http_Request($link);
		$return = $client->getAuth()->authenticatedRequest($request);
		$contacts_response = Google_Http_REST::decodeHttpResponse($return);

		$contacts = array_merge($contacts, $contacts_response['feed']['entry']);

		if (!empty($contacts_response['feed']['link'][5]) && $contacts_response['feed']['link'][5]['rel'] == 'next')
		{
			$link = $contacts_response['feed']['link'][5]['href'];
		}
		elseif (!empty($contacts_response['feed']['link'][6]) && $contacts_response['feed']['link'][6]['rel'] == 'next')
		{
			$link = $contacts_response['feed']['link'][6]['href'];
		}
		else
		{
			break;
		}
	}
	while (true);

	if (count($contacts))
	{
		$order = array();

		foreach ($contacts as $contact)
		{
			if (empty($contact['gd$email'][0]['address']) || strtolower($contact['gd$email'][0]['address']) == $user_email)
			{
				continue;
			}

			$opt = array('q' => 'list:' . $contact['gd$email'][0]['address']);

			if ($contacts_order == null)
			{
				$opt['maxResults'] = 1;
			}

			$response = $gmail_service->users_messages->listUsersMessages('me', $opt);

			if ($contacts_order == 'frequently')
			{
				$order[$contact['gd$email'][0]['address']] = $response->getResultSizeEstimate();
			}
			else
			{
				$messages = $response->getMessages();

				if (count($messages))
				{
					$__message = $gmail_service->users_messages->get('me', $messages[0]->getId(), array('format' => 'full'));
					$headers = $__message->getPayload()->getHeaders();

					if (count($headers))
					{
						foreach ($headers as $header)
						{
							if ($header->getName() == 'Date')
							{
								$order[$contact['gd$email'][0]['address']] = strtotime($header->getValue());

								break;
							}
						}
					}
				}
			}
		}

		arsort($order);

		if (count($order))
		{
			$__contacts = array();

			foreach ($order as $email => $data)
			{
				foreach ($contacts as $key => $contact)
				{
					if (!empty($contact['gd$email'][0]['address']) && $contact['gd$email'][0]['address'] == $email)
					{
						$__contacts[] = $contact;
						unset($contacts[$key]);
					}
				}
			}
			
			$contacts = array_merge($__contacts, $contacts);
		}
	}

	$calendars = $calendar_service->calendarList->listCalendarList()->getItems();

	$calendar_emails = array();
	$now = time();

	if (count($calendars))
	{
		foreach ($calendars as $calendar)
		{
			$events = $calendar_service->events->listEvents($calendar->id)->getItems();

			if (count($events))
			{
				foreach ($events as $event)
				{
					$attendees = $event->getAttendees();

					if (count($attendees) > 1)
					{
						$start_time = strtotime($event->getStart()->getDateTime());

						foreach ($attendees as $attendee)
						{
							$email = $attendee->getEmail();

							if ($email != $user_email)
							{
								if ($calendar_order == 'frequently')
								{
									if (isset($calendar_emails[$email]))
									{
										$calendar_emails[$email]++;
									}
									else
									{
										$calendar_emails[$email] = 1;
									}
								}
								else
								{
									if (!isset($calendar_emails[$email]) || $calendar_emails[$email] < $start_time &&
										($calendar_emails[$email] <= $now && $start_time <= $now || $calendar_emails[$email] > $now))
									{
										$calendar_emails[$email] = $start_time;
									}
								}
							}
						}
					}
				}
			}
		}

		if (count($calendar_emails))
		{
			if ($calendar_order == 'frequently')
			{
				arsort($calendar_emails);
			}
			else
			{
				$__calendar_emails = array();

				foreach ($calendar_emails as $email => $time)
				{
					if ($time <= $now)
					{
						$__calendar_emails[$email] = $time;
						unset($calendar_emails[$email]);
					}
				}

				arsort($__calendar_emails);
				asort($calendar_emails);

				$calendar_emails = array_merge($__calendar_emails, $calendar_emails);
			}
		}
	}
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>APP</title>
</head>
<body>
	<form action="/index.php" method="GET">
		<div class="box">
			<div class="request">
				<?php if (!empty($_SESSION['access_token'])): ?>
				<table>
					<tr>
						<th>name</th>
						<td><?php echo $user_name; ?></td>
					</tr>
					<tr>
						<th>email</th>
						<td><?php echo $user_email; ?></td>
					</tr>
					<tr>
						<th valign="top">
							<p>contacts</p>
							<p>
								<select name="contacts_order" onchange="this.form.submit();">
									<option value=""<?php if ($contacts_order == null): ?> selected<?php endif; ?>>Recently</option>
									<option value="frequently"<?php if ($contacts_order == 'frequently'): ?> selected<?php endif; ?>>Frequently</option>
								</select>
							</p>
						</th>
						<td>
							<?php if (count($contacts)): ?>
							<table>
								<tr>
									<th>ID</th>
									<th>Name</th>
									<th>Email</th>
									<th>Phone</th>
								</tr>
								<?php foreach ($contacts as $contact): ?>
								<tr>
									<td><?php echo preg_replace('/^.+\//', '', $contact['id']['$t']); ?></td>
									<td><?php echo !empty($contact['title']['$t']) ? $contact['title']['$t'] : '-'; ?></td>
									<td><?php echo !empty($contact['gd$email'][0]['address']) ? $contact['gd$email'][0]['address'] : '-'; ?></td>
									<td><?php echo !empty($contact['gd$phoneNumber'][0]['$t']) ? $contact['gd$phoneNumber'][0]['$t'] : '-'; ?></td>
								</tr>
								<?php endforeach; ?>
							</table>
							<?php else: ?>
							<i>empty</i>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th valign="top">
							<p>calendar</p>
							<p>
								<select name="calendar_order" onchange="this.form.submit();">
									<option value=""<?php if ($calendar_order == null): ?> selected<?php endif; ?>>Recently</option>
									<option value="frequently"<?php if ($calendar_order == 'frequently'): ?> selected<?php endif; ?>>Frequently</option>
								</select>
							</p>
						</th>
						<td>
							<?php if (count($calendar_emails)): ?>
							<table>
								<tr>
									<th>Email</th>
								</tr>
								<?php foreach ($calendar_emails as $email => $data): ?>
								<tr>
									<td><?php echo $email; ?></td>
								</tr>						
								<?php endforeach; ?>
							</table>
							<?php else: ?>
							<i>empty</i>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php else: ?>
				<a class="login" href="<?php echo $client->createAuthUrl(); ?>">Connect Me!</a>
				<?php endif; ?>
			</div>
		</div>
	</form>
</body>
</html>
