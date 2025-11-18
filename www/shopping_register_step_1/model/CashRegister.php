<?php
// Model/CashRegister.php

class CashRegister
{
	// Montants en CENTIMES
	private array $inventory = [
		50000 => 1,   // 1 billet de 500
		20000 => 2,   // 2 billets de 200
		10000 => 2,   // 2 billets de 100
		5000  => 4,   // 4 billets de 50
		2000  => 1,   // 1 billet de 20
		1000  => 23,  // 23 billets de 10
		500   => 0,   // 0 billet de 5
		200   => 34,  // 34 pièces de 2
		100   => 23,  // 23 pièces de 1
		50    => 23,  // 23 pièces de 0,5
		20    => 80,  // 80 pièces de 0,2
		10    => 12,  // 12 pièces de 0,1
		5     => 8,   // 8 pièces de 0,05
		2     => 45,  // 45 pièces de 0,02
		1     => 12,  // 12 pièces de 0,01
	];

	public function getInventory(): array
	{
		return $this->inventory;
	}

	/**
	 * Traite une transaction :
	 * - ajoute ce que le client donne
	 * - retire ce que l'on rend
	 * - met à jour l'état de caisse
	 */
	public function computeChange(int $amountDueCents, int $amountGivenCents): array
	{
		if ($amountGivenCents < $amountDueCents) {
			return [
				'success' => false,
				'error'   => "Le montant donné est insuffisant.",
			];
		}

		$changeCents = $amountGivenCents - $amountDueCents;
		$originalChange = $changeCents;

		// Sauvegarde de l'état initial avant toute opération
		$initialInventory = $this->inventory;

		// 1) ENTRÉE : on ajoute à la caisse ce que le client donne
		$this->addAmountToInventory($amountGivenCents);

		// 2) SORTIE : on tente de rendre la monnaie depuis la caisse mise à jour
		$breakdown = $this->makeChangeFromInventory($changeCents);

		if ($breakdown === null) {
			// Impossible de rendre la monnaie, on rollback la caisse
			$this->inventory = $initialInventory;

			return [
				'success' => false,
				'error'   => "Impossible de rendre la monnaie exacte avec l'état actuel de la caisse.",
			];
		}

		// newInventory = état final après entrée + sortie
		$newInventory = $this->inventory;

		return [
			'success'           => true,
			'changeCents'       => $originalChange,
			'breakdown'         => $breakdown,
			'initialInventory'  => $initialInventory,
			'newInventory'      => $newInventory,
		];
	}

	/**
	 * Ajoute un montant à la caisse en le décomposant
	 * avec les valeurs disponibles (entrée).
	 */
	private function addAmountToInventory(int $amountCents): void
	{
		$values = array_keys($this->inventory);
		rsort($values); // du plus grand au plus petit

		$remaining = $amountCents;

		foreach ($values as $value) {
			if ($remaining <= 0) {
				break;
			}

			$nb = intdiv($remaining, $value);
			if ($nb > 0) {
				$this->inventory[$value] += $nb;
				$remaining -= $nb * $value;
			}
		}
		// Comme on a 1 centime dans les valeurs, remaining devrait être 0.
	}

	/**
	 * Tente de rendre un montant en utilisant l'inventaire courant (sortie).
	 * Retourne le breakdown ou null si impossible.
	 */
	private function makeChangeFromInventory(int $changeCents): ?array
	{
		$remaining = $changeCents;
		$breakdown = [];

		// On travaille sur une copie pour valider avant d'appliquer
		$tempInventory = $this->inventory;
		krsort($tempInventory); // plus grande valeur d'abord

		foreach ($tempInventory as $value => $qtyAvailable) {
			if ($remaining <= 0) {
				break;
			}

			if ($value > $remaining || $qtyAvailable <= 0) {
				continue;
			}

			$maxNeeded = intdiv($remaining, $value);
			$toGive = min($maxNeeded, $qtyAvailable);

			if ($toGive > 0) {
				$breakdown[$value] = $toGive;
				$remaining -= $toGive * $value;
				$tempInventory[$value] -= $toGive;
			}
		}

		if ($remaining > 0) {
			return null; // impossible de rendre
		}

		// On valide la nouvelle caisse
		$this->inventory = $tempInventory;

		return $breakdown;
	}

	/**
	 * Helper pour formater les centimes en "xx,yy".
	 */
	public static function formatCents(int $cents): string
	{
		return number_format($cents / 100, 2, ',', ' ');
	}

	/**
	 * Helper pour obtenir un label lisible d'une valeur en cents.
	 */
	public static function labelForValue(int $value): string
	{
		$euro = $value / 100;
		if ($euro >= 5) {
			return "Billet de " . $euro . " €";
		}

		if ($euro >= 1) {
			return "Pièce de " . $euro . " €";
		}

		return "Pièce de " . str_replace('.', ',', (string)$euro) . " €";
	}
}
