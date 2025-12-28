<?php
new Page()
->get('/facturation-electronique', function($data) {

	throw new ViewAction($data);

});
?>
