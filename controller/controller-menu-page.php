<?php


namespace wp_gdpr\controller;

use wp_gdpr\lib\Appsaloon_Table_Builder;

class Controller_Menu_Page {

	/**
	 * Controller_Menu_Page constructor.
	 */
	public function __construct() {
		if ( ! has_action( 'init', array( $this, 'send_email' ) ) ) {
			add_action( 'init', array( $this, 'send_email' ) );
		}
	}

	public function build_table_with_requests() {
		global $wpdb;

		$query = "SELECT * FROM {$wpdb->prefix}gdpr_requests";

		$requesting_users = $wpdb->get_results( $query, ARRAY_A );
		$form_content     = $this->get_form_content( $requesting_users );

		$table = new Appsaloon_Table_Builder(
			array( 'id', 'email','',  'requested at' ),
			$requesting_users
			, array( $form_content ) );

		$table->print_table();
	}

	public function get_form_content( $requesting_users ) {
		ob_start();
		$controller = $this;
		include_once GDPR_DIR . 'view/admin/small-form.php';

		return ob_get_clean();
	}
	//TODO test
	//TODO add status of request
	//TODO update status of request

	public function print_inputs_with_emails() {
		global $wpdb;

		$query = "SELECT * FROM {$wpdb->prefix}gdpr_requests";

		$requesting_users = $wpdb->get_results( $query, ARRAY_A );

		foreach ( $requesting_users as $user ) {
			echo '<input hidden name="gdpr_emails[]" value="' . $user['email'] . '">';
		}

	}

	public function send_email() {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset( $_REQUEST['gdpr_emails'] ) && is_array( $_REQUEST['gdpr_emails'] ) ) {
			foreach ( $_REQUEST['gdpr_emails'] as $single_address ) {
				$to      = $single_address;
				$subject = 'Data request';
				$content = $this->get_email_content( $single_address );

				wp_mail( $to, $subject, $content, array() );
			}
		}
	}

	public function get_email_content( $single_adress ) {
		ob_start();
		$url = $this->create_unique_url( $single_adress );
		include GDPR_DIR . 'view/front/email-template.php';

		return ob_get_clean();
	}

	public function create_unique_url( $email_address ) {
		return home_url() . '/' . base64_encode( 'gdpr#' . $email_address );
	}

	public function build_table_with_plugins() {

		$plugins = get_plugins();
		$plugins = array_map( function ( $k ) {
			return array( $k['Name'] );
		}, $plugins );


		$plugins = $this->filter_plugins( $plugins );

		$table = new Appsaloon_Table_Builder(
			array( 'plugin name' ),
			$plugins
			, array() );

		$table->print_table();

	}

	/**
	 * @param array $plugins
	 *
	 * @return array
	 */
	public function filter_plugins( array $plugins ) {

		return array_filter( $plugins, function ( $data ) {
			$plugin_name = strtolower( $data[0] );
			foreach ( array( 'woocommerce', 'gdpr', 'gravity' ) as $pl ) {
				if ( strpos( $plugin_name, $pl ) !== false ) {
					return true;
				}
			}
		} );
	}
}
