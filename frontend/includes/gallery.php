<?php
$sql = "SELECT * FROM myapp_gallery ORDER BY uploaded_at DESC LIMIT 8";
$result = $conn->query($sql);
?>

<div class="container py-5">
    <h2 class="text-center mb-4">Gallery</h2>
    <div class="row g-3">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($image = $result->fetch_assoc()): ?>
                <?php
                    $django_base_url = "http://127.0.0.1:8000"; // Change this if needed
                    $image_path = "/media/" . $image['image'];
                    $image_url = $django_base_url . $image_path; // Full image URL
                ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="gallery-card">
                        <img src="<?php echo $image_url; ?>" alt="<?php echo $image['title']; ?>" width="200">
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No images found.</p>
        <?php endif; ?>
    </div>
</div>
