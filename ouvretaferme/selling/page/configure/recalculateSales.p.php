<?php
(new Page())
	->get('index', function($data) {

        $cSale = \selling\Sale::model()
            ->select(\selling\Sale::getSelection())
            ->getCollection();

        foreach($cSale as $eSale) {
            \selling\SaleLib::recalculate($eSale);
        }

	});
?>