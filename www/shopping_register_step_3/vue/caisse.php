<!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<title>Caisse enregistreuse</title>
	<link rel="stylesheet" href="/shopping_register_step_3/assets/styles.css">
</head>
<body class="app-body">

<div class="card">

	<h1>Caisse enregistreuse</h1>

	<form method="post" action="">

		<div class="form-group">
			<label for="amount_due">Montant à payer (€)</label>
			<input type="text" id="amount_due" name="amount_due"
			       value="<?= htmlspecialchars((string)($amountDue ?? '33,48'), ENT_QUOTES, 'UTF-8') ?>"
			       class="input-field">
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

</body>
</html>
