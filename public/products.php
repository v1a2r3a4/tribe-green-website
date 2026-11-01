<?php

require_once __DIR__ . '/../app/config/database.php';


function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Read filters from the URL.
$search = trim($_GET['search'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$occasionSlug = trim($_GET['occasion'] ?? '');
$budget = trim($_GET['budget'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 8;

// Load filter options.
$categories = $pdo->query(
    'SELECT name, slug FROM categories ORDER BY name'
)->fetchAll();

$occasions = $pdo->query(
    'SELECT name, slug FROM occasions ORDER BY name'
)->fetchAll();

// Build a safe, parameterised product query.
$where = ['p.is_active = 1'];
$params = [];

if ($search !== '') {
    $where[] = '(p.name LIKE :search_name OR
                 p.short_description LIKE :search_description OR
                 p.description LIKE :search_full_description)';
    $searchTerm = '%' . $search . '%';
    $params['search_name'] = $searchTerm;
    $params['search_description'] = $searchTerm;
    $params['search_full_description'] = $searchTerm;
}

if ($categorySlug !== '') {
    $where[] = 'c.slug = :category_slug';
    $params['category_slug'] = $categorySlug;
}

if ($occasionSlug !== '') {
    $where[] = 'EXISTS (
        SELECT 1
        FROM product_occasions po
        INNER JOIN occasions o ON o.id = po.occasion_id
        WHERE po.product_id = p.id
        AND o.slug = :occasion_slug
    )';
    $params['occasion_slug'] = $occasionSlug;
}

// Budget ranges use product price-range overlap.
$budgetRanges = [
    'under-500' => [0, 500],
    '500-1500' => [500, 1500],
    '1500-3000' => [1500, 3000],
    'above-3000' => [3000, null],
];

if (isset($budgetRanges[$budget])) {
    [$minimumBudget, $maximumBudget] = $budgetRanges[$budget];

    if ($maximumBudget === null) {
        $where[] = 'p.max_price > :minimum_budget';
        $params['minimum_budget'] = $minimumBudget;
    } else {
        $where[] = 'p.max_price >= :minimum_budget
                    AND p.min_price <= :maximum_budget';
        $params['minimum_budget'] = $minimumBudget;
        $params['maximum_budget'] = $maximumBudget;
    }
}

$whereSql = implode(' AND ', $where);

// Only allow known sort expressions.
$sortOptions = [
    'newest' => 'p.id DESC',
    'price-low' => 'p.min_price ASC, p.id DESC',
    'price-high' => 'p.max_price DESC, p.id DESC',
    'name' => 'p.name ASC',
];

$orderBy = $sortOptions[$sort] ?? $sortOptions['newest'];

// Count results for pagination.
$countSql = "
    SELECT COUNT(*)
    FROM products p
    INNER JOIN categories c ON c.id = p.category_id
    WHERE $whereSql
";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = (int) $countStmt->fetchColumn();

$totalPages = max(1, (int) ceil($totalProducts / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Load the current page of products.
$productSql = "
    SELECT p.id, p.name, p.slug, p.image_path,
           p.short_description, p.min_price, p.max_price,
           c.name AS category_name
    FROM products p
    INNER JOIN categories c ON c.id = p.category_id
    WHERE $whereSql
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
";

$productStmt = $pdo->prepare($productSql);
$productStmt->execute($params);
$products = $productStmt->fetchAll();

function catalogueUrl(array $changes = []): string
{
    $query = $_GET;

    foreach ($changes as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }

    return 'products.php?' . http_build_query($query);
}

$pageTitle = 'Gift Catalogue | Tribe Green';

$categoryIcons = [
    'Drinkware' => '☕',
    'Apparel' => '👕',
    'Work Essentials' => '💼',
    'Wellness' => '🌿',
];

$seo = [
    'title' => 'Corporate Gift Catalogue | Tribe Green',
    'description' => 'Explore corporate gifts, branded merchandise and employee gifting products by category, occasion and budget.',
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   
    <?php require dirname(__DIR__) . '/app/helpers/seo.php'; ?>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/catalogue.css">
</head>
<body>

<header class="site-header">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand brand-logo" href="index.php">
                <span class="brand-mark">T</span>
                <span>tribe<span class="brand-green">green</span></span>
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavigation"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavigation">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="products.php">Our Gifts</a>
                    </li>
                </ul>

                <a href="index.php#contact" class="btn btn-brand">
                    Get a Quote ↗
                </a>
            </div>
        </div>
    </nav>
</header>

<main>
    <section class="catalogue-banner">
        <div class="container">
            <span class="eyebrow">THE TRIBE GREEN COLLECTION</span>
            <h1>Find a gift that <span>means more.</span></h1>
            <p>
                Thoughtful corporate gifts for employees, clients,
                partners and every meaningful occasion.
            </p>
            <div class="catalogue-breadcrumb">
                <a href="index.php">Home</a>
                <span>/</span>
                <span>Gift Catalogue</span>
            </div>
        </div>
    </section>

    <section class="catalogue-section">
        <div class="container">
            <form action="products.php" method="GET" id="filterForm">
                <div class="catalogue-toolbar">
                    <div class="catalogue-search">
                        <span aria-hidden="true">⌕</span>
                        <input
                            type="search"
                            name="search"
                            value="<?= e($search) ?>"
                            placeholder="Search gifts..."
                            aria-label="Search products"
                        >
                        <button type="submit">Search</button>
                    </div>

                    <div class="sort-control">
                        <label for="sort">Sort by</label>
                        <select id="sort" name="sort">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>
                                Newest
                            </option>
                            <option value="price-low" <?= $sort === 'price-low' ? 'selected' : '' ?>>
                                Price: Low to High
                            </option>
                            <option value="price-high" <?= $sort === 'price-high' ? 'selected' : '' ?>>
                                Price: High to Low
                            </option>
                            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>
                                Name: A to Z
                            </option>
                        </select>
                    </div>
                </div>

                <div class="catalogue-layout">
                    <aside class="filter-sidebar">
                        <div class="filter-heading">
                            <h2>Filters</h2>
                            <a href="products.php">Clear all</a>
                        </div>

                        <div class="filter-group">
                            <h3>Categories</h3>

                            <label class="filter-option">
                                <input
                                    type="radio"
                                    name="category"
                                    value=""
                                    <?= $categorySlug === '' ? 'checked' : '' ?>
                                >
                                <span>All categories</span>
                            </label>

                            <?php foreach ($categories as $category): ?>
                                <label class="filter-option">
                                    <input
                                        type="radio"
                                        name="category"
                                        value="<?= e($category['slug']) ?>"
                                        <?= $categorySlug === $category['slug'] ? 'checked' : '' ?>
                                    >
                                    <span><?= e($category['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="filter-group">
                            <h3>Occasions</h3>

                            <label class="filter-option">
                                <input
                                    type="radio"
                                    name="occasion"
                                    value=""
                                    <?= $occasionSlug === '' ? 'checked' : '' ?>
                                >
                                <span>All occasions</span>
                            </label>

                            <?php foreach ($occasions as $occasion): ?>
                                <label class="filter-option">
                                    <input
                                        type="radio"
                                        name="occasion"
                                        value="<?= e($occasion['slug']) ?>"
                                        <?= $occasionSlug === $occasion['slug'] ? 'checked' : '' ?>
                                    >
                                    <span><?= e($occasion['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="filter-group">
                            <h3>Budget per gift</h3>

                            <?php
                            $budgetLabels = [
                                'under-500' => 'Under ₹500',
                                '500-1500' => '₹500 – ₹1,500',
                                '1500-3000' => '₹1,500 – ₹3,000',
                                'above-3000' => 'Above ₹3,000',
                            ];
                            ?>

                            <label class="filter-option">
                                <input
                                    type="radio"
                                    name="budget"
                                    value=""
                                    <?= $budget === '' ? 'checked' : '' ?>
                                >
                                <span>All budgets</span>
                            </label>

                            <?php foreach ($budgetLabels as $value => $label): ?>
                                <label class="filter-option">
                                    <input
                                        type="radio"
                                        name="budget"
                                        value="<?= e($value) ?>"
                                        <?= $budget === $value ? 'checked' : '' ?>
                                    >
                                    <span><?= e($label) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <button class="btn btn-brand filter-apply" type="submit">
                            Apply filters
                        </button>
                    </aside>

                    <div class="catalogue-results">
                        <div class="results-heading">
                            <div>
                                <h2>Our gifts</h2>
                                <p>
                                    Showing <?= $totalProducts === 0 ? 0 : $offset + 1 ?>
                                    – <?= min($offset + $perPage, $totalProducts) ?>
                                    of <?= $totalProducts ?> products
                                </p>
                            </div>
                        </div>

                        <?php if (count($products) > 0): ?>
                            <div class="row g-4">
                                <?php foreach ($products as $product): ?>
                                    <?php
                                    $categoryName = $product['category_name'];
                                    $icon = $categoryIcons[$categoryName] ?? '✦';
                                    ?>
                                    <div class="col-6 col-xl-3">
                                        <article class="product-card">
                                            <a
                                                class="catalogue-product-image"
                                                href="product-details.php?slug=<?= rawurlencode($product['slug']) ?>"
                                            >
                                                
<?php
$imagePath = trim($product['image_path'] ?? '');

$imageUrl = $imagePath !== ''
    ? $imagePath
    : '';
?>

<?php if ($imageUrl !== ''): ?>
    <img
        src="<?= e($imageUrl) ?>"
        alt="<?= e($product['name']) ?>"
        loading="lazy"
        onerror="this.onerror=null; this.src='assets/images/product-placeholder.png';"
    >
<?php else: ?>
    <div class="product-placeholder">
        <span class="product-placeholder-icon">
            <?= e($icon) ?>
        </span>
        <span class="placeholder-brand">TRIBE GREEN</span>
    </div>
<?php endif; ?>


                                                <span class="product-category">
                                                    <?= e($categoryName) ?>
                                                </span>
                                            </a>

                                            <div class="product-info">
                                                <h3>
                                                    <a href="product-details.php?slug=<?= rawurlencode($product['slug']) ?>">
                                                        <?= e($product['name']) ?>
                                                    </a>
                                                </h3>

                                                <p>
                                                    <?= e($product['short_description']) ?>
                                                </p>

                                                <div class="product-price">
                                                    ₹<?= number_format((float) $product['min_price']) ?>
                                                    – ₹<?= number_format((float) $product['max_price']) ?>
                                                </div>

                                                <a
                                                    class="product-link"
                                                    href="product-details.php?slug=<?= rawurlencode($product['slug']) ?>"
                                                >
                                                    View details <span aria-hidden="true">→</span>
                                                </a>
                                            </div>
                                        </article>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($totalPages > 1): ?>
                                <nav class="catalogue-pagination" aria-label="Product pages">
                                    <?php if ($page > 1): ?>
                                        <a href="<?= e(catalogueUrl(['page' => $page - 1])) ?>">
                                            ← Previous
                                        </a>
                                    <?php endif; ?>

                                    <span>
                                        Page <?= $page ?> of <?= $totalPages ?>
                                    </span>

                                    <?php if ($page < $totalPages): ?>
                                        <a href="<?= e(catalogueUrl(['page' => $page + 1])) ?>">
                                            Next →
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="no-products">
                                <span>⌕</span>
                                <h3>No gifts found</h3>
                                <p>Try another search or clear your filters.</p>
                                <a href="products.php" class="btn btn-brand">
                                    View all gifts
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <a class="navbar-brand brand-logo" href="index.php">
            <span class="brand-mark">T</span>
            <span>tribe<span class="brand-green">green</span></span>
        </a>
        <p>Thoughtful gifting. Meaningful connections.</p>
        <span>© <?= date('Y') ?> Tribe Green</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
    // Apply category, occasion, and budget filters when changed.
    document.querySelectorAll(
        '#filterForm input[type="radio"]'
    ).forEach(function (input) {
        input.addEventListener('change', function () {
            document.getElementById('filterForm').requestSubmit();
        });
    });

    document.getElementById('sort').addEventListener('change', function () {
        document.getElementById('filterForm').requestSubmit();
    });
</script>

</body>
</html>