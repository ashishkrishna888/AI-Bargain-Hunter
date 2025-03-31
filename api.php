<?php
header('Content-Type: application/json');
include 'db.php'; // Optional for search logging

// Load dataset
$dataFile = 'products.json';
$products = json_decode(file_get_contents($dataFile), true);

function searchProducts($query, $products) {
    $queryLower = strtolower($query);
    $categoryMap = [
        'mobiles' => ['mobile', 'phones', 'phone', 'smartphone', 'smartphones'],
        'laptops' => ['laptop', 'laptops', 'notebook', 'notebooks'],
        'earphones' => ['earphone', 'earphones', 'headphone', 'headphones', 'earbud', 'earbuds'],
        'speakers' => ['speaker', 'speakers'],
        'tablets' => ['tablet', 'tablets', 'ipad'],
        'smartwatches' => ['smartwatch', 'smartwatches', 'watch', 'watches']
    ];

    // Detect category
    $category = 'default';
    foreach ($categoryMap as $cat => $keywords) {
        if (in_array($queryLower, $keywords) || strpos($queryLower, $cat) !== false) {
            $category = $cat;
            break;
        }
    }

    // Detect price filters
    $maxPrice = null;
    $minPrice = null;
    if (preg_match('/under\s+(\d+)/', $queryLower, $matches)) {
        $maxPrice = (int)$matches[1];
    }
    if (preg_match('/above\s+(\d+)/', $queryLower, $matches)) {
        $minPrice = (int)$matches[1];
    }

    // Detect "best" keyword for sorting by rating
    $isBestQuery = strpos($queryLower, 'best') !== false;

    // Detect "gaming" keyword
    $isGamingQuery = strpos($queryLower, 'gaming') !== false;

    // Clean query for specific product search (remove "best", "under", "above", "gaming", and numbers)
    $cleanQuery = preg_replace('/\b(best|under\s+\d+|above\s+\d+|gaming)\b/', '', $queryLower);
    $cleanQuery = trim(preg_replace('/\s+/', ' ', $cleanQuery));

    // Filter products
    $filtered = array_filter($products, function($product) use ($queryLower, $cleanQuery, $category, $maxPrice, $minPrice, $isGamingQuery) {
        $nameLower = strtolower($product['name']);
        // Match category-level queries or specific queries
        $matchesCategory = $category === 'default' || $product['category'] === $category;
        $matchesQuery = $category !== 'default' || ($cleanQuery && strpos($nameLower, $cleanQuery) !== false);
        // Apply price filters
        $matchesPrice = ($maxPrice === null || $product['price'] <= $maxPrice) && ($minPrice === null || $product['price'] >= $minPrice);
        // Apply gaming filter (check for "gaming" in name or tags, and ensure high specs)
        $matchesGaming = true;
        if ($isGamingQuery && $product['category'] === 'laptops') {
            $matchesGaming = (isset($product['tags']) && in_array('gaming', $product['tags'])) || strpos($nameLower, 'gaming') !== false;
            // Additional gaming filters: RAM >= 8 GB, processor (Intel i5+ or Ryzen 5+)
            $specsLower = strtolower($product['specs']);
            $hasHighRam = preg_match('/(\d+)\s*gb\s*ram/', $specsLower, $ramMatch) && (int)$ramMatch[1] >= 8;
            $hasGoodProcessor = strpos($specsLower, 'i5') !== false || strpos($specsLower, 'i7') !== false || strpos($specsLower, 'i9') !== false || strpos($specsLower, 'ryzen 5') !== false || strpos($specsLower, 'ryzen 7') !== false;
            $matchesGaming = $matchesGaming && $hasHighRam && $hasGoodProcessor;
        }
        return $matchesCategory && $matchesQuery && $matchesPrice && $matchesGaming;
    });

    // Convert to array and remove duplicates by name
    $filtered = array_values($filtered);
    $uniqueProducts = [];
    $seenNames = [];
    foreach ($filtered as $product) {
        if (!in_array($product['name'], $seenNames)) {
            $seenNames[] = $product['name'];
            $uniqueProducts[] = $product;
        }
    }
    $filtered = $uniqueProducts;

    // Sort by rating for most queries, but by price for price-focused queries
    if ($maxPrice !== null || $minPrice !== null) {
        // Sort by price for "under" or "above" queries
        usort($filtered, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });
    } else {
        // Sort by rating for other queries
        usort($filtered, function($a, $b) {
            return $b['rating'] <=> $a['rating'];
        });
    }

    // Take top 3
    return array_slice($filtered, 0, 3);
}

