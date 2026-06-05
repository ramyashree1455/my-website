<?php
include 'db.php';
include 'image_helper.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $productId = intval($_POST['id']);

    $stmt = $conn->prepare("SELECT quantity FROM Stock WHERE medicine_id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stockRow = $result->fetch_assoc();
    $stmt->close();

    $currentStock = $stockRow ? intval($stockRow['quantity']) : 0;
    if ($currentStock <= 0) {
        $message = '<div class="message error">Out of stock. Cannot add to cart.</div>';
    } else {
        $stmt = $conn->prepare("UPDATE Stock SET quantity = quantity - 1 WHERE medicine_id = ? AND quantity > 0");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $message = '<div class="message success">Added to cart. Stock decreased by 1.</div>';
            $currentStock -= 1;
        } else {
            $message = '<div class="message error">Unable to add to cart right now. Please try again.</div>';
        }
        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT m.medicine_name, m.price, s.quantity, s.expiry_date FROM Medicine m JOIN Stock s ON m.medicine_id = s.medicine_id WHERE m.medicine_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    die('Product not found.');
}

if (isset($currentStock)) {
    $row['quantity'] = $currentStock;
}

if (!$row) {
    die('Product not found.');
}

$image = getMedicineImage($row['medicine_name']);

$class = ($row['quantity'] > 0) ? "available" : "out";
$status = ($row['quantity'] > 0) ? "Available" : "Out of Stock";
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $row['medicine_name']; ?> - MediCare</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="navbar">
    <h1>💊 MediCare</h1>
</div>

<div class="product-detail">
    <div class="product-image">
        <img src="<?php echo $image; ?>" alt="<?php echo $row['medicine_name']; ?>">
    </div>
    <div class="product-info">
        <?php echo $message; ?>
        <h2><?php echo $row['medicine_name']; ?></h2>
        <p class="price">₹<?php echo number_format($row['price'], 2); ?></p>
        <p class="stock <?php echo $class; ?>"><?php echo $row['quantity'] > 0 ? 'Stock: ' . $row['quantity'] . ' available' : 'Out of stock'; ?></p>
        <p>Expiry: <?php echo $row['expiry_date']; ?></p>
        <form method="post" action="product.php?id=<?php echo $id; ?>">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <button class="add-to-cart" type="submit" name="add_to_cart" <?php echo $row['quantity'] <= 0 ? 'disabled' : ''; ?>>Add to Cart</button>
        </form>
    </div>
</div>

</body>
</html>