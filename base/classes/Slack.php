<?php
	
class Slack {
	
	public function __construct($username, $token, $bot = "Bot") {
		
		$this->username = $username;
		$this->token = $token;
		$this->bot = $bot;
		
	}
		
	public function send($message, $room = "general", $icon = ":grey_exclamation:") {
		
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
	}
	
}