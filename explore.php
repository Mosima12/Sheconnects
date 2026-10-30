<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = require __DIR__ . '/config/db.php'; 
$userId = current_user_id();
$activeNav = 'explore';

$tab = $_GET['tab'] ?? 'resources';
$pageTitle = ucfirst($tab);

// Fetch data based on the active tab
if ($tab === 'resources') {
    $stmt = $pdo->prepare('SELECT r.*, u.name AS owner_name FROM resources r JOIN users u ON u.id = r.user_id WHERE r.status = "available" ORDER BY r.created_at DESC LIMIT 30');
    $stmt->execute();
    $items = $stmt->fetchAll();
} elseif ($tab === 'opportunities') {
    $stmt = $pdo->prepare('SELECT o.*, u.name AS poster_name FROM opportunities o JOIN users u ON u.id = o.user_id ORDER BY (o.deadline IS NULL), o.deadline ASC LIMIT 30');
    $stmt->execute();
    $items = $stmt->fetchAll();
}

require __DIR__ . '/includes/header.php';
?>

<div class="page-header header-resources" style="padding: 30px;">
  <h1>Explore Bloom</h1>
  <p>Discover resources, opportunities, and free courses to move forward.</p>
</div>

<!-- Tabs -->
<div style="display: flex; gap: 12px; margin-bottom: 32px; justify-content: center; flex-wrap: wrap;">
  <a href="?tab=resources" class="btn <?= $tab === 'resources' ? 'btn-primary' : 'btn-outline' ?>">Shared Resources</a>
  <a href="?tab=opportunities" class="btn <?= $tab === 'opportunities' ? 'btn-primary' : 'btn-outline' ?>">Opportunities</a>
  <a href="?tab=academy" class="btn <?= $tab === 'academy' ? 'btn-primary' : 'btn-outline' ?>">Bloom Academy</a>
</div>

<?php if ($tab === 'resources'): ?>
    <div style="display:flex;justify-content:flex-end;gap:12px;margin-bottom:20px;">
        <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/donate.php">Donate an item</a>
        <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/add-resource.php">+ Share a resource</a>
    </div>
    <section class="grid grid-3">
        <?php foreach ($items as $r): 
            // LOCAL IMAGES FOR RESOURCES
            $img = 'default.jpg'; 
            if ($r['category'] === 'Books & Study Materials') $img = 'books.jpg';
            if ($r['category'] === 'Technology') $img = 'laptop.jpg';
            if ($r['category'] === 'Clothing') $img = 'blazer.jpg';
            if ($r['category'] === 'Business Supplies') $img = 'desk.jpg';
        ?>
            <article class="card image-card">
            <!-- Points to assets/img/ folder -->
            <img src="<?= BASE_URL ?>/assets/img/<?= $img ?>" alt="<?= e($r['category']) ?>" style="object-position: center;">
            <div class="image-card-content">
                <span class="pill"><?= e($r['category']) ?></span>
                <h3 style="margin-bottom:8px;"><?= e($r['title']) ?></h3>
                <p class="meta" style="margin:0 0 12px;">Shared by <strong><?= e($r['owner_name']) ?></strong></p>
                <p style="margin:0 0 16px; font-size: 0.95rem; color: #444; flex-grow: 1;"><?= nl2br(e($r['description'])) ?></p>
                <div style="margin-top:auto;">
                <p class="meta" style="margin:0 0 16px;"><span class="pill pill-type"><?= e($r['exchange_type']) ?></span></p>
                <?php if ((int)$r['user_id'] === $userId): ?>
                    <span class="meta" style="font-weight: 600;">Your listing</span>
                <?php else: ?>
                    <a class="btn btn-primary" style="width: 100%; display: block;" href="<?= BASE_URL ?>/request-resource.php?id=<?= (int)$r['id'] ?>">Request this</a>
                <?php endif; ?>
                </div>
            </div>
            </article>
        <?php endforeach; ?>
    </section>

