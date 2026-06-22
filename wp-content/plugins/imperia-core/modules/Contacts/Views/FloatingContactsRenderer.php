<?php

namespace Imperia\Modules\Contacts\Views;

/**
 * ==========================================================
 * FLOATING CONTACTS RENDERER
 * ==========================================================
 *
 * Формирует HTML плавающих контактов.
 *
 * Получает:
 *
 * - готовый массив контактов;
 * - ContactIconRenderer.
 *
 * Не знает:
 *
 * - Repository;
 * - Cache;
 * - Manager;
 * - WordPress Hooks.
 *
 * ==========================================================
 */
final class FloatingContactsRenderer
{
	/**
	 * SVG renderer.
	 */
	private ContactIconRenderer $icons;



	/**
	 * Constructor.
	 */
	public function __construct(
		ContactIconRenderer $icons
	) {
		$this->icons = $icons;
	}



	/**
	 * Генерация HTML.
	 *
	 * @param array $contacts
	 *
	 * @return string
	 */
	public function render(
		array $contacts
	): string {

		ob_start();

?>

		<div class="imperia-floating-contacts">

			<div class="imperia-contact-list">

				<?php foreach ($contacts as $contact) :

					$title =
						esc_attr(
							$contact['title']
								?? ''
						);

					$value =
						esc_attr(
							$contact['value']
								?? ''
						);

					$type =
						esc_attr(
							$contact['type']
								?? ''
						);

					$isExternal =
						str_starts_with(
							$value,
							'http'
						);

				?>

					<a
						class="
						imperia-contact-item
						imperia-contact-<?php echo $type; ?>
						"
						href="<?php echo $value; ?>"
						aria-label="<?php echo $title; ?>"

						<?php if ($isExternal) : ?>

						target="_blank"
						rel="noopener noreferrer"

						<?php endif; ?>>

						<?php
						echo $this->icons->render(
							$type
						);
						?>

					</a>

				<?php endforeach; ?>

			</div>

			<button
				type="button"
				class="imperia-contact-button"
				aria-label="<?php echo esc_attr__(
									'Связаться с нами',
									'imperia-core'
								); ?>">

				<span class="icon-contact">

					<?php
					echo $this->icons->render(
						'contact'
					);
					?>

				</span>

				<span class="icon-arrow">

					<?php
					echo $this->icons->render(
						'arrow'
					);
					?>

				</span>

			</button>

		</div>

<?php

		return ob_get_clean();
	}
}
