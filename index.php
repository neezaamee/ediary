<?php
// index.php
require_once 'config/config.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Don't include standard header to have a custom landing page look, or just use it and customize style via CSS
// We'll use the standard header for consistency but add a hero section.
require_once 'includes/header.php';
?>

<div class="row min-vh-75 align-items-center">
    <div class="col-md-6 mb-4 fade-in">
        <h1 class="display-3 fw-bold mb-3">Your Life.<br> <span class="text-primary">Your Story.</span></h1>
        <p class="lead text-muted mb-4">
            A safe space to preserve your memories, thoughts, and dreams. 
            Connect with friends through our unique <strong>Autograph Wall</strong>.
        </p>
        <div class="d-grid gap-3 d-sm-flex">
            <a href="register.php" class="btn btn-primary btn-lg px-4 gap-3">Get Started</a>
            <a href="login.php" class="btn btn-outline-secondary btn-lg px-4">Login</a>
        </div>
        
        <div class="mt-5">
            <div class="d-flex align-items-center mb-2">
                <i class="fa-solid fa-lock text-success me-2"></i> Private & Secure
            </div>
            <div class="d-flex align-items-center mb-2">
                <i class="fa-solid fa-moon text-primary me-2"></i> Hijri Calendar Support
            </div>
             <div class="d-flex align-items-center">
                <i class="fa-solid fa-pen-nib text-danger me-2"></i> Emotional Autographs
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4 text-center fade-in" style="animation-delay: 0.2s;">
        <img src="https://source.unsplash.com/random/600x400/?diary,writing,journal" alt="Diary" class="img-fluid rounded-4 shadow-lg glass-card p-0">
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
