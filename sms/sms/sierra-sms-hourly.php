<?php
	/* Sierra SMS Script - Reminder for Hourly Items - March 2016 rev.
	* R.C. Miessler, Systems Librarian, Gettysburg College
	* CC-BY: This work is licensed under the Creative Commons Attribution 4.0 International License. To view a copy of this license, visit http://creativecommons.org/licenses/by/4.0/.
	*/

	// this script runs as a cron job 24/7, every 15 minutes
	// if your server host doesn't have a cPanel or other interface to add a cron job, then you will need to update your crontab
	// we run 3 cron jobs for this script:
	// */15	0,1,2,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23	*	*	* /path/to/sms/sierra-sms-hourly.php
	// 0,15,30,44	3	*	*	* /path/to/sms/sierra-sms-hourly.php
	// 1,15,30,45	4	*	*	* /path/to/sms/sierra-sms-hourly.php
	// we have to do some weird stuff because non-hourly items are due at 0400 hours and we don't want to send a notice for those, so we run it at 0344 and 0401, hence the need for 3 cron jobs
	// if we didn't have to do this, it could be 1 cron job and look like: */15 * * * * /path/to/sms/sierra-sms-hourly.php
	
	/* Requirements:
	* PostgreSQL PHP Extension - http://php.net/manual/en/book.pgsql.php - must be on your server
	* Twilio account and phone number - https://www.twilio.com/
	* Twilio PHP Library - https://www.twilio.com/docs/php/install
	* Sierra SQL access - Contact III
	*/
	
	/*  Log file variables */
	date_default_timezone_set('America/New_York'); // sets the time zone, see http://php.net/manual/en/timezones.php for supported zones
	$messageCount = 0; // tracks number of messages sent
	$activityLog = date('m-d-Y H:i:s')." - ".$messageCount." notices sent"; // logs how many messages sent and when
	$logFile = 'smslog.txt'; // name of our log file, lives in same directory as this PHP file

	/* Twilio message variables */
	$fromNumber = '555-555-1234'; // your Twilio number
	$messageBody = "XXX LIBRARY NOTICE: Your item is due within 15 minutes. Please return it to the circulation desk. Barcode: "; // message text
	
	/* Load credentials from other files - these files should not be stored in /www/, /public_html/, or any other web-accessible folder */
	require_once 'PATH/TO/sierra-auth.php'; // loads the Sierra credentials into our $dbHost/Name/Port/User/Pass/SSL variables below
	require_once 'PATH/TO/twilio-auth.php'; // loads our Twilio credentials into the $twilioSID and $twilioToken variables below
	
	/* You can also store your Sierra credentials in this file ... uncomment out each line and edit with your information*/
	// $dbHost = "DB.DOMAIN.TLD"; // Sierra DB Hostname
	// $dbName = "XXX"; // Sierra DB Name
	// $dbPort = "####"; // Sierra DB Port
	// $dbUser = "USERNAME"; // Sierra DB Username - needs to have "Sierra SQL Access" application, recommend dedicated login with no other perms
	// $dbPass = "PASSWORD"; // Password for $dbUser, it's a secret to everyone ...
	// $dbSSL = "sslmode=require"; // Require SSL
	
	/* Connect to the database with above variables */
	$conn = pg_connect("host=$dbHost port=$dbPort dbname=$dbName user=$dbUser password=$dbPass sslmode=$dbSSL")
	//$conn = pg_connect("host=DB.DOMAIN.TLD port=#### dbname=XXX user=USERNAME password=PASSWORD sslmode=require") - uncomment this line if not using an external file to load your Sierra credentials
		or die('Could not connect. Server says:' . pg_last_error()); // if for some reason it doesn't connect it will throw an error, really only useful if you're running the script manually
	
	/* ***Postgres Query***
	* Adapt as needed. This pulls the note field from the patron record and the barcode from the item record.
	* Only items with a due date between the current time and +15 minutes are pulled.
	* Local workflow requires pcode2 = "y" to opt in for text messages and the mobile number to live in a patron note field (x). This can be modified for your workflow and how you use fields in patron records.
	* ^\+[1][2-9]\d{9} = regex for 'field must start with +1, next digit must be 2-9, and the last 9 digits can only be 0-9', again local workflow dictates that phone numbers in the note field be formatted like +12125551234. less data manipulation required as Twilio accepts this as is, but fails to account for human error when inputting the data.
	* CHARLENGTH = 12 to only allow for 12 characters total in the note field total, again, paranoia to prevent bad returns
	* This could probably be cleaner with some JOINS
	*/
	$result = pg_query($conn,
		"SELECT 
			varfield.field_content as mobile, 
			item_record_property.barcode as barcode
		FROM 
			sierra_view.varfield, 
			sierra_view.item_record_property, 
			sierra_view.checkout, 
			sierra_view.patron_record
		WHERE 
			varfield.record_id = patron_record.record_id AND
			checkout.item_record_id = item_record_property.item_record_id AND
			patron_record.record_id = checkout.patron_record_id AND
			varfield_type_code = 'x' AND
			patron_record.pcode2 = 'y' AND
			varfield.field_content ~ '^\+[1][2-9]\d{9}' AND
			CHAR_LENGTH(varfield.field_content) = 12 AND
			checkout.due_gmt BETWEEN current_timestamp AND current_timestamp + (900 * interval '1 second');"
	);

	/* check to see if there are results; if no results, then call the writeLog() function to write to the log file and close the connection */
	if (pg_num_rows($result)==0) {
		writeLog();
	}
	
	/* Put the results of the query into an array and send your messages */
	while ($row = pg_fetch_assoc($result)) {
	
		/* Code from Twilio - https://www.twilio.com/docs/quickstart/php/sms/sending-via-rest */
		require_once 'PATH/TO/twilio-php/Services/Twilio.php'; // Calls the Twilio PHP helper library ... don't move this outside of the while loop
		
		/* Set the AccountSid and AuthToken from www.twilio.com/user/account. You can store your Twilio credentials in this file as well, uncomment out the next 2 lines and use your Twilio SID and Token values */
		// $AccountSid = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";
		// $AuthToken = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"; // it's a bad idea for this to be publicly visible, so don't let this file live in www, public_html, ftp, etc.

		$AccountSid = $twilioSID;
		$AuthToken = $twilioToken; // treat this as a password!
	 
		$client = new Services_Twilio($AccountSid, $AuthToken); // Instantiate a new Twilio Rest Client
	 
		/* Make an array of mobile numbers matched on the barcode, $people is Twilio's variable so don't change this */
		$people = array(
			$row['mobile'] => $row['barcode'],
			);
	 
		/* Loops for each iteration */
		foreach ($people as $row['mobile'] => $row['barcode']) {
			$sms = $client->account->messages->sendMessage(
				$fromNumber, // from number (your Twilio number)
				$row['mobile'], // to number (from SQL query)
				$messageBody.$row['barcode'] // message body, writes item barcode (from SQL query) to the end of the message
			); 
			// echo "Sent message to ".$row['mobile']; // Display a confirmation message on the screen, uncomment for testing purposes and running manually
			$sms->redact(); // deletes message body in Twilio logs for privacy
			$messageCount++; // +1 to the message count for the log file
		}
	}

	/* S-U-C-C-E-E-S, call the writeLog() function to write to the log file and close the connection */
	writeLog();

	/* Functions */
	/* writeLog() - writes the number of messages sent to the log file with a timestamp and closes the connection */
	function writeLog() {
		file_put_contents($logFile, date('m-d-Y H:i:s')." - ".$messageCount." notices sent".PHP_EOL, FILE_APPEND | LOCK_EX); // opens the log file, writes "x notices sent" with a timestamp
		pg_close($conn); // closes the connection to the database
		exit; // good bye!
	}
?>