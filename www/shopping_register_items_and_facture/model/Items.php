<?php

require_once __DIR__ . '/PDO.php';

class Items
{
	private PDO $pdo;

	public function __construct(?PDO $pdo = null)
	{
		$this->pdo = $pdo ?? getPDO();
	}

	/**
	 * Retourne la liste complète des articles disponibles.
	 */
	public function getAll(): array
	{
		$stmt = $this->pdo->query('SELECT id, name, price_ht, tva FROM items ORDER BY name ASC');

		$items = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$items[] = $this->normalizeRow($row);
		}

		return $items;
	}

	/**
	 * Retourne les articles correspondants aux identifiants fournis (indexés par id).
	 *
	 * @param int[] $ids
	 */
	public function getByIds(array $ids): array
	{
		$ids = array_values(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0));
		if (empty($ids)) {
			return [];
		}

		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$stmt = $this->pdo->prepare("SELECT id, name, price_ht, tva FROM items WHERE id IN ($placeholders)");
		foreach ($ids as $index => $id) {
			$stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
		}
		$stmt->execute();

		$items = [];
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$item = $this->normalizeRow($row);
			$items[$item['id']] = $item;
		}

		return $items;
	}

	private function normalizeRow(array $row): array
	{
		$priceHtCents = $this->toCents($row['price_ht'] ?? null);
		$tva = isset($row['tva']) ? (float)$row['tva'] : null;
		$priceTtcCents = $this->computePriceTtcCents($priceHtCents, $tva);

		return [
			'id'              => isset($row['id']) ? (int)$row['id'] : 0,
			'name'            => isset($row['name']) ? (string)$row['name'] : '',
			'price_ht_cents'  => $priceHtCents,
			'price_ttc_cents' => $priceTtcCents,
			'tva'             => $tva,
		];
	}

	private function computePriceTtcCents(int $priceHtCents, ?float $tva): int
	{
		if ($priceHtCents <= 0) {
			return 0;
		}

		$rate = ($tva ?? 0.0) / 100;

		return (int)round($priceHtCents * (1 + $rate));
	}

	private function toCents($value): int
	{
		if ($value === null || $value === '') {
			return 0;
		}

		if (is_string($value)) {
			$value = str_replace(',', '.', $value);
		}

		return (int)round((float)$value * 100);
	}
}
