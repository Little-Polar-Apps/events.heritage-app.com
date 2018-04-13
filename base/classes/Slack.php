<?php

class Slack {

	public function __construct($username, $token, $bot = "Bot") {

		$this->username = $username;
		$this->token = $token;
		$this->bot = $bot;

	}

	public function send($message, $room = "general", $icon = ":grey_exclamation:") {
/*

	    $data = "payload=" . json_encode(array(
	            "channel"       =>  "#{$room}",
	            "username"		=> 	$this->bot,
	            "text"          =>  $message,
	            "icon_emoji"    =>  $icon
	        ));

	    $ch = curl_init("https://$this->username.slack.com/services/hooks/incoming-webhook?token=$this->token");
		    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		    $result = curl_exec($ch);
		    curl_close($ch);

	    return $result;
*/


	      // Create a constant to store your Slack URL
        define('SLACK_WEBHOOK', 'https://hooks.slack.com/services/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');
        // Make your message
        $message = array('payload' => json_encode(array('text' => json_encode($message))));
        // Use curl to send your message
        $c = curl_init(SLACK_WEBHOOK);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_POSTFIELDS, $message);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_exec($c);
        curl_close($c);

        return;

	}

}