<?php elseif ($tab === 'opportunities'): ?>
    <div style="display:flex;justify-content:flex-end;gap:12px;margin-bottom:20px;">
        <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/add-opportunity.php">+ Share an opportunity</a>
    </div>
    <section class="grid grid-2">
        <?php foreach ($items as $o):
            // LOCAL IMAGES FOR OPPORTUNITIES
            $img = 'default.jpg'; 
            if ($o['category'] === 'Scholarship') $img = 'graduation.jpg';
            if ($o['category'] === 'Internship' || $o['category'] === 'Job') $img = 'interview.jpg';
            if ($o['category'] === 'Workshop' || $o['category'] === 'Networking Event') $img = 'meeting.jpg';
            if ($o['category'] === 'Entrepreneurship') $img = 'business.jpg';
        ?>
            <article class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: row; height: 100%;">
            <div style="width: 40%; min-height: 100%; background: #eee;">
                <!-- Points to assets/img/ folder -->
                <img src="<?= BASE_URL ?>/assets/img/<?= $img ?>" alt="<?= e($o['category']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div style="width: 60%; padding: 24px; display: flex; flex-direction: column;">
                <span class="pill" style="margin-bottom: 12px; align-self: flex-start;"><?= e($o['category']) ?></span>
                <h3 style="margin-bottom:8px; font-size: 1.3rem;"><?= e($o['title']) ?></h3>
                <p class="meta" style="margin:0 0 12px; color: var(--primary); font-weight: 600; font-size: 0.85rem; text-transform: uppercase;"><?= e($o['organization']) ?></p>
                <p style="margin:0 0 16px; font-size: 0.95rem; color: #444; flex-grow: 1;"><?= nl2br(e($o['description'])) ?></p>
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 16px;">
                <span class="meta" style="font-size: 0.85rem;"><?php if ($o['deadline']): ?>Closes <?= e(date('j M Y', strtotime($o['deadline']))) ?><?php else: ?>Open deadline<?php endif; ?></span>
                <?php if ($o['application_url']): ?><a class="btn btn-primary btn-sm" href="<?= e($o['application_url']) ?>" target="_blank">Apply ↗</a><?php endif; ?>
                </div>
            </div>
            </article>
        <?php endforeach; ?>
    </section>

<?php elseif ($tab === 'academy'): ?>
    <div class="grid grid-3">
        <div class="card" style="display: flex; flex-direction: column;">
            <img src="<?= BASE_URL ?>/assets/img/finance.jpg" alt="Finance" style="width: 100%; height: 140px; object-fit: cover; border-radius: 10px; margin-bottom: 16px;">
            <span class="pill" style="margin-bottom: 12px; align-self: flex-start;">Finance</span>
            <h3>Financial Literacy 101</h3>
            <p class="meta" style="margin-bottom: 16px;">4 Lessons • 30 mins</p>
            <p style="flex-grow: 1; font-size: 0.95rem; color: #444;">Learn how to create a budget, start a savings plan, and understand basic investing.</p>
            <button class="btn btn-primary" style="margin-top: 20px; width: 100%;">Start Course</button>
        </div>
        
        <div class="card" style="display: flex; flex-direction: column;">
            <img src="<?= BASE_URL ?>/assets/img/interview.jpg" alt="Career" style="width: 100%; height: 140px; object-fit: cover; border-radius: 10px; margin-bottom: 16px;">
            <span class="pill" style="margin-bottom: 12px; align-self: flex-start;">Career</span>
            <h3>Salary Negotiation</h3>
            <p class="meta" style="margin-bottom: 16px;">3 Lessons • 20 mins</p>
            <p style="flex-grow: 1; font-size: 0.95rem; color: #444;">Learn the exact scripts and strategies to confidently ask for a raise.</p>
            <button class="btn btn-primary" style="margin-top: 20px; width: 100%;">Start Course</button>
        </div>
        
        <div class="card" style="display: flex; flex-direction: column;">
            <img src="<?= BASE_URL ?>/assets/img/meeting.jpg" alt="Life Skills" style="width: 100%; height: 140px; object-fit: cover; border-radius: 10px; margin-bottom: 16px;">
            <span class="pill" style="margin-bottom: 12px; align-self: flex-start;">Life Skills</span>
            <h3>Setting Healthy Boundaries</h3>
            <p class="meta" style="margin-bottom: 16px;">5 Lessons • 45 mins</p>
            <p style="flex-grow: 1; font-size: 0.95rem; color: #444;">A guide on how to say 'no' without guilt and protect your energy.</p>
            <button class="btn btn-primary" style="margin-top: 20px; width: 100%;">Start Course</button>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>