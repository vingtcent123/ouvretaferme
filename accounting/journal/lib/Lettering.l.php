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

	public static function getByOperation(string $type, Operation $eOperation): \Collection {

		return Lettering::model()
			->select(Lettering::getSelection())
			->where($type.' = '.$eOperation['id'])
			->sort(['createdAt' => SORT_ASC])
			->getCollection();

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

	public static function isOperationLinkedInLettering(Operation $eOperation): bool {

		return (Lettering::model()
			->or(
				fn() => $this->whereCredit($eOperation),
				fn() => $this->whereDebit($eOperation),
			)
			->count() > 0);
	}

	/**
	 *
	 * @param Operation $eOperationToLetter
	 * @return void
	 * @throws \ElementException
	 */
	public static function letterOperation(Operation $eOperationToLetter, string $for): void {

		if($for === 'update') {

			$eOperationToLetter->expects(['id', 'type', 'amount']);
			$cLettering = self::getByOperation($eOperationToLetter['type'], $eOperationToLetter);

		} else {

			$eOperationToLetter->expects(['id', 'thirdParty', 'type', 'amount']);

		}

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
			->where('accountLabel LIKE "'.\account\AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS.'%" OR accountLabel LIKE "'.\account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS.'%"')
			->sort(['date' => SORT_ASC])
			->getCollection();

		// Chaque opération est lettrée au fur et à mesure en enregistrant le montant lettré
		if($for === 'update') {

			$amountToLetter = $eOperationToLetter['amount'];
			$amountLettered = $cLettering->reduce(fn($e, $n) => $n + $e['amount'], 0);

			// Tout est déjà lettré
			if(round($amountLettered, 2) === round($amountToLetter, 2)) {
				return;
			}

			if($amountToLetter > $amountLettered) {
				$amount = $amountToLetter - $amountLettered;
			} else {
				// Problème : on a lettré + que nécessaire => diminuer le lettrage de la dernière opération de contrepartie
				\Fail::log('Operation::lettering.inconsistency');
				return;
			}

		} else {

			$amount = $eOperationToLetter['amount'];

		}

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

			\account\LogLib::save('letter', 'Letter', ['id' => $eOperation['id']]);

		}

	}
}
?>
