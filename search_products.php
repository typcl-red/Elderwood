<?php
require_once 'includes/session.php';
require_once 'database/config.php';

if (!isset($_POST['search'])) {
    echo json_encode([]);
    exit;
}

$search = '%' . $_POST['search'] . '%';

try {
    $stmt = $pdo->prepare("
        SELECT 
            pi.product_id,
            pi.product_name,
            pi.length_feet,
            pi.width_feet,
            pi.height_feet,
            pi.quantity,
            pi.image_path,
            pi.total_square_feet,
            u.firstname,
            u.lastname,
            (pi.total_square_feet * 
                (SELECT price_per_square_foot 
                 FROM price_per_sqft
                 WHERE product_name = pi.product_name 
                 LIMIT 1)
            ) as amount,
            CONCAT(u.firstname, ' ', u.lastname) as seller_name
        FROM product_inventory pi
        JOIN users u ON pi.seller_id = u.user_id
        WHERE pi.product_name LIKE ?
        ORDER BY pi.product_name, pi.total_square_feet
    ");
    
    $stmt->execute([$search]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the amount with 2 decimal places
    foreach ($results as &$result) {
        $result['amount'] = number_format($result['amount'], 2);
    }
    
    echo json_encode($results);
    
} catch (PDOException $e) {
    error_log("Search error: " . $e->getMessage());
    echo json_encode(['error' => 'Search failed']);
}
?> 