<?php


declare( strict_types=1 );

namespace BytePerfect\EDI;


use Exception;
use WP_Error;

use function WP_Filesystem;


class DirectFileSystem {
	
	private string $root;

	
	public function __construct() {
				WP_Filesystem();

		$this->set_root();
	}

	
	protected function set_root(): void {
		$upload_dir = wp_upload_dir();
		$this->root = wp_normalize_path( path_join( $upload_dir['basedir'], 'edi-1c' ) );
	}

	
	public function strip_prefix( string $path ): string {
		return substr( $path, strlen( $this->root ) );
	}

	
	public function normalize_path( string $path ): string {
		$path = wp_normalize_path( $path );
		if ( str_starts_with( $path, $this->root ) || path_is_absolute( $path ) || wp_is_stream( $path ) ) {
			return $path;
		}

		return wp_normalize_path( path_join( $this->root, $path ) );
	}

	
	public function mkdir( string $directory = '' ): void {
		$directory = $this->normalize_path( $directory );
		if ( ! is_dir( $directory ) ) {
			if ( ! mkdir( $directory, FS_CHMOD_DIR, true ) ) {
				throw new Exception(
					sprintf(
					
						__( 'Error create directory: %s.', 'edi' ),
						$directory
					)
				);
			}
		}
	}

	
	public function rmdir( string $directory = '' ): void {
		$directory = $this->normalize_path( $directory );
		$directory = rtrim( $directory, '\\/' );

		if ( ! is_dir( $directory ) ) {
			return;
		}

		$files = $this->get_list( $directory );

		foreach ( $files as $file ) {
			$filename = $this->normalize_path( path_join( $directory, $file ) );

			if ( is_dir( $filename ) ) {
				$this->rmdir( $filename );
			} else {
				$this->unlink( $filename );
			}
		}

		if ( ! rmdir( $directory ) ) {
			throw new Exception(
				sprintf(
				
					__( 'Error remove directory: %s.', 'edi' ),
					$directory
				)
			);
		}
	}

	
	public function get_list( string $directory = '' ): array {
		$directory = $this->normalize_path( $directory );

				if ( ! is_readable( $directory ) ) {
			return array();
		}

		$list = scandir( $directory );
		if ( empty( $list ) ) {
			return array();
		}

		return array_diff( $list, array( '..', '.' ) );
	}

	
	public function get_list_except_system_files( string $directory = '' ): array {
		$file_list = array_diff(
			$this->get_list( $directory ),
			array( '.htaccess', 'index.html' )
		);

		return array_values( $file_list );
	}

	
	public function fopen( string $filename, string $mode ) {
		$filename = $this->normalize_path( $filename );

				$handle = fopen( $filename, $mode );
		if ( ! $handle ) {
			throw new Exception(
				sprintf(
				
					__( 'Error open stream: %s.', 'edi' ),
					$filename
				)
			);
		}

		return $handle;
	}

	
	public function fread( $handle, int $length ): string {
				$string = fread( $handle, $length );
		if ( false === $string ) {
			throw new Exception(
				sprintf(
				
					__( 'Error read from stream: %s.', 'edi' ),
					$this->get_stream_url( $handle )
				)
			);
		}

		return $string;
	}

	
	public function ftell( $handle ): int {
		$position = ftell( $handle );
		if ( false === $position ) {
			throw new Exception(
				sprintf(
				
					__( 'Error get pointer position: %s.', 'edi' ),
					$this->get_stream_url( $handle )
				)
			);
		}

		return $position;
	}

	
	public function fwrite( $handle, string $data ): void {
				if ( false === fwrite( $handle, $data ) ) {
			throw new Exception(
				sprintf(
				
					__( 'Error write to stream: %s.', 'edi' ),
					$this->get_stream_url( $handle )
				)
			);
		}
	}

	
	public function fseek( $handle, int $offset ): void {
		if ( - 1 === fseek( $handle, $offset ) ) {
			throw new Exception(
				sprintf(
				
					__( 'Error seek stream: %s.', 'edi' ),
					$this->get_stream_url( $handle )
				)
			);
		}
	}

	
	public function fclose( $handle ): void {
				if ( ! fclose( $handle ) ) {
			throw new Exception(
				sprintf(
				
					__( 'Error close stream: %s.', 'edi' ),
					$this->get_stream_url( $handle )
				)
			);
		}
	}

	
	public function stream_copy_to_stream( $source, $destination ): void {
		if ( ! stream_copy_to_stream( $source, $destination ) ) {
			throw new Exception(
				sprintf(
				
					__( 'Error copy stream from %1$s to %2$s.', 'edi' ),
					$this->get_stream_url( $source ),
					$this->get_stream_url( $destination )
				)
			);
		}
	}

	
	public function file_put_contents( string $filename, string $data ): void {
		$filename = $this->normalize_path( $filename );

		$handle = $this->fopen( $filename, 'wb' );
		$this->fwrite( $handle, $data );
		$this->fclose( $handle );

		$this->chmod( $filename, FS_CHMOD_FILE );
	}

	
	public function unlink( string $filename ): void {
		$filename = $this->normalize_path( $filename );

		if ( ! unlink( $filename ) ) {
			throw new Exception(
				sprintf(
				
					__( 'Error unlink file: %s.', 'edi' ),
					$filename
				)
			);
		}
	}

	
	public function chmod( string $filename, int $permissions ): void {
		$filename = $this->normalize_path( $filename );

		if ( ! chmod( $filename, $permissions ) ) {
			throw new Exception(
				sprintf(
				
					__( 'Error set file mode: %s.', 'edi' ),
					$filename
				)
			);
		}
	}

	
	public function unzip_file( string $filename, string $destination, bool $delete_source = true ): void {
		$filename    = $this->normalize_path( $filename );
		$destination = $this->normalize_path( $destination );

		$result = unzip_file( $filename, $destination );
		if ( is_wp_error( $result ) ) {
			
			throw new Exception(
				sprintf(
				
					__( 'Error unzip file: %s', 'edi' ),
					$result->get_error_message()
				)
			);
		}

		if ( true === $delete_source ) {
			$this->unlink( $filename );
		}
	}

	
	public function receive_file( string $filename ): void {
		$filename = $this->normalize_path( $filename );

		$this->mkdir( dirname( $filename ) );

		$source      = $this->fopen( 'php://input', 'r' );
		$destination = $this->fopen( $filename, 'ab' );

		$this->stream_copy_to_stream( $source, $destination );

		$this->fclose( $source );
		$this->fclose( $destination );

		$this->chmod( $filename, FS_CHMOD_FILE );
	}

	
	protected function get_stream_url( $handle ): string {
		$meta_data = stream_get_meta_data( $handle );

		return $meta_data['uri'];
	}
}
