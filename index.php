<?php

error_reporting(E_ALL);

require 'vendor/autoload.php';

Dotenv::load(__DIR__);

require 'base/config.php';
require 'base/classes/Database.php';
require 'base/classes/PHPLogin.php';
require 'base/classes/Options.php';
require 'base/classes/PrepareContent.php';
require 'base/classes/Post.php';
require 'base/classes/Slack.php';
require 'base/classes/OutputMessages.php';
include 'base/classes/pagination.php';
//	require 'base/cron/cron.php';

\Codebird\Codebird::setConsumerKey('ONPQ0txSJQ6SWJBf91new4wjB', '374QWGia2g5OSGHi20Dznu0Zom0muTKwjx98Nyrb8QtRFnC1b4');

$app        = new \Slim\Slim();
$login      = new PHPLogin();
$database   = new Database();
$post		= new Post();
$header     = new stdClass();
$footer     = new stdClass();
$content    = new stdClass();
$menu       = new stdClass();
$addExtras  = new stdClass();
$slack 		= new Slack("heritage", "HMt7775INxoEQmadRVhmTfux", "HeritageEventsBot");
$cb 		= \Codebird\Codebird::getInstance();
$hashids 	= new Hashids\Hashids('History is around us');

$cb->setToken('226727773-VNeLLE7VkOIWsAiQH6Lt4QdQmj2Bg2hgiwue1AGR', 'Ce8ZXFyWyo4gIxHbc31xE6pwXUiBzES04O47zwXt7GFDR');

$app->config(array(
	'view' => new Ets(),
    'templates.path' => 'base/templates'
));


$app->view->parserDirectory 	 = dirname(__FILE__) . '/vendor/little-polar-apps/ets';
$app->view->parserCacheDirectory = dirname(__FILE__) . '/base/cache';
$app->view->setTemplatesDirectory(dirname(__FILE__) . '/base/templates');

$addExtras->yoursite            = YOURSITE;
$addExtras->sitename            = 'Heritage Events';
$addExtras->copyrightdate       = isset($addExtras->copyrightdate) ? $addExtras->copyrightdate : date('Y');
$addExtras->logged_in           = $login->isUserLoggedIn();
$addExtras->themetemplates      = YOURSITE.'base/templates';
$addExtras->css_link            = YOURSITE.'base/css/styles.css';
$addExtras->javascript_link     = YOURSITE.'base/javascript/javascript.js';
$addExtras->images_folder       = YOURSITE.'base/images/';
$addExtras->templates_folder    = YOURSITE.'base/templates/';
$addExtras->css_folder          = YOURSITE.'base/css/';
$addExtras->javascript_folder   = YOURSITE.'base/js/';
$addExtras->webfonts_folder   	= YOURSITE.'base/webfonts/';

foreach($addExtras as $xp => $xv) {
	$header->{$xp}     = $xv;
	$footer->{$xp}     = $xv;
	$menu->{$xp}       = $xv;
	$xmlvars[$xp]      = $xv;
	$content->{$xp}    = $xv;
}

$app->view->set('database', $database);
$app->view->make_header($header);
$app->view->make_menu($header);
$app->view->make_footer($header);
$app->view->make_content($content);

$app->view->user_vars['header']['date']         = time();
$app->view->user_vars['main']['captcha']        = WORDING_REGISTRATION_CAPTCHA;
$app->view->user_vars['main']['remember_me']    = WORDING_REMEMBER_ME;
$app->view->user_vars['main']['output']         = OutputMessages::showMessage();


$app->map('/(page/:number)', function ($number=1) use ($app, $database) {

	$database->query("SELECT events_event.*, i_items.title AS name FROM events_event LEFT JOIN i_items ON i_items.id = events_event.pid WHERE events_event.start >= :plusday ORDER BY events_event.start");
	$database->bind(':plusday', time());
	$database->execute();

	$total     = $database->rowCount();
	$max       = 6;
	$maxNum    = 100;

	$nav       = new Pagination($max, $total, $maxNum, (int) $number, '');

	$database->query("SELECT events_event.*, i_items.title AS name, i_items.hrtgs FROM events_event LEFT JOIN i_items ON i_items.id = events_event.pid WHERE events_event.start >= :plusday ORDER BY events_event.start LIMIT :limit,:max");
	$database->bind(':plusday', time());
	$database->bind(':limit', $nav->start());
	$database->bind(':max', $max);
	$database->execute();

	$content = $database->resultset();

	foreach($content as $k => $v) {
		$content[$k]->page = $number;
	}

	$app->view->set('content', PrepareContent::getEventsItems($content));

	$app->view->user_vars['header']['title'] = ($number > 1) ? 'Heritage Events - Page ' . $number : 'Heritage Events';
	$app->render('home.tpl.html', array(
		'nav'     => $nav
	));

})->via('GET', 'POST');

