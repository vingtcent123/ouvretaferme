<?php
namespace website;

class WebsiteSetting extends \Settings  {

	public static function domain(): string {
		return 'site.'.\Lime::getDomain();
	}

	const DOMAIN_MAX_TRY = 3;

	const BLOG_FARM = 98;

	const DESIGN_DEFAULT_ID = 1;

	const CUSTOM_FONTS = [
		['label' => 'Cairo', 'value' => "'Cairo', sans-serif"],
		['label' =>'Figtree', 'value' => "'Figtree', serif"],
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
		['label' => 'Quattrocento Sans', 'value' => "'Quattrocento Sans', sans-serif", 'link' => "Quattrocento Sans:wght@400;700"],
		['label' => 'Roboto', 'value' => "'Roboto', sans-serif"],
		['label' => 'Sail', 'value' => "'Sail', cursive"],
		['label' => 'Source Sans Pro', 'value' => "'Source Sans Pro', sans-serif"],
		['label' => 'Titillium Web', 'value' => "'Titillium Web', sans-serif"],
	];

	const CUSTOM_TITLE_FONTS = [
		['label' => 'Acme', 'value' => "'Acme', sans-serif"],
		['label' => 'Anton', 'value' => "'Anton', sans-serif"],
		['label' => 'Barriecito', 'value' => "'Barriecito', serif"],
		['label' => 'Bungee Shade', 'value' => "'Bungee Shade', cursive"],
		['label' => 'Butterfly Kids', 'value' => "'Butterfly Kids', serif"],
		['label' => 'Cabin Sketch', 'value' => "'Cabin Sketch', cursive"],
		['label' => 'Chelsea Market', 'value' => "'Chelsea Market', serif"],
		['label' => 'Comfortaa', 'value' => "'Comfortaa', cursive"],
		['label' => 'Dancing Script', 'value' => "'Dancing Script', cursive"],
		['label' => 'Fredericka the Great', 'value' => "'Fredericka the Great', serif"],
		['label' => 'Frijole', 'value' => "'Frijole', serif"],
		['label' => 'Itim', 'value' => "'Itim', cursive"],
		['label' => 'Kurale', 'value' => "'Kurale', serif"],
		['label' => 'Josefin Slab', 'value' => "'Josefin Slab', serif"],
		['label' => 'Lobster', 'value' => "'Lobster', cursive"],
		['label' => 'Londrina Sketch', 'value' => "'Londrina Sketch', serif"],
		['label' => 'Mea Culpa', 'value' => "'Mea Culpa', cursive"],
		['label' => 'Megrim', 'value' => "'Megrim', cursive"],
		['label' => 'Miniver', 'value' => "'Miniver', serif"],
		['label' => 'Montez', 'value' => "'Montez', serif"],
		['label' => 'Mouse Memoirs', 'value' => "'Mouse Memoirs', serif"],
		['label' => 'Mystery Quest', 'value' => "'Mystery Quest', serif"],
		['label' => 'Nova Mono', 'value' => "'Nova Mono', monospace"],
		['label' => 'Open Sans', 'value' => "'Open sans', sans-serif"],
		['label' => 'Pacifico', 'value' => "'Pacifico', cursive"],
		['label' => 'Patrick Hand', 'value' => "'Patrick Hand', serif"],
		['label' => 'PT Mono', 'value' => "'PT Mono', serif"],
		['label' => 'PT Serif', 'value' => "'PT Serif', serif"],
		['label' => 'Schoolbell', 'value' => "'Schoolbell', serif"],
		['label' => 'Shizuru', 'value' => "'Shizuru', cursive"],
	];

}

?>
