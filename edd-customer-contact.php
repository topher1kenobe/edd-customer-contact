<?php
/*
Plugin Name: EDD Customer Contact
Version: 0.1
Description: Creates a tab on the Customer page for sending an email to that customer.
Author: Topher
Author URI: http://topher1kenobe.com
Plugin URI: http://topher1kenobe.com
Text Domain: edd-customer-contact
Domain Path: /languages
*/

/**
 * Create new tab for contacting a customer
 *
 * @since  2.3.1
 * @param  array $tabs An array of existing tabs
 * @return array	   The altered list of tabs, with the new one right above delete
 */
function edd_register_contact_customer_tab( $tabs ) {

	$tabs['contact'] = array( 'dashicon' => 'dashicons-email-alt', 'title' => __( 'Email Customer', 'edd' ) );

	return $tabs;
}
add_filter( 'edd_customer_tabs', 'edd_register_contact_customer_tab', 2, 1 );

/**
 * Register new view for the email form
 *
 * @since  2.3.1
 * @param  array $views An array of existing views
 * @return array	    The altered list of views, with the contact view added
 */
function edd_register_contact_view( $views ) {
	$views['contact'] = 'edd_customers_contact_view';

	return $views;
}
add_filter( 'edd_customer_views', 'edd_register_contact_view', 10, 1 );


/**
 * Render new view for emailing the customer
 *
 * @since  2.3.1
 * @param  object $customer An object holding all of the current customer information
 * @return string           The contact form
 */
function edd_customers_contact_view( $customer ) {
	$customer_edit_role = apply_filters( 'edd_edit_customers_role', 'edit_shop_payments' );

	?>

	<?php do_action( 'edd_customer_contact_top', $customer ); ?>

	<div class="info-wrapper customer-section">

		<form id="contact-customer" method="post" action="<?php echo admin_url( 'edit.php?post_type=download&page=edd-customers&view=contact&id=' . $customer->id ); ?>">

			<div class="customer-notes-header">
				<?php echo get_avatar( $customer->email, 30 ); ?> <span><?php echo esc_attr( $customer->name ); ?></span>
			</div>


			<div class="customer-info contact-customer">

				<br>
				<label for="customer-email-subject">Subject</label><br>
				<input type="text" name="customer-email-subject" id="customer-email-subject" class="text-large">

				<br><br>

				<label for="customer-email">Message</label><br>
				<textarea id="customer-email" name="customer-email-message" class="customer-note-input" rows="10"></textarea>

				<span id="customer-edit-actions">
					<input type="hidden" name="customer_id" value="<?php echo $customer->id; ?>" />
					<?php wp_nonce_field( 'contact-customer', '_wpnonce', false, true ); ?>
					<input type="hidden" name="edd_action" value="contact-customer" />
					<input type="submit" id="edd-contact-customer" class="button-primary" value="<?php _e( 'Email', 'edd' ); ?> <?php echo esc_attr( $customer->name ); ?>" />
				</span>

			</div>

		</form>
	<?php

	do_action( 'edd_customer_contact_bottom', $customer );
}

/**
 * Contact a customer
 *
 * @since  2.3
 * @param  array $args The $_POST array being passeed
 * @return int		   Whether it was a successful email
 */