$app->map('/(:year/:month)(/page/:number)', function ($year=2015, $month=1, $number=1) use ($app, $database) {

	$nmonth = date('m',strtotime($month));
	$nyear = date('Y',strtotime($year));

	$database->query("SELECT events_event.*, i_items.title AS name FROM events_event LEFT JOIN i_items ON i_items.id = events_event.pid WHERE MONTH(FROM_UNIXTIME(start)) = :month AND YEAR(FROM_UNIXTIME(start)) = :year ORDER BY events_event.start");
	$database->bind(':month', $nmonth);
	$database->bind(':year', $nyear);
	$database->execute();

	$total     = $database->rowCount();
	$max       = 6;
	$maxNum    = 100;

	$nav       = new Pagination($max, $total, $maxNum, (int) $number, '/'.$year.'/'.$month);

	$database->query("SELECT events_event.*, i_items.title AS name FROM events_event LEFT JOIN i_items ON i_items.id = events_event.pid WHERE MONTH(FROM_UNIXTIME(start)) = :month AND YEAR(FROM_UNIXTIME(start)) = :year ORDER BY events_event.start LIMIT :limit,:max");
	$database->bind(':month', $nmonth);
	$database->bind(':year', $nyear);
	$database->bind(':limit', $nav->start());
	$database->bind(':max', $max);
	$database->execute();

	$content = $database->resultset();

	foreach($content as $k => $v) {
		$content[$k]->page = $number;
	}

	$app->view->set('content', PrepareContent::getEventsItems($content));
	$app->view->add_loop(PrepareContent::yearPagination(), 'months');

	$app->view->user_vars['header']['title'] = ($number > 1) ? 'Heritage Events - Page ' . $number : 'Heritage Events';
	$app->render('home.tpl.html', array(
		'nav'     => $nav
	));



})->via('GET', 'POST');

$app->map('/login', function () use ($app) {

	if(isset($_POST['login'])) {

		if(!empty($login->errors)) {

			foreach($login->errors as $error) {

				OutputMessages::setMessage($error, 'danger');

			}

			if($_SESSION['user_access_level'] == 255) {

				$app->redirect($_SERVER['REQUEST_URI'], 301);

			} else {
				$app->pass();
			}

		} else {

			$app->redirect('/search', 301);
		}

	}

	$app->render('login.tpl.html');

})->via('GET', 'POST');


$app->get('/logout', function () use ($login, $app) {

    $login->doLogout();

	header("location: ". YOURSITE);
	exit;

});

$app->map('/register', function () use ($app, $login) {

	if(isset($_POST['register'])) {
	    if($login->errors) {
	        foreach($login->errors as $error) {
	        	OutputMessages::setMessage($error, 'danger');
	        }
	    }
	    if($login->messages) {
	        foreach ($login->messages as $message) {
	            OutputMessages::setMessage($message, 'success');
	        }
	    }
	}

	$app->view->user_vars['header']['title'] = 'Register';
	$app->view->user_vars['main']['registration_successful'] = (isset($_GET['verification_code']) || $login->isRegistrationSuccessful() &&
   (ALLOW_USER_REGISTRATION || (ALLOW_ADMIN_TO_REGISTER_NEW_USER && $_SESSION['user_access_level'] == 255))) ? true : null;
	$app->view->user_vars['main']['registration_verified'] = (isset($_GET['verification_code'])) ? true : null;

	$app->render('register.tpl.html');


})->via('GET', 'POST');

$app->map('/crypt/:id', function ($id) use ($app, $hashids, $login) {

	$app->view->user_vars['header']['title'] = 'Encrypt';
	$app->view->user_vars['main']['hash'] = $hashids->encrypt($id);
	$app->render('encrypt.tpl.html');

})->via('GET', 'POST');

