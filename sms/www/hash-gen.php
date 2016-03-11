<?php
	// generate a password hash
	// 1. modify "yourpassword" to whatever password you are going to use (keep the double quotes)
	// 2. run this file (either put into public_html or www and access via browser, or run in a CLI) and copy the output, this will go into the MD5 hash section of your PHP script
	// 3. delete the file from the server when you are done
	// 4. taken from http://stackoverflow.com/questions/8043699/how-to-create-a-simple-password-form-script-with-redirection-a-tiny-bit-of-s
	echo md5('blah@#$'.sha1('3NhNj8&'."yourpassword"));
?>