<?php
require_once "config/database.php";
session_start();

$database = new Database();
$conn = $database->getConnection();

$error = '';
$success = '';

// Get post data
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header("Location: index.php");
    exit();
}

// Fetch post with author, category, and tags
$query = "SELECT p.*, u.username, c.name as category_name 
          FROM posts p 
          JOIN users u ON p.user_id = u.user_id 
          JOIN categories c ON p.category_id = c.category_id 
          WHERE p.slug = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$slug]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header("Location: index.php");
    exit();
}

// Fetch tags for this post
$query = "SELECT t.name, t.slug 
          FROM tags t 
          JOIN post_tags pt ON t.tag_id = pt.tag_id 
          WHERE pt.post_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$post['post_id']]);
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $comment_content = trim($_POST['comment']);
    
    if (strlen($comment_content) < 5) {
        $error = "Comment must be at least 5 characters long";
    } else {
        $query = "INSERT INTO comments (content, user_id, post_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        
        if ($stmt->execute([$comment_content, $_SESSION['user_id'], $post['post_id']])) {
            $success = "Comment added successfully!";
        } else {
            $error = "An error occurred while adding the comment";
        }
    }
}

// Fetch comments with user information
$query = "SELECT c.*, u.username 
          FROM comments c 
          JOIN users u ON c.user_id = u.user_id 
          WHERE c.post_id = ? 
          ORDER BY c.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$post['post_id']]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - Blog System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">BlogSystem</div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
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
        <article class="post-full">
            <header class="post-header">
                <h1><?php echo htmlspecialchars($post['title']); ?></h1>
                <div class="post-meta">
                    <span class="author">By <?php echo htmlspecialchars($post['username']); ?></span>
                    <span class="date"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                    <span class="category"><?php echo htmlspecialchars($post['category_name']); ?></span>
                </div>
                <?php if (!empty($tags)): ?>
                    <div class="tags">
                        <?php foreach ($tags as $tag): ?>
                            <a href="tag.php?slug=<?php echo $tag['slug']; ?>" class="tag">
                                <?php echo htmlspecialchars($tag['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </header>

            <div class="post-content">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>
        </article>

        <section class="comments">
            <h2>Comments</h2>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form method="POST" class="comment-form">
                    <div class="form-group">
                        <label for="comment">Add a Comment</label>
                        <textarea id="comment" name="comment" required placeholder="Write your comment here..." rows="4"></textarea>
                    </div>
                    <button type="submit" class="btn">Submit Comment</button>
                </form>
            <?php else: ?>
                <p class="login-prompt">Please <a href="login.php">login</a> to leave a comment.</p>
            <?php endif; ?>

            <div class="comments-list">
                <?php if (empty($comments)): ?>
                    <p>No comments yet. Be the first to comment!</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment">
                            <div class="comment-meta">
                                <span class="comment-author"><?php echo htmlspecialchars($comment['username']); ?></span>
                                <span class="comment-date"><?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> BlogSystem. All rights reserved.</p>
    </footer>

    <script src="js/main.js"></script>
</body>
</html> 