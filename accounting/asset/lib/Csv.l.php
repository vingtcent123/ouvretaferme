<?php
namespace asset;

Class CsvLib {

	public static function importAssets(array $assets): bool {

		$eFinancialYear = \account\FinancialYearLib::getOpenFinancialYears()->last();
		$resumeDate = $eFinancialYear['startDate'];

		Asset::model()->beginTransaction();

			$cAsset = new \Collection();

			foreach($assets as $asset) {

				if($asset['economicMode'] === Asset::WITHOUT) {
					$endDate = NULL;
				} else {
					$endDate = date('Y-m-d', strtotime($asset['startDate'].' + '.$asset['economicDuration'].' month'));
				}

				if($endDate !== NULL and $endDate < $eFinancialYear['startDate']) {
					$status = Asset::ENDED;
				} else {
					$status = Asset::ONGOING;
				}

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
					'endDate' => $endDate,
					'isGrant' => AssetLib::isGrant($asset['account']),
					'status' => $status,
				];

				$eAsset = new Asset();
				$eAsset->build(array_keys($values), $values);

				$cAsset->append($eAsset);

			}

			Asset::model()->insert($cAsset);

		Asset::model()->commit();

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

		$eFinancialYear = $eFarm['eFinancialYear'];
		$errorsCount = 0;

		$assets = $import['assets'];
		foreach($assets as $key => $asset) {

			$eAsset = new Asset([
			]);
			$errors = [];

			$p = new \Properties();

			$properties = ['value', 'economicMode', 'fiscalMode', 'acquisitionDate', 'startDate', 'residualValue', 'economicAmortization'];

			if(!$asset['accountId']) {
				$errors[] = 'accountId';
			}

			if($asset['economicAmortization'] === 0.0 and $asset['startDate'] < $eFinancialYear['startDate'] and $asset['economicMode'] !== Asset::WITHOUT) {
				$errors[] = 'economicAmortization';
			}

			$eAsset->build($properties, array_filter($asset, fn($key) => in_array($key, $properties), ARRAY_FILTER_USE_KEY), $p);

			$errors = array_merge($errors, $p->getInvalidMessages());

			$errors = array_unique(array_filter($errors));
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

		return \main\CsvLib::upload('import-assets-'.$eFarm['id'], function($assets) {

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
					'description' => '',
					'value' => '',
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

				$value = \main\CsvLib::formatFloat($line['value']);
				$residualValue = \main\CsvLib::formatFloat($line['residual_value']);
				$economicAmortization = \main\CsvLib::formatFloat($line['economic_amortization']);

				$description = iconv('CP1252', 'UTF-8', $line['description']);
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

			return $import;

		});

	}

	private static function formatMode(mixed $value): ?string {

		return match(trim(mb_strtolower($value))) {
			's' => Asset::WITHOUT,
			'sans' => Asset::WITHOUT,
			'w' => Asset::WITHOUT,
			'l' => Asset::LINEAR,
			'lin' => Asset::LINEAR,
			'd' => Asset::DEGRESSIVE,
			'deg' => Asset::DEGRESSIVE,
			default => NULL,
		};

	}

}
