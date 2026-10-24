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

echo "Rank Math repair preparation\n";
echo "============================\n\n";

if (!$rows) {
	echo "ERROR: backup table or records not found.\n";
	exit;
}


/**
 * Исправляем только те четыре повреждённые
 * длины строк, которые были обнаружены диагностикой.
 *
 * Никаких других замен не выполняем.
 */
function repair_rank_math_serialization(string $serialized): string
{
	$replacements = [
		's:29:"https://imperia-pola-dnr.ru"'
		=> 's:27:"https://imperia-pola-dnr.ru"',

		's:86:"https://imperia-pola-dnr.ru/wp-content/uploads/2026/06/logotip-imperiya-pola-min.jpg"'
		=> 's:84:"https://imperia-pola-dnr.ru/wp-content/uploads/2026/06/logotip-imperiya-pola-min.jpg"',

		's:73:"https://imperia-pola-dnr.ru/wp-content/uploads/2026/06/imperiya-min.jpg"'
		=> 's:71:"https://imperia-pola-dnr.ru/wp-content/uploads/2026/06/imperiya-min.jpg"',
	];

	return str_replace(
		array_keys($replacements),
		array_values($replacements),
		$serialized
	);
}


foreach ($rows as $row) {

	echo "OPTION {$row->option_id}\n";
	echo "Name: {$row->option_name}\n";
	echo "Original length: " . strlen($row->option_value) . "\n";
	echo "-----------------------------------\n";

	/*
     * Исходное значение.
     */
	$original = $row->option_value;

	/*
     * Проверяем исходную сериализацию.
     */
	$originalValue = @unserialize($original);

	if ($originalValue === false && $original !== 'b:0;') {
		echo "Original unserialize: ERROR\n";
	} else {
		echo "Original unserialize: OK\n";
	}

	/*
     * Исправляем только обнаруженные ошибки.
     */
	$repaired = repair_rank_math_serialization($original);

	/*
     * Проверяем, изменилось ли значение.
     */
	if ($repaired === $original) {

		echo "Repair changes: NONE\n";
	} else {

		echo "Repair changes: YES\n";
		echo "Repaired serialized length: " . strlen($repaired) . "\n";
	}

	/*
     * Проверяем исправленную сериализацию.
     */
	$value = @unserialize($repaired);

	if ($value === false && $repaired !== 'b:0;') {

		echo "Repaired unserialize: ERROR\n";
		echo "!!! STOP !!!\n";
		echo "The repaired value is still invalid.\n";

		echo "\n============================\n\n";

		continue;
	}

	echo "Repaired unserialize: OK\n";

	/*
     * Проверяем, что получили массив.
     */
	if (is_array($value)) {

		echo "Result type: array\n";
		echo "Array elements: " . count($value) . "\n";
	} else {

		echo "Result type: " . gettype($value) . "\n";
	}

	/*
     * Теперь выполняем настоящий PHP serialize().
     *
     * Это полностью пересобирает сериализованную строку
     * с корректными длинами всех элементов.
     */
	$normalized = serialize($value);

	echo "Normalized serialized length: " . strlen($normalized) . "\n";

	/*
     * Финальная проверка.
     */
	$finalValue = @unserialize($normalized);

	if ($finalValue === false && $normalized !== 'b:0;') {

		echo "Final verification: ERROR\n";
		echo "!!! STOP !!!\n";
	} else {

		echo "Final verification: OK\n";

		if (is_array($finalValue)) {
			echo "Final array elements: " . count($finalValue) . "\n";
		}
	}

	/*
     * Сравниваем количество элементов массива
     * до и после нормализации.
     */
	if (is_array($value) && is_array($finalValue)) {

		if (count($value) === count($finalValue)) {

			echo "Array integrity check: OK\n";
		} else {

			echo "Array integrity check: ERROR\n";
			echo "Before: " . count($value) . "\n";
			echo "After:  " . count($finalValue) . "\n";
		}
	}

	echo "\n============================\n\n";
}

echo "DATABASE WAS NOT MODIFIED.\n";
echo "No INSERT / UPDATE / DELETE was executed.\n";
