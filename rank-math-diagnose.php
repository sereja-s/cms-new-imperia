<?php

require_once __DIR__ . '/wp-load.php';

global $wpdb;

$table = $wpdb->prefix . 'rank_math_backup_20260816';

$rows = $wpdb->get_results(
	"SELECT option_id, option_name, option_value
     FROM {$table}
     WHERE option_id IN (1303, 1304)
     ORDER BY option_id"
);

header('Content-Type: text/plain; charset=UTF-8');

echo "Rank Math serialization diagnostic\n";
echo "===================================\n\n";

if (!$rows) {
	echo "ERROR: backup table or records not found.\n";
	exit;
}

/**
 * Проверяем строковые элементы вида:
 *
 * s:29:"текст"
 *
 * и сравниваем заявленную длину
 * с фактической длиной строки в байтах.
 */
function check_serialized_string_lengths(string $serialized): array
{
	$errors = [];

	$pattern = '/s:(\d+):"(.*?)"/s';

	if (preg_match_all($pattern, $serialized, $matches, PREG_OFFSET_CAPTURE)) {

		foreach ($matches[1] as $index => $lengthMatch) {

			$declaredLength = (int) $lengthMatch[0];
			$fullMatchOffset = $matches[0][$index][1];

			$stringValue = $matches[2][$index][0];

			$actualLength = strlen($stringValue);

			if ($declaredLength !== $actualLength) {

				$errors[] = [
					'declared' => $declaredLength,
					'actual'   => $actualLength,
					'value'    => $stringValue,
					'offset'   => $fullMatchOffset,
				];
			}
		}
	}

	return $errors;
}

foreach ($rows as $row) {

	echo "option_id: {$row->option_id}\n";
	echo "option_name: {$row->option_name}\n";
	echo "length: " . strlen($row->option_value) . "\n";
	echo "-----------------------------------\n";

	/*
     * 1. Проверяем обычный unserialize()
     */
	$value = @unserialize($row->option_value);

	if ($value === false && $row->option_value !== 'b:0;') {
		echo "unserialize(): ERROR\n";
	} else {
		echo "unserialize(): OK\n";
	}

	echo "\n";

	/*
     * 2. Ищем неправильные длины строк
     */
	$errors = check_serialized_string_lengths($row->option_value);

	if (!$errors) {

		echo "String length check: OK\n";
	} else {

		echo "String length check: FOUND " . count($errors) . " ERROR(S)\n\n";

		foreach ($errors as $number => $error) {

			echo "ERROR #" . ($number + 1) . "\n";
			echo "Declared length: {$error['declared']}\n";
			echo "Actual length:   {$error['actual']}\n";
			echo "Offset:           {$error['offset']}\n";
			echo "Value:            {$error['value']}\n";
			echo "\n";
		}
	}

	echo "\n=============================\n\n";
}
