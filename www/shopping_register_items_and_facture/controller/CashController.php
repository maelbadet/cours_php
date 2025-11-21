<?php
namespace App\Controller;
// Controller/CashController.php

use App\Model\CashRegister;
use App\Model\Items;

class CashController
{
	public function handleRequest(): void
	{
		$result = null;
		$amountDue = null;
		$prioritySelection  = 'auto';
		$changeOrderSelection = 'desc';
		$clientBreakdown = [];
		$itemQuantities = [];
		$itemsTotalCents = 0;
		$itemsSelectionDetails = [];

		$cashRegister = CashRegister::builder()->build();
		$itemsModel = new Items();
		$availableItems = $itemsModel->getAll();
		$itemsById = [];
		foreach ($availableItems as $item) {
			$itemsById[$item['id']] = $item;
		}
		$denominationMeta = $cashRegister->getDenominationMetadata();
		$denominations = array_keys($denominationMeta);
		foreach ($denominations as $value) {
			$clientBreakdown[$value] = 0;
		}

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$rawItems = $_POST['items'] ?? [];
			if (is_array($rawItems)) {
				foreach ($rawItems as $itemId => $qtyRaw) {
					if ($qtyRaw === '' || $qtyRaw === null) {
						continue;
					}

					if (!is_numeric($qtyRaw)) {
						continue;
					}

					$quantity = (int)$qtyRaw;
					if ($quantity < 0) {
						$quantity = 0;
					}

					$itemQuantities[(int)$itemId] = $quantity;
				}
			}
			foreach ($itemQuantities as $itemId => $qty) {
				if ($qty <= 0) {
					continue;
				}

				if (!isset($itemsById[$itemId])) {
					continue;
				}

				$item = $itemsById[$itemId];
				$lineTotal = $qty * $item['price_ttc_cents'];
				$itemsTotalCents += $lineTotal;
				$itemsSelectionDetails[$itemId] = [
					'id'               => $itemId,
					'name'             => $item['name'],
					'quantity'         => $qty,
					'price_ttc_cents'  => $item['price_ttc_cents'],
					'line_total_cents' => $lineTotal,
				];
			}

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
			if ($itemsTotalCents <= 0) {
				$result = [
					'success' => false,
					'error'   => 'Sélectionnez au moins un article à encaisser.',
				];
			} else {
				$amountDueCents = $itemsTotalCents;
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
					$result['itemsBreakdown']       = array_map(
						static function (array $line): array {
							return [
								'name'             => $line['name'],
								'quantity'         => $line['quantity'],
								'priceCents'       => $line['price_ttc_cents'],
								'lineTotalCents'   => $line['line_total_cents'],
							];
						},
						array_values($itemsSelectionDetails)
					);
					$result['itemsTotalFormatted'] = CashRegister::formatCents($itemsTotalCents);
				}
			}
		}

		$amountDue = CashRegister::formatCents($itemsTotalCents);
		$denominationMeta = $cashRegister->getDenominationMetadata();

		// On charge la vue
		require __DIR__ . '/../vue/caisse.php';
	}
}
