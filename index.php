<?php
require_once "config/database.php";
session_start();

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Fetch latest posts with author and category information
$query = "SELECT p.*, u.username, c.name as category_name 
          FROM posts p 
          JOIN users u ON p.user_id = u.user_id 
          JOIN categories c ON p.category_id = c.category_id 
          ORDER BY p.created_at DESC 
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">BlogSystem</div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="categories.php">Categories</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="create-post.php">Create Post</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <section class="hero">
            <h1>Welcome to BlogSystem</h1>
            <p>Share your thoughts with the world</p>
        </section>

        <section class="posts">
            <h2>Latest Posts</h2>
            <div class="posts-grid">
                <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <article class="post-card">
                        <div class="post-meta">
                            <span class="category"><?php echo htmlspecialchars($row['category_name']); ?></span>
                            <span class="date"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                        </div>
                        <h3><a href="post.php?slug=<?php echo $row['slug']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></h3>
                        <p class="author">By <?php echo htmlspecialchars($row['username']); ?></p>
                        <p class="excerpt"><?php echo substr(strip_tags($row['content']), 0, 150) . '...'; ?></p>
                        <a href="post.php?slug=<?php echo $row['slug']; ?>" class="read-more">Read More</a>
                    </article>
                <?php endwhile; ?>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> BlogSystem. All rights reserved.</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html> 