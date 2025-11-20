<?php
// Controller/CashController.php

require_once __DIR__ . '/../model/CashRegister.php';

class CashController
{
	public function handleRequest(): void
	{
		$result = null;
		$amountDue = null;
		$prioritySelection = 'auto';
		$changeOrderSelection = 'desc';
		$clientBreakdown = [];

		$cashRegister = new CashRegister();
		$denominationMeta = $cashRegister->getDenominationMetadata();
		$denominations = array_keys($denominationMeta);
		foreach ($denominations as $value) {
			$clientBreakdown[$value] = 0;
		}

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			// On récupère les montants en euros (string)
			$amountDue = $_POST['amount_due'] ?? '';
			$prioritySelection = $_POST['priority_value'] ?? 'auto';
			$changeOrderSelection = $_POST['change_order'] ?? 'desc';
			if (!in_array($changeOrderSelection, ['asc', 'desc'], true)) {
				$changeOrderSelection = 'desc';
			}

			$rawBreakdown = $_POST['given'] ?? [];
			foreach ($denominations as $value) {
				$raw = $rawBreakdown[(string)$value] ?? $rawBreakdown[$value] ?? '';
				if ($raw === '' || !is_numeric($raw)) {
					$clientBreakdown[$value] = 0;
					continue;
				}

				$qty = (int)$raw;
				if ($qty < 0) {
					$qty = 0;
				}

				$clientBreakdown[$value] = $qty;
			}

			// Remplace virgules par points pour les float
			$amountDue = str_replace(',', '.', $amountDue);

			if (!is_numeric($amountDue)) {
				$result = [
					'success' => false,
					'error'   => "Le montant à payer doit être un nombre.",
				];
			} else {
				$amountDueFloat = (float)$amountDue;
				$amountDueCents = (int) round($amountDueFloat * 100);
				$givenBreakdown = array_filter(
					$clientBreakdown,
					static fn(int $qty): bool => $qty > 0
				);

				$priorityValue = null;
				if ($prioritySelection !== 'auto' && is_numeric($prioritySelection)) {
					$priorityCandidate = (int)$prioritySelection;
					if (in_array($priorityCandidate, $denominations, true)) {
						$priorityValue = $priorityCandidate;
					}
				}

				$result = $cashRegister->computeChange(
					$amountDueCents,
					$givenBreakdown,
					$priorityValue,
					$changeOrderSelection
				);

				if ($result['success']) {
					$result['amountDueFormatted']   = CashRegister::formatCents($amountDueCents);
					$result['amountGivenFormatted'] = CashRegister::formatCents($result['amountGivenCents']);
					$result['changeFormatted']      = CashRegister::formatCents($result['changeCents']);
					$result['priorityValue']        = $priorityValue;
					$result['changeOrder']          = $result['changeOrder'] ?? $changeOrderSelection;
				}
			}
		}

		$denominationMeta = $cashRegister->getDenominationMetadata();

		// On charge la vue
		require __DIR__ . '/../vue/caisse.php';
	}
}
