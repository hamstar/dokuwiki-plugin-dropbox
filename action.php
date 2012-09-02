<?php
/**
 * Proof of concept plugin for simple per page  permissions
 * in dokuwiki
 *
 * @license    WTFPL 2 (http://sam.zoy.org/wtfpl/)
 * @author     Robert Mcleod <hamstar@telescum.co.nz>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * All DokuWiki plugins to interfere with the event system
 * need to inherit from this class
 */
class action_plugin_dropbox extends DokuWiki_Action_Plugin {

	/**
	* Registers a callback function for a given event
	*/
	function register(&$controller) {

		$controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'check_dropbox_setting', array());
		$controller->register_hook('HTML_UPDATEPROFILEFORM_OUTPUT', 'BEFORE', $this, 'handle_dropbox', array());
	}

	function check_dropbox_setting( &$event, $param ) {

		global $ACT;

		switch ( $ACT ) {
		case "dropbox.enable":
			$this->_enable_dropbox( $USERINFO['name'] );
			break;
		case "dropbox.disable":
			$this->_disable_dropbox( $USERINFO['name'] );
			break;
		default:
			return;
			break;
		}
	}

	function _enable_dropbox( $user ) {

		file_put_contents("/var/lib/dokuwiki/data/pages/dropbox/enable_queue.txt", $user, FILE_APPEND);
	}

	function _disable_dropbox( $user ) {

		file_put_contents("/var/lib/dokuwiki/data/pages/dropbox/disable_queue.txt", $user, FILE_APPEND);
	}

	function _get_dropbox_status( $user ) {

		$enabled_users = file_read_contents("/var/lib/dokuwiki/data/pages/dropbox/enable_queue.txt");

		if ( preg_match("/^$user$/", $enabled_users ) )
			return "enabled";

		return "disabled";
	}

	function append_dropbox_elements( &$event ) {

		global $USERINFO;

		$status = $this->_get_dropbox_status( $USERINFO['name'] );
		$status_adjective = substr( $status, 0, -1 );
		$status_adjective_ucf = ucfirst( substr( $status, 0, -1 ) );

		$pos = $event->data->findElementByAttribute('type', 'reset');

		$out = <<<EOF
<fieldset style="margin-top: 10px;">
	<legend>Dropbox Backups</legend>
	<p>Upon clicking this button, dropbox will be setup for your user account.  You will receive confirmation and further instructions in an email.</p>
	<p>Dropbox Status: <strong>$status</strong></p>
	<input type="submit" value="$status_adjective_ucf Dropbox" class="button" id="{$status_adjective}_dropbox"/>
</fieldset>
EOF;

		$event->data->insertElement($pos+2,$out);
	}
}