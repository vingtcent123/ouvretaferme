<?php
namespace journal;

class LetteringLib extends LetteringCrud {

	public static function delegate(string $column): LetteringModel {

		return Lettering::model()
			->select(Lettering::getSelection())
			->sort(['createdAt' => SORT_DESC])
			->delegateCollection($column);

	}

	public static function getByCode(string $code): Lettering {

		return Lettering::model()
			->select(Lettering::getSelection())
			->whereCode($code)
			->get();

	}

	public static function generateNewCode(): string {

		$eLettering = Lettering::model()
			->select('code')
			->sort(['createdAt' => SORT_DESC])
			->get();

		if($eLettering->empty()) {
			return 'L'.date('Y').'-001';
		}

		$dashPosition = mb_strpos($eLettering['code'], '-');
		$prefix = mb_substr($eLettering['code'], 0, $dashPosition);

		$number = (int)mb_substr($eLettering['code'], mb_strlen($prefix) + 1);

		$attempts = 0;

		while($attempts < 20) {

			$number++;
			$newCode = $prefix.'-'.mb_str_pad($number, mb_strlen($eLettering['code']) - mb_strlen($prefix) - 1, '0', STR_PAD_LEFT);

			if(Lettering::model()->whereCode($newCode)->count() === 0) {
				return $newCode;
			}

			$attempts++;

		}

		return 'L'.date('Y').'-0001';

	}

	/**
	 *
	 * @param Operation $eOperationToLetter
	 * @return void
	 * @throws \ElementException
	 */
	public static function letter(Operation $eOperationToLetter): void {

		$eOperationToLetter->expects(['thirdParty', 'type', 'amount']);

		$letteringCode = self::generateNewCode();

		// Récupère toutes les opérations non lettrées
		$cOperation = Operation::model()
			->select(
				Operation::getSelection()
				+ [
					'cLetteringCredit' => self::delegate('credit'),
					'cLetteringDebit' => self::delegate('debit'),
				]
			)
			->or(
			// On continue si...
				fn() => $this->whereLetteringStatus(NULL),
				fn() => $this->whereLetteringStatus('!=', Operation::TOTAL)
			)
			->whereThirdParty($eOperationToLetter['thirdParty'])
			->where('accountLabel LIKE "'.\Setting::get('account\thirdAccountSupplierDebtClass').'%" OR accountLabel LIKE "'.\Setting::get('account\thirdAccountClientReceivableClass').'%"')
			->sort(['date' => SORT_ASC])
			->getCollection();

		// Chaque opération est lettrée au fur et à mesure en enregistrant le montant lettré
		$amount = $eOperationToLetter['amount'];
		$type = $eOperationToLetter['type'];

		foreach($cOperation as $eOperation) {

			// On ne lettre pas 2 opérations qui vont dans le même sens ou qu'on a déjà épuisé toute l'opération de paiement.
			if($type === $eOperation['type'] or $amount <= 0) {
				continue;
			}

			$letterings = 'cLettering'.(ucfirst($type === Operation::CREDIT ? Operation::DEBIT : Operation::CREDIT));
			$alreadyLetteredAmount = $eOperation[$letterings]->reduce(fn($eLettering, $n) => $eLettering['amount'] + $n, 0);
			$toLetterAmount = round($eOperation['amount'] - $alreadyLetteredAmount, 2);
			$eLettering = new Lettering([
				'credit' => $type === Operation::CREDIT ? $eOperationToLetter : $eOperation,
				'debit' => $type === Operation::DEBIT ? $eOperationToLetter : $eOperation,
				'code' => $letteringCode,
			]);

			if($toLetterAmount <= $amount) {

				$eLettering['amount'] = $toLetterAmount;
				$amount -= $toLetterAmount;

				$eOperation['letteringStatus'] = Operation::TOTAL;

			} else {

				$eLettering['amount'] = $amount;
				$amount = 0;

				$eOperation['letteringStatus'] = Operation::PARTIAL;

			}

			OperationLib::update($eOperation, ['letteringStatus']);

			if($amount > 0) {
				$eOperationToLetter['letteringStatus'] = Operation::PARTIAL;
			} else {
				$eOperationToLetter['letteringStatus'] = Operation::TOTAL;
			}
			OperationLib::update($eOperationToLetter, ['letteringStatus']);

			Lettering::model()->insert($eLettering);

		}

	}
}
?>
