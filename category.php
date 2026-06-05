<?php
include 'db.php';

$cat = isset($_GET['cat']) ? $_GET['cat'] : '';

$categoryMappings = [
    'cold-flu' => 'Cold & Flu',
    'pain-relief' => 'Pain Relief',
    'digestive-health' => 'Digestive Health',
    'skin-care' => 'Skin Care',
    'vitamins-supplements' => 'Vitamins & Supplements'
];

$categoryName = '';
$disease = '';

if (array_key_exists($cat, $categoryMappings)) {    
    $disease = $categoryMappings[$cat];
    $categoryName = str_replace('-', ' ', ucwords($cat));
} else {
    die('Invalid category.');
}

$sql = "SELECT DISTINCT m.medicine_id, m.medicine_name, m.price, s.quantity, s.expiry_date
FROM Disease d
JOIN Disease_Medicine dm ON d.disease_id = dm.disease_id
JOIN Medicine m ON dm.medicine_id = m.medicine_id
JOIN Stock s ON m.medicine_id = s.medicine_id
WHERE d.disease_name = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $disease);
$stmt->execute();
$result = $stmt->get_result();

$medicines = [];
while($row = $result->fetch_assoc()) {
    $medicines[] = $row;
}
$stmt->close();

$categoryFallback = [
    'cold-flu' => ['Cough Syrup', 'Cetrizine 10mg', 'Antacids'],
    'pain-relief' => ['Paracetamol', 'Aspirin', 'Ibuprofen 200mg'],
    'digestive-health' => ['Antacids', 'Lactobacillus', 'Pantoprazole'],
    'skin-care' => ['Aloe Vera Gel', 'Calamine Lotion', 'Benzoyl Peroxide'],
    'vitamins-supplements' => ['Vitamin C', 'Vitamin D', 'Iron Supplements']
];

if (count($medicines) < 2 && isset($categoryFallback[$cat])) {
    foreach ($categoryFallback[$cat] as $fallbackName) {
        if (count($medicines) >= 2) {
            break;
        }

        $alreadyIncluded = false;
        foreach ($medicines as $existing) {
            if (strtolower($existing['medicine_name']) === strtolower($fallbackName)) {
                $alreadyIncluded = true;
                break;
            }
        }

        if ($alreadyIncluded) {
            continue;
        }

        $lookupStmt = $conn->prepare("SELECT m.medicine_id, m.medicine_name, m.price, s.quantity, s.expiry_date FROM Medicine m JOIN Stock s ON m.medicine_id = s.medicine_id WHERE LOWER(m.medicine_name) = ? LIMIT 1");
        $lookupName = strtolower($fallbackName);
        $lookupStmt->bind_param("s", $lookupName);
        $lookupStmt->execute();
        $lookupResult = $lookupStmt->get_result();

        if ($lookupRow = $lookupResult->fetch_assoc()) {
            $medicines[] = $lookupRow;
        } else {
            $medicines[] = [
                'medicine_id' => 0,
                'medicine_name' => $fallbackName,
                'price' => 0,
                'quantity' => 0,
                'expiry_date' => 'N/A'
            ];
        }

        $lookupStmt->close();
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($categoryName); ?> - MediCare</title>
    <link rel='stylesheet' href='styles.css'>
</head>
<body>

<?php include 'image_helper.php'; ?>

<div class='navbar'>
    <h1>💊 MediCare</h1>
</div>

<div class='search-results'>
    <h2 class='search-title'>Medicines for: <?php echo htmlspecialchars($categoryName); ?></h2>
    <div class='container'>

    <?php
    if (count($medicines) > 0) {
        foreach ($medicines as $row) {
            $class = ($row['quantity'] > 0) ? "available" : "out";
            $image = getMedicineImage($row['medicine_name']);
            $hasLink = intval($row['medicine_id']) > 0;
            $linkStart = $hasLink ? "<a href='product.php?id=" . $row['medicine_id'] . "' class='card-link'>" : "<div class='card-link'>";
            $linkEnd = $hasLink ? "</a>" : "</div>";

            echo $linkStart;
            echo "<div class='card'>";
            echo "<div class='card-image'><img src='$image' alt='" . $row['medicine_name'] . "'></div>";
            echo "<div class='card-body'>";
            echo "<h3>" . $row['medicine_name'] . "</h3>";
            echo "<p class='price'>₹" . number_format($row['price'], 2) . "</p>";
            echo "<p class='stock $class'>" . ($row['quantity'] > 0 ? "Stock: " . $row['quantity'] . " available" : "Out of stock") . "</p>";
            echo "<p class='expiry'>Expiry: " . $row['expiry_date'] . "</p>";
            echo "</div>";
            echo "</div>";
            echo $linkEnd;
        }
    } else {
        echo "<p>No medicines found for this category.</p>";
    }
    ?>

    </div>
</div>

</body>
</html>