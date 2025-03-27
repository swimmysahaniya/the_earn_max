<?php
session_start();
include("includes/config.php");

$user_mobile = $_SESSION['user_mobile'] ?? '';

if (!$user_mobile) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['slug'])) {
    $slug = $_GET['slug'];
    $sql = "SELECT * FROM myapp_blog WHERE slug = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $blog = $result->fetch_assoc();
} else {
    die("Blog not found.");
}
?>

<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<div class="container mt-5">
    <h1><?php echo $blog['title']; ?></h1>
    <p><strong>Author:</strong> <?php echo $blog['author']; ?> | <strong>Date:</strong> <?php echo date("F j, Y", strtotime($blog['created_at'])); ?></p>


    <?php
        $django_base_url = "http://127.0.0.1:8000"; // Change this if needed
        $image_path = "/media/blogs/" . basename($blog['image']);
        $image_url = $django_base_url . $image_path; // Full image URL
    ?>
    <img class="blog-image" src="<?php echo $image_url; ?>" alt="<?php echo $row['title']; ?>" style="max-width:100%;">

    <p><?php echo nl2br($blog['content']); ?></p>
</div>

<?php include("includes/footer-nav.php"); ?>
<?php include("includes/footer.php"); ?>