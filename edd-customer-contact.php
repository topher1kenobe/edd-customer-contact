<?php
/*
Plugin Name: Easy Digital Downloads - Customer Contact
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

function edd_register_contact_view( $views ) {
	$views['contact'] = 'edd_customers_contact_view';

	return $views;
}
add_filter( 'edd_customer_views', 'edd_register_contact_view', 10, 1 );

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
				<textarea id="customer-email" name="customer_email" class="customer-note-input" rows="10"></textarea>

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
 * @return int		   Wether it was a successful deletion
 */
function edd_customer_contact( $args ) {

	$customer_edit_role = apply_filters( 'edd_edit_customers_role', 'edit_shop_payments' );

	if ( ! is_admin() || ! current_user_can( $customer_edit_role ) ) {
		wp_die( __( 'You do not have permission to contact this customer.', 'edd' ) );
	}

	if ( empty( $args ) ) {
		return;
	}

	$customer_id   = (int)$args['customer_id'];
	$remove_data   = ! empty( $args['edd-customer-contact-records'] ) ? true : false;
	$nonce		   = $args['_wpnonce'];

	if ( ! wp_verify_nonce( $nonce, 'contact-customer' ) ) {
		wp_die( __( 'Cheatin\' eh?!', 'edd' ) );
	}

	if ( edd_get_errors() ) {
		wp_redirect( admin_url( 'edit.php?post_type=download&page=edd-customers&view=overview&id=' . $customer_id ) );
		exit;
	}

	$customer = new EDD_Customer( $customer_id );

	do_action( 'edd_pre_contact_customer', $customer_id, $confirm, $remove_data );

	$success = false;

	if ( $customer->id > 0 ) {



	} else {

		edd_set_error( 'edd-customer-contact-invalid-id', __( 'Invalid Customer ID', 'edd' ) );
		$redirect = admin_url( 'edit.php?post_type=download&page=edd-customers' );

	}

	wp_redirect( $redirect );
	exit;

}
add_action( 'edd_contact-customer', 'edd_customer_contact', 10, 1 );
