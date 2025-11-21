<?php
namespace App\Model;

use PDO;

final class CashRegisterBuilder
{
	private ?PDO $pdo = null;
	private bool $refreshInventory = true;
	private ?array $inventoryOverride = null;
	private ?array $inventoryMetaOverride = null;

	public function withPDO(PDO $pdo): self
	{
		$this->pdo = $pdo;
		return $this;
	}

	public function withoutInitialRefresh(): self
	{
		$this->refreshInventory = false;
		return $this;
	}

	public function withInventory(array $inventory, array $inventoryMeta = []): self
	{
		$this->inventoryOverride = $inventory;
		$this->inventoryMetaOverride = $inventoryMeta;
		return $this;
	}

	public function build(): CashRegister
	{
		$cashRegister = new CashRegister($this->pdo, $this->refreshInventory);

		if ($this->inventoryOverride !== null) {
			$cashRegister->overrideInventory(
				$this->inventoryOverride,
				$this->inventoryMetaOverride ?? []
			);
		}

		return $cashRegister;
	}
}

