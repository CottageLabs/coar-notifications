<?php
// create curl resource
$ch = curl_init();

// set url
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 5); //timeout in seconds
curl_setopt($ch, CURLOPT_URL, "http://localhost:81/post");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/ld+json'));
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode("{'test':'test'}"));

// Send request.
$result = curl_exec($ch);
curl_close($ch);

print_r($result);