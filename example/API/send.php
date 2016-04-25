<?php

define('MY_KEY', 'GOOGLE_KEY');
define('TIME_TO_LIVE', 300);

$subscribers = json_decode(file_get_contents('subscribers.json'), true);

foreach ($subscribers as $browser => $subscribers_list) {
  foreach ($subscribers_list as $subscriber_id) {
    $result = send_push_message($browser, $subscriber_id);
    print_r($result);
  }
}

function send_push_message($browser, $subscriber_id) {
  $ch = curl_init();
  switch($browser) {
    
    case 'chrome':
      curl_setopt($ch, CURLOPT_URL, 'https://gcm-http.googleapis.com/gcm/send');
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: key='.MY_KEY, 'Content-Type: application/json']);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, 
        json_encode([
          'registration_ids' => [$subscriber_id],
          'data' => ['message' => 'send'],
          'time_to_live' => TIME_TO_LIVE,
          'collapse_key' => 'test'
        ])
      );
      break;

    case 'firefox':
      curl_setopt($ch, CURLOPT_URL, 'https://updates.push.services.mozilla.com/push/v5/'.$subscriber_id);
      curl_setopt($ch, CURLOPT_PUT, TRUE);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_HTTPHEADER, ['TTL: '.TIME_TO_LIVE]);
      break;

  }
  $result = curl_exec($ch);
  curl_close($ch);
  return $result;
}