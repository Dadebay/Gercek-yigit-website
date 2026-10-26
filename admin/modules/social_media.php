<?php
require_once './includes/functions.php';
require_once './includes/db.php';

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $twitter = $_POST['twitter'] ?? '';
    $facebook = $_POST['facebook'] ?? '';
    $youtube = $_POST['youtube'] ?? '';
    $linkedin = $_POST['linkedin'] ?? '';
    $instagram = $_POST['instagram'] ?? '';
    
    try {
        // Check if social media record exists
        $check = $pdo->query("SELECT id FROM social_media LIMIT 1");
        $exists = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($exists) {
            // Update existing record
            $stmt = $pdo->prepare("UPDATE social_media SET twitter=?, facebook=?, youtube=?, linkedin=?, instagram=? WHERE id=?");
            $stmt->execute([$twitter, $facebook, $youtube, $linkedin, $instagram, $exists['id']]);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("INSERT INTO social_media (twitter, facebook, youtube, linkedin, instagram) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$twitter, $facebook, $youtube, $linkedin, $instagram]);
        }
        
        $success = "Social media links updated successfully!";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch current social media links
$stmt = $pdo->query("SELECT * FROM social_media LIMIT 1");
$social = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$social) {
    $social = [
        'twitter' => '',
        'facebook' => '',
        'youtube' => '',
        'linkedin' => '',
        'instagram' => ''
    ];
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Social Media Links</h3>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fab fa-twitter text-info me-2"></i>Twitter URL
                                </label>
                                <input type="url" class="form-control" name="twitter" 
                                       value="<?php echo htmlspecialchars($social['twitter']); ?>" 
                                       placeholder="https://twitter.com/yourprofile">
                                <small class="text-muted">Enter full URL including https://</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fab fa-facebook text-primary me-2"></i>Facebook URL
                                </label>
                                <input type="url" class="form-control" name="facebook" 
                                       value="<?php echo htmlspecialchars($social['facebook']); ?>" 
                                       placeholder="https://facebook.com/yourpage">
                                <small class="text-muted">Enter full URL including https://</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fab fa-youtube text-danger me-2"></i>YouTube URL
                                </label>
                                <input type="url" class="form-control" name="youtube" 
                                       value="<?php echo htmlspecialchars($social['youtube']); ?>" 
                                       placeholder="https://youtube.com/yourchannel">
                                <small class="text-muted">Enter full URL including https://</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fab fa-linkedin text-primary me-2"></i>LinkedIn URL
                                </label>
                                <input type="url" class="form-control" name="linkedin" 
                                       value="<?php echo htmlspecialchars($social['linkedin']); ?>" 
                                       placeholder="https://linkedin.com/in/yourprofile">
                                <small class="text-muted">Enter full URL including https://</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fab fa-instagram text-danger me-2"></i>Instagram URL
                                </label>
                                <input type="url" class="form-control" name="instagram" 
                                       value="<?php echo htmlspecialchars($social['instagram']); ?>" 
                                       placeholder="https://instagram.com/yourprofile">
                                <small class="text-muted">Enter full URL including https://</small>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Save Social Media Links
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4 p-3 bg-light rounded">
                        <h5 class="mb-3">Preview:</h5>
                        <div class="d-flex gap-2">
                            <?php if (!empty($social['twitter'])): ?>
                                <a href="<?php echo htmlspecialchars($social['twitter']); ?>" target="_blank" class="btn btn-outline-info">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($social['facebook'])): ?>
                                <a href="<?php echo htmlspecialchars($social['facebook']); ?>" target="_blank" class="btn btn-outline-primary">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($social['youtube'])): ?>
                                <a href="<?php echo htmlspecialchars($social['youtube']); ?>" target="_blank" class="btn btn-outline-danger">
                                    <i class="fab fa-youtube"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($social['linkedin'])): ?>
                                <a href="<?php echo htmlspecialchars($social['linkedin']); ?>" target="_blank" class="btn btn-outline-primary">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($social['instagram'])): ?>
                                <a href="<?php echo htmlspecialchars($social['instagram']); ?>" target="_blank" class="btn btn-outline-danger">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 