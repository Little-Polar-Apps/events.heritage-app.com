<?php
/**
 * stripe_connect_url function.
 *
 * @access public
 * @return void
 */
function stripe_connect_url() {

	$authorize_request_body = array(
		'response_type' => 'code',
		'scope' => 'read_write',
		'client_id' => CLIENT_ID
	);

	$url = AUTHORIZE_URI . '?' . http_build_query($authorize_request_body);

	return $url;

}
