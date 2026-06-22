/**
==================================================
IMPERIA CONTACTS
==================================================
*/

document.addEventListener(
	'DOMContentLoaded',
	() =>
	{
		const floating =
			document.querySelector(
				'.imperia-floating-contacts'
			);

		if (!floating)
		{
			return;
		}

		const toggle =
			floating.querySelector(
				'.imperia-contact-button'
			);

		if (!toggle)
		{
			return;
		}

		toggle.addEventListener(
			'click',
			() =>
			{
				floating.classList.toggle(
					'open'
				);
			}
		);
	}
);

console.log(
'Imperia Catalog loaded'
);