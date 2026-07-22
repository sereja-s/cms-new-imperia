<?php


declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

?>

<div class="notice notice-info notice-alt">
	<h2>
		<?php
		esc_html_e(
			'Having trouble with setup?',
			'edi'
		);
		?>
	</h2>
	<p>
		<?php
		esc_html_e(
			'On the plugin\'s official website, you can get qualified help from the plugin\'s author.',
			'edi'
		);
		?>
	</p>
	<p>
		<a href="https://ediplugin.org/?utm_source=wp-admin&utm_medium=referral&utm_campaign=plugin_installed" target="_blank" class="button button-primary">
			<?php
			esc_html_e(
				'Learn more about help options',
				'edi'
			);
			?>
		</a>
	</p>
</div>
