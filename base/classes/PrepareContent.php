<?php

class PrepareContent {

	private static $dateformat = 'l, F jS, Y';
	private static $timeformat = 'g:i a ';
	private static $timeoffset = 8;
	public static $count = -1;

	public function __construct() {

	}

	public static function get($section) {

		$database = new Database();

		$array = array('content');

		return $array;

	}

	public static function assignContent($content, $extraVars = array()) {
		
		global $hashids;
	
		foreach($content as $k => $itemObj) {

			$content[$k] = $itemObj;
			$content[$k]->image = ($itemObj->image) ? 'http://uploads.heritage-app.com.s3.amazonaws.com/static/retina/'.$itemObj->image : '';
			$content[$k]->excerpt = self::trim_excerpt($itemObj->descr, 200);
			$content[$k]->unique = uniqid();
			$content[$k]->hash = $hashids->encrypt($itemObj->id);
			$content[$k]->apikey = md5($itemObj->dirtitle);
			$content[$k]->yoursite = YOURSITE;
			$content[$k]->events = self::getEventsItems($extraVars);
			
		}

		return $content;
	}
	
	public static function getEventsItems($content) {
		
		global $hashids;
			
		foreach($content as $k => $itemObj) {
			
			if(date('H:i', $itemObj->start) == '00:00' && date('H:i', $itemObj->end) == '00:00') {
				if(date('l d M Y', $itemObj->start) == date('l d M Y', $itemObj->end)) {
					$dates = date('l d M Y', $itemObj->start);
				} else {
					$dates = date('l d M Y', $itemObj->start) . ' to ' . date('l d M Y', $itemObj->end);
				}
			} else {
				if(date('l d M Y', $itemObj->start) == date('l d M Y', $itemObj->end)) {
					$dates = date('l d M Y', $itemObj->start) . ' ' . date('H:i', $itemObj->start) . ' - ' . date('H:i', $itemObj->end);
				} else {
					$dates = date('l d M Y', $itemObj->start) . ' to ' . date('l d M Y', $itemObj->end) . ' ' . date('H:i', $itemObj->start) . ' - ' . date('H:i', $itemObj->end);
				}
			}
			
			$content[$k]->hash = $hashids->encrypt($itemObj->id);
			$content[$k]->unique = uniqid();
			$content[$k]->dates = $dates;
			
		}
		
		return $content;
				
	}
	
	public static function getEventsItemsFeed($content, $format = 'rss') {
		
		global $hashids;
			
		if($format == 'rss') {
			foreach($content as $k => $itemObj) {
				
				$content[$k]->date = date('l d M Y', $itemObj->start);
				$content[$k]->time = date('H:i', $itemObj->start);
				$content[$k]->enddate = date('l d M Y', $itemObj->end);
				$content[$k]->endtime = date('H:i', $itemObj->end);
				$content[$k]->title = htmlspecialchars($itemObj->title);
				$content[$k]->description = htmlspecialchars($itemObj->description);
				
			}
			
		} elseif('json') {
			
			foreach($content as $k => $itemObj) {
				
				$content[$k]->date = date('l d M Y', $itemObj->start);
				$content[$k]->time = date('H:i', $itemObj->start);
				$content[$k]->enddate = date('l d M Y', $itemObj->end);
				$content[$k]->endtime = date('H:i', $itemObj->end);
				$content[$k]->title = html_entity_decode($itemObj->title);
				$content[$k]->description = html_entity_decode($itemObj->description);
				
			}
			
		}
		
		return $content;
		
	}
	
	public static function getEventsItem($content) {
		
		global $hashids;
		
			
			if(date('H:i', $content->start) == '00:00' && date('H:i', $content->end) == '00:00') {
				if(date('l d M Y', $content->start) == date('l d M Y', $content->end)) {
					$dates = date('l d M Y', $content->start);
				} else {
					$dates = date('l d M Y', $content->start) . ' to ' . date('l d M Y', $content->end);
				}
			} else {
				if(date('l d M Y', $content->start) == date('l d M Y', $content->end)) {
					$dates = date('l d M Y', $content->start) . ' ' . date('H:i', $content->start) . ' - ' . date('H:i', $content->end);
				} else {
					$dates = date('l d M Y', $content->start) . ' to ' . date('l d M Y', $content->end) . ' ' . date('H:i', $content->start) . ' - ' . date('H:i', $content->end);
				}
			}
			
			$content->hash = $hashids->encrypt($content->id);
			$content->unique = uniqid();
			$content->dates = $dates;
			$content->allday = (date('l d M Y', $content->start) == date('l d M Y', $content->end)) ? 'checked="checked" ' : false;
			
		
		return $content;
				
	}
	
