<?php
  // Simple control panel to view and modify score, streak, and kisses
  $dataFile = __DIR__ . '/data.json';

  function read_data(string $file): array {
    $defaults = [ 'score' => 0, 'streak' => 0, 'kisses' => 0 ];
    if (!is_readable($file)) { return $defaults; }
    $raw = @file_get_contents($file);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) { return $defaults; }
    $data = array_merge($defaults, $decoded);
    // normalize to integers and non-negative
    $data['score'] = max(0, (int)$data['score']);
    $data['streak'] = max(0, (int)$data['streak']);
    $data['kisses'] = max(0, (int)$data['kisses']);
    return $data;
  }

  function write_data(string $file, array $data): bool {
    $payload = [
      'score' => max(0, (int)($data['score'] ?? 0)),
      'streak' => max(0, (int)($data['streak'] ?? 0)),
      'kisses' => max(0, (int)($data['kisses'] ?? 0)),
    ];
    $dir = dirname($file);
    if (!is_dir($dir)) { return false; }
    $fh = @fopen($file, 'c+');
    if ($fh === false) { return false; }
    $ok = false;
    if (flock($fh, LOCK_EX)) {
      ftruncate($fh, 0);
      rewind($fh);
      $ok = (fwrite($fh, json_encode($payload, JSON_PRETTY_PRINT)) !== false);
      fflush($fh);
      flock($fh, LOCK_UN);
    }
    fclose($fh);
    return $ok;
  }

  function redirect(string $to): void {
    header('Location: ' . $to);
    exit;
  }

  $message = null;
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
    $data = read_data($dataFile);

    if ($action === 'quick_point_increase') {
      $data['score'] = (int)$data['score'] + 1;
      if ($data['score'] % 4 === 0) { $data['kisses'] = (int)$data['kisses'] + 1; }
      write_data($dataFile, $data);
      $message = 'Increased points by 1' . (($data['score'] % 4 === 0) ? ' and kisses by 1' : '');
    } elseif ($action === 'quick_streak_increase') {
      $streakNow = (int)$data['streak'];
      if ($streakNow > 0) { $data['kisses'] = (int)$data['kisses'] + $streakNow; }
      $data['streak'] = $streakNow + 1;
      write_data($dataFile, $data);
      $message = 'Increased kisses by ' . $streakNow . ' and streak by 1';
    } elseif ($action === 'set_values') {
      $score = isset($_POST['score']) ? (int)$_POST['score'] : $data['score'];
      $streak = isset($_POST['streak']) ? (int)$_POST['streak'] : $data['streak'];
      $kisses = isset($_POST['kisses']) ? (int)$_POST['kisses'] : $data['kisses'];
      $data['score'] = max(0, $score);
      $data['streak'] = max(0, $streak);
      $data['kisses'] = max(0, $kisses);
      write_data($dataFile, $data);
      $message = 'Values updated.';
    }
  }

  $data = read_data($dataFile);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Control Panel</title>
    <style>
      body { font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; background:#ffecf7; color:#5a2257; padding:20px; }
      .card { background:#ffffff; border:2px solid #ffc5dd; border-radius:12px; padding:16px; max-width:520px; margin:0 auto 16px; }
      .row { display:flex; gap:12px; align-items:center; margin:8px 0; }
      label { min-width:80px; display:inline-block; }
      input[type=number] { width:120px; padding:6px 8px; border-radius:8px; border:1px solid #ffb6d0; }
      button { background:#ff2b83; color:#fff; border:none; border-radius:999px; padding:8px 12px; cursor:pointer; }
      button.secondary { background:#ff86b8; }
      .muted { color:#b34a7f; font-size:12px; }
      .actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
      a { color:#ff2b83; }
    </style>
  </head>
  <body>
    <div class="card">
      <h2 style="margin:0 0 8px 0;">Control Panel</h2>
      <div class="muted">View and modify current values. <a href="index.php">View UI</a></div>
      <?php if ($message): ?>
        <div style="margin-top:8px; padding:8px 10px; background:#fff7fb; border:1px solid #ffb6d0; border-radius:8px;">
          <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>
      <div class="row"><strong>Score:</strong> <span><?= (int)$data['score']; ?></span></div>
      <div class="row"><strong>Streak:</strong> <span><?= (int)$data['streak']; ?></span></div>
      <div class="row"><strong>Kisses:</strong> <span><?= (int)$data['kisses']; ?></span></div>
      <form method="post" class="actions">
        <input type="hidden" name="action" value="quick_point_increase" />
        <button type="submit">+1 Point (auto +1 Kiss on /4)</button>
      </form>
      <form method="post" class="actions">
        <input type="hidden" name="action" value="quick_streak_increase" />
        <button type="submit" class="secondary">Quick Streak Increase</button>
      </form>
    </div>

    <div class="card">
      <h3 style="margin:0 0 8px 0;">Set Values</h3>
      <form method="post">
        <input type="hidden" name="action" value="set_values" />
        <div class="row">
          <label for="score">Score</label>
          <input id="score" name="score" type="number" min="0" value="<?= (int)$data['score']; ?>" />
        </div>
        <div class="row">
          <label for="streak">Streak</label>
          <input id="streak" name="streak" type="number" min="0" value="<?= (int)$data['streak']; ?>" />
        </div>
        <div class="row">
          <label for="kisses">Kisses</label>
          <input id="kisses" name="kisses" type="number" min="0" value="<?= (int)$data['kisses']; ?>" />
        </div>
        <div class="row">
          <button type="submit">Save</button>
        </div>
      </form>
    </div>
  </body>
</html>

