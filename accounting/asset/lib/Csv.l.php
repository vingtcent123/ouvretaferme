<?php
namespace asset;

Class CsvLib {

	public static function importAssets(\farm\Farm $eFarm, array $assets): bool {

		$eFinancialYear = \account\FinancialYearLib::getOpenFinancialYears()->last();
		$resumeDate = $eFinancialYear['startDate'];

		$cAsset = new \Collection();

		foreach($assets as $asset) {

			$values = [
				'account' => $asset['accountId'],
				'accountLabel' => \account\AccountLabelLib::pad($asset['account']),
				'resumeDate' => $resumeDate,
				'value' => $asset['value'],
				'residualValue' => $asset['residualValue'],
				'description' => $asset['description'],
				'economicMode' => $asset['economicMode'],
				'economicDuration' => $asset['economicDuration'],
				'fiscalMode' => $asset['fiscalMode'],
				'fiscalDuration' => $asset['fiscalDuration'],
				'economicAmortization' => $asset['economicAmortization'],
				'acquisitionDate' => $asset['acquisitionDate'],
				'startDate' => $asset['startDate'],
				'endDate' => date('Y-m-d', strtotime($asset['startDate'].' + '.$asset['economicDuration'].' month')),
				'isGrant' => AssetLib::isGrant($asset['account']),
			];

			$eAsset = new Asset();
			$eAsset->build(array_keys($values), $values);

			$cAsset->append($eAsset);

		}

		Asset::model()->insert($cAsset);

		return TRUE;
	}

	public static function reset(\farm\Farm $eFarm): bool {

		return \Cache::redis()->delete('import-assets-'.$eFarm['id']);

	}

	public static function getAssets(\farm\Farm $eFarm): ?array {

		$import = \Cache::redis()->get('import-assets-'.$eFarm['id']);

		if($import === FALSE) {
			return NULL;
		}

		$errorsCount = 0;

		$assets = $import['assets'];
		foreach($assets as $key => $asset) {

			$errorsCommon = [];

			if(in_array($asset['economicMode'], Asset::model()->getPropertyEnum('economicMode')) === FALSE) {
				$errorsCommon[] = 'economicMode';
			}

			if(in_array($asset['fiscalMode'], Asset::model()->getPropertyEnum('fiscalMode')) === FALSE) {
				$errorsCommon[] = 'fiscalMode';
			}

			$error = self::checkDateField($line['acquisitionDate'], 'acquisitionDate');
			if($error !== NULL) {
				$errorsCommon[] = $error;
			}
			$error = self::checkDateField($line['startDate'], 'startDate');
			if($error !== NULL) {
				$errorsCommon[] = $error;
			}
			if(mb_strlen($asset['startDate']) > 0 and !!\util\DateLib::isValid($asset['startDate']) === FALSE) {
				$errorsCommon[] = 'startDate';
			}
			if(!$asset['accountId']) {
				$errorsCommon[] = 'accountId';
			}

			if($asset['residualValue'] >= $asset['value']) {
				$errorsCommon[] = 'residualValue';
			}
			if($asset['economicAmortization'] >= $asset['value']) {
				$errorsCommon[] = 'economicAmortization';
			}

			$errors = array_unique(array_filter($errorsCommon));
			$assets[$key]['errors'] = $errors;

			$errorsCount += count($errors);

		}

		return [
			'import' => $assets,
			'errorsCount' => $errorsCount,
			'resumeDate' => $import['resumeDate']
		];

	}

	public static function uploadAssets(\farm\Farm $eFarm): bool {

		if(isset($_FILES['csv']) === FALSE) {
			return FALSE;
		}

		$file = $_FILES['csv']['tmp_name'];

		if(empty($file)) {
			return FALSE;
		}

		// Vérification de la taille (max 1 Mo)
		if(filesize($file) > 1024 * 1024) {
			\Fail::log('csvSize');
			return FALSE;
		}

		$content = file_get_contents($file);

		if(mb_detect_encoding($content, ['UTF-8', 'UTF-16']) === 'UTF-16') {
			$content = iconv('UTF-16', 'UTF-8', $content);
		}

		$content = trim($content);

		file_put_contents($file, $content);

		$delimiter = \series\CsvLib::detectDelimiter($file);
		$assets = \util\CsvLib::parseCsv($file, $delimiter);


		if($assets === []) {
			\Fail::log('csvSource');
			return FALSE;
		}

		$import = ['assets' => []];

		$head = array_shift($assets);

		foreach($assets as $asset) {

			if(count($asset) < count($head)) {
				$asset = array_merge($asset, array_fill(0, count($head) - count($asset), ''));
			} else if(count($head) < count($asset)) {
				$asset = array_slice($head, 0, count($head));
			}

			$line = array_combine($head, $asset) + [
				'account' => '',
				'value' => '',
				'description' => '',
				'economic_mode' => '',
				'economic_duration' => '',
				'economic_amortization' => '',
				'fiscal_mode' => '',
				'fiscal_duration' => '',
				'acquisition_date' => '',
				'start_date' => '',
				'residual_value' => '',
			];

			$acquisitionDate = $line['acquisition_date'];
			$startDate = $line['start_date'] ?: $line['acquisition_date'];

			$economicMode = self::formatMode($line['economic_mode']);
			$fiscalMode = self::formatMode($line['fiscal_mode'] !== '' ? $line['fiscal_mode']:  $line['economic_mode']);

			$economicDuration = (int)$line['economic_duration'];
			$fiscalDuration = (int)($line['fiscal_duration'] !== '' ? $line['fiscal_duration'] : $line['economic_duration']);

			$value = self::formatMoney($line['value']);
			$residualValue = self::formatMoney($line['residual_value']);
			$economicAmortization = self::formatMoney($line['economic_amortization']);

			$description = $line['description'];
			$account = $line['account'];
			$cAccount = \account\Account::model()
				->select('id', 'class')
				->whereClass('LIKE', mb_substr($account, 0, 3).'%')
				->getCollection(NULL, NULL, 'class');

			$eAccount = new \account\Account();
			$currentAccount = $account;
			while($eAccount->empty() and mb_strlen($currentAccount) >= 3) {
				if($cAccount->offsetExists($currentAccount)) {
					$eAccount = $cAccount->offsetGet($currentAccount);
				} else {
					$currentAccount = mb_substr($currentAccount, 0, mb_strlen($currentAccount) - 1);
				}
			}

			$hash = md5($acquisitionDate.'-'.$startDate.'-'.$economicMode.'-'.$fiscalMode.'-'.$economicDuration.'-'.$fiscalDuration.'-'.$value.'-'.$residualValue.'-'.$economicAmortization.'-'.($eAccount->notEmpty() ? $eAccount['id'] : '').'-'.$description);

			$import['assets'][$hash] ??= [
				'acquisitionDate' => $acquisitionDate,
				'startDate' => $startDate,
				'economicMode' => $economicMode,
				'fiscalMode' => $fiscalMode,
				'economicDuration' => $economicDuration,
				'fiscalDuration' => $fiscalDuration,
				'value' => $value,
				'residualValue' => $residualValue,
				'economicAmortization' => $economicAmortization,
				'accountId' => $eAccount['id'] ?? NULL,
				'description' => $description,
				'account' => $account,
			];
		}

		$eFinancialYear = \account\FinancialYearLib::getOpenFinancialYears()->last();
		$import['resumeDate'] = $eFinancialYear['startDate'];

		\Cache::redis()->set('import-assets-'.$eFarm['id'], $import);

		return TRUE;

	}

	private static function formatMoney(mixed $value): ?float {

		return (float)str_replace(',', '.', $value);

	}

	private static function formatMode(mixed $value): ?string {

		return match(trim(mb_strtolower($value))) {
			's' => Asset::WITHOUT,
			'sans' => Asset::WITHOUT,
			'l' => Asset::LINEAR,
			'lin' => Asset::LINEAR,
			'd' => Asset::DEGRESSIVE,
			'deg' => Asset::DEGRESSIVE,
			default => NULL,
		};

	}

	private static function checkDateField(mixed &$value, string $error): ?string {

		if(
			$value !== NULL and
			\Filter::check('date', $value) === FALSE
		) {
			return $error;
		} else {
			return NULL;
		}

	}

}
