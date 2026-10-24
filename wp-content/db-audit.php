<?php

/**
 * Temporary read-only WordPress DB serialized-data auditor.
 *
 * IMPORTANT:
 * - Does NOT modify the database.
 * - Does NOT call update_option(), update_post_meta(), etc.
 * - Does NOT require Rank Math.
 * - Does NOT use WordPress authentication functions.
 *
 * DELETE THIS FILE AFTER AUDIT.
 */

declare(strict_types=1);

/*
 * Security:
 * Replace this token with your own random string.
 */
const DB_AUDIT_TOKEN = 'ImperiaAudit_2026_X7pQ92LmK4vR8tZ1';

/*
 * Safety limits.
 */
const MAX_VALUE_PREVIEW = 500;
const MAX_ERROR_CONTEXT = 180;
const MAX_ROWS_PER_TABLE = 20000;

/*
 * Allowed table suffixes.
 *
 * We deliberately start with the important WordPress tables rather than
 * blindly scanning every table in the database.
 */
$allowed_tables = [
	'options',
	'postmeta',
	'termmeta',
	'usermeta',
	'commentmeta',
	'posts',
	'terms',
	'term_taxonomy',
	'term_relationships',
	'woocommerce_attribute_taxonomies',
];

/*
 * Token check BEFORE loading WordPress.
 */
$token = isset($_GET['token']) ? (string) $_GET['token'] : '';

if (!hash_equals(DB_AUDIT_TOKEN, $token)) {
	http_response_code(403);
	exit('Forbidden');
}

/*
 * Load WordPress.
 *
 * This is intentionally done here rather than as a mu-plugin.
 */
$wp_load = dirname(__DIR__) . '/wp-load.php';

if (!is_file($wp_load)) {
	http_response_code(500);
	exit('wp-load.php not found');
}

require_once $wp_load;

/*
 * HTML helpers.
 */
function audit_h(mixed $value): string
{
	return htmlspecialchars(
		(string) $value,
		ENT_QUOTES | ENT_SUBSTITUTE,
		'UTF-8'
	);
}

function audit_preview(string $value, int $limit = MAX_VALUE_PREVIEW): string
{
	if (strlen($value) <= $limit) {
		return $value;
	}

	return substr($value, 0, $limit) . "\n...[TRUNCATED]";
}

/**
 * Test whether a value looks like PHP serialized data.
 *
 * We intentionally use a non-invasive check.
 */
function audit_looks_serialized(string $value): bool
{
	$value = trim($value);

	if ($value === '') {
		return false;
	}

	if ($value === 'N;') {
		return true;
	}

	if (preg_match('/^[aOsibdC]:/', $value)) {
		return true;
	}

	return false;
}

/**
 * Safely test unserialization.
 *
 * We suppress the PHP warning and capture it explicitly.
 */
function audit_unserialize(string $value): array
{
	$error = null;

	set_error_handler(
		static function (
			int $severity,
			string $message,
			string $file,
			int $line
		) use (&$error): bool {
			$error = [
				'severity' => $severity,
				'message'  => $message,
				'file'     => $file,
				'line'     => $line,
			];

			return true;
		}
	);

	try {
		$result = unserialize($value, [
			'allowed_classes' => false,
		]);
	} catch (Throwable $e) {
		$error = [
			'severity' => E_ERROR,
			'message'  => $e->getMessage(),
			'file'     => $e->getFile(),
			'line'     => $e->getLine(),
		];

		$result = false;
	} finally {
		restore_error_handler();
	}

	return [
		'ok'     => $error === null,
		'result' => $result,
		'error'  => $error,
	];
}

/**
 * Find context around an offset.
 */
function audit_context(string $value, ?int $offset): string
{
	if ($offset === null || $offset < 0) {
		return '';
	}

	$start = max(0, $offset - MAX_ERROR_CONTEXT);
	$length = MAX_ERROR_CONTEXT * 2;

	return substr($value, $start, $length);
}

/*
 * Database.
 */
global $wpdb;

$prefix = $wpdb->prefix;

/*
 * Resolve existing tables safely.
 */
$tables = [];

foreach ($allowed_tables as $suffix) {
	$table = $prefix . $suffix;

	if ($wpdb->get_var(
		$wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table
		)
	) === $table) {
		$tables[$suffix] = $table;
	}
}

/*
 * Audit counters.
 */
$total_values = 0;
$serialized_values = 0;
$corrupted_values = 0;
$old_domain_values = 0;

$results = [];

