<?php


declare( strict_types=1 );


namespace BytePerfect\EDI;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use BytePerfect\EDI\CLI\EDI_CLI;
use Exception;
use WC_Logger;
use WC_Order;


class EDI {
	
	const VERSION = '2.0.0';

	
	public function __construct() {
		load_plugin_textdomain( 'edi', false, plugin_basename( dirname( EDI_PLUGIN_FILE ) ) . '/languages' );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			new EDI_CLI();
		}

		register_activation_hook(
			EDI_PLUGIN_FILE,
			array( __NAMESPACE__ . '\\Activator', 'run' )
		);

		register_deactivation_hook(
			EDI_PLUGIN_FILE,
			array( __NAMESPACE__ . '\\Deactivator', 'run' )
		);

		register_uninstall_hook(
			EDI_PLUGIN_FILE,
			array( __NAMESPACE__ . '\\Uninstaller', 'run' )
		);

		if ( class_exists( __NAMESPACE__ . '\\Updater' ) ) {
			add_action(
				'upgrader_process_complete',
				array( __NAMESPACE__ . '\\Updater', 'run' ),
				10,
				2
			);
		}

		add_action(
			'plugins_loaded',
			array( $this, 'edi_loaded' ),
			PHP_INT_MAX
		);

		if ( $this->is_woocommerce_activated() ) {
			add_action(
				'before_woocommerce_init',
				function () {
					if ( class_exists( FeaturesUtil::class ) ) {
						FeaturesUtil::declare_compatibility( 'custom_order_tables', EDI_PLUGIN_FILE );
					}
				}
			);

			add_action(
				'woocommerce_after_order_object_save',
				array( $this, 'set_order_modified_timestamp' )
			);

						add_filter(
				'plugin_action_links_e-commerce-data-interchange/e-commerce-data-interchange.php',
				array( $this, 'settings_link' )
			);

			new Settings();
			new Request();
		}

		add_action(
			'admin_notices',
			array( $this, 'show_missing_notice' )
		);
	}

	
	public function settings_link( array $links ): array {
		$settings = array(
			'setting' => sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'admin.php?page=edi' ),
				__( 'Settings', 'edi' )
			),
		);

		return array_merge( $settings, $links );
	}

	
	protected function is_woocommerce_activated(): bool {
		return in_array(
			'woocommerce/woocommerce.php',
			(array) apply_filters( 'active_plugins', get_option( 'active_plugins' ) ),
			true
		);
	}

	
	public function show_missing_notice(): void {
		if ( ! get_option( 'permalink_structure' ) ) {
			require plugin_dir_path( EDI_PLUGIN_FILE ) . 'src/partials/permalinks-missing-notice.php';
		}

		if ( ! $this->is_woocommerce_activated() ) {
			require plugin_dir_path( EDI_PLUGIN_FILE ) . 'src/partials/woocommerce-missing-notice.php';
		}
	}

	
	public function edi_loaded(): void {
		do_action( 'edi_loaded', self::VERSION );
	}

	
	public function set_order_modified_timestamp( WC_Order $order ): void {
		static $is_modified;

		if ( ! is_null( $is_modified ) ) {
			return;
		}

		if ( doing_action( '_/КоммерческаяИнформация/Документ' ) ) {
			return;
		}

				$order->add_meta_data( '_edi_modified', current_time( 'timestamp' ) );

		self::log()->debug(
			__( 'Order modified timestamp was set. Order ID: ', 'edi' ) . PHP_EOL . $order->get_id()
		);

		$is_modified = true;
	}

	
	public static function repository(): Repository {
		static $repository = null;

		if ( is_null( $repository ) ) {
			$repository = new Repository();
		}

		return $repository;
	}

	
	public static function log(): WC_Logger {
		static $logger = null;

		if ( is_null( $logger ) ) {
			$logger = new WC_Logger(
				array( new LogHandlerNull() ),
				apply_filters( 'edi_logging_level', EDI_DEFAULT_LOGGING_LEVEL )
			);
		}

		return $logger;
	}

	
	public static function filesystem(): DirectFileSystem {
		static $filesystem = null;

		if ( is_null( $filesystem ) ) {
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			$access_type = get_filesystem_method();
			if ( 'direct' === $access_type ) {
				$filesystem = new DirectFileSystem();
			} else {
				throw new Exception(
				
					sprintf( esc_html__( 'File system %s is not implemented.', 'edi' ), esc_html( $access_type ) )
				);
			}
		}

		return $filesystem;
	}
}
