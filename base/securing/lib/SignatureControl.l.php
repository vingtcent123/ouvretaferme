<?php
namespace securing;

class SignatureControlLib {

	public static function controlSales(\farm\Farm $eFarm, \selling\Sale $eSale = new \selling\Sale()) {

		\farm\FarmLib::connectDatabase($eFarm);

		$cSale = \selling\Sale::model()
			->select(\selling\SaleElement::getSelection())
			->whereFarm($eFarm)
			->whereId($eSale, if: $eSale->notEmpty())
			->whereSecured(TRUE)
			->recordset()
			->sort(['id' => SORT_ASC])
			->getCollection();

		$counter = 0;

		foreach($cSale as $eSale) {

			echo "\r".(++$counter).' verified';

			$eSignature = Signature::model()
				->select(Signature::getSelection())
				->whereSource(Signature::SALE)
				->whereReference($eSale['id'])
				->sort([
					'id' => SORT_DESC
				])
				->get();

			if($eSignature->empty()) {
				echo "\r".'* Sale '.$eSale['id'].': No signature'."\n";
				continue;
			}

			$eSignature['data'] = serialize(SignatureLib::getSaleData($eSale));

			$hmac = SignatureLib::getHmac($eSignature);

			if($hmac !== $eSignature['hmac']) {
				echo "\r".'* Sale '.$eSale['id'].': '.$hmac.' expected, '.$eSignature['hmac'].' found'."\n";
			}

		}

		echo "\n";

	}

	public static function controlCash(\farm\Farm $eFarm) {

		\farm\FarmLib::connectDatabase($eFarm);

		$cCash = \cash\Cash::model()
			->select(\cash\Cash::getSelection())
			->recordset()
			->sort(['id' => SORT_ASC])
			->getCollection();

		$counter = 0;

		foreach($cCash as $eCash) {

			echo "\r".(++$counter).' verified';

			$eSignature = Signature::model()
				->select(Signature::getSelection())
				->whereSource(Signature::CASH)
				->whereReference($eCash['id'])
				->sort([
					'id' => SORT_DESC
				])
				->get();

			if($eSignature->empty()) {
				echo "\r".'* Cash '.$eCash['id'].': No signature'."\n";
				continue;
			}

			$eSignature['data'] = serialize(SignatureLib::getCashData($eCash));

			$hmac = SignatureLib::getHmac($eSignature);

			if($hmac !== $eSignature['hmac']) {
				echo "\r".'* Cash '.$eCash['id'].': '.$hmac.' expected, '.$eSignature['hmac'].' found'."\n";
			}

		}

		echo "\n";

	}

	public static function controlHmac(\farm\Farm $eFarm) {

		\farm\FarmLib::connectDatabase($eFarm);

		$cSignature = Signature::model()
			->select('id', 'hmac', 'hmacChained', 'data', 'key')
			->recordset()
			->sort(['id' => SORT_ASC])
			->getCollection();

		$counter = 0;

		foreach($cSignature as $eSignature) {

			echo "\r".(++$counter).' verified';

			$hmac = SignatureLib::getHmac($eSignature);

			if(
				$hmac !== NULL and
				$hmac !== $eSignature['hmac']
			) {
				echo "\r".'* '.$hmac.' expected, '.$eSignature['hmac'].' found'."\n";
			}

		}

		echo "\n";

	}

	public static function rebuild(\farm\Farm $eFarm) {

		\farm\FarmLib::connectDatabase($eFarm);

		Signature::model()
			->all()
			->delete();

		$c = \selling\Sale::model()
			->select(\selling\Sale::getSelection())
			->whereSecured(TRUE)
			->whereFarm($eFarm)
			->sort([
				'securedAt' => SORT_ASC
			])
			->getCollection();

		$counter = 0;

		echo 'Sales'."\n";

		foreach($c as $e) {

			echo "\r".(++$counter).' updated';

			\securing\SignatureLib::signSale($e);

		}

		echo "\n";
		echo 'Cash'."\n";

		$c = \cash\Cash::model()
			->select(\cash\Cash::getSelection())
			->sort([
				'id' => SORT_ASC
			])
			->getCollection();

		$counter = 0;

		foreach($c as $e) {

			echo "\r".(++$counter).' updated';

			\securing\SignatureLib::signCash($e);

		}

		echo "\n";

	}

}
