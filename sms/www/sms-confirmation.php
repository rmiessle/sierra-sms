<?php
	// Twilio credentials for API
	require_once 'path/to/twilio-auth.php';

	// Twilio PHP Helper
	require_once 'path/to/twilio-php/Services/Twilio.php';
	
	// Variables 
	$AccountSid = $twilioSID; // from twilio-auth.php
	$AuthToken = $twilioToken; // from twilio-auth.php
 	$smsNumber = $_POST['mobile']; // from form
 	$smsOption = $_POST['option']; // from form

 	// Verify the password and give information to Twilio
	if(md5('blah@#$'.sha1('3NhNj8&'.$_POST['password']) ) =='HASH FROM HASH-GEN.PHP' ) {  	// Password hash, this line needs to be updated if the password is changed

		// If password is verified, set the message variable based on what option was selected on the form
		if ($smsOption == 'new') {
			$smsMessage = "MUSSELMAN LIBRARY NOTICE: You are now signed up for text notifications. For assistance, call 717-337-7024 or email ask@gettysburg.edu";
		} elseif ($smsOption == 'update') {
			$smsMessage = "MUSSELMAN LIBRARY NOTICE: Your mobile number for text notifications has been updated. For assistance, call 717-337-7024 or email ask@gettysburg.edu";
		} elseif ($smsOption == 'stop') {
			$smsMessage = "MUSSELMAN LIBRARY NOTICE: You will no longer receive text notifications. For assistance, call 717-337-7024 or email ask@gettysburg.edu";
		} else {
			$errorMessage .= "Select the confirmation message type"; // error catcher, will probably never be called
		}

		$client = new Services_Twilio($AccountSid, $AuthToken); // Authenticate Twilio service and create instance
		 
		 	// Sends the appropriate message to the mobile number
			$sms = $client->account->messages->sendMessage(
				"555-555-1234", // Twilio SMS number
				$smsNumber, // from form
				$smsMessage // from form
			);
			$sms->redact(); // redacts message from Twilio logs
		header('Location: path/to/confirm.html'); // if successful, go to confirmation page
		exit();
	}
	
	// If bad password, then redirect to bad password page
	else {
		header('Location: path/to/fail.html'); // bad password page
		exit();
	}
?>