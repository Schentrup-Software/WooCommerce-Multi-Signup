<?php
/**
 * Template for displaying additional information in the order confirmation page.
 *
 * @var array<string, array{label: string, value: string}> $additional_fields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<section class="checkout-fields-for-blocks">

	<h3 class="additonal-information-title"><?php esc_html_e( 'Additional Information', 'checkout-fields-for-blocks' ); ?></h3>

	<ul class="additional-information-details" style="padding-inline-start: 10px;">

		<?php foreach ( $additional_fields as $field ) : ?>

		<li class="order" style="list-style-type: none; margin-left: 0;"><?php echo esc_html( $field['label'] ); ?>: <?php echo esc_html( $field['value'] ); ?></li>

		<?php endforeach; ?>

	</ul>

</section>
