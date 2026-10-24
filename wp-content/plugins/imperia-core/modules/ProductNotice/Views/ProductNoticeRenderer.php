<?php

declare(strict_types=1);

namespace Imperia\Modules\ProductNotice\Views;

/**
 * Формирует HTML уведомления с важной информацией о товаре.
 *
 * Класс не регистрирует хуки и не подключает CSS.
 * Его единственная задача — вывести разметку блока.
 */
final class ProductNoticeRenderer
{
	/**
	 * Выводит информационный блок.
	 *
	 * Разметка использует:
	 * - section — самостоятельный тематический раздел;
	 * - aria-labelledby — связь блока с его заголовком;
	 * - h2 — заголовок раздела на странице товара;
	 * - p — отдельные предупреждения.
	 */
	public function render(): void
	{
?>
		<section
			class="imperia-product-notice"
			aria-labelledby="imperia-product-notice-title">
			<h2
				class="imperia-product-notice__title"
				id="imperia-product-notice-title">
				<?php
				echo esc_html__(
					'Важная информация о товаре',
					'imperia-core'
				);
				?>
			</h2>

			<div class="imperia-product-notice__content">
				<p class="imperia-product-notice__item">
					<?php
					echo esc_html__(
						'Цена на товар может отличаться от указанной на сайте. Для получения актуальной информации о стоимости и наличии товара свяжитесь с нами.',
						'imperia-core'
					);
					?>
				</p>

				<p class="imperia-product-notice__item">
					<?php
					echo esc_html__(
						'Реальный внешний вид и технические характеристики товара могут отличаться от представленных на сайте. Уточняйте важные для вас параметры товара у наших продавцов.',
						'imperia-core'
					);
					?>
				</p>
			</div>
		</section>
<?php
	}
}