foreach ($tables as $suffix => $table) {

	/*
     * Determine columns.
     */
	$columns = $wpdb->get_results(
		"SHOW COLUMNS FROM `{$table}`",
		ARRAY_A
	);

	if (!is_array($columns)) {
		continue;
	}

	$column_names = [];

	foreach ($columns as $column) {
		if (
			isset($column['Field']) &&
			preg_match('/^[A-Za-z0-9_]+$/', $column['Field'])
		) {
			$column_names[] = $column['Field'];
		}
	}

	/*
     * We only inspect text-like columns.
     */
	$text_columns = [];

	foreach ($columns as $column) {
		$type = strtolower((string) ($column['Type'] ?? ''));

		if (
			str_contains($type, 'char') ||
			str_contains($type, 'text') ||
			str_contains($type, 'blob')
		) {
			$field = (string) ($column['Field'] ?? '');

			if (
				$field !== '' &&
				in_array($field, $column_names, true)
			) {
				$text_columns[] = $field;
			}
		}
	}

	if (!$text_columns) {
		continue;
	}

	/*
     * Build SELECT.
     *
     * We avoid SELECT * and only retrieve textual columns.
     */
	$select = [];

	foreach ($text_columns as $column) {
		$select[] = "`{$column}`";
	}

	$select_sql = implode(', ', $select);

	/*
     * Determine a useful identifier.
     */
	$id_column = null;

	foreach (['option_id', 'post_id', 'term_id', 'umeta_id', 'comment_id', 'ID'] as $candidate) {
		foreach ($column_names as $column_name) {
			if ($column_name === $candidate) {
				$id_column = $candidate;
				break 2;
			}
		}
	}

	if ($id_column !== null) {
		$select_sql = "`{$id_column}`, {$select_sql}";
	}

	$sql = "SELECT {$select_sql} FROM `{$table}` LIMIT " . MAX_ROWS_PER_TABLE;

	$rows = $wpdb->get_results($sql, ARRAY_A);

	if (!is_array($rows)) {
		continue;
	}

	foreach ($rows as $row_index => $row) {

		foreach ($text_columns as $column) {

			if (!array_key_exists($column, $row)) {
				continue;
			}

			$value = $row[$column];

			if (!is_string($value) || $value === '') {
				continue;
			}

			$total_values++;

			/*
             * Check old local domain.
             */
			if (str_contains($value, 'cms-new-imperia.local')) {

				$old_domain_values++;

				$results[] = [
					'type'    => 'OLD_DOMAIN',
					'table'   => $table,
					'suffix'  => $suffix,
					'column'  => $column,
					'id'      => $id_column !== null
						? ($row[$id_column] ?? '')
						: $row_index,
					'length'  => strlen($value),
					'offset'  => strpos($value, 'cms-new-imperia.local'),
					'preview' => audit_preview($value),
				];
			}

			/*
             * Serialized-data audit.
             */
			if (!audit_looks_serialized($value)) {
				continue;
			}

			$serialized_values++;

			$test = audit_unserialize($value);

			if ($test['ok']) {
				continue;
			}

			$corrupted_values++;

			$error_message = (string) (
				$test['error']['message'] ?? 'Unknown unserialize error'
			);

			$error_offset = null;

			if (
				preg_match(
					'/offset\s+(\d+)/i',
					$error_message,
					$matches
				)
			) {
				$error_offset = (int) $matches[1];
			}

			$results[] = [
				'type'    => 'CORRUPTED_SERIALIZED',
				'table'   => $table,
				'suffix'  => $suffix,
				'column'  => $column,
				'id'      => $id_column !== null
					? ($row[$id_column] ?? '')
					: $row_index,
				'length'  => strlen($value),
				'offset'  => $error_offset,
				'error'   => $error_message,
				'context' => audit_context($value, $error_offset),
				'preview' => audit_preview($value),
			];
		}
	}
}

/*
 * Rank Math specific options.
 */
$rank_math_options = [];

$rank_math_names = [
	'rank-math-options-titles',
	'rank-math-options-general',
	'rank-math-options-sitemap',
];

foreach ($rank_math_names as $option_name) {

	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT option_id, option_name, option_value
             FROM `{$prefix}options`
             WHERE option_name = %s
             LIMIT 1",
			$option_name
		),
		ARRAY_A
	);

	if (!$row) {
		continue;
	}

	$value = (string) $row['option_value'];

	$test = audit_unserialize($value);

	$rank_math_options[] = [
		'name'      => $option_name,
		'option_id' => $row['option_id'],
		'length'    => strlen($value),
		'ok'        => $test['ok'],
		'error'     => $test['error']['message'] ?? null,
	];
}

?>
<!doctype html>
<html lang="ru">

