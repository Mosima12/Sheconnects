<?php
// my-requests.php — incoming and outgoing resource requests + connection requests
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo    = require __DIR__ . '/config/db.php';
$userId = current_user_id();

// ---- Handle action (POST) before any output ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $action = (string)($_POST['action'] ?? '');
    $kind   = (string)($_POST['kind']   ?? '');   // 'resource' or 'connection'
    $id     = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        flash('error', 'Invalid request.');
        header('Location: ' . BASE_URL . '/my-requests.php');
        exit;
    }

    if ($kind === 'resource') {
        // Load the request + owning resource so we can verify ownership.
        $stmt = $pdo->prepare(
            'SELECT rr.*, r.user_id AS owner_id
               FROM resource_requests rr
               JOIN resources r ON r.id = rr.resource_id
              WHERE rr.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $req = $stmt->fetch();
        if (!$req) {
            flash('error', 'That request no longer exists.');
            header('Location: ' . BASE_URL . '/my-requests.php');
            exit;
        }

        if ($action === 'accept' || $action === 'decline') {
            // Only the resource owner may accept or decline.
            if ((int)$req['owner_id'] !== $userId) {
                flash('error', 'You can only accept or decline requests for your own resources.');
                header('Location: ' . BASE_URL . '/my-requests.php');
                exit;
            }
            if ($req['status'] !== 'pending') {
                flash('error', 'That request has already been resolved.');
                header('Location: ' . BASE_URL . '/my-requests.php');
                exit;
            }
            $newStatus = $action === 'accept' ? 'accepted' : 'declined';
            $stmt = $pdo->prepare('UPDATE resource_requests SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $id]);

            if ($action === 'accept') {
                // Mark resource as reserved.
                $stmt = $pdo->prepare('UPDATE resources SET status = "reserved" WHERE id = ?');
                $stmt->execute([$req['resource_id']]);
            }
            flash('success', 'Request ' . $newStatus . '.');
        } elseif ($action === 'cancel') {
            // Only the requester may cancel, and only while pending.
            if ((int)$req['requester_id'] !== $userId) {
                flash('error', 'You can only cancel your own requests.');
                header('Location: ' . BASE_URL . '/my-requests.php');
                exit;
            }
            if ($req['status'] !== 'pending') {
                flash('error', 'Only pending requests can be cancelled.');
                header('Location: ' . BASE_URL . '/my-requests.php');
                exit;
            }
            $stmt = $pdo->prepare('UPDATE resource_requests SET status = "cancelled" WHERE id = ?');
            $stmt->execute([$id]);
            flash('success', 'Request cancelled.');
        } else {
            flash('error', 'Unknown action.');
        }
        header('Location: ' . BASE_URL . '/my-requests.php');
        exit;
    }

    if ($kind === 'connection') {
        $stmt = $pdo->prepare('SELECT * FROM connections WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $conn = $stmt->fetch();
        if (!$conn) {
            flash('error', 'That connection request no longer exists.');
            header('Location: ' . BASE_URL . '/my-requests.php');
            exit;
        }

        if ($action === 'accept' || $action === 'decline') {
            if ((int)$conn['receiver_id'] !== $userId) {
                flash('error', 'You can only respond to connection requests sent to you.');
                header('Location: ' . BASE_URL . '/my-requests.php');
                exit;
            }
            if ($conn['status'] !== 'pending') {
                flash('error', 'That connection request has already been resolved.');
                header('Location: ' . BASE_URL . '/my-requests.php');
                exit;
            }
            $newStatus = $action === 'accept' ? 'accepted' : 'declined';
            $stmt = $pdo->prepare('UPDATE connections SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $id]);
            flash('success', 'Connection ' . $newStatus . '.');
        } elseif ($action === 'cancel') {
            if ((int)$conn['sender_id'] !== $userId) {
                flash('error', 'You can only cancel your own connection requests.');
                header('Location: ' . BASE_URL . '/my-requests.php');
                exit;
            }
            if ($conn['status'] !== 'pending') {
                flash('error', 'Only pending requests can be cancelled.');
                header('Location: ' . BASE_URL . '/my-requests.php');
                exit;
            }
            $stmt = $pdo->prepare('UPDATE connections SET status = "cancelled" WHERE id = ?');
            $stmt->execute([$id]);
            flash('success', 'Connection request cancelled.');
        } else {
            flash('error', 'Unknown action.');
        }
        header('Location: ' . BASE_URL . '/my-requests.php');
        exit;
    }

    flash('error', 'Unknown request type.');
    header('Location: ' . BASE_URL . '/my-requests.php');
    exit;
}

// ---- Load data for display ----

// Incoming resource requests (where I own the resource).
$stmt = $pdo->prepare(
    'SELECT rr.id, rr.message, rr.status, rr.created_at,
            r.title AS resource_title, r.id AS resource_id,
            u.name AS requester_name
       FROM resource_requests rr
       JOIN resources r ON r.id = rr.resource_id
       JOIN users u     ON u.id = rr.requester_id
      WHERE r.user_id = ?
      ORDER BY rr.created_at DESC'
);
$stmt->execute([$userId]);
$incomingResource = $stmt->fetchAll();

// Outgoing resource requests (that I sent).
$stmt = $pdo->prepare(
    'SELECT rr.id, rr.message, rr.status, rr.created_at,
            r.title AS resource_title, r.id AS resource_id,
            u.name AS owner_name
       FROM resource_requests rr
       JOIN resources r ON r.id = rr.resource_id
       JOIN users u     ON u.id = r.user_id
      WHERE rr.requester_id = ?
      ORDER BY rr.created_at DESC'
);
$stmt->execute([$userId]);
$outgoingResource = $stmt->fetchAll();

// Incoming connection requests (people who want to connect with me).
$stmt = $pdo->prepare(
    'SELECT c.id, c.message, c.status, c.created_at, u.name AS sender_name, u.id AS sender_id
       FROM connections c
       JOIN users u ON u.id = c.sender_id
      WHERE c.receiver_id = ?
      ORDER BY c.created_at DESC'
);
$stmt->execute([$userId]);
$incomingConnections = $stmt->fetchAll();

// Outgoing connection requests (that I sent).
$stmt = $pdo->prepare(
    'SELECT c.id, c.message, c.status, c.created_at, u.name AS receiver_name, u.id AS receiver_id
       FROM connections c
       JOIN users u ON u.id = c.receiver_id
      WHERE c.sender_id = ?
      ORDER BY c.created_at DESC'
);
$stmt->execute([$userId]);
$outgoingConnections = $stmt->fetchAll();

$pageTitle = 'My Requests';
$activeNav = '';
require __DIR__ . '/includes/header.php';

// Small helper to render a status pill.
function status_pill(string $status): string {
    $allowed = ['pending','accepted','declined','cancelled','available','reserved','given'];
    $cls = in_array($status, $allowed, true) ? $status : 'pending';
    return '<span class="pill pill-' . $cls . '">' . e($status) . '</span>';
}
?>

<h1>My Requests</h1>
<p class="meta" style="margin-bottom:20px;">Manage requests you've sent and received across resources and connections.</p>

<!-- ======================= INCOMING RESOURCE REQUESTS ======================= -->
<h2 style="margin-top:28px;">Incoming resource requests</h2>
<?php if (!$incomingResource): ?>
  <div class="card"><p class="meta" style="margin:0;">No one has requested your resources yet.</p></div>
<?php else: ?>
  <table class="table">
    <thead>
      <tr>
        <th>Resource</th>
        <th>From</th>
        <th>Message</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($incomingResource as $row): ?>
        <tr>
          <td><?= e($row['resource_title']) ?></td>
          <td><?= e($row['requester_name']) ?></td>
          <td><?= nl2br(e($row['message'])) ?></td>
          <td><?= status_pill($row['status']) ?></td>
          <td>
            <div class="actions">
              <?php if ($row['status'] === 'pending'): ?>
                <form method="post" style="display:inline;">
                  <?php csrf_field(); ?>
                  <input type="hidden" name="kind" value="resource">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <input type="hidden" name="action" value="accept">
                  <button class="btn btn-green btn-sm" type="submit">Accept</button>
                </form>
                <form method="post" style="display:inline;">
                  <?php csrf_field(); ?>
                  <input type="hidden" name="kind" value="resource">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <input type="hidden" name="action" value="decline">
                  <button class="btn btn-danger btn-sm" type="submit">Decline</button>
                </form>
              <?php else: ?>
                <span class="meta">—</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<!-- ======================= OUTGOING RESOURCE REQUESTS ======================= -->
<h2 style="margin-top:32px;">My resource requests</h2>
<?php if (!$outgoingResource): ?>
  <div class="card"><p class="meta" style="margin:0;">You haven't requested any resources yet.
    <a href="<?= BASE_URL ?>/resources.php">Browse resources →</a></p></div>
<?php else: ?>
  <table class="table">
    <thead>
      <tr>
        <th>Resource</th>
        <th>Owner</th>
        <th>My message</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($outgoingResource as $row): ?>
        <tr>
          <td><?= e($row['resource_title']) ?></td>
          <td><?= e($row['owner_name']) ?></td>
          <td><?= nl2br(e($row['message'])) ?></td>
          <td><?= status_pill($row['status']) ?></td>
          <td>
            <div class="actions">
              <?php if ($row['status'] === 'pending'): ?>
                <form method="post" style="display:inline;">
                  <?php csrf_field(); ?>
                  <input type="hidden" name="kind" value="resource">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <input type="hidden" name="action" value="cancel">
                  <button class="btn btn-danger btn-sm" type="submit"
                          data-confirm="Cancel this request?">Cancel</button>
                </form>
              <?php else: ?>
                <span class="meta">—</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<!-- ======================= INCOMING CONNECTION REQUESTS ======================= -->
<h2 style="margin-top:32px;">Connection requests to me</h2>
<?php if (!$incomingConnections): ?>
  <div class="card"><p class="meta" style="margin:0;">No connection requests yet.</p></div>
<?php else: ?>
  <table class="table">
    <thead>
      <tr><th>From</th><th>Message</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($incomingConnections as $row): ?>
        <tr>
          <td><?= e($row['sender_name']) ?></td>
          <td><?= nl2br(e($row['message'])) ?></td>
          <td><?= status_pill($row['status']) ?></td>
          <td>
            <div class="actions">
              <?php if ($row['status'] === 'pending'): ?>
                <form method="post" style="display:inline;">
                  <?php csrf_field(); ?>
                  <input type="hidden" name="kind" value="connection">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <input type="hidden" name="action" value="accept">
                  <button class="btn btn-green btn-sm" type="submit">Accept</button>
                </form>
                <form method="post" style="display:inline;">
                  <?php csrf_field(); ?>
                  <input type="hidden" name="kind" value="connection">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <input type="hidden" name="action" value="decline">
                  <button class="btn btn-danger btn-sm" type="submit">Decline</button>
                </form>
              <?php else: ?>
                <span class="meta">—</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<!-- ======================= OUTGOING CONNECTION REQUESTS ======================= -->
<h2 style="margin-top:32px;">My connection requests</h2>
<?php if (!$outgoingConnections): ?>
  <div class="card"><p class="meta" style="margin:0;">You haven't sent any connection requests yet.
    <a href="<?= BASE_URL ?>/mentors.php">Browse mentors →</a></p></div>
<?php else: ?>
  <table class="table">
    <thead>
      <tr><th>To</th><th>My message</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($outgoingConnections as $row): ?>
        <tr>
          <td><?= e($row['receiver_name']) ?></td>
          <td><?= nl2br(e($row['message'])) ?></td>
          <td><?= status_pill($row['status']) ?></td>
          <td>
            <div class="actions">
              <?php if ($row['status'] === 'pending'): ?>
                <form method="post" style="display:inline;">
                  <?php csrf_field(); ?>
                  <input type="hidden" name="kind" value="connection">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <input type="hidden" name="action" value="cancel">
                  <button class="btn btn-danger btn-sm" type="submit"
                          data-confirm="Cancel this connection request?">Cancel</button>
                </form>
              <?php else: ?>
                <span class="meta">—</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>