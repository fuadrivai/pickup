<?php

$data = [
    'api_key' => '3155ce33af52899fce869a3753d76b6280aedf7b',
    'sender'  => '6281383151326',
    'number'  => '6285280497754',
    'content' => 'hello',
    'keyword_auto_reply' => 'Write your keyword in auto reply or auto reply button',
    'text_button' => 'Write text button for keyword auto reply above',
    'keyword_auto_replytwo' => 'Write your keyword in auto reply or auto reply button',
    'text_button_two' => 'Write text button for keyword auto reply two aboe'
];

$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://waysender-v2.ridped.com/apiv2/send-button.php",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => json_encode($data))
);

$response = curl_exec($curl);

curl_close($curl);
echo $response;