function getFallbackData($query) {
    // Clean the query for the fallback name (remove "under", "above", and numbers)
    $cleanQuery = preg_replace('/\b(under\s+\d+|above\s+\d+)\b/', '', strtolower($query));
    $cleanQuery = trim(preg_replace('/\s+/', ' ', $cleanQuery));
    $categoryMap = [
        'mobiles' => ['mobile', 'phones', 'phone', 'smartphone', 'smartphones'],
        'laptops' => ['laptop', 'laptops', 'notebook', 'notebooks'],
        'earphones' => ['earphone', 'earphones', 'headphone', 'headphones', 'earbud', 'earbuds'],
        'speakers' => ['speaker', 'speakers'],
        'tablets' => ['tablet', 'tablets', 'ipad'],
        'smartwatches' => ['smartwatch', 'smartwatches', 'watch', 'watches']
    ];

    $category = 'default';
    foreach ($categoryMap as $cat => $keywords) {
        if (in_array($cleanQuery, $keywords) || strpos($cleanQuery, $cat) !== false) {
            $category = $cat;
            break;
        }
    }

    $message = $category !== 'default' ? "Sorry, we don't have any $category in our database yet. Try our earphones for a great audio experience or check out our laptops for productivity!" : "No matching products found. Try searching for mobiles, laptops, or earphones!";

    return [[
        'name' => "Sample " . ucfirst($cleanQuery),
        'price' => rand(500, 5000),
        'rating' => 4.0,
        'source' => 'Deals Database',
        'link' => '#',
        'specs' => $message
    ]];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    $query = trim($_POST['query']);

    // Optional: Log search
    try {
        $stmt = $pdo->prepare("INSERT INTO searches (query) VALUES (?)");
        $stmt->execute([$query]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }

    $results = searchProducts($query, $products);
    if (empty($results)) {
        $results = getFallbackData($query);
    }

    // Build "real-time" response
    $message = "<div class='text-gray-800'>";
    $message .= "<p class='text-2xl font-bold text-purple-600 mb-6 animate-bounce'>✨ Real-Time Deals on " . ucfirst($query) . " in India! ✨</p>";
    $message .= "<div class='space-y-6'>";

    foreach ($results as $index => $item) {
        $highlight = $index === 0 ? 'bg-gradient-to-r from-yellow-200 to-orange-200 border-l-4 border-orange-500 shadow-lg' : 'bg-white shadow-md';
        $price = number_format($item['price'], 0, '', ',');
        // Add fake discount
        $originalPrice = $item['price'] * (1 + rand(10, 30) / 100);
        $discount = round(($originalPrice - $item['price']) / $originalPrice * 100);
        $originalPriceFormatted = number_format($originalPrice, 0, '', ',');
        $source = ['Deals Database', 'Price Tracker', 'Bargain Hub'][array_rand(['Deals Database', 'Price Tracker', 'Bargain Hub'])];

        $message .= "<div class='p-5 rounded-lg $highlight transition-all hover:shadow-xl hover:-translate-y-1'>";
        $message .= "<p class='font-bold text-xl text-teal-700'>{$item['name']}</p>";
        $message .= "<p class='text-gray-800'>💸 ₹{$price} <span class='line-through text-gray-400'>₹{$originalPriceFormatted}</span> (-{$discount}%)</p>";
        $message .= "<p class='text-gray-800'><a href='{$item['link']}' class='text-blue-600 hover:underline' target='_blank'>{$source}</a></p>";
        $message .= "<p class='text-sm text-gray-600 mt-1'>{$item['specs']}</p>";
        $message .= "<p class='text-sm text-yellow-600 mt-1'>⭐ {$item['rating']}/5</p>";
        // Add "Top Rated" badge for ratings >= 4.5
        if ($item['rating'] >= 4.5) {
            $message .= "<p class='text-green-600 font-bold mt-2'>🌟 Top Rated 🌟</p>";
        }
        // Add "Best Value" badge for good price-to-rating ratio
        $priceToRatingRatio = $item['price'] / $item['rating'];
        if ($priceToRatingRatio < 5000 && $item['rating'] >= 4.0) {
            $message .= "<p class='text-blue-600 font-bold mt-2'>💰 Best Value 💰</p>";
        }
        if ($index === 0) {
            $message .= "<p class='text-red-600 font-bold mt-2 animate-pulse'>🎉 Hot Deal Alert! 🎉</p>";
        }
        $message .= "</div>";
    }

    $message .= "</div>";
    $message .= "<p class='mt-6 text-purple-600 font-semibold text-lg'>Grab these now! 🚀 Fetched: " . date('d M Y, h:i A') . "</p>";
    $message .= "</div>";

    echo json_encode(['message' => $message]);
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>