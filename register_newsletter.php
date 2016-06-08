<?php

# Require config to get admin mail
//require_once __DIR__ . '/../config.php';
define('ADMIN_MAIL', 'your@mail.com');

# read POST stream
$postdata = file_get_contents("php://input");
$request = json_decode($postdata, true);
# get 'contact' field

$email = $request['email'];
$newsletter_file = __DIR__ . '/newsletter.json';

$response = ['status' => 'error', 'message' => ''];

try
{
	if(isset($email))
	{
		if(!empty($email))
		{
			if(is_valid_email($email))
			{
				if(!is_junk_email($email))
				{
					$subscribers = get_members($newsletter_file);

					// check member does not already exist
					if(is_new_member($email, $subscribers))
					{
						if(save_member($email, $subscribers, $newsletter_file))
						{
							$response['status'] = 'success';
							// send webmaster an email to warn he has a new subscriber : yahoo !
							warn_webmaster_new_subscriber($email);
						}
						else
						{
							$response['message'] = 'ERROR_SAVING_SUBSCRIPTION';
						}
					}
					else
					{
						$response['message'] = 'EMAIL_ALREADY_SUBSCRIBER';		
					}
				}
				else
				{
					$response['message'] = 'EMAIL_JUNK';
				}
			}
			else
			{
				$response['message'] = 'EMAIL_INVALID';
			}
		}
		else
		{
			$response['message'] = 'EMAIL_EMPTY';
		}
	}
	else
	{
		$response['message'] = 'EMAIL_NOT_SET';
	}
}
catch(Exception $e)
{
	error_log('Error refister_newsletter.php : ' . $e->getMessage());
	warn_webmaster_server_error($e->getMessage());
	$response['message'] = 'INTERNAL_SERVER_ERROR';
}


/**
 * Save new subscriber to subscribers list
 * 
 * @param $string email new subscriber email
 * @param array $subscribers
 * @return bool is subscriber list saved 
 */
function save_member($email, $subscribers, $newsletter_file)
{
	// create new member
	$new_member = ['email' => $email, 'date' => (new DateTime())->format('Y-m-d H:i:s')];

	// add new member to list
	$subscribers[] = $new_member;

	return set_members($subscribers, $newsletter_file);
}

/**
 * Return newsletter members array (from json file)
 */
function set_members($subscribers, $newsletter_file)
{
	$saved = false;
	try
	{
		// save results
		$saved = file_put_contents($newsletter_file, json_encode($subscribers, JSON_PRETTY_PRINT));
		if($saved!==false)
			$saved = true;

	}
	catch(Exception $e)
	{
		error_log('set_members' . $e->getMessage());
		throws('HugException');
	}

	return $saved;
}

/**
 * Return newsletter members array (from json file)
 *
 * @return array $subscribers
 */
function get_members($newsletter_file)
{
	$subscribers = [];

	try
	{
		$subscribers = file_get_contents($newsletter_file);

		if($subscribers)
		{
			$subscribers = json_decode($subscribers, true);
			//error_log('subscribers : ' . print_r($subscribers, true));
		}
		else
		{
			file_put_contents($newsletter_file, json_encode([]));
		}
	}
	catch(Exception $e)
	{
		error_log('get_members' . $e->getMessage());
		throws('HugException');
	}

	return $subscribers;
}

/**
 * Test if a subscriber already exists
 *
 * @param string $email new subscriber email
 * @param array $subscribers Array of newsletter subscribers
 * @return bool is new email in susbscribers list or not
 */
function is_new_member($email, $subscribers)
{
	foreach ($subscribers as $subscriber)
	{
		if($subscriber['email']===$email)
		{
			return false;
		}
	}
	return true;
}

/**
 * Filter email
 *
 * @param string $email email to test
 * @return bool is email valid
 */
function is_valid_email($email)
{
	// many techniques
	return !!filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 *
 */
function is_junk_email($email)
{
	// check against any junk email service like ...
	// 
	return false;
}

/**
 * Send mail to webmaster to warn him he has a new subscriber
 * In real life such a mail would be send by a cron task every day / week / month
 *
 * @param string $new_member_email
 * @return bool
 */
function warn_webmaster_new_subscriber($new_member_email)
{
	$mail_sent = false;
	error_log('ADMIN_MAIL : ' . ADMIN_MAIL);
	try
	{
		$message = [
			'Un nouvel abonné pour la newsletter',
			$new_member_email,
			'Encore un effort ;)'
		];
		$message = implode("\n", $message);
		# Send contact mail to admin
		if( mail(ADMIN_MAIL, "Newsletter Hugo Maugey", $message, "", "-f hugo@maugey.fr -F Hugo Maugey Website") )
		{
			$mail_sent = true;
		}
		else
		{
			error_log('Error sending webmaster mail');
		}
	}
	catch(Exception $e)
	{
		error_log('Error sending webmaster mail' . $e->getMessage());
	}

	return $mail_sent;
}


function warn_webmaster_server_error($error)
{
	$mail_sent = false;
	error_log('ADMIN_MAIL : ' . ADMIN_MAIL);
	try
	{
		$message = [
			'Une erreure est survenue sur le serveur',
			$error,
			'Va falloir régler ça ... ;)'
		];
		$message = implode("\n", $message);
		# Send contact mail to admin
		if( mail(ADMIN_MAIL, "Error Hugo Maugey", $message, "", "-f hugo@maugey.fr -F Hugo Maugey Website") )
		{
			$mail_sent = true;
		}
		else
		{
			error_log('Error sending webmaster mail');
		}
	}
	catch(Exception $e)
	{
		error_log('Error sending webmaster mail' . $e->getMessage());
	}

	return $mail_sent;
}


// Set Header Content Type
header('Content-Type: application/json');

// Set Correct Header Http Status
if($response['status']==='success')
	http_response_code(200);
else
	http_response_code(404);

// return json
echo json_encode($response);