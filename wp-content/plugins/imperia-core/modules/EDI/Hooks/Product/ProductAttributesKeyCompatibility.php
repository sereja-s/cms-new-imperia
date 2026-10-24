<?php

declare(strict_types=1);

namespace Imperia\Modules\EDI\Hooks\Product;

use Throwable;
use WC_Product;
use WC_Product_Attribute;

final class ProductAttributesKeyCompatibility
{
	/**
	 * Register hooks.
	 *
	 * Priority 5 is intentional:
	 * ProductAttributesParser::process() runs at priority 10.
	 *
	 * We normalize the existing product attributes before
	 * ProductAttributesParser rebuilds the attribute collection.
	 */
	public function register(): void
	{
		try {
			add_action(
				'edi_product_before_save',
				[$this, 'normalize'],
				5,
				2
			);
		} catch (Throwable $e) {
			$this->logError(
				'register',
				null,
				$e
			);
		}
	}

	/**
	 * Normalize existing product attribute array keys.
	 *
	 * Important:
	 * WooCommerce WC_Product::set_attributes() internally uses:
	 *
	 *     sanitize_title($attribute->get_name())
	 *
	 * as the array key.
	 *
	 * Therefore existing attributes must use exactly the same key
	 * before ProductAttributesParser calls set_attributes().
	 *
	 * @param WC_Product $product
	 * @param array      $productData
	 */
	public function normalize(
		WC_Product &$product,
		array &$productData
	): void {
		try {
			$currentAttributes = $product->get_attributes('edit');

			/*
             * Fast path:
             * nothing to normalize.
             */
			if (empty($currentAttributes)) {
				return;
			}

			$normalizedAttributes = [];
			$changed = false;

			foreach ($currentAttributes as $currentKey => $attribute) {

				/*
                 * Stale/non-object entries must not participate
                 * in the normalized collection.
                 *
                 * In particular, this removes NULL entries that
                 * can remain after WooCommerce's set_attributes()
                 * has encountered an old/incompatible key.
                 */
				if (! $attribute instanceof WC_Product_Attribute) {
					$changed = true;
					continue;
				}

				$attributeName = $attribute->get_name();

				/*
                 * Invalid attribute name.
                 *
                 * Do not try to invent a key.
                 */
				if (! is_string($attributeName) || $attributeName === '') {
					$changed = true;
					continue;
				}

				/*
                 * This is exactly the key WooCommerce itself will
                 * generate inside WC_Product::set_attributes().
                 */
				$normalizedKey = sanitize_title($attributeName);

				/*
                 * If WooCommerce cannot produce a valid key,
                 * leave this attribute out of the rebuilt collection.
                 */
				if ($normalizedKey === '') {
					$changed = true;
					continue;
				}

				/*
                 * Collision protection.
                 *
                 * Two different existing attributes can theoretically
                 * normalize to the same key.
                 *
                 * Silently overwriting one would be worse than leaving
                 * the product untouched, so abort normalization for this
                 * product and log the problem.
                 */
				if (
					isset($normalizedAttributes[$normalizedKey]) &&
					$normalizedAttributes[$normalizedKey] !== $attribute
				) {
					$this->log(
						'normalization skipped: key collision' .
							' | product_id=' . $product->get_id() .
							' | key=' . $normalizedKey
					);

					return;
				}

				$normalizedAttributes[$normalizedKey] = $attribute;

				/*
                 * The existing key already matches the key WooCommerce
                 * expects — nothing needs to be changed for this item.
                 */
				if ((string) $currentKey === $normalizedKey) {
					continue;
				}

				$changed = true;
			}

			/*
             * Fast path:
             * all existing keys are already compatible.
             *
             * No set_attributes(), no additional processing.
             */
			if (! $changed) {
				return;
			}

			/*
             * Rebuild through the public WooCommerce API.
             *
             * We intentionally pass only attribute objects.
             * WooCommerce will recreate its internal keyed array
             * using sanitize_title($attribute->get_name()).
             */
			$product->set_attributes(
				array_values($normalizedAttributes)
			);
		} catch (Throwable $e) {
			/*
             * Absolute requirement:
             * our compatibility layer must never break the EDI exchange.
             *
             * Any Throwable is logged and swallowed.
             */
			$this->logError(
				'normalize',
				$product,
				$e
			);

			return;
		}
	}

	/**
	 * Write diagnostic/error message to debug.log.
	 *
	 * Successful operations are intentionally NOT logged.
	 */
	private function log(string $message): void
	{
		try {
			imperia_log(
				'[ProductAttributesKeyCompatibility] ' . $message
			);
		} catch (Throwable $e) {
			/*
             * Logging must never become a reason for the exchange
             * to fail.
             */
			return;
		}
	}

	/**
	 * Safely log an exception/error.
	 */
	private function logError(
		string $stage,
		?WC_Product $product,
		Throwable $e
	): void {
		try {
			$productId = 'unknown';
			$sku = 'unknown';

			if ($product instanceof WC_Product) {
				try {
					$productId = (string) $product->get_id();
				} catch (Throwable $ignored) {
					$productId = 'unknown';
				}

				try {
					$sku = (string) $product->get_sku();
				} catch (Throwable $ignored) {
					$sku = 'unknown';
				}
			}

			imperia_log(
				'[ProductAttributesKeyCompatibility] ERROR' .
					' | stage=' . $stage .
					' | product_id=' . $productId .
					' | sku=' . $sku .
					' | message=' . $e->getMessage() .
					' | file=' . $e->getFile() .
					' | line=' . $e->getLine()
			);
		} catch (Throwable $ignored) {
			/*
             * Even a failure of imperia_log() must never propagate.
             */
			return;
		}
	}
}
