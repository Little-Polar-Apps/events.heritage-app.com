<?php

class Post {

	public function saveEvent($post, $r) {
		global $database;
			
		$database->beginTransaction();
		$database->query('INSERT INTO events_event (start, end, title, description, url, pid, objectId) VALUES (:start, :end, :title, :description, :url, :pid, :objectid)');
	
		$required = array(':start-date', ':start-time', ':end-date', ':end-time', ':event-title', ':event-description');
	
		if(isset($post[':ad']) && $post[':ad'] == 'on') {
			$required = array(':start-date', ':end-date', ':event-title', ':event-description');
	
			$startDate = DateTime::createFromFormat('d/m/Y g:i A', $post[':start-date'] . ' 00:00 AM');
			$startDateToBeInserted = $startDate->format('U');
	
			$endDate = DateTime::createFromFormat('d/m/Y g:i A', $post[':end-date'] . ' 00:00 AM');
			$endDateToBeInserted = $endDate->format('U');
	
			$database->bind(':start', $startDateToBeInserted);
			$database->bind(':end', $endDateToBeInserted);
		} else {
			$startDate = DateTime::createFromFormat('d/m/Y g:i A', $post[':start-date'] . ' ' . $post[':start-time']);
			$startDateToBeInserted = $startDate->format('U');
	
			$endDate = DateTime::createFromFormat('d/m/Y g:i A', $post[':end-date'] . ' ' . $post[':end-time']);
			$endDateToBeInserted = $endDate->format('U');
	
			$database->bind(':start', $startDateToBeInserted);
			$database->bind(':end', $endDateToBeInserted);
		}
	
		$database->bind(':title', $post[':event-title']);
		$database->bind(':description', $post[':event-description']);
		$database->bind(':url', $post[':permalink']);
		$database->bind(':pid', $r[0]);
		$database->bind(':objectid', $post[':object-id']);
	
		$error = false;
		foreach($required as $field) {
			if(empty($post[$field])) {
				$error = true;
			}
		}
	
		if($error) {
			$message = "All fields are required.";
			$database->cancelTransaction();
			return;
		} else {
			//echo "Proceed...";
			$database->execute();
			$database->endTransaction();
		}
		return;
	}
	
	public function editEvent($post, $r) {
		global $database;
		global $hashids;
			
		$database->beginTransaction();
		$database->query('UPDATE events_event SET start=:start, end=:end, title=:title, description=:description, url=:url, pid=:pid, objectId=:objectId WHERE id=:id');
	
		$required = array(':start-date', ':start-time', ':end-date', ':end-time', ':event-title', ':event-description');
	
		if(isset($post[':ad']) && $post[':ad'] == 'on') {
			$required = array(':start-date', ':end-date', ':event-title', ':event-description');
	
			$startDate = DateTime::createFromFormat('d/m/Y g:i A', $post[':start-date'] . ' 00:00 AM');
			$startDateToBeInserted = $startDate->format('U');
	
			$endDate = DateTime::createFromFormat('d/m/Y g:i A', $post[':end-date'] . ' 00:00 AM');
			$endDateToBeInserted = $endDate->format('U');
	
			$database->bind(':start', $startDateToBeInserted);
			$database->bind(':end', $endDateToBeInserted);
		} else {
			$startDate = DateTime::createFromFormat('d/m/Y g:i A', $post[':start-date'] . ' ' . $post[':start-time']);
			$startDateToBeInserted = $startDate->format('U');
	
			$endDate = DateTime::createFromFormat('d/m/Y g:i A', $post[':end-date'] . ' ' . $post[':end-time']);
			$endDateToBeInserted = $endDate->format('U');
	
			$database->bind(':start', $startDateToBeInserted);
			$database->bind(':end', $endDateToBeInserted);
		}
		
		$id = $hashids->decrypt($post[':ident']);
		$pid = $hashids->decrypt($post[':pid']);	
				
		$database->bind(':title', $post[':event-title']);
		$database->bind(':description', $post[':event-description']);
		$database->bind(':url', $post[':permalink']);
		$database->bind(':pid', $pid[0]);
		$database->bind(':objectId', $post[':object-id']);
		$database->bind(':id', $id[0]);
	
		$error = false;
		foreach($required as $field) {
			if(empty($post[$field])) {
				$error = true;
			}
		}
	
	
	
		if($post[':html'] == true) {
		//	strtotime($post[':start-date'] . $post[':start-time']);
		//	strtotime($post[':end-date'] . $post[':end-time']);
		}
	
		if($error) {
			$message = "All fields are required.";
			$database->cancelTransaction();
			return;
		} else {
			//echo "Proceed...";
			$database->execute();
			$database->endTransaction();
		}
		return;
	}
	
	public function deleteEvent($post) {
		global $database;
		global $hashids;
		
		$id = $hashids->decrypt($post['id']);
		
		$database->query('DELETE FROM events_event WHERE id = :id');
		$database->bind(':id', $id[0]);
		$database->execute();
	
		return;
	}
	
}
