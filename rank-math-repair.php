<?php

require_once __DIR__ . '/wp-load.php';

global $wpdb;

$backup_table = $wpdb->prefix . 'rank_math_backup_20260816';
$options_table = $wpdb->options;

header('Content-Type: text/plain; charset=UTF-8');

echo "Rank Math FINAL REPAIR\n";
echo "======================\n\n";


/**
 * Проверяем существование таблицы.
 */
$table_exists = $wpdb->get_var(
	$wpdb->prepare(
		"SHOW TABLES LIKE %s",
		$backup_table
	)
);

if ($table_exists !== $backup_table) {
	echo "ERROR: Backup table not found.\n";
	exit;
}

echo "Backup table: {$backup_table}\n";
echo "Options table: {$options_table}\n\n";


/**
 * Получаем резервные записи.
 */
$rows = $wpdb->get_results(
	"SELECT option_id, option_name, option_value, autoload
     FROM {$backup_table}
     WHERE option_id IN (1303, 1304)
     ORDER BY option_id"
);

if (count($rows) !== 2) {
	echo "ERROR: Expected exactly 2 backup records.\n";
	echo "Found: " . count($rows) . "\n";
	exit;
}

echo "Backup records: OK\n\n";


/**
 * Исправляем только известные повреждения.
 */
function repair_rank_math_serialization(
	string $serialized,
	int $option_id
): string {

	if ($option_id === 1303) {

		$replacements = [
			's:29:"https://imperia-pola-dnr.ru"'
			=> 's:27:"https://imperia-pola-dnr.ru"',
		];
	} elseif ($option_id === 1304) {

		$replacements = [
			's:86:"https://imperia-pola-dnr.ru/wp-content/uploads/2026/06/logotip-imperiya-pola-min.jpg"'
			=> 's:84:"https://imperia-pola-dnr.ru/wp-content/uploads/2026/06/logotip-imperiya-pola-min.jpg"',

			's:73:"https://imperia-pola-dnr.ru/wp-content/uploads/2026/06/imperiya-min.jpg"'
			=> 's:71:"https://imperia-pola-dnr.ru/wp-content/uploads/2026/06/imperiya-min.jpg"',

			's:29:"https://imperia-pola-dnr.ru"'
			=> 's:27:"https://imperia-pola-dnr.ru"',
		];
	} else {

		throw new RuntimeException(
			"Unexpected option_id: {$option_id}"
		);
	}

	foreach ($replacements as $old => $new) {

		$count = substr_count($serialized, $old);

		if ($count !== 1) {

			throw new RuntimeException(
				"Unexpected occurrence count for:\n{$old}\nFound: {$count}"
			);
		}

		$serialized = str_replace(
			$old,
			$new,
			$serialized
		);
	}

	return $serialized;
}


/**
 * Подготавливаем восстановленные значения.
 */
$prepared = [];

foreach ($rows as $row) {

	echo "OPTION {$row->option_id}\n";
	echo "Name: {$row->option_name}\n";
	echo "Original length: " . strlen($row->option_value) . "\n";

	/*
     * Проверяем ожидаемое количество элементов.
     */
	$expected_elements = [
		1303 => 47,
		1304 => 160,
	];

	/*
     * Исправляем сериализацию.
     */
	try {

		$repaired = repair_rank_math_serialization(
			$row->option_value,
			(int) $row->option_id
		);
	} catch (Throwable $e) {

		echo "ERROR during repair preparation:\n";
		echo $e->getMessage() . "\n";
		echo "\nDATABASE WAS NOT MODIFIED.\n";
		exit;
	}

	echo "Length correction: OK\n";

	/*
     * Проверяем unserialize().
     */
	$value = @unserialize($repaired);

	if ($value === false && $repaired !== 'b:0;') {

		echo "ERROR: repaired value failed unserialize().\n";
		echo "\nDATABASE WAS NOT MODIFIED.\n";
		exit;
	}

	echo "Unserialize: OK\n";

	/*
     * Проверяем, что получили массив.
     */
	if (!is_array($value)) {

		echo "ERROR: result is not an array.\n";
		echo "\nDATABASE WAS NOT MODIFIED.\n";
		exit;
	}

	echo "Array elements: " . count($value) . "\n";

	/*
     * Проверяем ожидаемое количество элементов.
     */
	if (count($value) !== $expected_elements[$row->option_id]) {

		echo "ERROR: unexpected array element count.\n";
		echo "Expected: {$expected_elements[$row->option_id]}\n";
		echo "Actual: " . count($value) . "\n";
		echo "\nDATABASE WAS NOT MODIFIED.\n";
		exit;
	}

	echo "Array structure: OK\n";

	/*
     * Полностью пересериализуем массив PHP.
     */
	$normalized = serialize($value);

	echo "Normalized length: " . strlen($normalized) . "\n";

	/*
     * Финальная проверка сериализации.
     */
	$final_test = @unserialize($normalized);

	if ($final_test === false && $normalized !== 'b:0;') {

		echo "ERROR: normalized value failed final unserialize().\n";
		echo "\nDATABASE WAS NOT MODIFIED.\n";
		exit;
	}

	if (!is_array($final_test)) {

		echo "ERROR: normalized result is not an array.\n";
		echo "\nDATABASE WAS NOT MODIFIED.\n";
		exit;
	}

	if (count($final_test) !== $expected_elements[$row->option_id]) {

		echo "ERROR: final array element count changed.\n";
		echo "\nDATABASE WAS NOT MODIFIED.\n";
		exit;
	}

	echo "Final serialization: OK\n";
	echo "Final array integrity: OK\n\n";

	/*
     * Сохраняем подготовленное значение
     * только в PHP-памяти.
     */
	$prepared[$row->option_id] = [
		'option_name'     => $row->option_name,
		'option_value'    => $normalized,
		'autoload'        => $row->autoload,
		'array_elements'  => count($value),
	];
}


