<?php
require_once('config.php');
require_once('db.php');

// Instantiate database.
$database = new Database();

function array2xml($array, $node_name="root") {
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;
    $root = $dom->createElement($node_name);
    $dom->appendChild($root);

    $array2xml = function ($node, $array) use ($dom, &$array2xml) {
        foreach($array as $key => $value){
            if ( is_array($value) ) {
                $n = $dom->createElement($key);
                $node->appendChild($n);
                $array2xml($n, $value);
            }else{
                $attr = $dom->createAttribute($key);
                $attr->value = $value;
                $node->appendChild($attr);
            }
        }
    };

    $array2xml($root, $array);

    return $dom->saveXML();
}

$database->query("SELECT * FROM events_event WHERE start > :plusday AND posted != 1 ORDER BY start");
$database->bind(':plusday', time());
$database->execute();

$api = $database->resultset();

if(defined("FORMAT") && FORMAT == 'rss') {
	header("Content-Type: application/xml; charset=UTF-8");
	echo '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
	echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
		<channel>
		<atom:link href="rss_feed" rel="self" type="application/rss+xml" />
		<title>'.$rows['title'].'</title>
		<link>http://heritage-app.com/i/p/'.$rows['dirtitle'].'</link>
		<description>'.strip_tags($rows['descr']).'</description>
		<language>en-us</language>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>';
		foreach($api as $entry) {
		echo '<event>
			<title>'.htmlspecialchars($entry['title']).'</title>
			<description>'.htmlspecialchars($entry['description']).'</description>
			<link>'.$entry['url'].'</link>
			<date>'.date('l d M Y', $entry['start']).'</date>
			<time>'.date('H:i', $entry['start']).'</time>
			<enddate>'.date('l d M Y', $entry['end']).'</enddate>
			<endtime>'.date('H:i', $entry['end']).'</endtime>
		</event>';
		}
		echo '</channel>
	</rss>';
	exit();

} elseif(defined("FORMAT") && FORMAT == 'json') {
	$sample = array();
	foreach($api as $entry) {
		$sample[] = array(
			'pid'=>$entry['id'],
		    'title'=>html_entity_decode($entry['title']),
		    'description'=>html_entity_decode($entry['description']),
		    'url'=>$entry['url'],
		    'start'=>$entry['start'],
		    'end'=>$entry['end']
		);
	}
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($sample);
	exit();

} else {
	foreach($api as $entry) {
		if(date('H:i', $entry['start']) == '00:00' && date('H:i', $entry['end']) == '00:00') {
			if(date('l d M Y', $entry['start']) == date('l d M Y', $entry['end'])) {
				$dates = date('l d M Y', $entry['start']);
			} else {
				$dates = date('l d M Y', $entry['start']) . ' to ' . date('l d M Y', $entry['end']);
			}
		} else {
			if(date('l d M Y', $entry['start']) == date('l d M Y', $entry['end'])) {
					$dates = date('l d M Y', $entry['start']) . ' ' . date('H:i', $entry['start']) . ' - ' . date('H:i', $entry['end']);
			} else {
					$dates = date('l d M Y', $entry['start']) . ' to ' . date('l d M Y', $entry['end']) . ' ' . date('H:i', $entry['start']) . ' - ' . date('H:i', $entry['end']);
			}

		}
		echo '<a href="'.$entry['url'] .'" class="list-group-item" data-ident="'. $entry['id'] .'">';
			?><h4 class="list-group-item-heading">
				<small><?= $dates ?></small><br>
				<?= $entry['title'] ?>
			</h4>
			<p class="list-group-item-text"><?= $entry['description'] ?></p>
			<hr>
		</a>
		<?php
	}
}

