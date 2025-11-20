<!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<title>Caisse enregistreuse</title>
	<link rel="stylesheet" href="/shopping_register_items_and_facture/assets/styles.css">
</head>
<body class="app-body">

<div class="card">

	<h1>Caisse enregistreuse</h1>

	<form method="post" action="">

		<div class="form-group">
			<label>Articles à encaisser</label>
			<?php if (!empty($availableItems)): ?>
				<div class="table-wrapper">
					<table class="inventory-table items-table">
						<thead>
						<tr>
							<th>Article</th>
							<th>Prix HT</th>
							<th>TVA</th>
							<th>Prix TTC</th>
							<th>Quantité</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($availableItems as $item): ?>
							<tr>
								<td><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></td>
								<td><?= htmlspecialchars(CashRegister::formatCents((int)$item['price_ht_cents']), ENT_QUOTES, 'UTF-8') ?> €</td>
								<td>
									<?= $item['tva'] !== null
										? htmlspecialchars(rtrim(rtrim(number_format((float)$item['tva'], 2, ',', ' '), '0'), ','), ENT_QUOTES, 'UTF-8') . ' %'
										: '-' ?>
								</td>
								<td><?= htmlspecialchars(CashRegister::formatCents((int)$item['price_ttc_cents']), ENT_QUOTES, 'UTF-8') ?> €</td>
								<td>
									<input type="number"
									       min="0"
									       step="1"
									       class="input-field item-qty-input"
									       name="items[<?= (int)$item['id'] ?>]"
									       data-item-price="<?= (int)$item['price_ttc_cents'] ?>"
									       value="<?= htmlspecialchars((string)($itemQuantities[$item['id']] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<div class="items-total-display">
					<span>Total TTC sélectionné :</span>
					<strong id="items_total_value"><?= htmlspecialchars((string)($amountDue ?? '0,00'), ENT_QUOTES, 'UTF-8') ?> €</strong>
				</div>
			<?php else: ?>
				<p class="helper-text">Aucun article n'est encore configuré dans la base.</p>
			<?php endif; ?>
			<p class="helper-text">Indiquez les quantités d'articles à encaisser, le total se calcule automatiquement.</p>
		</div>

		<div class="form-group">
			<label for="amount_due">Montant à payer (TTC)</label>
			<input type="text" id="amount_due" name="amount_due"
			       value="<?= htmlspecialchars((string)($amountDue ?? '0,00'), ENT_QUOTES, 'UTF-8') ?>"
			       class="input-field"
			       readonly>
			<p class="helper-text">Ce montant est automatiquement calculé à partir des articles sélectionnés.</p>
		</div>

		<div class="form-group">
			<label>Monnaie reçue</label>
			<div class="denomination-grid">
				<?php foreach ($denominationMeta as $value => $meta): ?>
					<div class="denomination-card">
						<?php if (!empty($meta['image'])): ?>
							<img src="<?= htmlspecialchars($meta['image'], ENT_QUOTES, 'UTF-8') ?>" alt=""
							     class="denomination-image">
						<?php endif; ?>
						<div class="denomination-label">
							<?= htmlspecialchars($meta['label'] ?? CashRegister::labelForValue((int)$value), ENT_QUOTES, 'UTF-8') ?>
						</div>
						<input type="number" min="0" class="input-field denomination-input"
						       name="given[<?= (int)$value ?>]"
						       value="<?= htmlspecialchars((string)($clientBreakdown[$value] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
					</div>
				<?php endforeach; ?>
			</div>
			<p class="helper-text">Indiquez combien de billets/pièces de chaque valeur ont été reçus.</p>
		</div>

		<div class="form-group">
			<label for="priority_value">Dénomination prioritaire</label>
			<select id="priority_value" name="priority_value" class="select-field">
				<option value="auto" <?= ($prioritySelection ?? 'auto') === 'auto' ? 'selected' : '' ?>>
					Automatique (plus grands billets en premier)
				</option>
				<?php foreach ($denominationMeta as $value => $meta): ?>
					<option value="<?= (int)$value ?>" <?= ((string)$prioritySelection === (string)$value) ? 'selected' : '' ?>>
						<?= htmlspecialchars($meta['label'] ?? CashRegister::labelForValue((int)$value), ENT_QUOTES, 'UTF-8') ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="helper-text">Choisissez une valeur à privilégier dans la monnaie rendue.</p>
		</div>

		<div class="form-group switch-group">
			<label for="change_order_toggle">Ordre du rendu de monnaie</label>
			<div class="switch-control">
				<span class="switch-text">Grandes valeurs → Petites valeurs</span>
				<label class="toggle-switch">
					<input type="checkbox" id="change_order_toggle" name="change_order" value="asc"
						<?= (($changeOrderSelection ?? 'desc') === 'asc') ? 'checked' : '' ?>>
					<span class="slider"></span>
				</label>
				<span class="switch-text">Petites valeurs → Grandes valeurs</span>
			</div>
			<p class="helper-text">Activez l’interrupteur pour commencer par les plus petites valeurs.</p>
		</div>

		<button type="submit" class="button-primary">
			Calculer la monnaie
		</button>
	</form>

	<hr class="separator">

	<?php if ($result !== null): ?>
		<?php if (!empty($result['success']) && $result['success'] === true): ?>

			<div class="result-card">
				<h2 class="section-title">Résultat</h2>

				<p><strong>Montant à payer :</strong> <?= htmlspecialchars($result['amountDueFormatted'], ENT_QUOTES, 'UTF-8') ?> €</p>
				<p><strong>Montant donné :</strong> <?= htmlspecialchars($result['amountGivenFormatted'], ENT_QUOTES, 'UTF-8') ?> €</p>
				<p><strong>Monnaie à rendre :</strong> <?= htmlspecialchars($result['changeFormatted'], ENT_QUOTES, 'UTF-8') ?> €</p>
				<p class="priority-note">
					Priorité appliquée :
				<?php if (!empty($result['priorityValue'])): ?>
					<?= htmlspecialchars($denominationMeta[$result['priorityValue']]['label'] ?? CashRegister::labelForValue((int)$result['priorityValue']), ENT_QUOTES, 'UTF-8') ?>
					<?php else: ?>
						Automatique (du plus grand au plus petit)
					<?php endif; ?>
				</p>
				<?php
				$orderUsed = $result['changeOrder'] ?? 'desc';
				$orderRequested = $changeOrderSelection ?? 'desc';
				?>
				<p class="priority-note">
					Ordre de rendu :
					<?= ($orderUsed === 'asc')
						? 'Du plus petit au plus grand'
						: 'Du plus grand au plus petit' ?>
					<?php if ($orderRequested !== $orderUsed): ?>
						<span class="order-adjusted-note">(ajusté faute de pièces suffisantes)</span>
					<?php endif; ?>
				</p>

				<h3 class="section-title">Détail de la monnaie rendue</h3>
				<ul class="money-list">
					<?php foreach ($result['breakdown'] as $value => $qty): ?>
						<li>
							<?= (int)$qty ?> ×
							<?= htmlspecialchars($denominationMeta[$value]['label'] ?? CashRegister::labelForValue((int)$value), ENT_QUOTES, 'UTF-8') ?>
						</li>
					<?php endforeach; ?>
				</ul>

				<?php if (!empty($result['itemsBreakdown'])): ?>
					<h3 class="section-title">Articles encaissés</h3>
					<div class="table-wrapper">
						<table class="inventory-table items-summary-table">
							<thead>
							<tr>
								<th>Article</th>
								<th>Quantité</th>
								<th>Prix unitaire TTC</th>
								<th>Total TTC</th>
							</tr>
							</thead>
							<tbody>
							<?php foreach ($result['itemsBreakdown'] as $line): ?>
								<tr>
									<td><?= htmlspecialchars($line['name'], ENT_QUOTES, 'UTF-8') ?></td>
									<td><?= (int)$line['quantity'] ?></td>
									<td><?= htmlspecialchars(CashRegister::formatCents((int)$line['priceCents']), ENT_QUOTES, 'UTF-8') ?> €</td>
									<td><?= htmlspecialchars(CashRegister::formatCents((int)$line['lineTotalCents']), ENT_QUOTES, 'UTF-8') ?> €</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
							<tfoot>
							<tr>
								<td colspan="3">Total</td>
								<td><?= htmlspecialchars($result['itemsTotalFormatted'] ?? '0,00', ENT_QUOTES, 'UTF-8') ?> €</td>
							</tr>
							</tfoot>
						</table>
					</div>
				<?php endif; ?>

				<?php if (!empty($result['receivedBreakdown'])): ?>
					<h3 class="section-title">Détail de la monnaie reçue</h3>
					<ul class="money-list">
						<?php foreach ($result['receivedBreakdown'] as $value => $qty): ?>
							<li>
								<?= (int)$qty ?> ×
								<?= htmlspecialchars($denominationMeta[$value]['label'] ?? CashRegister::labelForValue((int)$value), ENT_QUOTES, 'UTF-8') ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<?php if (!empty($result['initialInventory']) && !empty($result['newInventory'])): ?>
				<h3 class="section-title">État de la caisse (avant / après transaction)</h3>

				<div class="table-wrapper">
					<table class="inventory-table">
						<thead>
					<tr>
						<th>Dénomination</th>
						<th>Avant</th>
						<th>Après</th>
					</tr>
					</thead>
					<tbody>
					<?php
					$initial = $result['initialInventory'];
					$new     = $result['newInventory'];

					// On trie les valeurs du plus grand au plus petit
					$values = array_keys($initial);
					rsort($values);

					foreach ($values as $value):
						$before = $initial[$value] ?? 0;
						$after  = $new[$value] ?? 0;
						?>
						<tr>
							<td>
								<?= htmlspecialchars($denominationMeta[$value]['label'] ?? CashRegister::labelForValue((int)$value), ENT_QUOTES, 'UTF-8') ?>
							</td>
							<td>
								<?= (int)$before ?>
							</td>
							<td>
								<?= (int)$after ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				</div>
			<?php endif; ?>

		<?php else: ?>

			<div class="error-card">
				<?= htmlspecialchars($result['error'] ?? 'Erreur inconnue.', ENT_QUOTES, 'UTF-8') ?>
			</div>

		<?php endif; ?>
	<?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
	const qtyInputs = document.querySelectorAll('[data-item-price]');
	const totalField = document.getElementById('amount_due');
	const totalDisplay = document.getElementById('items_total_value');
	const formatter = new Intl.NumberFormat('fr-FR', {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2,
	});

	const updateTotals = () => {
		let totalCents = 0;
		qtyInputs.forEach((input) => {
			const price = parseInt(input.dataset.itemPrice ?? '0', 10);
			const qty = parseInt(input.value ?? '0', 10);
			if (!Number.isFinite(price) || !Number.isFinite(qty) || qty <= 0) {
				return;
			}
			totalCents += price * qty;
		});

		const formatted = formatter.format(totalCents / 100);
		if (totalField) {
			totalField.value = formatted;
		}
		if (totalDisplay) {
			totalDisplay.textContent = formatted + ' €';
		}
	};

	qtyInputs.forEach((input) => {
		input.addEventListener('input', updateTotals);
	});

	updateTotals();
});
</script>
</body>
</html>
