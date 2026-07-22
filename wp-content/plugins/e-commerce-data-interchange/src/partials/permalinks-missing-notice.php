<?php

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

?>

<div class="notice notice-error">
	<p>
		<?php
		printf(
		
			esc_html__( 'Please configure the permanent links on %s page to ensure proper operation of the electronic data interchange.', 'edi' ),
			'<a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">' . __( 'Permalinks' ) . '</a>'
		);
		?>
	</p>
</div>
