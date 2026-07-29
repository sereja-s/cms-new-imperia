<?php


declare(strict_types=1);

namespace BytePerfect\EDI;

use Exception;
use Throwable;


class Request
{
	const EDI_ENDPOINT = '/edi/1c';

	const XML_BITRIX_FILENAME_MASK = '/^(?:import|offers|prices|rests|documents|contragents|references).*\.xml$/';

	const XML_IMPORT_FILENAME_MASK = '/^(?:import|offers).*\.xml$/';

	const ZIP_IMPORT_FILENAME_MASK = '/^v8_.+\.zip$/';

	const IMAGE_IMPORT_FILENAME_MASK =
	'/^import_files\/[A-Za-z0-9_-]+\/[A-Za-z0-9_.-]+\.(?:jpg|jpeg|png|gif|webp)$/i';

	const IMPORT_FILES_MASK =
	'/^import_files\/.+$/i';

	const SALE_IMPORT_FILENAME_MASK = '/^orders-[a-f0-9-]*_?.\.xml$/';

	const CLEAR_REPOSITORY        = true;
	const DO_NOT_CLEAR_REPOSITORY = false;


	protected string $previous_mode = '';


	protected string $previous_type = '';


	protected string $previous_filename = '';


	protected string $mode = '';


	protected string $type = '';


	protected string $filename = '';


	protected array $last_xml_entry;


	public function __construct()
	{
		$this->previous_mode     = strval(get_option('_edi_mode', ''));
		$this->previous_type     = strval(get_option('_edi_type', ''));
		$this->previous_filename = strval(get_option('_edi_filename', ''));

		if ($this->is_edi_request() && $this->authorize()) {
			$this->set_mode();
			$this->set_type();
			$this->set_filename();
			$this->set_last_xml_entry();

			add_action('wp_loaded', array($this, 'process_request'), PHP_INT_MAX);
		}

		add_action('wp_ajax_edi_get_status', array($this, 'get_status'));
		add_action('wp_ajax_edi_interrupt', array($this, 'interrupt'));
	}


	protected function set_mode(): void
	{
		$this->mode = isset($_REQUEST['mode']) ? sanitize_text_field($_REQUEST['mode']) : '';
		if (
			! in_array(
				$this->mode,
				array('checkauth', 'init', 'file', 'import', 'query', 'success', 'clear'),
				true
			)
		) {
			throw new Exception('Unexpected mode: ' . $this->mode);
		}

		update_option('_edi_mode', $this->mode, false);
	}


	protected function set_type(): void
	{
		$this->type = isset($_REQUEST['type']) ? sanitize_text_field($_REQUEST['type']) : '';
		if (! in_array($this->type, array('catalog', 'sale', 'test'), true)) {
			throw new Exception('Unexpected type: ' . $this->type);
		}

		update_option('_edi_type', $this->type, false);
	}


	protected function set_filename(): void
	{
		$this->filename = isset($_REQUEST['filename']) ? sanitize_text_field($_REQUEST['filename']) : '';
		if (
			'test' !== $this->type
			&&
			! empty($this->filename)
			&&
			! preg_match(self::XML_BITRIX_FILENAME_MASK, $this->filename)
			&&
			! preg_match(self::XML_IMPORT_FILENAME_MASK, $this->filename)
			&&
			! preg_match(self::ZIP_IMPORT_FILENAME_MASK, $this->filename)
			&&
			! preg_match(self::SALE_IMPORT_FILENAME_MASK, $this->filename)
			&&
			! preg_match(self::IMAGE_IMPORT_FILENAME_MASK, $this->filename)
			&&
			! preg_match(self::IMPORT_FILES_MASK, $this->filename)
		) {
			throw new Exception('Unexpected file name: ' . $this->filename);
		}

		update_option('_edi_filename', $this->filename, false);
	}


	protected function set_last_xml_entry(): void
	{
		try {
			$this->last_xml_entry = get_option('_edi_last_xml_entry', array());
		} catch (Throwable $e) {
			throw new Exception(__('Unexpected XML entry.', 'edi'));
		}
	}


	public function update_last_xml_entry(array $last_xml_entry): void
	{
		$this->last_xml_entry = $last_xml_entry;

		update_option('_edi_last_xml_entry', $this->last_xml_entry);
	}


