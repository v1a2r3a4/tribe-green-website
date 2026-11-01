
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../app/config/database.php';

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$allowedStatuses = ['New', 'Contacted', 'Converted', 'Lost'];

if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

$counts = $pdo->query(
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'New'), 0) AS new_count,
        COALESCE(SUM(status = 'Contacted'), 0) AS contacted_count,
        COALESCE(SUM(status = 'Converted'), 0) AS converted_count,
        COALESCE(SUM(status = 'Lost'), 0) AS lost_count
     FROM enquiries"
)->fetch();

$sql = "
    SELECT
        e.id,
        e.reference_no,
        e.full_name,
        e.company_name,
        e.phone,
        e.email,
        e.quantity,
        e.budget_range,
        e.status,
        e.created_at,
        o.name AS occasion_name,
        GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') AS product_names
    FROM enquiries e
    LEFT JOIN occasions o ON o.id = e.occasion_id
    LEFT JOIN enquiry_products ep ON ep.enquiry_id = e.id
    LEFT JOIN products p ON p.id = ep.product_id
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (
        e.reference_no LIKE :ref
        OR e.full_name LIKE :name
        OR e.company_name LIKE :company
        OR e.phone LIKE :phone
        OR e.email LIKE :email
    )";

    $term = '%' . $search . '%';
    $params['ref'] = $term;
    $params['name'] = $term;
    $params['company'] = $term;
    $params['phone'] = $term;
    $params['email'] = $term;
}

if ($status !== '') {
    $sql .= ' AND e.status = :status';
    $params['status'] = $status;
}

$sql .= "
    GROUP BY
        e.id, e.reference_no, e.full_name, e.company_name,
        e.phone, e.email, e.quantity, e.budget_range,
        e.status, e.created_at, o.name
    ORDER BY e.created_at DESC, e.id DESC
    LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$enquiries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enquiry Dashboard | Tribe Green</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f7f9f3; color: #26352b; }
        .admin-header { background: #fff; border-bottom: 1px solid #e4e9df; }
        .admin-main { padding: 35px 0 60px; }
        .stat-card, .table-card { background: #fff; border: 1px solid #e4e9df; border-radius: 12px; }
        .stat-card { padding: 20px; height: 100%; }
        .stat-card small { color: #737b72; }
        .stat-card strong { display: block; margin-top: 8px; font-size: 29px; }
        .table-card { margin-top: 25px; overflow: hidden; }
        .table-card-heading { padding: 22px; border-bottom: 1px solid #e4e9df; }
        .table th { color: #687267; font-size: 11px; text-transform: uppercase; white-space: nowrap; }
        .table td { font-size: 13px; vertical-align: middle; }
        .status-pill { display: inline-block; padding: 5px 9px; border-radius: 30px; background: #edf3e8; color: #245b42; font-size: 11px; font-weight: 700; }
        .admin-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .filter-form { display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-form .form-control { min-width: 220px; }
        @media (max-width: 575px) {
            .admin-main { padding-top: 22px; }
            .table-card-heading { padding: 16px; }
        }
    </style>
</head>
<body>
<header class="admin-header py-3">
    <div class="container d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <a class="navbar-brand brand-logo" href="../index.php">
            <span class="brand-mark">T</span>
            <span>tribe<span class="brand-green">green</span></span>
        </a>

        <div class="admin-actions">
            <span class="small text-muted">
                Signed in as <?= adminEscape((string) $_SESSION['admin_username']) ?>
            </span>
            <a href="logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
        </div>
    </div>
</header>

<main class="admin-main">
    <div class="container">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
            <div>
                <h1 class="h2 fw-bold mb-2">Enquiry dashboard</h1>
                <p class="text-muted mb-0">Manage your corporate gifting enquiries.</p>
            </div>
            <a href="../index.php" class="btn btn-brand">View website ↗</a>
        </div>

        <div class="row g-3">
            <div class="col-6 col-lg">
                <div class="stat-card"><small>Total enquiries</small><strong><?= (int) $counts['total'] ?></strong></div>
            </div>
            <div class="col-6 col-lg">
                <div class="stat-card"><small>New</small><strong><?= (int) $counts['new_count'] ?></strong></div>
            </div>
            <div class="col-6 col-lg">
                <div class="stat-card"><small>Contacted</small><strong><?= (int) $counts['contacted_count'] ?></strong></div>
            </div>
            <div class="col-6 col-lg">
                <div class="stat-card"><small>Converted</small><strong><?= (int) $counts['converted_count'] ?></strong></div>
            </div>
            <div class="col-6 col-lg">
                <div class="stat-card"><small>Lost</small><strong><?= (int) $counts['lost_count'] ?></strong></div>
            </div>
        </div>

        <section class="table-card">
            <div class="table-card-heading">
                <h2 class="h5 fw-bold mb-3">Customer enquiries</h2>

                <form method="get" class="filter-form">
                    <input class="form-control" type="search" name="search"
                           value="<?= adminEscape($search) ?>"
                           placeholder="Search name, company, phone or reference">

                    <select class="form-select" name="status" style="max-width: 180px;">
                        <option value="">All statuses</option>
                        <?php foreach ($allowedStatuses as $option): ?>
                            <option value="<?= adminEscape($option) ?>"
                                <?= $status === $option ? 'selected' : '' ?>>
                                <?= adminEscape($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button class="btn btn-brand" type="submit">Search</button>
                    <a class="btn btn-outline-secondary" href="index.php">Reset</a>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Occasion</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$enquiries): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                No enquiries found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($enquiries as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= adminEscape($item['reference_no']) ?></strong>
                                </td>
                                <td>
                                    <strong><?= adminEscape($item['full_name']) ?></strong><br>
                                    <span class="text-muted"><?= adminEscape($item['company_name']) ?></span><br>
                                    <span class="text-muted"><?= adminEscape($item['phone']) ?></span>
                                </td>
                                <td><?= adminEscape($item['product_names'] ?: 'No product linked') ?></td>
                                <td><?= (int) $item['quantity'] ?></td>
                                <td><?= adminEscape($item['occasion_name'] ?? '—') ?></td>
                                <td><span class="status-pill"><?= adminEscape($item['status']) ?></span></td>
                                <td><?= adminEscape(date('d M Y', strtotime($item['created_at']))) ?></td>
                                <td>
                                    <a class="btn btn-sm btn-outline-success"
                                       href="view-enquiry.php?id=<?= (int) $item['id'] ?>">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-3 small text-muted">
                Showing up to 200 matching enquiries, newest first.
            </div>
        </section>
    </div>
</main>
</body>
</html>
