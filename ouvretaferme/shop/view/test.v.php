<?php
new AdaptativeView('index', function($data, MainTemplate $t) {

	echo '<h1>Boutique</h1>';
	echo '<script type="text/javascript">(function() { const element = document.createElement("script"); element.src = "http://boutique.dev-ouvretaferme.org/embed.js?id='.GET('id').'"; document.getElementsByTagName("head")[0].appendChild(element); })()</script><div id="otf-shop"></div>';

});
?>