function edd_customer_contact( $args ) {

	// set the user role that we have to have in order to edit a customer account
	$customer_edit_role = apply_filters( 'edd_edit_customers_role', 'edit_shop_payments' );

	// make sure we're allowed to be here at all
	if ( ! is_admin() || ! current_user_can( $customer_edit_role ) ) {
		wp_die( __( 'You do not have permission to contact this customer.', 'edd' ) );
	}

	// make sure we have some input at all
	if ( empty( $args ) ) {
		return;
	}

	// reassign some vars to nicer handles
	$customer_id = (int)$args['customer_id'];
	$nonce		 = $args['_wpnonce'];

	// verify our nonce so we know we came from the proper place
	if ( ! wp_verify_nonce( $nonce, 'contact-customer' ) ) {
		wp_die( __( 'Cheatin\' eh?!', 'edd' ) );
	}

	// check for errors, and if we have some, bounce use to the main customer profile page
	if ( edd_get_errors() ) {
		wp_redirect( admin_url( 'edit.php?post_type=download&page=edd-customers&view=overview&id=' . $customer_id ) );
		exit;
	}

	// go get all the data for the current customer
	$customer = new EDD_Customer( $customer_id );

	// provide a hook for doing stuff before emailing the customer
	do_action( 'edd_pre_contact_customer', $customer_id, $confirm, $remove_data );

	// set a default of true on our required fields
	$required_fields = true;

	// make sure email is valid, else make required fields false
	if ( is_email( $customer->email ) ) {
		$to = $customer->email;
	} else {
		$required_fields = false;
	}

	// make sure subject isn't empty, else make required fields false
	if ( trim( $args['customer-email-subject'] ) != '' ) {
		$subject = $args['customer-email-subject'];
	} else {
		$required_fields = false;
	}

	// make sure message isn't empty, else make required fields false
	if ( trim( $args['customer-email-message'] ) != '' ) {
		$message = $args['customer-email-message'];
	} else {
		$required_fields = false;
	}


	// Make sure we have a customer ID and required fields is true, then send an email and redirect with a success message
	if ( $customer->id > 0 && true === $required_fields ) {
			EDD()->emails->send( sanitize_email( $to ), stripslashes( strip_tags( $subject ) ), stripslashes( strip_tags( $message ) ) );
			$redirect = admin_url( 'edit.php?post_type=download&page=edd-customers&edd-message=customer-contacted&view=contact&id=' . $customer->id );
	} else {

		// set up some error messages

		if ( false === $required_fields ) {

			edd_set_error( 'edd-customer-contact-no-content', __( 'Subject and Message are required.', 'edd' ) );
			$redirect = admin_url( 'edit.php?post_type=download&page=edd-customers&view=contact&id=' . $customer->id );

		} else {

			edd_set_error( 'edd-customer-contact-invalid-id', __( 'Invalid Customer ID', 'edd' ) );
			$redirect = admin_url( 'edit.php?post_type=download&page=edd-customers' );

		}

	}

	// send us to where we should go when we're done
	wp_redirect( $redirect );
	exit;

}
add_action( 'edd_contact-customer', 'edd_customer_contact', 10, 1 );



/**
 * EDD_Contact_Notices Class
 *
 * Heavily borrowed from EDD_Notices in /includes/admin/class-edd-notices.php
 *
 * @since 2.3
 */
class EDD_Contact_Notices {

	/**
	 * Get things started
	 *
	 * @since 2.3
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'show_notices' ) );
		add_action( 'edd_dismiss_notices', array( $this, 'dismiss_notices' ) );
	}

	/**
	 * Show relevant notices
	 *
	 * @since 2.3
	 */
	public function show_notices() {
		$notices = array(
			'updated'	=> array(),
			'error'		=> array()
		);

		if ( isset( $_GET['edd-message'] ) ) {
			// Shop reports errors
			if( current_user_can( 'view_shop_reports' ) ) {
				switch( $_GET['edd-message'] ) {
					case 'customer-contacted' :
						$notices['updated']['edd-customer-contacted'] = __( 'The customer has been emailed.', 'edd' );
						break;
				}
			}

			// Shop settings errors

		}

		if ( count( $notices['updated'] ) > 0 ) {
			foreach( $notices['updated'] as $notice => $message ) {
				add_settings_error( 'edd-notices', $notice, $message, 'updated' );
			}
		}

		if ( count( $notices['error'] ) > 0 ) {
			foreach( $notices['error'] as $notice => $message ) {
				add_settings_error( 'edd-notices', $notice, $message, 'error' );
			}
		}

		settings_errors( 'edd-notices' );
	}

	/**
	 * Dismiss admin notices when Dismiss links are clicked
	 *
	 * @since 2.3
	 * @return void
	 */
	function dismiss_notices() {
		if( isset( $_GET['edd_notice'] ) ) {
			update_user_meta( get_current_user_id(), '_edd_' . $_GET['edd_notice'] . '_dismissed', 1 );
			wp_redirect( remove_query_arg( array( 'edd_action', 'edd_notice' ) ) );
			exit;
		}
	}
}
new EDD_Contact_Notices;
