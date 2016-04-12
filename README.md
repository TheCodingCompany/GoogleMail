# Google Mail Client

Google Mail class for PHP5.6+ to download all attachments. Easy extendable to download complete emails.

Made available by The Coding Company

https://thecodingcompany.se

Build by:  Victor Angelier <vangelier \u0040 hotmail.com>

#Install/Composer

Easy:  composer require thecodingcompany/googlemail

#Example
```
chmod 0777 public/data

require_once("GoogleMail.php");

$mail = new GoogleMail("my@gmail.com", "Very$ecretPassword");
$mail->read_mailbox();

```
