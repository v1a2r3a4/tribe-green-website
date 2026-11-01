
<?php

$seoDefaults = [
    'title' => 'Tribe Green | Corporate Gifting Solutions',
    'description' => 'Discover premium corporate gifts, employee welcome kits, branded merchandise and customised business gifting solutions with Tribe Green.',
    'image' => '/assets/images/tribe-green-share.jpg',
];

$seo = array_merge(
    $seoDefaults,
    $seo ?? []
);

// Replace with your real production website origin.
// Do not use localhost for live social sharing.
$siteUrl = 'https://YOUR-LIVE-DOMAIN.com';

$seoTitle = $seo['title'];
$seoDescription = $seo['description'];
$seoImage = $siteUrl . $seo['image'];
$seoCanonical = $siteUrl . ($_SERVER['REQUEST_URI'] ?? '/');

$seoEscape = static function ($value): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
};
?>

<title><?= $seoEscape($seoTitle) ?></title>
<meta name="description" content="<?= $seoEscape($seoDescription) ?>">

<meta property="og:type" content="website">
<meta property="og:site_name" content="Tribe Green">
<meta property="og:title" content="<?= $seoEscape($seoTitle) ?>">
<meta property="og:description" content="<?= $seoEscape($seoDescription) ?>">
<meta property="og:image" content="<?= $seoEscape($seoImage) ?>">
<meta property="og:url" content="<?= $seoEscape($seoCanonical) ?>">
<meta property="og:image:alt" content="Tribe Green corporate gifting solutions">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $seoEscape($seoTitle) ?>">
<meta name="twitter:description" content="<?= $seoEscape($seoDescription) ?>">
<meta name="twitter:image" content="<?= $seoEscape($seoImage) ?>">
