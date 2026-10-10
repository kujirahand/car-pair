<?php
require_once __DIR__ . '/../logic/FamilyLabel.php';
$familyInitial = FamilyLabel::initial($p['family_id'] ?? '');
?>
<?php if ($familyInitial !== ''): ?>
<span class="family-initial" style="--family-hue: <?= FamilyLabel::hue($p['family_id']) ?>" title="<?= htmlspecialchars((string)$p['family_id'], ENT_QUOTES, 'UTF-8') ?>" aria-label="家族: <?= htmlspecialchars((string)$p['family_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($familyInitial, ENT_QUOTES, 'UTF-8') ?></span>
<?php endif; ?>
