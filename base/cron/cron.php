<?php
header('Content-Type: text/html; charset=utf-8');
require_once(realpath(dirname(__FILE__).'/../config.php'));
require_once(realpath(dirname(__FILE__).'/../db.php'));
require_once(realpath(dirname(__FILE__).'/../twitteroauth/twitteroauth.php'));

function slack($message, $room = "general", $icon = ":grey_exclamation:") {

    $room = ($room) ? $room : "general";
    $data = "payload=" . json_encode(array(
            "channel"       =>  "#{$room}",
            "username"		=> 	"HeritageEventsBot",
            "text"          =>  $message,
            "icon_emoji"    =>  $icon
        ));

    $ch = curl_init("https://heritage.slack.com/services/hooks/incoming-webhook?token=HMt7775INxoEQmadRVhmTfux");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;

}

date_default_timezone_set('UTC');

$APPLICATION_ID = "ByVcWVLEb8aME7OZKACz55OKy5RG9FbG7qUsD8Cz";
$REST_API_KEY = "bTSlWMrxbtZepC4U2iVyvFdCMW1UERUmY0ErcGaa";

if(!$APPLICATION_ID || !$REST_API_KEY) {
	slack('You need to set your API keys.', 'general', ':heritage:');
	return;
}

$database = new Database();

$database->query("SELECT events_event.*, i_items.title AS name FROM events_event LEFT JOIN i_items ON i_items.id = events_event.pid WHERE start > :plusday AND start < :plustwoday AND posted != 1 ORDER BY start");
$database->bind(':plusday', strtotime("midnight", time()));
$database->bind(':plustwoday', strtotime('+2 days', strtotime("23:59", time())));
$database->execute();

$api = $database->resultset();

if (!defined('CONSUMER_KEY')) {
    define('CONSUMER_KEY', 'ONPQ0txSJQ6SWJBf91new4wjB');
}

if (!defined('CONSUMER_SECRET')) {
    define('CONSUMER_SECRET', '374QWGia2g5OSGHi20Dznu0Zom0muTKwjx98Nyrb8QtRFnC1b4');
}

if (!defined('OAUTH_TOKEN')) {
    define('OAUTH_TOKEN', '226727773-VNeLLE7VkOIWsAiQH6Lt4QdQmj2Bg2hgiwue1AGR');
}

if (!defined('OAUTH_SECRET')) {
    define('OAUTH_SECRET', 'Ce8ZXFyWyo4gIxHbc31xE6pwXUiBzES04O47zwXt7GFDR');
}

$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_TOKEN, OAUTH_SECRET);
//$content = $connection->get('account/verify_credentials');

if($database->rowCount() > 0) {
	try {
		$time = 0;
		foreach($api as $row) {
			if(date('H:i', $row['start']) == '00:00' && date('H:i', $row['end']) == '00:00') {
				if(date('l d M Y', $row['start']) == date('l d M Y', $row['end'])) {
					$alert = date('l d M Y', $row['start']);
				} else {
					$alert = date('l d M Y', $row['start']) . ' to ' . date('l d M Y', $row['end']);
				}
			} else {
				if(date('l d M Y', $row['start']) == date('l d M Y', $row['end'])) {
					$alert = date('l d M Y', $row['start']) . ' ' . date('H:i', $row['start']) . ' - ' . date('H:i', $row['end']);
				} else {
					$alert = date('l d M Y', $row['start']) . ' to ' . date('l d M Y', $row['end']) . ' ' . date('H:i', $row['start']) . ' - ' . date('H:i', $row['end']);
				}

			}

			$pushTime = time() + $time;

// 			$url = 'https://api.parse.com/1/push';
// 			$data = array(
// //			    'channel' => ['male', 'female', 'no-login'],
// 				'where' => [
// 					'channels' => [
// 						'$in' => ['male', 'female', 'no-login']
// 					],
// 					'deviceType' => 'ios'
// 				],
// //			    'type' => 'ios',
// 			    "push_time" => gmdate("Y-m-d\TH:i:s\Z", $pushTime),
// 			    'data' => array(
// 			        'alert' => $row['title'] . ' @ ' . $row['name'] . ' - '. $alert,
// 			        'hatype' => 'property',
// 			        'id' => (int) $row['pid'],
// 			        'sound' => 'push.caf',
// 			    ),
// 			);
			$_data = json_encode($data);
			$headers = array(
			    'X-Parse-Application-Id: ' . $APPLICATION_ID,
			    'X-Parse-REST-API-Key: ' . $REST_API_KEY,
			    'Content-Type: application/json',
			    'Content-Length: ' . strlen($_data),
			);

			$url = parse_url($row->url, PHP_URL_HOST);

    		$url = str_replace("www.", "", $url);

    		$url = "https://whosername.com/api/M9hy9cMKiDMcPSz4uwzswL/".trim($url)."/json";

    		$cURL = curl_init();

    		curl_setopt($cURL, CURLOPT_URL, $url);
    		curl_setopt($cURL, CURLOPT_HTTPGET, true);
    		curl_setopt($cURL, CURLOPT_RETURNTRANSFER,1);

    		curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
    		    'Content-Type: application/json',
    		    'Accept: application/json'
    		));

    		$whosername = curl_exec($cURL);

    		curl_close($cURL);

    		$twitterUsername = json_decode($whosername);

    		$username = ($twitterUsername->properties->twitter) ? ' via @'.$twitterUsername->properties->twitter : '';

			$connection->post('statuses/update', array('status' => $row['title'] . ' @ ' . $row['name'] . ' - '. $alert . $username));
			slack($row['title'] . ' @ ' . $row['name'] . ' - '. $alert, 'general', ':heritage:');

			$time = $time + 15*60;

			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $_data);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
			curl_exec($curl);

			$database->query('UPDATE events_event SET posted = :posted WHERE id = :id');
			$database->bind(':posted', 1);
			$database->bind(':id', $row['id']);
			$database->execute();

			slack('Posted. ' . $row['id'], 'general', ':heritage:');
		}

	} catch(Exception $e) {
		echo $e;
	}
} else {
	slack('No upcoming events.', 'general', ':heritage:');
	$database->query("SELECT * FROM events_event WHERE start > :plusday AND start < :endday AND posted != 1 ORDER BY start LIMIT 20");
	$database->bind(':plusday', time());
	$database->bind(':endday', strtotime('+ 2 days'));
	$database->execute();

	$api = $database->resultset();

	$time = 0;
	foreach($api as $row) {
		if(date('H:i', $row['start']) == '00:00' && date('H:i', $row['end']) == '00:00') {
			if(date('l d M Y', $row['start']) == date('l d M Y', $row['end'])) {
				$alert = date('l d M Y', $row['start']);
			} else {
				$alert = date('l d M Y', $row['start']) . ' to ' . date('l d M Y', $row['end']);
			}
		} else {
			if(date('l d M Y', $row['start']) == date('l d M Y', $row['end'])) {
				$alert = date('l d M Y', $row['start']) . ' ' . date('H:i', $row['start']) . ' - ' . date('H:i', $row['end']);
			} else {
				$alert = date('l d M Y', $row['start']) . ' to ' . date('l d M Y', $row['end']) . ' ' . date('H:i', $row['start']) . ' - ' . date('H:i', $row['end']);
			}

		}
		$pushTime = time() + $time;

		slack($row['title'] . ' - '. $alert. "\n" . "Push Time ". gmdate("Y-m-d\TH:i:s\Z", $pushTime), 'general', ':heritage:');

		$time = $time + 15*60;
	}
}
