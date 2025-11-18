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
		$prioritySelection = 'auto';
		$changeOrderSelection = 'desc';

		$cashRegister = new CashRegister();
		$denominations = array_keys($cashRegister->getInventory());
		rsort($denominations);

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			// On récupère les montants en euros (string)
			$amountDue = $_POST['amount_due'] ?? '';
			$amountGiven = $_POST['amount_given'] ?? '';
			$prioritySelection = $_POST['priority_value'] ?? 'auto';
			$changeOrderSelection = $_POST['change_order'] ?? 'desc';
			if (!in_array($changeOrderSelection, ['asc', 'desc'], true)) {
				$changeOrderSelection = 'desc';
			}

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

				$priorityValue = null;
				if ($prioritySelection !== 'auto' && is_numeric($prioritySelection)) {
					$priorityCandidate = (int)$prioritySelection;
					if (in_array($priorityCandidate, $denominations, true)) {
						$priorityValue = $priorityCandidate;
					}
				}

				$result = $cashRegister->computeChange(
					$amountDueCents,
					$amountGivenCents,
					$priorityValue,
					$changeOrderSelection
				);

				if ($result['success']) {
					$result['amountDueFormatted']   = CashRegister::formatCents($amountDueCents);
					$result['amountGivenFormatted'] = CashRegister::formatCents($amountGivenCents);
					$result['changeFormatted']      = CashRegister::formatCents($result['changeCents']);
					$result['priorityValue']        = $priorityValue;
					$result['changeOrder']          = $result['changeOrder'] ?? $changeOrderSelection;
				}
			}
		}

		// On charge la vue
		require __DIR__ . '/../vue/caisse.php';
	}
}
