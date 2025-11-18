<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Caisse enregistreuse</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-start justify-center p-6">

<div class="w-full max-w-3xl bg-white shadow-lg rounded-lg p-8">

    <h1 class="text-2xl font-bold mb-6 text-center">Caisse enregistreuse</h1>

    <form method="post" action="" class="space-y-5">

        <div>
            <label for="amount_due" class="block mb-1 font-medium">Montant à payer (€)</label>
            <input type="text" id="amount_due" name="amount_due"
                   value="<?= htmlspecialchars((string)($amountDue ?? '33,48'), ENT_QUOTES, 'UTF-8') ?>"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label for="amount_given" class="block mb-1 font-medium">Montant donné (€)</label>
            <input type="text" id="amount_given" name="amount_given"
                   value="<?= htmlspecialchars((string)($amountGiven ?? '50'), ENT_QUOTES, 'UTF-8') ?>"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
            Calculer la monnaie
        </button>
    </form>

    <hr class="my-6">

	<?php if ($result !== null): ?>
		<?php if (!empty($result['success']) && $result['success'] === true): ?>

            <div class="p-4 bg-green-50 border-l-4 border-green-600 rounded mb-6">
                <h2 class="text-xl font-semibold mb-3">Résultat</h2>

                <p><strong>Montant à payer :</strong> <?= htmlspecialchars($result['amountDueFormatted'], ENT_QUOTES, 'UTF-8') ?> €</p>
                <p><strong>Montant donné :</strong> <?= htmlspecialchars($result['amountGivenFormatted'], ENT_QUOTES, 'UTF-8') ?> €</p>
                <p class="mt-2 text-lg font-bold text-green-700">
                    Monnaie à rendre : <?= htmlspecialchars($result['changeFormatted'], ENT_QUOTES, 'UTF-8') ?> €
                </p>

                <h3 class="text-lg font-semibold mt-4 mb-2">Détail de la monnaie rendue</h3>
                <ul class="list-disc ml-6 space-y-1">
					<?php foreach ($result['breakdown'] as $value => $qty): ?>
                        <li>
							<?= (int)$qty ?> ×
							<?= htmlspecialchars(CashRegister::labelForValue((int)$value), ENT_QUOTES, 'UTF-8') ?>
                        </li>
					<?php endforeach; ?>
                </ul>
            </div>

			<?php if (!empty($result['initialInventory']) && !empty($result['newInventory'])): ?>
                <h3 class="text-lg font-semibold mb-3">État de la caisse (avant / après transaction)</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200 text-sm">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 border-b text-left">Dénomination</th>
                            <th class="px-3 py-2 border-b text-right">Avant</th>
                            <th class="px-3 py-2 border-b text-right">Après</th>
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
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 border-b">
									<?= htmlspecialchars(CashRegister::labelForValue((int)$value), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="px-3 py-2 border-b text-right">
									<?= (int)$before ?>
                                </td>
                                <td class="px-3 py-2 border-b text-right">
									<?= (int)$after ?>
                                </td>
                            </tr>
						<?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
			<?php endif; ?>

		<?php else: ?>

            <div class="p-4 bg-red-50 border-l-4 border-red-600 rounded text-red-700">
				<?= htmlspecialchars($result['error'] ?? 'Erreur inconnue.', ENT_QUOTES, 'UTF-8') ?>
            </div>

		<?php endif; ?>
	<?php endif; ?>

</div>

</body>
</html>
