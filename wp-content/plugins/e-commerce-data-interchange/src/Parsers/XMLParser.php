<?php declare( strict_types=1 );


namespace BytePerfect\EDI\Parsers;


use BytePerfect\EDI\EDI;
use BytePerfect\EDI\Request;
use Exception;


class XMLParser {
	
	protected array $parsers = array();

	
	protected Request $request;

	
	protected int $start_time;

	
	protected $file_handler;

	
	protected string $file_charset = 'UTF-8';

	
	protected int $file_position = 0;

	
	protected string $xml_position = '';

	
	protected array $position_stack = array();

	
	protected array $element_stack = array();

	
	protected array $element_handlers = array();

	
	protected bool $eof = false;

	
	public function __construct( Request $request ) {
		$this->request = $request;

		$this->start_time = time();

		$this->file_handler = EDI::filesystem()->fopen( $request->filename, 'rb' );
	}

	
	public function parse(): bool {
				if ( empty( $this->parsers ) ) {
			return true;
		}

		set_time_limit( 0 );

		$this->set_position( $this->request->last_xml_entry );

		do {
			$this->find_next();
		} while (
			! $this->eof
			&&
			( ( defined( 'WP_CLI' ) && WP_CLI ) || ( ! $this->time_exceeded() && ! $this->memory_exceeded() ) )
		);

		$this->request->update_last_xml_entry( $this->get_position() );

		return $this->eof;
	}

	
	protected function memory_exceeded(): bool {
		$memory_limit   = $this->get_memory_limit() * 0.9; 		$current_memory = memory_get_usage( true );

		return ( $current_memory >= $memory_limit );
	}

	
	protected function get_memory_limit(): int {
		$kb_in_bytes = 1024;
		$mb_in_bytes = 1024 * $kb_in_bytes;
		$gb_in_bytes = 1024 * $mb_in_bytes;

		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
						$memory_limit = '128M';
		}

		if ( ! $memory_limit || - 1 === intval( $memory_limit ) ) {
						$memory_limit = '32000M';
		}

		$memory_limit = strtolower( trim( $memory_limit ) );
		$bytes        = (int) $memory_limit;

		if ( false !== strpos( $memory_limit, 'g' ) ) {
			$bytes *= $gb_in_bytes;
		} elseif ( false !== strpos( $memory_limit, 'm' ) ) {
			$bytes *= $mb_in_bytes;
		} elseif ( false !== strpos( $memory_limit, 'k' ) ) {
			$bytes *= $kb_in_bytes;
		}

