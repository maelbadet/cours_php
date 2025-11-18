<!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<title>Caisse enregistreuse</title>
	<link rel="stylesheet" href="/shopping_register_step_2/assets/styles.css">
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
			<label for="amount_given">Montant donné (€)</label>
			<input type="text" id="amount_given" name="amount_given"
			       value="<?= htmlspecialchars((string)($amountGiven ?? '50'), ENT_QUOTES, 'UTF-8') ?>"
			       class="input-field">
		</div>

		<div class="form-group">
			<label for="priority_value">Dénomination prioritaire</label>
			<select id="priority_value" name="priority_value" class="select-field">
				<option value="auto" <?= ($prioritySelection ?? 'auto') === 'auto' ? 'selected' : '' ?>>
					Automatique (plus grands billets en premier)
				</option>
				<?php foreach ($denominations as $value): ?>
					<option value="<?= (int)$value ?>" <?= ((string)$prioritySelection === (string)$value) ? 'selected' : '' ?>>
						<?= htmlspecialchars(CashRegister::labelForValue((int)$value), ENT_QUOTES, 'UTF-8') ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="helper-text">Choisissez une valeur à privilégier dans la monnaie rendue.</p>
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
						<?= htmlspecialchars(CashRegister::labelForValue((int)$result['priorityValue']), ENT_QUOTES, 'UTF-8') ?>
					<?php else: ?>
						Automatique (du plus grand au plus petit)
					<?php endif; ?>
				</p>

				<h3 class="section-title">Détail de la monnaie rendue</h3>
				<ul class="money-list">
					<?php foreach ($result['breakdown'] as $value => $qty): ?>
						<li>
							<?= (int)$qty ?> ×
							<?= htmlspecialchars(CashRegister::labelForValue((int)$value), ENT_QUOTES, 'UTF-8') ?>
						</li>
					<?php endforeach; ?>
				</ul>
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
									<?= htmlspecialchars(CashRegister::labelForValue((int)$value), ENT_QUOTES, 'UTF-8') ?>
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
