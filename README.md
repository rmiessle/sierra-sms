# SMS Notices for Sierra (III)

As of 6/18/19, Gettysburg College is no longer using Sierra. This repository will no longer be updated.

The following scripts are designed to provide text (SMS) notifications to Sierra patrons by way of the Twilio service. The PHP scripts run a SQL query on the Sierra database server and sends the data to Twilio, which handles the sending of text messages.

## Requirements

You will need a server to execute the PHP scripts and run the CRON jobs. If you don't have access to a server, I recommend [Reclaim Hosting](http://reclaimhosting.com); ask them for a PostgreSQL-enabled server and have them whitelist the IP of your Sierra database server. 

In addition to a server, you will need a [Twilio](https://www.twilio.com) account to send and receive text messages, and the [Twilio PHP Helper Library](https://github.com/twilio/twilio-php) on your server. In addition, you will need to have access to the [SierraDNA](http://techdocs.iii.com/sierradna/); contact III if you need access. It is recommended that you create a single-purpose account in Sierra with only the Sierra SQL Access application.

## Recommendations

Do not place these scripts in the public_html, www, or otherwise Internet-accessible directories of your server, as your Sierra username and password will be publically viewable, as will your Twilio SID and token. If you are more tech-savvy than I am, you can set up encryption on these variables

## Scripts (/sms/)

### sierra-sms-hourly.php

We run this script every 15 minutes, checking to see if items are due in the next 15 minutes. If they are, a text notifcation will be sent advising them to return the item, as well as providing the barcode of the item due.

#### CRON job

\*/15	0,1,2,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23	*	*	* /path/to/sms/sierra-sms-hourly.php

0,15,30,44	3	*	*	* /path/to/sms/sierra-sms-hourly.php

1,15,30,45	4	*	*	* /path/to/sms/sierra-sms-hourly.php

It looks kind of weird for 0300 and 0400 because non-hourly items are due at 0400, so we don't want to send a notice for those. If we didn't have this issue, we could have one job: */15 * * * * /path/to/sms/sierra-sms-hourly.php

### sierra-sms-daily-pm.php

We run this script at 2000 once a day, and it returns the number of items due on the next day, sending a text advsing they have that many items due.

#### CRON job

\* 0	20 		*	*	* /path/to/sms/sierra-sms-hourly.php

#### smslog.txt 

This is just the log file that the scripts write to each time they run.

## Authorizations (/auth/)

### sierra-auth.php

A file to store your Sierra SQL authorization variables. This is optional, you can just define the variables in the hourly and daily scripts.

### twilio-auth.php

A file to store your Twilio SID and token variables. Again, this is optional and you can just define the variables in the main scripts.

## Web files (/www/)

### sms-confirm.html, fail.html, confirm.html

These are the pages we use to send a confirmation text to new users. It calls sms-confirmation.php. If the password entry is successful, the confirm.html file is called, otherwise, it goes to fail.html.

### sms-confirmation.php

From sms-confirm.html, this salls the Twilio PHP helper library to send a confirmation text message, and verifies the password for the form is correct.

### hash-gen.php

This is required to generate a password hash for sms-confirmation.php. Don't keep this in public_html or www, just run it once from a browser, then delete it from the server (or move to a non-public folder).



