<?php

/**
 * OutputMessages class.
 */
class OutputMessages {


	/**
	 * setMessage function.
	 *
	 * @access public
	 * @param mixed $string
	 * @param mixed $type
	 * @param mixed $dismissable (default: null)
	 * @return void
	 */
	public static function setMessage($string, $type, $dismissable=null) {

		$_SESSION['output_messages'] = array('type' => $type, 'message' => (string) $string, 'dismissible' => $dismissable);

	}


	/**
	 * showMessage function.
	 *
	 * @access public
	 * @return void
	 */
	public static function showMessage() {

		if(!isset($_SESSION['output_messages'])) {

			return false;

		} else {

			if(isset($_SESSION['output_messages']['dismissible']) && $_SESSION['output_messages']['dismissible'] != null) {

				$output = sprintf('<div class="alert alert-%s alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>%s</div>', $_SESSION['output_messages']['type'], $_SESSION['output_messages']['message']);

			} else {

				$output = sprintf('<div class="alert alert-%s">%s</div>', $_SESSION['output_messages']['type'], $_SESSION['output_messages']['message']);

			}

			unset($_SESSION['output_messages']);

			return $output;

		}

	}

}
