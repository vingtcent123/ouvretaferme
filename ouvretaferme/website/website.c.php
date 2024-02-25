<?php
Privilege::register('website', [
	'admin' => FALSE,
]);

Setting::register('website', [

	'dnsIP' => '51.83.98.183',
	'domain' => 'site.'.Lime::getDomain(),
	'blogFarm' => 98,
	'designDefaultId' => 1,
	'customFonts' => [
		['label' => 'Cairo', 'value' => "'Cairo', sans-serif"],
		['label' =>'Genos', 'value' => "'Genos', sans-serif"],
		['label' => 'Hina Mincho', 'value' => "'Hina Mincho', serif"],
		['label' => 'Kodchasan', 'value' => "'Kodchasan', sans-serif"],
		['label' => 'Lato', 'value' => "'Lato', sans-serif"],
		['label' => 'Lora', 'value' => "'Lora', serif"],
		['label' => 'Montserrat', 'value' => "'Montserrat', sans-serif"],
		['label' => 'Old Standard TT', 'value' => "'Old Standard TT', serif"],
		['label' => 'Open Sans', 'value' => "'Open sans', sans-serif"],
		['label' => 'Parisienne', 'value' => "'Parisienne', cursive"],
		['label' => 'Poiret One', 'value' => "'Poiret One', cursive"],
		['label' => 'PT Serif', 'value' => "'PT Serif', serif"],
		['label' => 'Quattrocento Sans', 'value' => "'Quattrocento Sans', sans-serif"],
		['label' => 'Roboto', 'value' => "'Roboto', sans-serif"],
		['label' => 'Sail', 'value' => "'Sail', cursive"],
		['label' => 'Source Sans Pro', 'value' => "'Source Sans Pro', sans-serif"],
		['label' => 'Titillium Web', 'value' => "'Titillium Web', sans-serif"],
	],
	'customTitleFonts' => [
		['label' => 'Acme', 'value' => "'Acme', sans-serif"],
		['label' => 'Anton', 'value' => "'Anton', sans-serif"],
		['label' => 'Bungee Shade', 'value' => "'Bungee Shade', cursive"],
		['label' => 'Cabin Sketch', 'value' => "'Cabin Sketch', cursive"],
		['label' => 'Comfortaa', 'value' => "'Comfortaa', cursive"],
		['label' => 'Dancing Script', 'value' => "'Dancing Script', cursive"],
		['label' => 'Itim', 'value' => "'Itim', cursive"],
		['label' => 'Kurale', 'value' => "'Kurale', serif"],
		['label' => 'Josefin Slab', 'value' => "'Josefin Slab', serif"],
		['label' => 'Lobster', 'value' => "'Lobster', cursive"],
		['label' => 'Mea Culpa', 'value' => "'Mea Culpa', cursive"],
		['label' => 'Megrim', 'value' => "'Megrim', cursive"],
		['label' => 'Nova Mono', 'value' => "'Nova Mono', monospace"],
		['label' => 'Open Sans', 'value' => "'Open sans', sans-serif"],
		['label' => 'Pacifico', 'value' => "'Pacifico', cursive"],
		['label' => 'Shizuru', 'value' => "'Shizuru', cursive"],
		['label' => 'PT Serif', 'value' => "'PT Serif', serif"],
	],

]);
?>