	public static function getFeedsItems($user_id, $limit=null, $max=null) {

		$database = new Database();
		$humanise = new Humanise();
		$database->query("SELECT a.*, a.id AS pid, c.*, d.twitter_sentiment, d.twitter_publish_time FROM feeds_items a
		LEFT JOIN subscription b ON a.feed = b.feed
		LEFT JOIN feeds c ON b.feed = c.id
		LEFT JOIN twitter d ON a.id = d.item_id AND d.user_id = :user_id
		WHERE b.user_id = :user_id AND a.item_date > :item_date
		ORDER BY item_date DESC
		LIMIT :limit,:max");
		$database->bind(":user_id", $user_id);
		$database->bind(":limit", $limit);
		$database->bind(":max", $max);
		$database->bind(":item_date", strtotime('-2 weeks'));

		$database->execute();

		$content = $database->resultset();

		foreach($content as $k=>$itemObj) {
			$content[$k]->category = $itemObj->feed_title;
			$content[$k]->title = $itemObj->item_title;
			$content[$k]->descr = (!empty($itemObj->item_content)) ? $itemObj->item_content : $itemObj->item_title;
			$content[$k]->excerpt = self::trim_excerpt($itemObj->item_content, 200);
			$content[$k]->permalink = $itemObj->item_url;
			$content[$k]->date = gmdate(self::$dateformat, $itemObj->item_date + self::$timeoffset);
			$content[$k]->time = gmdate(self::$timeformat, $itemObj->item_date + self::$timeoffset);
			$content[$k]->shortcode = ' ' . YOURSITE . self::getShortCode($itemObj->item_url);
			$shortLength = strlen($content[$k]->shortcode);
			$titleLength = strlen($content[$k]->title);
			$descrLength = strlen($content[$k]->descr);
			$content[$k]->post = ($content[$k]->descr != '') ? trim(strip_tags($content[$k]->descr)) . $content[$k]->shortcode : trim($content[$k]->title) . $content[$k]->shortcode;
			$content[$k]->tweet_descr = ($descrLength + $shortLength <= 140) ? strip_tags($content[$k]->descr) . $content[$k]->shortcode : self::trim_excerpt(strip_tags($content[$k]->descr), 140 - $shortLength, true) . $content[$k]->shortcode;
			$content[$k]->tweet_title = ($titleLength + $shortLength <= 140) ? strip_tags($content[$k]->title) . $content[$k]->shortcode : self::trim_excerpt(strip_tags($content[$k]->title), 140 - $shortLength, true) . $content[$k]->shortcode;
			switch($itemObj->twitter_sentiment) {
				case 'neg':
					$content[$k]->twitter_sentiment = 'Negative';
					$content[$k]->twitter_sentiment_label = 'label-danger';
				break;
				case 'neu':
					$content[$k]->twitter_sentiment = 'Neutral';
					$content[$k]->twitter_sentiment_label = 'label-default';
				break;
				case 'pos':
					$content[$k]->twitter_sentiment = 'Positive';
					$content[$k]->twitter_sentiment_label = 'label-success';
				break;
			}
			$content[$k]->twitter_queue_time = ($itemObj->twitter_publish_time) ? gmdate(self::$dateformat . ' ' . self::$timeformat, $itemObj->twitter_publish_time + self::$timeoffset) : 'Not queued';

		}

		return $content;

	}

	public static function getFeeds($user_id, $limit=null, $max=null) {

		$database = new Database();
		$database->query("SELECT a.* FROM feeds a LEFT JOIN subscription b ON a.id = b.feed WHERE b.user_id = :user_id LIMIT :limit,:max");
		$database->bind(":user_id", $user_id);
		$database->bind(":limit", $limit);
		$database->bind(":max", $max);

		$database->execute();

		$content = $database->resultset();

		foreach($content as $k=>$itemObj) {
			$content[$k]->category = $itemObj->feed_title;
			$content[$k]->permalink = $itemObj->feed_url;
			$content[$k]->date = gmdate(self::$dateformat, $itemObj->feed_date + self::$timeoffset);
			$content[$k]->time = gmdate(self::$timeformat, $itemObj->feed_date + self::$timeoffset);
		}

		return $content;

	}

	public static function getShortCode($url) {

		$database = new Database();
		$database->query("SELECT * FROM yourls_url WHERE url = :url");
		$database->bind(":url", $url);
		$database->execute();

		$found = $database->single();

		if($found) {
			return $found->keyword;
		} else {
			$found = yourls_add_new_link(yourls_sanitize_url($url), "", "");
			return $found['url']['keyword'];
		}
	}

	public static function getCount($user_id) {

		$database = new Database();
		$database->query("SELECT a.* FROM feeds_items a LEFT JOIN subscription b ON a.feed = b.feed AND a.item_date > :item_date WHERE b.user_id = :user_id");
		$database->bind(":user_id", $user_id);
		$database->bind(":item_date", strtotime('-2 weeks'));
		$database->execute();

		return $database->rowCount();

	}

	public static function trim_excerpt($text, $len, $strip = false) {
		$excerpt_length = $len;
		$text = trim($text);
		$chars = strlen($text);
		$words = explode(' ', $text, $excerpt_length + 1);
		if($strip == true) {
			if(strlen($text) > $len) {
				$len = $len -1;
				$text = wordwrap($text, $len);
				$text = substr($text, 0, strpos($text, "\n")) . '&hellip;';
			}
		} elseif(count($words) > $excerpt_length) {
			array_pop($words);
			array_push($words, '&hellip;</p>');
			$text = implode(' ', $words);
		}
		return $text;
	}

	public static function twitterSettings($user_id, $cb, $options) {

		$token = $options->getOption('token');
		$tokenSecret = $options->getOption('tokenSecret');

		$content[0]->token = $token;
		$content[0]->token_secret = $tokenSecret;

		return $content;

	}

}