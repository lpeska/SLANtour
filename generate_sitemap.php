<?php
// Path to the JSON file
$jsonFilePath = 'data_group.json';

// Check if the JSON file exists
if (!file_exists($jsonFilePath)) {
    die('JSON file not found.');
}

// Read and decode the JSON file
$jsonData = file_get_contents($jsonFilePath);
$tours = json_decode($jsonData, true);

// Start building the XML sitemap
header("Content-Type: application/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// Loop through each item in the JSON data
foreach ($tours as $item) {
    // Extract relevant data
    $idSerial = $item['id_serial'];
    $nazev = $item['nazev'];
    $idZajezd = json_decode($item['id_zajezd'], true); // Decode id_zajezd array
    $odDates = explode(',', $item['od']); // Split "od" dates
    $doDates = explode(',', $item['do']); // Split "do" dates

    // Generate URLs for each id_zajezd and date range
    foreach ($idZajezd as $index => $zajezdId) {
        $odDate = $odDates[$index] ?? null;
        $doDate = $doDates[$index] ?? null;

        // Construct the URL (modify the base URL as needed)
        $url = "https://example.com/zajezd/$idSerial/$zajezdId";

        // Add the URL to the sitemap
        echo '<url>';
        echo '<loc>' . htmlspecialchars($url) . '</loc>';
        echo '<lastmod>' . htmlspecialchars($odDate) . '</lastmod>';
        echo '<changefreq>weekly</changefreq>';
        echo '<priority>0.8</priority>';
        echo '</url>';
    }
}

// Close the XML sitemap
echo '</urlset>';
?>