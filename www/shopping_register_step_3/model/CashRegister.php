<?php
// Model/CashRegister.php

require_once __DIR__ . '/PDO.php';

class CashRegister
{
	private const DENOMINATIONS = [
		50000,
		20000,
		10000,
		5000,
		2000,
		1000,
		500,
		200,
		100,
		50,
		20,
		10,
		5,
		2,
		1,
	];

	private PDO $pdo;
	private array $inventory = [];
	private array $inventoryMeta = [];

	public function __construct(?PDO $pdo = null)
	{
		$this->pdo = $pdo ?? getPDO();
		$this->refreshInventory();
	}

	public function refreshInventory(): void
	{
		$rows = $this->fetchInventoryRows();
		$inventory = [];
		$meta = [];

		foreach ($rows as $value => $row) {
			$inventory[$value] = $row['quantity'];
			$meta[$value] = [
				'id'    => $row['id'],
				'name'  => $row['name'],
				'image' => $row['image'],
			];
		}

		foreach (self::DENOMINATIONS as $value) {
			if (!array_key_exists($value, $inventory)) {
				$inventory[$value] = 0;
				$meta[$value] = [
					'id'    => null,
					'name'  => self::labelForValue($value),
					'image' => null,
				];
			}
		}

		krsort($inventory);
		$orderedMeta = [];
		foreach (array_keys($inventory) as $value) {
			$orderedMeta[$value] = $meta[$value] ?? [
				'id'    => null,
				'name'  => self::labelForValue($value),
				'image' => null,
			];
		}

		$this->inventory = $inventory;
		$this->inventoryMeta = $orderedMeta;
	}

	public function getInventory(): array
	{
		return $this->inventory;
	}

	public function getDenominations(): array
	{
		return array_keys($this->inventory);
	}

	public function getDenominationMetadata(): array
	{
		$meta = [];
		foreach ($this->inventory as $value => $quantity) {
			$info = $this->inventoryMeta[$value] ?? [];
			$meta[$value] = [
				'label'    => $info['name'] ?? self::labelForValue($value),
				'image'    => $info['image'] ?? null,
				'quantity' => $quantity,
			];
		}
		return $meta;
	}

	/**
	 * Traite une transaction à partir du détail fourni par le client.
	 */
	public function computeChange(int $amountDueCents, array $givenBreakdown, ?int $priorityValue = null, string $changeOrder = 'desc', ?int $clientId = null): array
	{
		if (empty($givenBreakdown)) {
			return [
				'success' => false,
				'error'   => 'Veuillez indiquer les billets ou pièces reçus.',
			];
		}

		$amountGivenCents = $this->totalFromBreakdown($givenBreakdown);
		if ($amountGivenCents < $amountDueCents) {
			return [
				'success' => false,
				'error'   => 'Le montant donné est insuffisant.',
			];
		}

		$changeCents = $amountGivenCents - $amountDueCents;

		$this->refreshInventory();
		$initialInventory = $this->inventory;

		try {
			$this->pdo->beginTransaction();

			$this->addBreakdownToInventory($givenBreakdown);

			$orderToTry = in_array($changeOrder, ['asc', 'desc'], true) ? $changeOrder : 'desc';
			$breakdown = $this->makeChangeFromInventory($changeCents, $priorityValue, $orderToTry);
			if ($breakdown === null && $orderToTry === 'asc') {
				$orderToTry = 'desc';
				$breakdown = $this->makeChangeFromInventory($changeCents, $priorityValue, $orderToTry);
			}

			if ($breakdown === null) {
				$this->pdo->rollBack();
				$this->inventory = $initialInventory;

				return [
					'success' => false,
					'error'   => "Impossible de rendre la monnaie exacte avec l'état actuel de la caisse.",
				];
			}

			$this->persistInventory($clientId);
			$this->pdo->commit();

			return [
				'success'           => true,
				'changeCents'       => $changeCents,
				'breakdown'         => $breakdown,
				'initialInventory'  => $initialInventory,
				'newInventory'      => $this->inventory,
				'changeOrder'       => $orderToTry,
				'amountGivenCents'  => $amountGivenCents,
				'receivedBreakdown' => $givenBreakdown,
			];
		} catch (Throwable $e) {
			if ($this->pdo->inTransaction()) {
				$this->pdo->rollBack();
			}
			$this->inventory = $initialInventory;

			return [
				'success' => false,
				'error'   => "Une erreur est survenue pendant la mise à jour de la caisse.",
			];
		}
	}