echo "===================================\n";
echo "ALL PRE-UPDATE CHECKS PASSED\n";
echo "===================================\n\n";


/**
 * Теперь начинаем транзакцию.
 */
$transaction_started = $wpdb->query('START TRANSACTION');

if ($transaction_started === false) {

	echo "ERROR: Could not start transaction.\n";
	echo "\nDATABASE WAS NOT MODIFIED.\n";
	exit;
}

echo "Transaction: STARTED\n\n";


try {

	foreach ($prepared as $option_id => $data) {

		echo "Updating option_id {$option_id}...\n";

		/*
         * UPDATE только по конкретному option_id.
         */
		$updated = $wpdb->update(
			$options_table,
			[
				'option_name'  => $data['option_name'],
				'option_value' => $data['option_value'],
				'autoload'     => $data['autoload'],
			],
			[
				'option_id' => $option_id,
			],
			[
				'%s',
				'%s',
				'%s',
			],
			[
				'%d',
			]
		);

		if ($updated === false) {

			throw new RuntimeException(
				"UPDATE failed for option_id {$option_id}: " .
					$wpdb->last_error
			);
		}

		echo "UPDATE: OK\n";
	}


	/**
	 * Проверяем записанные данные ДО COMMIT.
	 */
	echo "\nVerifying written data...\n\n";

	foreach ($prepared as $option_id => $data) {

		$written = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT option_id, option_name, option_value, autoload
                 FROM {$options_table}
                 WHERE option_id = %d",
				$option_id
			)
		);

		if (!$written) {

			throw new RuntimeException(
				"Written record not found for option_id {$option_id}"
			);
		}

		/*
         * Проверяем option_name.
         */
		if ($written->option_name !== $data['option_name']) {

			throw new RuntimeException(
				"option_name verification failed for {$option_id}"
			);
		}

		/*
         * Проверяем autoload.
         */
		if ($written->autoload !== $data['autoload']) {

			throw new RuntimeException(
				"autoload verification failed for {$option_id}"
			);
		}

		/*
         * Проверяем сериализацию записанного значения.
         */
		$written_value = @unserialize($written->option_value);

		if ($written_value === false && $written->option_value !== 'b:0;') {

			throw new RuntimeException(
				"Written value failed unserialize() for {$option_id}"
			);
		}

		if (!is_array($written_value)) {

			throw new RuntimeException(
				"Written value is not an array for {$option_id}"
			);
		}

		if (count($written_value) !== $data['array_elements']) {

			throw new RuntimeException(
				"Written array integrity check failed for {$option_id}"
			);
		}

		/*
         * Проверяем длину.
         */
		if (strlen($written->option_value) !== strlen($data['option_value'])) {

			throw new RuntimeException(
				"Written serialized length mismatch for {$option_id}"
			);
		}

		echo "option_id {$option_id}: WRITE VERIFICATION OK\n";
	}


	/**
	 * Все проверки прошли.
	 */
	$committed = $wpdb->query('COMMIT');

	if ($committed === false) {

		throw new RuntimeException(
			"COMMIT failed: " . $wpdb->last_error
		);
	}

	echo "\n===================================\n";
	echo "REPAIR COMPLETED SUCCESSFULLY\n";
	echo "===================================\n\n";

	echo "Updated options:\n";
	echo "1303 - rank-math-options-general\n";
	echo "1304 - rank-math-options-titles\n\n";

	echo "1305 and 1306 were NOT modified.\n";
} catch (Throwable $e) {

	/*
     * При любой ошибке откатываем транзакцию.
     */
	$wpdb->query('ROLLBACK');

	echo "\n===================================\n";
	echo "REPAIR FAILED\n";
	echo "===================================\n\n";

	echo "ERROR:\n";
	echo $e->getMessage() . "\n\n";

	echo "TRANSACTION ROLLED BACK.\n";
	echo "DATABASE CHANGES WERE NOT COMMITTED.\n";
}