$app->map('/search', function () use ($app, $hashids, $database, $content) {

	if($_SESSION['user_access_level'] == 255) {
		$title = $app->request()->get('search');

		if($title) {
			$database->query("SELECT * FROM i_items WHERE title LIKE CONCAT('%', :title, '%') AND twitter != '' GROUP BY twitter");
			$database->bind(":title", urldecode($title));
		} else {
			$database->query("SELECT * FROM i_items WHERE twitter != '' GROUP BY twitter");
		}

		$database->execute();

		$rows = $database->resultset();

		$app->view->user_vars['header']['title'] = 'Share';
		$app->view->set('content', PrepareContent::assignContent($rows));
		$app->render('search.tpl.html');
	} else {
		$app->pass();
	}

})->via('GET', 'POST');

$app->get('/api(/:key)(/:format)', function($key, $format) use($app, $database) {

	$key = preg_replace("-8xhKhJ18Iez", "", $key);

	if($key) {
		$database->query("SELECT * FROM i_items LEFT JOIN events_event ON i_items.id = events_event.pid WHERE MD5(dirtitle) = :dirtitle AND events_event.start > :time ORDER BY events_event.start");
		$database->bind(':time', time());
		$database->bind(':dirtitle', $key);

	} else {
		$database->query('SELECT * FROM events_event WHERE start > :time ORDER BY start');
		$database->bind(':time', strtotime("midnight", time()));
	}
	$database->execute();


	$content = $database->resultset();

	if($format === "rss") {

		$app->response->headers->set('Content-Type', 'application/rss+xml');
		$app->view->set('content', PrepareContent::getEventsItemsFeed($content, 'rss'));
		$app->render(array('rss.tpl.html'));

	} elseif($format === "json") {

		$app->response->headers->set('Content-Type', 'application/json');
		$app->view->set('content', PrepareContent::getEventsItemsFeed($content, 'json'));
		$app->render(array('json.tpl.html'));

	}

});

$app->map('/:id/:hrtgs/:dirtitle(/page/:number)(/:format)(/edit/:edit)', function($id, $hrtgs, $dirtitle, $number=1, $format=null, $edit=null) use ($app, $database, $hashids, $post, $login) {

	if($login->isUserLoggedIn()) {

		$id_decrypt = $hashids->decrypt($id);

		$database->query("SELECT * FROM i_items WHERE id = :id AND hrtgs = :hrtgs AND MD5(dirtitle) = :dirtitle");
		$database->bind(':id', $id_decrypt[0]);
		$database->bind(':hrtgs', $hrtgs);
		$database->bind(':dirtitle', $dirtitle);
		$database->execute();

		$rows = $database->resultset();

		if(!empty($_POST)) {
			if(isset($_POST[':save-event'])) {
			    $post->saveEvent($_POST, $id_decrypt);
			    $app->redirect($_SERVER['REQUEST_URI'], 301);
			} elseif(isset($_POST['deleting'])) {
				$post->deleteEvent($_POST);
			    $app->redirect($_SERVER['REQUEST_URI'], 301);
			} elseif(isset($_POST[':edit-event'])) {
			    $app->redirect($_SERVER['REQUEST_URI'] . '/edit/'.$_POST['ident'], 301);
			} elseif(isset($_POST['editing'])) {
				$post->editEvent($_POST, $id_decrypt);
				$app->redirect("/$id/$hrtgs/$dirtitle", 301);
			}
		}


		if($edit != null) {

			$ident = $hashids->decrypt($edit);

			if(!$ident) {
				$app->pass();
			}

			$database->query('SELECT * FROM events_event WHERE pid = :pid AND id = :id ORDER BY start');
			$database->bind(':pid', $id_decrypt[0]);
			$database->bind(':id', $ident[0]);
			$database->execute();

			$events = $database->single();

			if($events->pid == $id_decrypt[0]) {

				$events->parentid = $hashids->encrypt($id_decrypt[0]);
				$events->hrtgs = $hrtgs;
				$events->dirtitle = $dirtitle;

				$app->view->user_vars['header']['title'] = 'Edit';
				$app->view->set('content', PrepareContent::getEventsItem($events));
				$app->render('edit.tpl.html');
			} else {
				$app->pass();
			}
			exit;

		}

		$database->query('SELECT * FROM events_event WHERE pid = :pid AND start > :time ORDER BY start');
		$database->bind(':pid', $id_decrypt[0]);
		$database->bind(':time', strtotime("midnight", time()));
		$database->execute();

		$events = $database->resultset();

		if($rows) {

			$app->view->user_vars['header']['title'] = 'Add an event';
			$app->view->set('content', PrepareContent::assignContent($rows, $events));
			$app->render('property.tpl.html');

		}
	} else {
		$app->pass();
	}

})->via('GET', 'POST');

