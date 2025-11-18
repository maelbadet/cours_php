<?php
// Controller/CashController.php

require_once __DIR__ . '/../model/CashRegister.php';

class CashController
{
	public function handleRequest(): void
	{
		$result = null;
		$amountDue = null;
		$amountGiven = null;

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			// On récupère les montants en euros (string)
			$amountDue = $_POST['amount_due'] ?? '';
			$amountGiven = $_POST['amount_given'] ?? '';

			// Remplace virgules par points pour les float
			$amountDue = str_replace(',', '.', $amountDue);
			$amountGiven = str_replace(',', '.', $amountGiven);

			if (!is_numeric($amountDue) || !is_numeric($amountGiven)) {
				$result = [
					'success' => false,
					'error'   => "Les montants doivent être des nombres.",
				];
			} else {
				$amountDueFloat = (float)$amountDue;
				$amountGivenFloat = (float)$amountGiven;

				$amountDueCents = (int) round($amountDueFloat * 100);
				$amountGivenCents = (int) round($amountGivenFloat * 100);

				$cashRegister = new CashRegister();
				$result = $cashRegister->computeChange($amountDueCents, $amountGivenCents);

				// On ajoute quelques infos formatées pour la vue
				if ($result['success']) {
					$result['amountDueFormatted']   = CashRegister::formatCents($amountDueCents);
					$result['amountGivenFormatted'] = CashRegister::formatCents($amountGivenCents);
					$result['changeFormatted']      = CashRegister::formatCents($result['changeCents']);
				}
			}
		}

		// On charge la vue
		require __DIR__ . '/../vue/caisse.php';
	}
}
