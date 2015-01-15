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
	require 'base/classes/OutputMessages.php';
	include 'base/lib/pagination.php';
//	require 'base/cron/cron.php';
	
	$app        = new \Slim\Slim();
	$login      = new PHPLogin();
	$database   = new Database();
	$post		= new Post();
	$header     = new stdClass();
	$footer     = new stdClass();
	$content    = new stdClass();
	$menu       = new stdClass();
	$addExtras  = new stdClass();
	
	$hashids 	= new Hashids\Hashids('History is around us');
	
	$app->config(array(
		'view' => new Ets(),
	    'templates.path' => 'base/templates'
	));
	
	
	$app->view->parserDirectory 	 = dirname(__FILE__) . '/vendor/little-polar-apps/ets';
	$app->view->parserCacheDirectory = dirname(__FILE__) . '/base/cache';
	$app->view->setTemplatesDirectory(dirname(__FILE__) . '/base/templates');
	
	$addExtras->yoursite            = YOURSITE;
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
	
	
	$app->map('/', function () use ($app) {

	
		$app->view->user_vars['header']['title'] = 'Home';
		$app->render('home.tpl.html');
	
	})->via('GET', 'POST');
	
	$app->map('/crypt/:id', function ($id) use ($app, $hashids) {
		
		$app->view->user_vars['header']['title'] = 'Encrypt';
		$app->view->user_vars['main']['hash'] = $hashids->encrypt($id);
		$app->render('encrypt.tpl.html');
		
	})->via('GET', 'POST');
	
	$app->map('/share', function () use ($app, $hashids, $database, $content) {
		
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
		$app->render('share.tpl.html');
		
	})->via('GET', 'POST');
	
	$app->map('/:id/:hrtgs/:dirtitle(/page/:number)(/:format)(/edit/:edit)', function($id, $hrtgs, $dirtitle, $number=1, $format='json', $edit=null) use ($app, $database, $hashids, $post) {
		
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
		
	})->via('GET', 'POST');

	/**
	 * app- Run
	 *
	 * @var mixed
	 * @access public
	 */
	$app->run();
	
?>


