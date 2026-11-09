<?php $schoolInformation = require __DIR__ . '/../config/school.php'; ?>
<section class="card border-0 shadow-sm mt-4" aria-label="School information">
    <div class="card-body">
        <h3 class="h5"><?= htmlspecialchars($schoolInformation['name']) ?></h3>
        <p><?= htmlspecialchars($schoolInformation['tagline']) ?></p>
        <p class="mb-1"><strong>Motto:</strong> <?= htmlspecialchars($schoolInformation['motto']) ?></p>
        <p class="mb-1"><strong>Location:</strong> <?= htmlspecialchars($schoolInformation['address']) ?></p>
        <p class="mb-1"><strong>Telephone:</strong> <?= htmlspecialchars($schoolInformation['telephone']) ?></p>
        <p class="mb-1"><strong>Classes:</strong> <?= htmlspecialchars($schoolInformation['classes']) ?></p>
        <p class="mb-1"><strong>Registration:</strong> <?= htmlspecialchars($schoolInformation['registration_hours']) ?></p>
        <p class="mb-1"><strong>Registration fees:</strong> Continuing pupils: UGX <?= number_format($schoolInformation['continuing_registration_fee']) ?>; newcomers: UGX <?= number_format($schoolInformation['newcomer_registration_fee']) ?>.</p>
        <p class="mb-0"><?= htmlspecialchars($schoolInformation['facilities']) ?></p>
    </div>
</section>
