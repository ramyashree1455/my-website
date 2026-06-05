
<?php
include 'db.php';

$disease = $_GET['disease'];

$sql = "SELECT DISTINCT m.medicine_id, m.medicine_name, m.price, s.quantity, s.expiry_date
FROM Disease d
JOIN Disease_Medicine dm ON d.disease_id = dm.disease_id
JOIN Medicine m ON dm.medicine_id = m.medicine_id
JOIN Stock s ON m.medicine_id = s.medicine_id
WHERE d.disease_name LIKE '%$disease%'";

$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Search Results - MediCare</title>
    <link rel='stylesheet' href='styles.css'>
</head>
<body>

<?php include 'image_helper.php'; ?>

<div class='navbar'>
    <h1>💊 MediCare</h1>
</div>

<div class='search-results'>
    <h2 class='search-title'>Results for: <?php echo htmlspecialchars($disease); ?></h2>
    <div class='container'>

    <?php
    while($row = $result->fetch_assoc()) {

        $class = ($row['quantity'] > 0) ? "available" : "out";
        $image = getMedicineImage($row['medicine_name']);

        echo "<a href='product.php?id=" . $row['medicine_id'] . "' class='card-link'>";
        echo "<div class='card'>";
        echo "<div class='card-image'><img src='$image' alt='" . $row['medicine_name'] . "'></div>";
        echo "<div class='card-body'>";
        echo "<h3>" . $row['medicine_name'] . "</h3>";
        echo "<p class='price'>₹" . number_format($row['price'], 2) . "</p>";
        echo "<p class='stock $class'>" . ($row['quantity'] > 0 ? "Stock: " . $row['quantity'] . " available" : "Out of stock") . "</p>";
        echo "<p class='expiry'>Expiry: " . $row['expiry_date'] . "</p>";
        echo "</div>";
        echo "</div>";
        echo "</a>";
    }
    ?>

    </div>
</div>

</body>
</html>