	private function addBreakdownToInventory(array $breakdown): void
	{
		foreach ($breakdown as $value => $qty) {
			if (!array_key_exists($value, $this->inventory)) {
				continue;
			}

			$this->inventory[$value] += $qty;
		}
	}

	private function totalFromBreakdown(array $breakdown): int
	{
		$total = 0;
		foreach ($breakdown as $value => $qty) {
			if ($qty <= 0) {
				continue;
			}

			$total += $value * $qty;
		}

		return $total;
	}

	/**
	 * Tente de rendre un montant en utilisant l'inventaire courant (sortie).
	 * Retourne le breakdown ou null si impossible.
	 */
	private function makeChangeFromInventory(int $changeCents, ?int $priorityValue = null, string $order = 'desc'): ?array
	{
		$remaining = $changeCents;
		$breakdown = [];

		$tempInventory = $this->inventory;
		$values = array_keys($tempInventory);
		if ($order === 'asc') {
			sort($values);
		} else {
			rsort($values);
			$order = 'desc';
		}

		if ($priorityValue !== null && in_array($priorityValue, $values, true)) {
			$values = array_merge([$priorityValue], array_values(array_diff($values, [$priorityValue])));
		}

		foreach ($values as $value) {
			$qtyAvailable = $tempInventory[$value] ?? 0;
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
			return null;
		}

		$this->inventory = $tempInventory;

		return $breakdown;
	}

	private function persistInventory(?int $clientId): void
	{
		$stmt = $this->pdo->prepare('UPDATE caisse SET quantity = :quantity, client_id_update = :clientId, updated_at = NOW() WHERE id = :id');

		foreach ($this->inventory as $value => $quantity) {
			$meta = $this->inventoryMeta[$value] ?? null;
			if (empty($meta['id'])) {
				continue;
			}

			$stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
			if ($clientId === null) {
				$stmt->bindValue(':clientId', null, PDO::PARAM_NULL);
			} else {
				$stmt->bindValue(':clientId', $clientId, PDO::PARAM_INT);
			}
			$stmt->bindValue(':id', $meta['id'], PDO::PARAM_INT);
			$stmt->execute();
		}
	}

	private function fetchInventoryRows(): array
	{
		$stmt = $this->pdo->prepare('SELECT id, name, quantity, image FROM caisse WHERE deleted_at IS NULL ORDER BY id ASC');
		$stmt->execute();

		$rows = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$value = $this->valueFromName((string)$row['name']);
			if ($value === null) {
				continue;
			}

			$rows[$value] = [
				'id'       => (int)$row['id'],
				'name'     => (string)$row['name'],
				'quantity' => (int)$row['quantity'],
				'image'    => $row['image'] ? (string)$row['image'] : null,
			];
		}

		return $rows;
	}

	private function valueFromName(string $name): ?int
	{
		$normalized = preg_replace('/[^0-9,\.]/', '', str_replace('€', '', $name));
		if ($normalized === null) {
			return null;
		}

		$normalized = trim($normalized);
		if ($normalized === '') {
			return null;
		}

		$normalized = str_replace(',', '.', $normalized);
		if (!is_numeric($normalized)) {
			return null;
		}

		$lowerName = mb_strtolower($name);
		$value = (float)$normalized;

		if (str_contains($lowerName, 'centime')) {
			$candidate = (int)round($value);
			return $this->isValidDenomination($candidate) ? $candidate : null;
		}

		$candidateEuro = (int)round($value * 100);
		if ($this->isValidDenomination($candidateEuro)) {
			return $candidateEuro;
		}

		$candidateDirect = (int)round($value);
		if ($this->isValidDenomination($candidateDirect)) {
			return $candidateDirect;
		}

		return null;
	}

	private function isValidDenomination(int $value): bool
	{
		return in_array($value, self::DENOMINATIONS, true);
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
			return 'Billet de ' . $euro . ' €';
		}

		if ($euro >= 1) {
			return 'Pièce de ' . $euro . ' €';
		}

		return 'Pièce de ' . str_replace('.', ',', (string)$euro) . ' €';
	}
}
