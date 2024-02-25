<?php
(new Page())
	->http('index', fn($data) => throw new ViewAction($data));

(new Page())
	->http('demo', fn($data) => throw new ViewAction($data));

?>