$app->get('/cron', function () use ($app, $database, $slack, $cb) {

	$database->query("SELECT events_event.*, i_items.title AS name FROM events_event LEFT JOIN i_items ON i_items.id = events_event.pid WHERE start > :plusday AND start < :plustwoday AND posted != 1 ORDER BY start");
	$database->bind(':plusday', strtotime("midnight", time()));
	$database->bind(':plustwoday', strtotime('+2 days', strtotime("23:59", time())));
	$database->execute();

	$api = $database->resultset();

	// Parse
	$APPLICATION_ID = APPLICATION_ID;
	$REST_API_KEY = REST_API_KEY;

	if($database->rowCount() > 0) {
		try {
			$time = 0;
			foreach($api as $row) {
				if(date('H:i', $row->start) == '00:00' && date('H:i', $row->end) == '00:00') {
					if(date('l d M Y', $row->start) == date('l d M Y', $row->end)) {
						$alert = date('l d M Y', $row->start);
					} else {
						$alert = date('l d M Y', $row->start) . ' to ' . date('l d M Y', $row->end);
					}
				} else {
					if(date('l d M Y', $row->start) == date('l d M Y', $row->end)) {
						$alert = date('l d M Y', $row->start) . ' ' . date('H:i', $row->start) . ' - ' . date('H:i', $row->end);
					} else {
						$alert = date('l d M Y', $row->start) . ' to ' . date('l d M Y', $row->end) . ' ' . date('H:i', $row->start) . ' - ' . date('H:i', $row->end);
					}

				}

				$pushTime = time() + $time;

				$url = 'https://api.parse.com/1/push';
				$data = array(
					'where' => [
						'channels' => [
							'$in' => ['male', 'female', 'no-login']
							//'$in' => ['test']
						],
						'deviceType' => 'ios'
					],
				    "push_time" => gmdate("Y-m-d\TH:i:s\Z", $pushTime),
				    'data' => array(
				        'alert' => $row->title . ' @ ' . $row->name . ' - '. $alert,
				        'hatype' => 'property',
				        'id' => (int) $row->pid,
				        'sound' => 'push.caf',
				    ),
				);
				$_data = json_encode($data);
				$headers = array(
				    'X-Parse-Application-Id: ' . $APPLICATION_ID,
				    'X-Parse-REST-API-Key: ' . $REST_API_KEY,
				    'Content-Type: application/json',
				    'Content-Length: ' . strlen($_data),
				);

				$slack->send($row->title . ' @ ' . $row->name . ' - '. $alert, 'general', ':heritage:');

				$time = $time + 15*60;

				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $_data);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
				curl_exec($curl);

				$database->query('UPDATE events_event SET posted = :posted WHERE id = :id');
				$database->bind(':posted', 1);
				$database->bind(':id', $row->id);
				$database->execute();

				$cb->setToken('226727773-VNeLLE7VkOIWsAiQH6Lt4QdQmj2Bg2hgiwue1AGR', 'Ce8ZXFyWyo4gIxHbc31xE6pwXUiBzES04O47zwXt7GFDR');
				$params = array(

				    'status' => htmlspecialchars_decode($row->title . ' @ ' . $row->name . ' - '. $alert)

				);

				$reply = $cb->statuses_update($params);

				$slack->send('Posted. ' . $row->id, 'general', ':heritage:');


			}

		} catch(Exception $e) {
			echo $e;
		}
	} else {
		$database->query("SELECT events_event.*, i_items.title AS name FROM events_event LEFT JOIN i_items ON i_items.id = events_event.pid WHERE start > :plusday AND posted != 1 ORDER BY start LIMIT 1 ");
		$database->bind(':plusday', strtotime("midnight", time()));
		$database->execute();

		$next = $database->single();

		$slack->send('No upcoming events. Next is ' . $next->title . ' on ' . date('l d M Y', $next->start), 'general', ':heritage:');
	}

});

/**
 * app- Run
 *
 * @var mixed
 * @access public
 */
$app->run();
