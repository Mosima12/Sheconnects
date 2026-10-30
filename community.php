<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = require __DIR__ . '/config/db.php'; 
$userId = current_user_id();
$activeNav = 'community';

$tab = $_GET['tab'] ?? 'mentors';
$pageTitle = 'Community';

// Fetch mentor data if that tab is active
if ($tab === 'mentors') {
    $stmt = $pdo->prepare('SELECT u.* FROM users u WHERE u.id <> ? AND u.is_mentor = 1 ORDER BY u.name ASC LIMIT 30');
    $stmt->execute([$userId]);
    $members = $stmt->fetchAll();
    
    // Check pending connections
    $stmt = $pdo->prepare('SELECT receiver_id FROM connections WHERE sender_id = ? AND status = "pending"'); 
    $stmt->execute([$userId]); 
    $pendingTo = array_flip(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
}

// Handle Donation form submit
$donationSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'donate') {
    verify_csrf_or_die(); 
    $donationSuccess = true;
}

require __DIR__ . '/includes/header.php';
?>

<div class="page-header header-wellness" style="padding: 30px;">
  <h1>Community & Support</h1>
  <p>Connect with mentors, prioritize your mental health, and give back.</p>
</div>

<!-- Tabs -->
<div style="display: flex; gap: 12px; margin-bottom: 32px; justify-content: center; flex-wrap: wrap;">
  <a href="?tab=mentors" class="btn <?= $tab === 'mentors' ? 'btn-primary' : 'btn-outline' ?>">Find a Mentor</a>
  <a href="?tab=wellness" class="btn <?= $tab === 'wellness' ? 'btn-primary' : 'btn-outline' ?>">Wellness & Mental Health</a>
  <a href="?tab=donate" class="btn <?= $tab === 'donate' ? 'btn-primary' : 'btn-outline' ?>">Donate Items</a>
</div>

<?php if ($tab === 'mentors'): ?>
    <section class="grid grid-3">
        <?php foreach ($members as $m): $mid = (int)$m['id']; $iSentPending = isset($pendingTo[$mid]); ?>
            <article class="card">
            <h3 style="margin-bottom:4px;"><?= e($m['name']) ?> <span class="pill pill-accepted" style="font-size:.7rem;vertical-align:middle;">Mentor</span></h3>
            <p class="meta" style="margin:0 0 8px;"><?= $m['industry'] ? e($m['industry']) : 'Industry not set' ?></p>
            <?php if ($m['bio']): ?><p style="margin:0 0 10px; font-size: 0.95rem;"><?= nl2br(e($m['bio'])) ?></p><?php endif; ?>
            <?php if ($m['skills']): ?><p class="meta" style="margin:0 0 6px;"><strong>Skills:</strong> <?= e($m['skills']) ?></p><?php endif; ?>
            <p style="margin-top:20px;">
                <?php if ($iSentPending): ?>
                    <span class="meta">Request pending</span>
                <?php else: ?>
                    <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/send-connection.php?id=<?= $mid ?>">Connect</a>
                <?php endif; ?>
            </p>
            </article>
        <?php endforeach; ?>
    </section>

<?php elseif ($tab === 'wellness'): ?>
    <div class="grid grid-2" style="margin-bottom: 40px;">
        <div class="card" style="background: var(--grad-soft-pink); border: none;">
            <h3>🌱 Daily Check-in</h3>
            <p>How are you feeling today? Take a moment to log your mood and reflect.</p>
            <button class="btn btn-outline" style="margin-top: 10px; background: white;">Start Reflection</button>
        </div>
        <div class="card" style="background: var(--grad-soft-pink); border: none;">
            <h3>🧘🏽‍♀️ 5-Minute Breathing Space</h3>
            <p>A quick guided audio exercise to help you center yourself and reduce anxiety.</p>
            <button class="btn btn-primary" style="margin-top: 10px;">Play Audio</button>
        </div>
    </div>

    <h2>Emergency & Crisis Support</h2>
    <p class="meta" style="margin-bottom: 20px;">If you or someone you know is in immediate danger, please reach out.</p>
    <div class="grid grid-3">
        <div class="card" style="border-top: 4px solid #bd1ba6;">
            <h3>Suicide Crisis Helpline</h3>
            <p style="font-size: 1.2rem; font-weight: bold; color: var(--secondary);">0800 567 567</p>
            <p class="meta">Toll-free, 24/7 support.</p>
        </div>
        <div class="card" style="border-top: 4px solid #bd1ba6;">
            <h3>GBV Command Centre</h3>
            <p style="font-size: 1.2rem; font-weight: bold; color: var(--secondary);">0800 428 428</p>
            <p class="meta">For gender-based violence support.</p>
        </div>
        <div class="card" style="border-top: 4px solid #bd1ba6;">
            <h3>Lifeline SA</h3>
            <p style="font-size: 1.2rem; font-weight: bold; color: var(--secondary);">0861 322 322</p>
            <p class="meta">Counselling via phone or WhatsApp.</p>
        </div>
    </div>

<?php elseif ($tab === 'donate'): ?>
    <?php if ($donationSuccess): ?>
        <div class="flash flash-success" style="text-align: center; font-size: 1.1rem; padding: 40px;">
            <h2 style="color: var(--secondary); margin-bottom: 8px;">Thank you for your pledge! 💖</h2>
            <p>Our team will review your donation and match it with a woman in need.</p>
            <a href="?tab=donate" class="btn btn-primary" style="margin-top: 16px;">Make another pledge</a>
        </div>
    <?php else: ?>
        <div style="max-width: 700px; margin: 0 auto;">
            <div class="card" style="margin-bottom: 32px; background: var(--grad-home); border: none;">
                <h3>Why donate?</h3>
                <p>Many women have the skills and drive but lack the basic tools—a laptop, a textbook, or a professional blazer. Your donation bridges that gap.</p>
            </div>
            <form class="form" method="post" style="max-width: 100%;">
                <?php csrf_field(); ?>
                <h2>Pledge a Donation</h2>
                <div class="field">
                    <label>Item / Contribution</label>
                    <input type="text" name="item" required placeholder="e.g., HP Laptop, or R500 for a course">
                </div>
                <div class="row">
                    <div class="field">
                        <label>Category</label>
                        <select name="category"><option>Technology</option><option>Books</option><option>Clothing</option><option>Financial Contribution</option></select>
                    </div>
                    <div class="field">
                        <label>Condition</label>
                        <select name="condition"><option>Brand New</option><option>Gently Used</option><option>Needs Repair</option></select>
                    </div>
                </div>
                <div class="field">
                    <label>Message / Details (Optional)</label>
                    <textarea name="message" placeholder="Tell us a bit about the item..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Submit Pledge</button>
            </form>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>