	public function __get(string $name)
	{
		if (property_exists($this, $name)) {
			return $this->{$name};
		}


		throw new Exception(sprintf(__('Undefined property: %s', 'edi'), $name));
	}


	protected function is_edi_request(): bool
	{
		return self::EDI_ENDPOINT === wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	}


	public function process_request(): void
	{
		try {
			$this->maybe_interrupt();

			$this->authorize();

			$interchange = __NAMESPACE__ . '\\' . ucfirst($this->type) . 'Interchange';
			$interchange = new $interchange($this);
			$interchange->run();
		} catch (Exception $e) {
			EDI::log()->error($e->getMessage());
			exit('failure');
		}
	}


	protected function maybe_interrupt(): void
	{
		if (get_transient('edi-interrupt')) {
			$this->reset('debug' !== apply_filters('edi_logging_level', EDI_DEFAULT_LOGGING_LEVEL));

			throw new Exception(__('Synchronization was interrupted on the site side.', 'edi'));
		}
	}


	protected function authorize(): bool
	{
		if (defined('WP_CLI') && WP_CLI) {
			return true;
		}

		$username    = Settings::get_username();
		$password    = Settings::get_password();
		$username_1c = isset($_SERVER['PHP_AUTH_USER']) ? sanitize_text_field($_SERVER['PHP_AUTH_USER']) : '';
		$password_1c = isset($_SERVER['PHP_AUTH_PW']) ? sanitize_text_field($_SERVER['PHP_AUTH_PW']) : '';

		if (empty($username)) {
			throw new Exception('Empty username on site.');
		}

		if (empty($password)) {
			throw new Exception('Empty password on site.');
		}

		if (empty($username_1c)) {
			throw new Exception('Empty username');
		}

		if (empty($password_1c)) {
			throw new Exception('Empty password.');
		}

		if ($username !== $username_1c || $password !== $password_1c) {
			throw new Exception('Wrong username or password.');
		}

		return true;
	}


	public function get_status(): void
	{
		$status = array();
		if ('catalog' === $this->previous_type) {
			$status[] = __('Products synchronization', 'edi');
		} elseif ('sale' === $this->previous_type) {
			$status[] = __('Orders synchronization', 'edi');
		}

		if ('checkauth' === $this->previous_mode) {
			$status = array();
		} elseif ('init' === $this->previous_mode) {
			$status[] = __('Initialization', 'edi');
		} elseif ('file' === $this->previous_mode) {
			$status[] = __('Getting the import file', 'edi');
		} elseif ('import' === $this->previous_mode) {
			$status[] = __('Import', 'edi');
		} elseif ('query' === $this->previous_mode) {
			$status[] = __('Export orders', 'edi');
		}
		$status = implode(' > ', $status);

		if (get_transient('edi-interrupt')) {
			$status       = __('Interrupting the import process...', 'edi');
			$interrupting = true;
		} else {
			$interrupting = false;
		}

		wp_send_json_success(compact('status', 'interrupting'));
	}


	public function interrupt(): void
	{
		if (check_admin_referer('edi-interrupt')) {
			set_transient('edi-interrupt', true, 60);

			$this->reset('debug' !== apply_filters('edi_logging_level', EDI_DEFAULT_LOGGING_LEVEL));

			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}


	public function reset($clear_repository = self::CLEAR_REPOSITORY)
	{
		update_option('_edi_mode', '', false);
		update_option('_edi_type', '', false);
		update_option('_edi_filename', '', false);
		update_option('_edi_last_xml_entry', array());

		if ($clear_repository) {
			EDI::repository()->clear();
		}
	}


	public function __toString()
	{
		$string  = sprintf(
			'%-11s %-10s %-8s %s' . PHP_EOL,
			'',
			'[mode]',
			'[type]',
			'[filename]'
		);
		$string .= sprintf(
			'%-11s %-10s %-8s %s' . PHP_EOL,
			'[current]',
			$this->mode,
			$this->type,
			$this->filename
		);
		$string .= sprintf(
			'%-11s %-10s %-8s %s',
			'[previous]',
			$this->previous_mode,
			$this->previous_type,
			$this->previous_filename
		);

		return $string;
	}
}
