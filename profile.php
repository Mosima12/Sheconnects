<?php
// profile.php — exact Figma replica
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo    = require __DIR__ . '/config/db.php';
$userId = current_user_id();

$errors = [];
$old = [];

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$me = $stmt->fetch();
if (!$me) { header('Location: ' . BASE_URL . '/logout.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
    $name         = trim((string)($_POST['name'] ?? ''));
    $bio          = trim((string)($_POST['bio'] ?? ''));
    $skills       = trim((string)($_POST['skills'] ?? ''));
    $industry     = trim((string)($_POST['industry'] ?? ''));
    $isMentor     = isset($_POST['is_mentor']) ? 1 : 0;

    if (mb_strlen($name) < 2) $errors[] = 'Name must be at least 2 characters.';

    if (!$errors) {
        $stmt = $pdo->prepare('UPDATE users SET name=?, bio=?, skills=?, industry=?, is_mentor=? WHERE id=?');
        $stmt->execute([$name, $bio, $skills, $industry, $isMentor, $userId]);
        flash('success', 'Profile saved successfully.');
        header('Location: ' . BASE_URL . '/profile.php');
        exit;
    }
    $old = compact('name', 'bio', 'skills', 'industry');
    $old['is_mentor'] = $isMentor;
}

$form = [
    'name'         => $old['name']         ?? $me['name'],
    'bio'          => $old['bio']          ?? $me['bio']          ?? '',
    'skills'       => $old['skills']       ?? $me['skills']       ?? '',
    'industry'     => $old['industry']     ?? $me['industry']     ?? '',
    'is_mentor'    => $old['is_mentor']    ?? (int)$me['is_mentor'],
];

// Extract initials for avatar
$words = explode(' ', $form['name']);
$initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

$pageTitle = 'Profile & safety';
// INCLUDE HEADER
require __DIR__ . '/includes/header.php';
?>

<!-- LINK TO THE NEW CSS FILE -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/profile.css">

<div class="profile-wrap">
    
    <?php if ($errors): ?>
        <div style="background:#fee2e2; color:#b91c1c; padding:16px; border-radius:12px; margin-bottom:24px;">
            <ul style="margin:0;padding-left:18px;"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="profile-header">
        <div>
            <div class="subtitle">YOUR SPACE, YOUR BOUNDARIES</div>
            <h1>Profile &amp; safety</h1>
            <p>Choose what you share and who can contact you. Your safety comes first.</p>
        </div>
        <!-- Save button triggers the form submit below -->
        <button type="submit" form="profileForm" class="save-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Save changes
        </button>
    </div>

    <form id="profileForm" method="post" class="profile-grid-layout">
        <?php csrf_field(); ?>

        <!-- LEFT SIDEBAR -->
        <aside class="profile-card">
            <div class="profile-cover">
                <div class="profile-avatar-wrap">
                    <div class="profile-avatar"><?= e($initials) ?></div>
                    <div class="profile-avatar-plus">+</div>
                </div>
            </div>
            
            <div class="profile-details">
                <h2><?= e($form['name']) ?></h2>
                <div class="profile-industry"><?= $form['industry'] ? e($form['industry']) : 'Member' ?> &middot; Johannesburg</div>
                
                <div class="verified-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    Verified member
                </div>

                <div class="strength-box">
                    <div class="strength-header">
                        <span>Profile strength</span>
                        <span style="color: #ec4899;">80%</span>
                    </div>
                    <div class="strength-track">
                        <div class="strength-fill"></div>
                    </div>
                    <p style="font-size:0.8rem; color:#666; margin:0;">Add your mentoring interests to help the right women find you.</p>
                </div>

                <div class="privacy-box">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <div>
                        <p>Your private details stay private.</p>
                        <span>Your email, password and exact location are never shown publicly.</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- RIGHT PANELS -->
        <div>
            <!-- About you -->
            <div class="settings-panel">
                <div class="panel-head">
                    <div class="panel-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                    <div>
                        <h2>About you</h2>
                        <p>Only share details you are comfortable showing to the community.</p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Display name</label>
                        <input type="text" name="name" value="<?= e($form['name']) ?>" required>
                        <span class="hint">Use a first name and initial if you prefer more privacy.</span>
                    </div>
                    <div class="form-group">
                        <label>Industry</label>
                        <select name="industry">
                            <option value="">Select industry...</option>
                            <option value="Product design" <?= $form['industry'] === 'Product design' ? 'selected' : '' ?>>Product design</option>
                            <option value="Technology" <?= $form['industry'] === 'Technology' ? 'selected' : '' ?>>Technology</option>
                            <option value="Finance" <?= $form['industry'] === 'Finance' ? 'selected' : '' ?>>Finance</option>
                            <option value="Marketing" <?= $form['industry'] === 'Marketing' ? 'selected' : '' ?>>Marketing</option>
                            <option value="Legal" <?= $form['industry'] === 'Legal' ? 'selected' : '' ?>>Legal</option>
                            <option value="Public Health" <?= $form['industry'] === 'Public Health' ? 'selected' : '' ?>>Public Health</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>City or region</label>
                        <input type="text" value="Johannesburg" readonly style="background: #fdfafb;">
                        <span class="hint">Never enter your street address.</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Short bio</label>
                    <textarea name="bio" style="min-height: 110px;"><?= e($form['bio']) ?></textarea>
                </div>

                <div class="form-group">
                    <label>Skills &amp; interests</label>
                    <div style="display:flex; flex-wrap:wrap; gap:8px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 12px; background: #fdfafb;">
                        <?php 
                        $skillsArray = array_filter(array_map('trim', explode(',', $form['skills'])));
                        if (empty($skillsArray)) $skillsArray = ['Add a skill'];
                        foreach ($skillsArray as $skill): 
                        ?>
                            <div style="background: #f3e8ff; color: #7e22ce; padding: 6px 12px; border-radius: 99px; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                <?= e($skill) ?> <span style="cursor:pointer;">&times;</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="skills" value="<?= e($form['skills']) ?>">
                </div>
            </div>

            <!-- Privacy & contact -->
            <div class="settings-panel">
                <div class="panel-head">
                    <div class="panel-icon" style="background: #fdf2f8; color: #ec4899;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </div>
                    <div>
                        <h2>Privacy &amp; contact</h2>
                        <p>You are always in control of your visibility and conversations.</p>
                    </div>
                </div>

                <div class="form-row" style="grid-template-columns: 1fr auto; margin-bottom: 24px; background: #fdfafb; padding: 16px; border-radius: 12px;">
                    <div style="font-weight: 700; color: #4b1979; display: flex; align-items: center;">Who can see my full profile?</div>
                    <select style="padding: 10px 16px; border-radius: 99px; border: 1px solid #e5e7eb; background: white; font-family: inherit; font-weight: 500;">
                        <option>My connections only</option>
                        <option>Everyone on Bloom</option>
                    </select>
                </div>

                <div class="toggle-row">
                    <div>
                        <h4>Allow connection requests</h4>
                        <p>Verified members can ask to connect. You approve every request.</p>
                    </div>
                    <label class="switch"><input type="checkbox" checked><span class="slider"></span></label>
                </div>

                <div class="toggle-row">
                    <div>
                        <h4>Available to mentor</h4>
                        <p>Show up in the mentorship directory for other women.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="is_mentor" value="1" <?= $form['is_mentor'] ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <!-- Account security -->
            <div class="settings-panel">
                <div class="panel-head">
                    <div class="panel-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    </div>
                    <div>
                        <h2>Account security</h2>
                        <p>Extra protection keeps your account and community safer.</p>
                    </div>
                </div>

                <div class="toggle-row">
                    <div style="display:flex; align-items:center; gap:16px;">
                        <div class="panel-icon" style="width:40px; height:40px; background:#f3e8ff; color:#7e22ce;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        </div>
                        <div>
                            <h4>Email verified</h4>
                            <p>a*****@gmail.com</p>
                        </div>
                    </div>
                    <span style="background: #dcfce7; color: #15803d; padding: 4px 12px; border-radius: 99px; font-size: 0.8rem; font-weight: 700;">Protected</span>
                </div>
            </div>

        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>