<?php
(new Page())
	->get('index', fn($data) => throw new ViewAction($data));
?>
