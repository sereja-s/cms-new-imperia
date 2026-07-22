<?php declare( strict_types=1 );


namespace BytePerfect\EDI\Parsers;


use BytePerfect\EDI\EDI;
use BytePerfect\EDI\Request;
use BytePerfect\EDI\Settings;
use Exception;


class ImportXMLParser extends XMLParser {
	
	protected bool $only_changes = false;

	
	public function __construct( Request $request ) {
		parent::__construct( $request );

		add_action(
			'/КоммерческаяИнформация/Каталог',
			array( $this, 'set_import_mode' )
		);

		if ( Settings::get_import_categories() ) {
			$this->parsers[] = __NAMESPACE__ . '\\CategoriesParser';
			$this->parsers[] = __NAMESPACE__ . '\\GroupsParser';
			$this->parsers[] = __NAMESPACE__ . '\\ProductCategoriesParser';
		}
		if ( Settings::get_import_products() ) {
			$this->parsers[] = __NAMESPACE__ . '\\ProductsParser';
		}
		if ( Settings::get_import_attributes() ) {
			$this->parsers[] = __NAMESPACE__ . '\\AttributesParser';
			$this->parsers[] = __NAMESPACE__ . '\\ProductAttributesParser';
		}
		if ( Settings::get_import_images() ) {
			$this->parsers[] = __NAMESPACE__ . '\\ProductImagesParser';
		}

		$this->parsers = (array) apply_filters( 'edi_register_import_parsers', $this->parsers );
		foreach ( $this->parsers as $parser ) {
			new $parser();
		}
	}

	
	public function set_import_mode( ?array $attr ): void {
		if ( is_null( $attr ) ) {
						return;
		}

		if ( isset( $attr['СодержитТолькоИзменения'] ) && 'true' === $attr['СодержитТолькоИзменения'] ) {
			$this->only_changes = true;
		} else {
			$this->only_changes = false;

			if ( apply_filters( 'edi_should_mark_products_pending', true ) ) {
				global $wpdb;

								$result = $wpdb->update(
					$wpdb->posts,
					array( 'post_status' => 'pending' ),
					array(
						'post_type'   => 'product',
						'post_status' => 'publish',
					)
				);

				EDI::log()->debug(
					sprintf(
					
						__( '%d products were moved to "Pending"', 'edi' ),
						$result
					)
				);
			} else {
				EDI::log()->debug(
					__( 'Skipping moving products to "Pending" due to filter configuration', 'edi' )
				);
			}
		}
	}
}