<head>
	<meta charset="utf-8">
	<title>WordPress DB Audit</title>

	<style>
		body {
			font-family: Arial, sans-serif;
			margin: 30px;
			line-height: 1.45;
		}

		h1,
		h2 {
			margin-top: 30px;
		}

		.ok {
			color: #087f23;
			font-weight: bold;
		}

		.bad {
			color: #b00020;
			font-weight: bold;
		}

		.warn {
			color: #9a6700;
			font-weight: bold;
		}

		table {
			border-collapse: collapse;
			width: 100%;
			margin-top: 15px;
		}

		th,
		td {
			border: 1px solid #ccc;
			padding: 8px;
			vertical-align: top;
			text-align: left;
		}

		th {
			background: #eee;
		}

		pre {
			white-space: pre-wrap;
			word-break: break-word;
			max-width: 100%;
			background: #f6f6f6;
			padding: 10px;
		}

		.summary {
			padding: 15px;
			background: #f5f5f5;
			border: 1px solid #ddd;
		}
	</style>
</head>

<body>

	<h1>WordPress DB Audit — READ ONLY</h1>

	<div class="summary">

		<p>
			<strong>Database:</strong>
			<?= audit_h($wpdb->dbname) ?>
		</p>

		<p>
			<strong>Prefix:</strong>
			<?= audit_h($wpdb->prefix) ?>
		</p>

		<p>
			<strong>Values inspected:</strong>
			<?= audit_h($total_values) ?>
		</p>

		<p>
			<strong>Serialized values:</strong>
			<?= audit_h($serialized_values) ?>
		</p>

		<p>
			<strong>Corrupted serialized values:</strong>

			<?php if ($corrupted_values > 0): ?>
				<span class="bad">
					<?= audit_h($corrupted_values) ?>
				</span>
			<?php else: ?>
				<span class="ok">0</span>
			<?php endif; ?>

		</p>

		<p>
			<strong>Values containing old local domain:</strong>

			<?php if ($old_domain_values > 0): ?>
				<span class="warn">
					<?= audit_h($old_domain_values) ?>
				</span>
			<?php else: ?>
				<span class="ok">0</span>
			<?php endif; ?>

		</p>

	</div>

	<h2>Rank Math options</h2>

	<table>
		<tr>
			<th>Option</th>
			<th>ID</th>
			<th>Length</th>
			<th>Unserialize</th>
			<th>Error</th>
		</tr>

		<?php foreach ($rank_math_options as $item): ?>

			<tr>
				<td><?= audit_h($item['name']) ?></td>
				<td><?= audit_h($item['option_id']) ?></td>
				<td><?= audit_h($item['length']) ?></td>

				<td>
					<?php if ($item['ok']): ?>
						<span class="ok">OK</span>
					<?php else: ?>
						<span class="bad">CORRUPTED</span>
					<?php endif; ?>
				</td>

				<td>
					<?= audit_h($item['error'] ?? '') ?>
				</td>
			</tr>

		<?php endforeach; ?>

	</table>

	<h2>Problems found</h2>

	<?php if (!$results): ?>

		<p class="ok">
			No corrupted serialized values or old local-domain values found
			in the inspected tables.
		</p>

	<?php else: ?>

		<table>

			<tr>
				<th>Type</th>
				<th>Table</th>
				<th>Column</th>
				<th>ID</th>
				<th>Length</th>
				<th>Offset</th>
				<th>Error</th>
				<th>Context / Preview</th>
			</tr>

			<?php foreach ($results as $item): ?>

				<tr>

					<td>
						<?php if ($item['type'] === 'CORRUPTED_SERIALIZED'): ?>
							<span class="bad">
								CORRUPTED_SERIALIZED
							</span>
						<?php else: ?>
							<span class="warn">
								OLD_DOMAIN
							</span>
						<?php endif; ?>
					</td>

					<td>
						<?= audit_h($item['table']) ?>
					</td>

					<td>
						<?= audit_h($item['column']) ?>
					</td>

					<td>
						<?= audit_h($item['id']) ?>
					</td>

					<td>
						<?= audit_h($item['length']) ?>
					</td>

					<td>
						<?= audit_h($item['offset'] ?? '') ?>
					</td>

					<td>
						<?= audit_h($item['error'] ?? '') ?>
					</td>

					<td>

						<?php if (isset($item['context'])): ?>

							<strong>Context:</strong>

							<pre><?= audit_h($item['context']) ?></pre>

						<?php endif; ?>

						<strong>Preview:</strong>

						<pre><?= audit_h($item['preview']) ?></pre>

					</td>

				</tr>

			<?php endforeach; ?>

		</table>

	<?php endif; ?>

	<hr>

	<p>
		<strong>IMPORTANT:</strong>
		This script is READ ONLY and must be deleted after the audit.
	</p>

</body>

</html>