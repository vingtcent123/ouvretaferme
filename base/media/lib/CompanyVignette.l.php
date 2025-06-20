<?php
namespace media;

class CompanyVignetteLib extends MediaLib {

	public function buildElement(): \company\Company {

		$eCompany = POST('id', 'company\Company');

		if(
			$eCompany->empty() or
			\company\Company::model()
				->select('vignette')
				->get($eCompany) === FALSE
		) {
			throw new \NotExistsAction('company');
		}

		// L'utilisateur n'est pas le propriétaire de la ferme
		if($eCompany->canWrite() === FALSE) {

			// L'utilisateur n'est pas non plus admin
			if(\Privilege::can('company\admin') === FALSE) {
				throw new \NotAllowedAction();
			}

		}

		return $eCompany;

	}

}
?>
