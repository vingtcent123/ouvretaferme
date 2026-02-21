<?php
namespace pdp;

/**
 *
 * Lib pour gérer l'entreprise sur Super PDP
 *
 */

Class CompanyLib extends CompanyCrud {

	public static function get(): Company {


		return self::applyNumberFilter()
			->select(Company::getSelection())
			->get();

	}

	public static function getWithAddresses(): Company {

		return self::applyNumberFilter()
			->select(
				Company::getSelection() + [
					'cAddress' => Address::model()
						->select(Address::getSelection())
						->sort(['createdAt' => SORT_DESC])
						->delegateCollection('company')
				])
			->get();

	}

	public static function synchronize(): void {

		$accessToken = ConnectionLib::getValidToken();

		$dataCompany = CurlLib::send($accessToken, PdpSetting::SUPER_PDP_API_URL.'companies/me', [], 'GET');

		if($dataCompany === NULL) {
			return;
		}

		Company::model()->beginTransaction();

			$eCompany = new Company([
				'id' => $dataCompany['id'],
				'number' => $dataCompany['number'],
				'formalName' => $dataCompany['formal_name'],
				'address' => $dataCompany['address'],
				'postcode' => $dataCompany['postcode'],
				'city' => $dataCompany['city'],
			]);

			Company::model()->option('add-replace')->insert($eCompany);

			AddressLib::synchronize($eCompany);

		Company::model()->commit();
	}

	private static function applyNumberFilter(): CompanyModel {

		if(LIME_ENV === 'dev') {

			return Company::model()->whereNumber(1);

		} else {

			$eFarm = \farm\Farm::getConnected();

			if($eFarm->isFR()) {
				return Company::model()->whereNumber($eFarm->siren());
			} else {
				// TODO belges : numéro entreprise ?? C'est le siren ?
				return Company::model()->whereNumber($eFarm->siren());
			}
		}

	}

}
