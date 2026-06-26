/**
==================================================
IMPERIA CATALOG MENU
==================================================
*/

document.addEventListener(
'DOMContentLoaded',
()=>{


const catalog =
document.querySelector(
'.imperia-catalog'
);


if(!catalog)
{
	return;
}


const button =
catalog.querySelector(
'.imperia-catalog__button'
);


const dropdown =
catalog.querySelector(
'.imperia-catalog__dropdown'
);


const submenu =
catalog.querySelector(
'.imperia-catalog__submenu'
);


const arrows =
catalog.querySelectorAll(
'.imperia-catalog__toggle'
);


if(!window.imperiaCatalogTree)
{
	console.error(
		'Imperia Catalog: tree missing'
	);

	return;
}


/**
==================================================
OPEN MENU
==================================================
*/

button.addEventListener(
'click',
()=>{


const opened =
catalog.classList.toggle(
'imperia-catalog--open'
);


button.setAttribute(
'aria-expanded',
opened
	? 'true'
	: 'false'
);


if(!opened)
{

closeDesktopSubmenu();

closeAllMobileCategories();

}


});



/**
==================================================
ARROWS
==================================================
*/

arrows.forEach(
arrow=>{


arrow.addEventListener(
'click',
event=>{


event.preventDefault();

event.stopPropagation();


const category =
arrow.closest(
'.imperia-catalog__category'
);


const id =
Number(
category.dataset.categoryId
);


const isMobile =
window.matchMedia(
'(max-width: 768px)'
).matches;


if(isMobile)
{

toggleMobileCategory(
id,
category,
arrow
);

}
else
{

showDesktopChildren(
id,
category
);

}


});


});



/**
==================================================
DESKTOP SUBMENU
==================================================
*/

function showDesktopChildren(
id,
element
)
{


const category =
window.imperiaCatalogTree.find(
item =>
Number(item.id) === id
);


if(
!category ||
!category.children ||
!category.children.length
)
{

submenu.hidden = true;

return;

}


let html =
'<ul>';


category.children.forEach(
child=>{


html +=
`

<li>

<a href="${child.url}">

${child.name}

</a>

</li>

`;

});


html +=
'</ul>';


submenu.innerHTML =
html;


const rect =
element.getBoundingClientRect();


const parentRect =
dropdown.getBoundingClientRect();


submenu.style.top =
(
rect.top -
parentRect.top
)
+
'px';


submenu.hidden = false;


}



/**
==================================================
MOBILE CATEGORY
==================================================
*/

function toggleMobileCategory(
id,
category,
arrow
)
{


const container =
category.querySelector(
'.imperia-catalog__mobile-children'
);


if(!container)
{
	return;
}


const opened =
!container.hasAttribute(
'hidden'
);


/**
==================================================
CLOSE CURRENT
==================================================
*/

if(opened)
{

container.setAttribute(
'hidden',
''
);

container.innerHTML='';


category.classList.remove(
'is-active'
);


arrow.classList.remove(
'is-open'
);


arrow.setAttribute(
'aria-expanded',
'false'
);


return;

}


/**
==================================================
LOAD CHILDREN
==================================================
*/

const data =
window.imperiaCatalogTree.find(
item =>
Number(item.id) === id
);


if(
!data ||
!data.children ||
!data.children.length
)
{
	return;
}


let html =
'<ul>';


data.children.forEach(
child=>{


html +=
`

<li>

<a href="${child.url}">

${child.name}

</a>

</li>

`;

});


html +=
'</ul>';


container.innerHTML =
html;


container.removeAttribute(
'hidden'
);


category.classList.add(
'is-active'
);


arrow.classList.add(
'is-open'
);


arrow.setAttribute(
'aria-expanded',
'true'
);


}



/**
==================================================
CLOSE ALL MOBILE
==================================================
*/

function closeAllMobileCategories()
{


catalog
.querySelectorAll(
'.imperia-catalog__mobile-children'
)
.forEach(
container=>{

container.setAttribute(
'hidden',
''
);

container.innerHTML='';

}
);


catalog
.querySelectorAll(
'.imperia-catalog__category'
)
.forEach(
category=>{

category.classList.remove(
'is-active'
);

}
);


catalog
.querySelectorAll(
'.imperia-catalog__toggle'
)
.forEach(
arrow=>{

arrow.classList.remove(
'is-open'
);

arrow.setAttribute(
'aria-expanded',
'false'
);

}
);


}



/**
==================================================
CLOSE DESKTOP
==================================================
*/

function closeDesktopSubmenu()
{

submenu.hidden = true;

submenu.innerHTML = '';

}



/**
==================================================
CLICK OUTSIDE
==================================================
*/

document.addEventListener(
'click',
event=>{


if(
!catalog.contains(
event.target
)
)
{

catalog.classList.remove(
'imperia-catalog--open'
);

closeDesktopSubmenu();

closeAllMobileCategories();

}


});


console.log(
'Imperia Catalog loaded'
);


});