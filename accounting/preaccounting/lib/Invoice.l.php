<?php
namespace preaccounting;

Class InvoiceLib {

	public static function setReadyForAccounting(\farm\Farm $eFarm): void {

		\selling\Invoice::model()
			->whereFarm($eFarm)
			->whereStatus('!=', \selling\Invoice::DRAFT)
			->whereAccountingHash(NULL)
			->wherePaymentMethod('!=', NULL)
			->whereReadyForAccounting(FALSE)
			->update(['readyForAccounting' => TRUE]);

	}

	public static function filterForAccountingCheck(\farm\Farm $eFarm, \Search $search): \selling\InvoiceModel {

		return \selling\Invoice::model()
			->whereStatus('!=', \selling\Invoice::DRAFT)
			->where('priceExcludingVat != 0.0')
			->where('m1.farm = '.$eFarm['id'])
			->whereReadyForAccounting(FALSE)
			->where('date BETWEEN '.\selling\Sale::model()->format($search->get('from')).' AND '.\selling\Sale::model()->format($search->get('to')))
			->where(new \Sql('DATE(date) < CURDATE()'));

	}

	public static function countForAccountingPaymentCheck(\farm\Farm $eFarm, \Search $search): int {

		return self::filterForAccountingCheck($eFarm, $search)
			->wherePaymentMethod('=', NULL)
			->count();

	}

	public static function countForAccountingCheckVerified(\farm\Farm $eFarm, \Search $search): int {

		return self::filterForAccountingCheck($eFarm, $search)
			->whereClosed(TRUE)
			->wherePaymentMethod('=', NULL)
			->count();

	}
	public static function getForAccountingCheck(\farm\Farm $eFarm, \Search $search): \Collection {

		return self::filterForAccountingCheck($eFarm, $search)
			->select(\selling\Invoice::getSelection())
			->whereClosed(FALSE)
			->wherePaymentMethod(NULL)
			->sort(['date' => SORT_DESC])
			->getCollection(NULL, NULL, 'id');

	}

	public static function extractInvoiceSignature(string $ref): array {
    $ref = mb_strtolower($ref, 'UTF-8');

    preg_match_all('/[a-z]+/', $ref, $alpha);
    preg_match_all('/\d+/', $ref, $nums);

    return [
        'alpha' => $alpha[0] ?? [],
        'nums'  => array_map(
            fn($n) => ltrim($n, '0'),
            $nums[0] ?? []
        ),
    ];
	}

	public static function extractBankNumbers(string $label): array {
    preg_match_all('/\d+/', $label, $m);
    return $m[0] ?? [];
	}


	public static function hasInvoiceContext(string $label): bool {
    $label = mb_strtolower($label, 'UTF-8');

    return preg_match(
        '/\b(fa|fact|facture|inv|invoice|regl|reglement)\b/',
        $label
    ) === 1;
	}



	public static function scoreInvoiceReference(string $invoiceRef, string $bankLabel): int {
    // --- Préparation ---
    $sig = self::extractInvoiceSignature($invoiceRef);
    if (empty($sig['nums'])) {
        return 0;
    }

    $label = mb_strtolower($bankLabel, 'UTF-8');
    $bankNums = self::extractBankNumbers($label);
    $hasContext = self::hasInvoiceContext($label);

    $score = 0;

    // --- Identifier année et numéro(s) ---
    $year = null;
    $numbers = [];

    foreach ($sig['nums'] as $n) {
        if (preg_match('/^20\d{2}$/', $n)) {
            $year = $n;
        } else {
            $numbers[] = $n;
        }
    }

    // --- Scoring des numéros ---
    foreach ($numbers as $num) {
        if (!in_array($num, $bankNums, true)) {
            continue;
        }

        $len = strlen($num);

        // numéro long = très fiable
        if ($len >= 4) {
            $score += 150;
        }
        // numéro court accepté uniquement avec contexte facture
        elseif ($hasContext) {
            $score += 80;
        }
    }

    // --- Scoring de l'année ---
    if ($year !== null && in_array($year, $bankNums, true)) {
        $score += 60;
    }

    // --- Bonus combinaison année + numéro ---
    if (
        $year !== null &&
        in_array($year, $bankNums, true)
    ) {
        foreach ($numbers as $num) {
            if (in_array($num, $bankNums, true)) {
                $score += 50;
                break;
            }
        }
    }

    // --- Bonus préfixe alpha (fa, fact, sj, etc.) ---
    foreach ($sig['alpha'] as $a) {
        if (preg_match('/\b' . preg_quote($a, '/') . '\b/', $label)) {
            $score += 40;
            break;
        }
    }

    // --- Bonus contexte facture ---
    if ($hasContext) {
        $score += 40;
    }

    // --- Bonus fiabilité : numéro court + année + contexte ---
    if ($hasContext && $year !== null) {
        foreach ($numbers as $num) {
            if (
                strlen($num) <= 2 &&
                in_array($num, $bankNums, true)
            ) {
                $score += 40;
                break;
            }
        }
    }

    // --- Sécurité anti-faux positifs ---
    // Numéro court sans contexte facture → rejet
    if (!$hasContext) {
        foreach ($numbers as $num) {
            if (strlen($num) <= 2 && in_array($num, $bankNums, true)) {
                return 0;
            }
        }
    }

    return $score;
	}


}
