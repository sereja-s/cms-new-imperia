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

	if(!submenu)
{
	console.error(
		'Imperia Catalog: submenu container missing'
	);

	return;
}

const categories =
catalog.querySelectorAll(
'.imperia-catalog__category'
);



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

submenu.hidden=true;

}


});




/**
==================================================
ARROW CLICK
==================================================
*/


const arrows =
catalog.querySelectorAll(
'.imperia-catalog__toggle'
);



arrows.forEach(
arrow=>{


arrow.addEventListener(
'click',
event=>{


/**
Не даём перейти
по ссылке родителя.
*/
event.preventDefault();

event.stopPropagation();



const category =
arrow.closest(
'.imperia-catalog__category'
);



categories.forEach(
item=>
item.classList.remove(
'is-active'
)
);



category.classList.add(
'is-active'
);



const id =
Number(
category.dataset.categoryId
);



showChildren(
id,
category
);



});


});




/**
==================================================
SHOW CHILDREN
==================================================
*/


function showChildren(
id,
element
)
{


if(
!window.imperiaCatalogTree
)
{
return;
}



const category =
window.imperiaCatalogTree.find(
item=>
item.id===id
);



if(
!category
||
!category.children.length
)
{

submenu.hidden=true;

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



submenu.innerHTML=html;



/**
Позиция относительно категории
*/


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



submenu.hidden=false;


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


submenu.hidden=true;


}


});



console.log(
'Imperia Catalog loaded'
);


});