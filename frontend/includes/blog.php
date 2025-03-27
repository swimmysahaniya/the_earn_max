<?php
$sql = "SELECT * FROM myapp_blog ORDER BY created_at DESC limit 3";
$result = $conn->query($sql);
?>

<div class="container py-5">
<h2 class="text-center mb-4">News & Events</h2>
    <div class="row g-4">
      <!-- Blog Thumbnail 1 -->
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
        <?php
            $django_base_url = "http://127.0.0.1:8000"; // Change this if needed
            $image_path = "/media/blogs/" . basename($row['image']);
            $image_url = $django_base_url . $image_path; // Full image URL
        ?>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="card blog-thumbnail">
                  <img class="blog-image" src="<?php echo $image_url; ?>" alt="<?php echo $row['title']; ?>">
                  <div class="card-body">
                    <h5 class="card-title"><?php echo $row['title']; ?></h5>
                    <p><strong>Author:</strong> <?php echo $row['author']; ?> | <strong>Date:</strong> <?php echo date("F j, Y", strtotime($row['created_at'])); ?></p>
                    <p class="card-text"><?php echo substr(strip_tags($row['content']), 0, 150); ?>...</p>
                    <a href="blog-detail.php?slug=<?php echo $row['slug']; ?>" class="read-more">Read More &raquo;</a>
                  </div>
                </div>
              </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No blog posts found.</p>
      <?php endif; ?>
    </div>
</div>
