<?php

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

?>

<div class="notice notice-error">
	<p>
		<?php
		printf(
		
			esc_html__( 'The %s plugin is required for electronic data interchange.', 'edi' ),
			'<a href="https://woocommerce.com/woocommerce-features/" target="_blank">WooCommerce</a>'
		);
		?>
	</p>
</div>