<?php
/*	
// Events

require_once('config.php');
require_once('db.php');



$r = $_SERVER['REQUEST_URI'];

$r = explode('/', $r);

$r = array_filter($r);

$r = array_merge($r, array());

// $r = 0=>id, 1=>hrtgs, 2=>md5(dirtitle) 3=>rss/json

//print_r($r);

if(empty($r)) {
	include("home.php");
	exit();
}


// Instantiate database.
$database = new Database();

if($r[0] === 'api') {
	$dirtitle = $r[1];
	$dirtitle = explode('-', $dirtitle);
	$dirtitle = array_filter($dirtitle);
	if($dirtitle[1] === '8xhKhJ18Iez') {
		$database->query("SELECT * FROM i_items WHERE MD5(dirtitle) = :dirtitle");
		$database->bind(':dirtitle', (string) $dirtitle[0]);
		$database->execute();

	} else {
		exit();
	}

} else {
	$database->query("SELECT * FROM i_items WHERE id = :id AND hrtgs = :hrtgs AND MD5(dirtitle) = :dirtitle");
	$database->bind(':id', $r[0]);
	$database->bind(':hrtgs', $r[1]);
	$database->bind(':dirtitle', $r[2]);
	$database->execute();
}
$rows = $database->single();

if(empty($rows)) {
	exit();
}

if($r[0] === 'api') {
	if($r[2] === 'rss') {
		define("FORMAT", "rss");
		include_once('list-events.php');
	} elseif($r[2] === 'json') {
		define("FORMAT", "json");
		include_once('list-events.php');
	} else {
	//	exit();
	}
//	exit();
}

define("PID", $r[0]);
define("HRTGS", $r[1]);
define("DIRTITLE", $r[2]);


function trim_excerpt($text, $alt_text = L_NO_TEXT_IN_DESCRIPTION, $keep_line_breaks = false, $newlines = false) {
	$text = str_replace(']]>', ']]&gt;', $text);
	$paragraph = explode('</p>', $text, 2);
	$paragraph = $paragraph[0];
	if($paragraph != '') {
		if($paragraph[strlen($paragraph)-1] = '.') {
			$paragraph[strlen($paragraph)-1] = ' ';
		}
	}
	$paragraph .= '&hellip;</p>';
	$text = $paragraph;
	return $text;
}

function excerpt_length($text) {
	$excerpt_length = 40;
	$text = trim($text);
	$words = explode(' ', $text, $excerpt_length + 1);
	if(count($words) > $excerpt_length) {
		array_pop($words);
		array_push($words, '&hellip;</p>');
		$text = implode(' ', $words);
	}
	return $text;
}


if($_POST && !isset($_POST['deleting'])) {
	saveEvent($_POST, $r);
} else {
	deleteEvent($_POST);
}

$error_messages = '';
if($error && $message != '') {
	$ul = '';
	foreach($message as $list) {
		$ul .= '<li>'.$list.'</li>';
	}
	$error_messages = '<div class="alert alert-danger">';
	$error_messages .= '<p>Whoops! Looks like we hit an error</p>';
	if($ul != '') {
		$error_messages .= '<ul>';
		$error_messages .= $ul;
		$error_messages .= '</ul>';
	}
	$error_messages .= '</div>';
}

/*
echo "<pre>";
print_r($rows);
echo "</pre>";

echo $database->rowCount();


define("TITLE", $rows['title']);
define("EXCERPT", excerpt_length($rows['descr']));
define("IMAGE", 'http://uploads.heritage-app.com.s3.amazonaws.com/static/retina/'.$rows['image']);
define("ERROR", $error_messages);
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title><?= TITLE ?></title>
	<meta name="description" content="">
	<meta name="keywords" content="" />
	<meta name="author" content="">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="/webfonts/ss-gizmo.css">
	<link rel="stylesheet" href="/css/bootstrap.css">
	<link rel="stylesheet" href="/css/bootstrap-datetimepicker.min.css">
	<link rel="stylesheet" href="/css/nprogress.css">
	<link rel="stylesheet" href="/css/styles.css">

	<script src="/js/modernizr.custom.js"></script>
</head>
<body style='display: none'>
	<?= ERROR ?>
	<div class="fading outing">

		<div class="container">
			<div class="row">
				<div class="col-sm-8">
					<div class="thumbnail clearfix">
						<img src="<?= IMAGE ?>" class="pull-left" width="320" height="198" />
						<div class="caption pull-right col-sm-6">
							<h3><?= TITLE ?></h3>
							<p>
								<?= EXCERPT ?>

							</p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="container">
			<div class="row">
				<form method="post">
					<input type="hidden" name=":html" value="true" />
					<input type="hidden" name=":object-id" value="<?= uniqid(); ?>" />
					<div class="form-group">
						<label for=":event-title" class="sr-only">Event title</label>
						<input type="text" class="form-control" id="event-title" name=":event-title" placeholder="Event title">
					</div>
					<div class="form-inline">
						<div class="form-group">
							<label for=":start-date" class="sr-only">Start date</label>
							<input type="text" class="form-control pickDate" id="start-date" name=":start-date" placeholder="Start date" value="<?= date('d/m/Y') ?>" data-format="DD/MM/YYYY">
						</div>
						<div class="form-group">
							<label for=":start-time" class="sr-only">Start time</label>
							<input type="text" class="form-control pickTime" id="start-time" name=":start-time" placeholder="Start time" value="<?= date('g:i A') ?>">
						</div>
						<div class="form-group">
							<p class="form-control-static">to</p>
						</div>
						<div class="form-group">
							<label for=":end-time" class="sr-only">End time</label>
							<input type="text" class="form-control pickTime" id="end-time" name=":end-time" placeholder="End time" value="<?= date("g:i A", time()+60*60) ?>">
						</div>
						<div class="form-group">
							<label for=":end-date" class="sr-only">End date</label>
							<input type="text" class="form-control pickDate" id="end-date" name=":end-date" placeholder="End date" value="<?= date('d/m/Y') ?>" data-format="DD/MM/YYYY">
						</div>
					</div>
					<div class="checkbox">
						<label>
							<input type="checkbox" name=":ad"> All day
						</label>
					</div>
					<h5>Event Details</h5>
					<div class="form-horizontal">
						<div class="form-group">
							<label class="col-sm-2">Where</label>
							<div class="col-sm-10">
								<p class="form-control-static"><?= TITLE ?></p>
							</div>
						</div>
						<div class="form-group">
							<label for=":event-desription" class="col-sm-2">Description</label>
							<div class="col-sm-10">
								<textarea class="form-control" id="description" name=":event-description" rows="3"></textarea>
							</div>
						</div>
						<div class="form-group">
							<label for=":permalink" class="col-sm-2">Direct URL</label>
							<div class="col-sm-10">
								<input class="form-control" id="permalink" name=":permalink" value="" placeholder="e.g. http://heritage-app.com/article" />
							</div>
						</div>
					</div>
					<hr>
					<button type="submit" class="btn btn-default" id="saveEvent">Save</button>
				</form>
			</div>
		</div>
		<hr>
		<div class="container">
			<div class="row">
				<div class="list-group" id="list-events">
				<?php include_once('list-events.php'); ?>
				</div>
			</div>
		</div>
	</div>

	<script src="/webfonts/ss-gizmo.js"></script>
	<script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
	<script src="https://rawgithub.com/ngryman/jquery.finger/v0.1.0/dist/jquery.finger.js"></script>
	<script src="/js/moment.js"></script>
	<script src="/js/bootstrap.min.js"></script>
	<script src="/js/bootstrap-datetimepicker.js"></script>
	<script src="/js/nprogress.js"></script>
	<script src="/js/scripts.js"></script>
</body>
</html>
*/