				return min( $bytes, PHP_INT_MAX );
	}

	
	protected function time_exceeded(): bool {
		$finish = $this->start_time + 20; 
		return ( time() >= $finish );
	}

	

	
	protected function set_position( array $position ): void {
		if ( isset( $position[0] ) ) {
			$this->file_charset = (string) $position[0];
		}
		if ( isset( $position[1] ) ) {
			$this->file_position = (int) $position[1];
		}
		if ( isset( $position[2] ) ) {
			$this->xml_position = (string) $position[2];
		}

		if ( $this->file_position > 0 ) {
			EDI::filesystem()->fseek( $this->file_handler, $this->file_position );
		}

		$this->element_stack  = array();
		$this->position_stack = array();
		foreach ( explode( '/', $this->xml_position ) as $path_part ) {
			

			$parts = explode( '@', $path_part, 2 );
			if ( count( $parts ) !== 2 ) {
				EDI::log()->critical( "🔥 XMLParser::set_position [$path_part]" );
			}

			$this->position_stack[] = (int) ( $parts[0] ?? 0 );
			$this->element_stack[]  = (string) ( $parts[1] ?? '' );
		}
	}

	
	protected function get_position(): array {
		$xml_position = array();
		foreach ( $this->element_stack as $i => $element_name ) {
			$xml_position[] = $this->position_stack[ $i ] . '@' . $element_name;
		}
		$this->xml_position = implode( '/', $xml_position );

		return array(
			$this->file_charset,
			$this->file_position,
			$this->xml_position,
		);
	}

	
	protected function find_next(): void {
		$this->eof = false;

		do {
			$xml_chunk = $this->get_xml_chunk();

			if ( is_null( $xml_chunk ) ) {
				break;
			} elseif ( '!' === $xml_chunk[0] || '?' === $xml_chunk[0] ) {
				continue;
			} elseif ( '/' === $xml_chunk[0] ) {
				$this->end_element();

				return;
			} else {
				$this->start_element( $xml_chunk );

								$position = mb_strpos( $xml_chunk, '>' );
				if ( ( false !== $position ) && ( '/' === mb_substr( $xml_chunk, $position - 1, 1 ) ) ) {
					$this->end_element();

					return;
				}
			}
		} while ( true );

		$this->eof = true;
	}

	
	protected function start_element( string $xml_chunk ): void {
		$position = mb_strpos( $xml_chunk, '>' );
		if ( false === $position ) {
			return;
		}

		if ( '/' === mb_substr( $xml_chunk, $position - 1, 1 ) ) {
			$element_name = mb_substr( $xml_chunk, 0, $position - 1 );
		} else {
			$element_name = mb_substr( $xml_chunk, 0, $position );
		}

		$position = mb_strpos( $element_name, ' ' );
		if ( false === $position ) {
			$element_attrs = '';
		} else {
			$element_attrs = mb_substr( $element_name, $position + 1 );
			$element_name  = mb_substr( $element_name, 0, $position );
		}

		$this->element_stack[]  = $element_name;
		$this->position_stack[] = $this->file_position - mb_strlen( $xml_chunk, 'latin1' );

		$xml_path   = implode( '/', $this->element_stack );
		$attributes = $this->parse_attributes( $element_attrs );
		do_action( $xml_path, $attributes );
	}

	
	protected function parse_attributes( string $element_attrs ): array {
		$search = array(
			"'&(quot|#34);'i",
			"'&(lt|#60);'i",
			"'&(gt|#62);'i",
			"'&(amp|#38);'i",
		);

		$replace = array(
			'"',
			'<',
			'>',
			'&',
		);

		$attributes = array();

		if ( '' !== $element_attrs ) {
			preg_match_all( '/(\S+)\s*=\s*["](.*?)["]/s', $element_attrs, $attrs_tmp );
			if ( false === mb_strpos( $element_attrs, '&' ) ) {
				foreach ( $attrs_tmp[1] as $i => $attrs_tmp_1 ) {
					$attributes[ $attrs_tmp_1 ] = $attrs_tmp[2][ $i ];
				}
			} else {
				foreach ( $attrs_tmp[1] as $i => $attrs_tmp_1 ) {
					$attributes[ $attrs_tmp_1 ] = preg_replace(
						$search,
						$replace,
						$attrs_tmp[2][ $i ]
					);
				}
			}
		}

		return $attributes;
	}

	
	protected function end_element(): void {
		$element_name     = array_pop( $this->element_stack );
		$element_position = array_pop( $this->position_stack );

		if ( empty( $element_name ) || empty( $element_position ) ) {
			return;
		}

		$xml_path = implode( '/', $this->element_stack ) . '/' . $element_name;
		do_action( $xml_path, null );
		if ( has_action( "_$xml_path" ) ) {
			$xml_object = $this->read_xml( $element_position, $this->file_position );
			do_action( "_$xml_path", $xml_object );
		}
	}

	
	protected function get_xml_chunk(): ?string {
		
		static $buf = '';
		
		static $buf_position = 0;
		
		static $buf_len = 0;

		
		$read_size = 1024;

		if ( $buf_position >= $buf_len ) {
			if ( ! feof( $this->file_handler ) ) {
				$buf          = EDI::filesystem()->fread( $this->file_handler, $read_size );
				$buf_position = 0;
				$buf_len      = strlen( $buf );
			} else {
				return null;
			}
		}

				$xml_position = strpos( $buf, '<', $buf_position );
		while ( $xml_position === $buf_position ) {
			$buf_position ++;
			$this->file_position ++;
						if ( $buf_position >= $buf_len ) {
				if ( ! feof( $this->file_handler ) ) {
					$buf          = EDI::filesystem()->fread( $this->file_handler, $read_size );
					$buf_position = 0;
					$buf_len      = strlen( $buf );
				} else {
					return null;
				}
			}
			$xml_position = strpos( $buf, '<', $buf_position );
		}

				while ( false === $xml_position ) {
			$next_search = $buf_len;
						if ( ! feof( $this->file_handler ) ) {
				$buf     .= EDI::filesystem()->fread( $this->file_handler, $read_size );
				$buf_len = strlen( $buf );
			} else {
				break;
			}

						$xml_position = strpos( $buf, '<', $next_search );
		}
		if ( false === $xml_position ) {
			$xml_position = $buf_len + 1;
		}

		$len                 = $xml_position - $buf_position;
		$this->file_position += $len;
		$result              = substr( $buf, $buf_position, $len );
		$buf_position        = $xml_position;

		return $result;
	}

	
	protected function read_xml( int $start_position, int $end_position ): DataXML {
		$xml_chunk = $this->read_file_part( $start_position, $end_position );

		$xml_object = new DataXML();
		$xml_object->load_string( $xml_chunk );

		return $xml_object;
	}

	
	protected function read_file_part( int $start_position, int $end_position ): string {
		$saved_position = EDI::filesystem()->ftell( $this->file_handler );

		EDI::filesystem()->fseek( $this->file_handler, $start_position );
		$xml_chunk = EDI::filesystem()->fread( $this->file_handler, $end_position - $start_position );

		EDI::filesystem()->fseek( $this->file_handler, $saved_position );

		return $xml_chunk;
	}
}
