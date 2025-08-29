<?php
  // Values now loaded from data.json; the UI will adjust automatically
  $just_increased = false; // set true to show a celebratory score-up animation

  // Email-friendly mode: disables animations and adds legacy attrs like bgcolor
  $email_mode = false;

  // Load current score, streak, and kisses from JSON
  $dataFile = __DIR__ . '/data.json';
  $defaultData = [ 'score' => 0, 'streak' => 0, 'kisses' => 0 ];
  $data = $defaultData;
  if (is_readable($dataFile)) {
    $json = @file_get_contents($dataFile);
    $parsed = json_decode($json, true);
    if (is_array($parsed)) {
      $data = array_merge($defaultData, $parsed);
    }
  }

  $score = (int)$data['score'];
  // Current streak: number of days without any beauty point loss
  $streakDays = (int)$data['streak'];
  // Second metric: kisses
  $kisses = (int)$data['kisses'];
  $kissPrizes = [
    [ 'name' => 'purse',  'cost' => 150, 'emoji' => '👜' ],
    [ 'name' => 'mejuri', 'cost' => 150, 'emoji' => '💍' ],
    [ 'name' => 'omakase restaurant', 'cost' => 200, 'emoji' => '🍣' ],
    [ 'name' => 'Save streak', 'cost' => 30, 'emoji' => '🛟' ],
  ];

  // Ensure non-negative, since points start at 0
  if (!is_int($score)) { $score = (int)$score; }
  if ($score < 0) { $score = 0; }
  if (!is_int($kisses)) { $kisses = (int)$kisses; }
  if ($kisses < 0) { $kisses = 0; }

  $minPoint = $score;
  $maxPoint = $score + 20;
  $prevPoint = max(0, $score - 1);
  $horizonPercent = (int)round(($score / max(1, $maxPoint)) * 100);

  // Generic repeating milestones: define label and repeats_every
  // kind: 'icon' renders in the dot column as an emoji; 'pill' stacks as a label
  $repeatingMilestones = [
    [ 'label' => '+10 kisses', 'repeats_every' => 4,  'kind' => 'pill' ],
    [ 'label' => 'kiss', 'repeats_every' => 4,  'kind' => 'icon', 'icon' => '💋' ],
    [ 'label' => 'Store (e.g. Clothes, sephora)', 'repeats_every' => 10, 'kind' => 'pill' ],
    [ 'label' => 'Experience (e.g. nail salon, spa day, hair day)', 'repeats_every' => 17, 'kind' => 'pill' ],
  ];

  // Custom, one-off milestones mapping (won't repeat)
  // Example: 13 => ['netflix subscription'].
  // You can add multiple per point: 21 => ['cute scrunchies', 'pink water bottle']
  $customMilestones = [
    13 => ['Kitchen spoon rest'],
    25 => ['running shoes'],
  ];
?>
<?php
  // Compute next upcoming milestone and next prize info
  $nextPointWithLabel = null;
  $nextLabelText = null;
  $nextIconAtNext = null;
  for ($np = $score + 1; $np <= $score + 100; $np++) {
    $tmpIcon = null;
    $tmpLabels = [];
    foreach ($repeatingMilestones as $rm) {
      $every = isset($rm['repeats_every']) ? (int)$rm['repeats_every'] : 0;
      $kind  = isset($rm['kind']) ? $rm['kind'] : 'pill';
      if ($every > 0 && $np > 0 && $np % $every === 0) {
        if ($kind === 'icon' && $tmpIcon === null) {
          $tmpIcon = isset($rm['icon']) ? $rm['icon'] : '✨';
        } else {
          $tmpLabels[] = isset($rm['label']) ? $rm['label'] : 'milestone';
        }
      }
    }
    if (isset($customMilestones[$np]) && is_array($customMilestones[$np])) {
      $tmpLabels = array_merge($tmpLabels, $customMilestones[$np]);
    }
    if (count($tmpLabels) > 0 || $tmpIcon !== null) {
      $nextPointWithLabel = $np;
      $nextLabelText = count($tmpLabels) > 0 ? $tmpLabels[0] : null;
      $nextIconAtNext = $tmpIcon;
      break;
    }
  }
  $pointsToNext = ($nextPointWithLabel !== null) ? max(0, $nextPointWithLabel - $score) : null;

  // Determine next prize from kisses
  $nextPrize = null;
  foreach ($kissPrizes as $kp) {
    if (!isset($kp['cost'])) { continue; }
    if ((int)$kp['cost'] > (int)$kisses) {
      if ($nextPrize === null || (int)$kp['cost'] < (int)$nextPrize['cost']) {
        $nextPrize = $kp;
      }
    }
  }
  $nextPrizeName = $nextPrize ? (isset($nextPrize['name']) ? $nextPrize['name'] : 'prize') : null;
  $nextPrizeEmoji = $nextPrize ? (isset($nextPrize['emoji']) ? $nextPrize['emoji'] : '🎁') : null;
  $nextPrizeCost = $nextPrize ? (int)$nextPrize['cost'] : null;
  $kissesToNextPrize = ($nextPrizeCost !== null) ? max(0, $nextPrizeCost - (int)$kisses) : null;
  $kissProgressPercent = ($nextPrizeCost !== null && $nextPrizeCost > 0) ? (int)round((($kisses) / $nextPrizeCost) * 100) : null;
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sweet Progress</title>
  </head>
  <body style="margin:0;padding:0;background:#ffecf7;overflow-x:hidden;max-width:100vw;" bgcolor="#ffecf7">
    <?php if (!$email_mode): ?>
    <style>
      @keyframes popIn { 0% { transform: scale(0.7); opacity: 0.2; } 60% { transform: scale(1.06); opacity: 1; } 100% { transform: scale(1); } }
      @keyframes bounceSoft { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-2px); } }
      @keyframes pulseGlow { 0% { transform: scale(0.92); } 50% { transform: scale(1.06); } 100% { transform: scale(0.92); } }
      @keyframes twinkle { 0%,100% { opacity: 1; } 50% { opacity: 0.75; } }
      @keyframes floatUp { 0% { transform: translateY(8px); opacity: 0.0; } 50% { transform: translateY(-2px); opacity: 1; } 100% { transform: translateY(-10px); opacity: 0; } }

      /* Extra web-only visuals */
      :root { --pink:#ff2b83; --pink-200:#ff86b8; --pink-100:#ffc5dd; --rose-50:#fff3f9; --lavender:#c8a2ff; --peach:#ffb48a; --mint:#a6f0d3; --gold:#ffd56b; }
      @keyframes shimmerX { 0% { background-position: 0% 50%; } 100% { background-position: 200% 50%; } }
      @keyframes shimmerY { 0% { background-position: 50% 0%; } 100% { background-position: 50% 200%; } }
      @keyframes bob { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
      @keyframes drift { 0% { transform: translateY(0) translateX(0) rotate(0deg); opacity: 0; } 10% { opacity: 1; } 100% { transform: translateY(-120vh) translateX(var(--driftX, 0)) rotate(360deg); opacity: 0; } }
      .bg-gradient { position:fixed; inset:0; z-index:0; background: radial-gradient(1200px 800px at 10% 110%, rgba(255, 197, 221, 0.55), transparent 50%), radial-gradient(1000px 700px at 90% -10%, rgba(200, 162, 255, 0.30), transparent 40%), linear-gradient(135deg, #ffe6f2, #ffecf7 40%, #ffe3f0 70%); filter: saturate(108%); animation: shimmerX 12s linear infinite; background-size: 200% 200%; max-width:100%; overflow:hidden; }
      .titleShimmer { background: linear-gradient(90deg, #ff2b83, #ff86b8, #ff2b83); background-size: 200% 100%; -webkit-background-clip: text; background-clip: text; color: transparent; animation: shimmerX 5s ease-in-out infinite; text-shadow: 0 2px 18px rgba(255, 43, 131, 0.25); }
      #cardWrap { display:inline-block; will-change: transform, filter; transform-style: preserve-3d; transition: transform 300ms ease, filter 300ms ease; filter: drop-shadow(0 14px 28px rgba(255, 43, 131, 0.24)) drop-shadow(0 4px 10px rgba(0,0,0,0.08)); }
      #cardWrap:hover { filter: drop-shadow(0 18px 36px rgba(255, 43, 131, 0.30)) drop-shadow(0 6px 14px rgba(0,0,0,0.10)); }
      #cardWrap.ambientPulse { animation: ambientGlow 6s ease-in-out infinite; }
      @keyframes ambientGlow { 0%, 100% { filter: drop-shadow(0 14px 28px rgba(255, 43, 131, 0.24)) drop-shadow(0 4px 10px rgba(0,0,0,0.08)); } 50% { filter: drop-shadow(0 18px 36px rgba(255, 43, 131, 0.34)) drop-shadow(0 8px 14px rgba(0,0,0,0.12)); } }
      #floaters { position:fixed; inset:0; pointer-events:none; z-index:1; overflow:hidden; }
      .floater { position:absolute; bottom:-40px; font-size:20px; will-change: transform, opacity; animation: drift var(--dur, 8s) linear forwards; filter: drop-shadow(0 2px 8px rgba(255, 43, 131, 0.25)); }
      #cursor-trail { position:fixed; inset:0; pointer-events:none; z-index:3; }
      .trail-heart { position:absolute; font-size:12px; transform: translate(-50%, -50%); animation: floatUp 1400ms ease-out forwards; filter: drop-shadow(0 2px 6px rgba(255, 43, 131, 0.35)); }
      .ticker { position:relative; overflow:hidden; border-radius:12px; border:1px solid var(--pink-100); background:linear-gradient(90deg, rgba(255,255,255,0.85), rgba(255,246,250,0.95)); box-shadow: 0 8px 18px rgba(255, 43, 131, 0.08) inset; }
      .tickerTrack { display:inline-block; white-space:nowrap; padding:8px 0; animation: marquee 24s linear infinite; }
      .tickerItem { display:inline-block; margin:0 14px; font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif; font-size:12px; line-height:16px; color:#b34a7f; }
      @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
      .prizeScroller { white-space:nowrap; overflow-x:auto; -webkit-overflow-scrolling:touch; scroll-snap-type:x mandatory; padding-bottom:2px; }
      .prizeCard { display:inline-block; vertical-align:top; margin-right:8px; background:#ffe3f0; border:1px solid #ffb6d0; border-radius:12px; padding:8px; scroll-snap-align:center; transition: transform 300ms ease, box-shadow 300ms ease; animation: bob 4s ease-in-out infinite; box-shadow: 0 1px 0 rgba(255,255,255,0.6) inset, 0 8px 16px rgba(255, 43, 131, 0.10); will-change: transform; }
      .prizeCard:hover { transform: translateY(-6px) rotate(-2deg) scale(1.05); box-shadow: 0 1px 0 rgba(255,255,255,0.7) inset, 0 14px 28px rgba(255,43,131,0.18); }
      .prizeImg { display:block; border-radius:8px; background:#ffffff; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
      .ringWrap { position:relative; width:64px; height:64px; border-radius:999px; background: conic-gradient(from 270deg, var(--gold) <?php echo $horizonPercent; ?>%, #ffe3f0 <?php echo $horizonPercent; ?>%); display:flex; align-items:center; justify-content:center; box-shadow: 0 4px 16px rgba(0,0,0,0.06), 0 0 0 4px rgba(255,255,255,0.8) inset; }
      .ringInner { width:48px; height:48px; border-radius:999px; background: radial-gradient(120% 120% at 30% 20%, #ffffff, #fff6fb); display:flex; align-items:center; justify-content:center; font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif; color:#ff2b83; font-weight:bold; font-size:14px; }
      .miniBar { position:relative; height:8px; border-radius:999px; background:linear-gradient(90deg, #ffe3f0, #ffecf7); overflow:hidden; border:1px solid #ffb6d0; }
      .miniBarFill { height:100%; border-radius:999px; background:linear-gradient(90deg, var(--mint), var(--peach), var(--pink)); box-shadow:0 0 6px rgba(255, 43, 131, 0.3); }
      .nextBubble { display:inline-block; background:#fff7fb; border:1px solid #ffb6d0; border-radius:14px; padding:6px 10px; color:#ff2b83; font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif; font-size:12px; line-height:14px; box-shadow: 0 1px 0 rgba(255,255,255,0.7) inset; }
      .js-ripple { position:relative; overflow:hidden; }
      .js-ripple::after { content:""; position:absolute; left:50%; top:50%; width:6px; height:6px; border-radius:999px; background:rgba(255, 43, 131, 0.25); transform: translate(-50%,-50%) scale(1); opacity:0; }
      .js-ripple.is-rippling::after { animation: ripple 500ms ease-out; }
      @keyframes ripple { 0% { opacity:0.5; transform: translate(-50%,-50%) scale(1); } 100% { opacity:0; transform: translate(-50%,-50%) scale(18); } }

      /* 1) Global overflow and sizing guards */
      html, body { width:100%; max-width:100vw; overflow-x:hidden; }
      *, *::before, *::after { box-sizing: border-box; }
      body { overflow-wrap:anywhere; word-break:break-word; }
      img, svg, video, canvas { max-width:100%; height:auto; display:block; }

      /* 2) No-horizontal-scroll wrapper */
      .noHScroll { position:relative; overflow-x:hidden; max-width:100%; }

      /* 3) Harden ticker visuals & containment */
      #cardWrap { max-width:100%; }
      .ticker { width:100%; max-width:200px; overflow:hidden; -webkit-mask-image: linear-gradient(to right, transparent 0, black 14px, black calc(100% - 14px), transparent 100%); mask-image: linear-gradient(to right, transparent 0, black 14px, black calc(100% - 14px), transparent 100%); }
      .tickerTrack { contain: layout paint; will-change: transform; }

      /* 4) Progress bar safety */
      .miniBar { width:100%; max-width:200px; }

      /* 5) Mobile tuning for ticker */
      @media (max-width: 480px) {
        .tickerItem { margin: 0 10px; }
        .tickerTrack { animation-duration: 36s; }
        .ticker { -webkit-mask-image: linear-gradient(to right, transparent 0, black 10px, black calc(100% - 10px), transparent 100%); mask-image: linear-gradient(to right, transparent 0, black 10px, black calc(100% - 10px), transparent 100%); }
      }
    </style>
    <div class="bg-gradient"></div>
    <div id="floaters"></div>
    <div id="cursor-trail"></div>
    <?php endif; ?>
    <!-- Outer wrapper table (email-safe) -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffecf7;" bgcolor="#ffecf7">
      <tr>
        <td align="center" style="padding:20px 12px;">
          <!-- Card container -->
          <?php if (!$email_mode): ?><div id="cardWrap" class="ambientPulse"><?php endif; ?>
          <table role="presentation" width="360" cellpadding="0" cellspacing="0" border="0" style="width:360px;max-width:94%;background:#ffffff;border-radius:16px;border:2px solid #ffc5dd;overflow:hidden;" bgcolor="#ffffff">
            <tr>
              <td align="center" style="padding:14px 20px 0 20px;">
                <?php if ((int)$streakDays > 0): ?>
                  <span style="display:inline-block;background:#ffe3f0;border:1px solid #ffb6d0;color:#ff2b83;border-radius:999px;padding:6px 12px;font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:14px;<?php if (!$email_mode) { echo 'animation:popIn 500ms ease-out both;'; } ?>">
                    <span role="img" aria-label="fire" title="fire" style="margin-right:6px;">🔥</span>
                    Current streak: <strong style="color:#ff2b83;"><?php echo (int)$streakDays; ?></strong> day<?php echo ((int)$streakDays === 1 ? '' : 's'); ?>
                  </span>
                  <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:16px;color:#b34a7f;margin-top:6px;">
                    Maintain your streak (no beauty point loss) and you’ll earn <?php echo (int)$streakDays; ?> kisses at the end of today.
                  </div>
                <?php else: ?>
                  <span style="display:inline-block;background:#ffe3f0;border:1px solid #ffb6d0;color:#ff2b83;border-radius:999px;padding:6px 12px;font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:14px;<?php if (!$email_mode) { echo 'animation:popIn 500ms ease-out both;'; } ?>">
                    <span role="img" aria-label="fire" title="fire" style="margin-right:6px;">🔥</span>
                    No current streak
                  </span>
                  <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:16px;color:#b34a7f;margin-top:6px;">
                    When you have a streak (no beauty point loss), you’ll earn that many kisses at the end of each day. For example, a 3-day streak earns 3 kisses at day’s end.
                  </div>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:20px 20px 8px 20px;">
                <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:22px;line-height:28px;color:#ff2b83;font-weight:bold;<?php if (!$email_mode) { echo 'animation:popIn 600ms ease-out both;'; } ?>">
                  <?php if (!$email_mode): ?><span class="titleShimmer"><?php endif; ?>
                  Your Sweet Progress 💖
                  <?php if (!$email_mode): ?></span><?php endif; ?>
                </div>
                <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:13px;line-height:18px;color:#b34a7f;margin-top:6px;">
                  Every step is a sparkle closer ✨
                </div>
              </td>
            </tr>
            <?php if (!$email_mode): ?>
            <tr>
              <td style="padding:0 12px 8px 12px;">
                <div class="noHScroll">
                  <div class="ticker">
                    <div id="tickerTrack" class="tickerTrack"></div>
                  </div>
                </div>
              </td>
            </tr>
            <?php endif; ?>
            <tr>
              <td style="padding:8px 12px 18px 12px;">
                <!-- Prominent current score & progress bar -->
                <div class="noHScroll">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:8px;">
                  <tr>
                    <td align="left" valign="middle" style="padding:0 4px 8px 4px;">
                      <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:14px;line-height:18px;color:#ff2b83;font-weight:bold;<?php if (!$email_mode) { echo 'animation:popIn 500ms ease-out both;'; } ?>">
                        Current score: <span style="color:#ff2b83;"><?php echo (int)$score; ?></span> pts
                      </div>
                      <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:16px;color:#b34a7f;">
                        You’ve come so far. Keep going!
                      </div>
                      <div class="miniBar" style="margin-top:6px;<?php if ($email_mode) { echo 'background:#ffe3f0;border:1px solid #ffb6d0;height:8px;border-radius:999px;'; } ?>">
                        <div class="miniBarFill" style="width:<?php echo $horizonPercent; ?>%;<?php if ($email_mode) { echo 'background:#ff86b8;height:8px;border-radius:999px;'; } ?>"></div>
                      </div>
                    </td>
                    <?php if (!$email_mode): ?>
                    <td align="right" valign="middle" style="padding:0 4px 8px 4px;">
                      <div class="ringWrap"><div class="ringInner"><?php echo (int)$score; ?></div></div>
                    </td>
                    <?php else: ?>
                    <td align="right" valign="middle" style="padding:0 4px 8px 4px;">
                      <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;border-spacing:0;">
                        <tr>
                          <td align="center" style="width:48px;height:48px;border-radius:999px;background:#fff3f9;border:1px solid #ffb6d0;color:#ff2b83;font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:14px;font-weight:bold;">
                            <?php echo (int)$score; ?>
                          </td>
                        </tr>
                      </table>
                    </td>
                    <?php endif; ?>
                  </tr>
                </table>
                </div>
                <?php if ($nextPointWithLabel !== null): ?>
                <div style="margin:4px 4px 10px 4px;">
                  <span class="nextBubble js-ripple" style="<?php if ($email_mode) { echo 'background:#fff7fb;border:1px solid #ffb6d0;border-radius:14px;padding:6px 10px;color:#ff2b83;'; } ?>">
                    <?php if ($nextIconAtNext): ?><span style="margin-right:6px;"><?php echo htmlspecialchars($nextIconAtNext, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                    Next at <strong style="color:#ff2b83;"><?php echo (int)$nextPointWithLabel; ?></strong>:
                    <span style="color:#b34a7f;"><?php echo htmlspecialchars($nextLabelText ? $nextLabelText : 'sweet surprise', ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php if ($pointsToNext !== null): ?><span style="color:#cc6a9a;margin-left:6px;">(<?php echo (int)$pointsToNext; ?> to go)</span><?php endif; ?>
                  </span>
                </div>
                <?php endif; ?>
                <!-- Progress list table: three columns (value | line+dot | labels) -->
                <div class="noHScroll">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;border-spacing:0;">
                  <?php for ($p = $minPoint; $p <= $maxPoint; $p++) :
                    $isCurrent = ($p === $score);

                    // Determine icon and labels for this point using generic definitions
                    $iconAtPoint = null; // e.g., kiss emoji
                    $labels = [];

                    foreach ($repeatingMilestones as $rm) {
                      $every = isset($rm['repeats_every']) ? (int)$rm['repeats_every'] : 0;
                      $kind  = isset($rm['kind']) ? $rm['kind'] : 'pill';
                      if ($every > 0 && $p > 0 && $p % $every === 0) {
                        if ($kind === 'icon' && $iconAtPoint === null) {
                          $iconAtPoint = isset($rm['icon']) ? $rm['icon'] : '✨';
                        } else {
                          $labels[] = isset($rm['label']) ? $rm['label'] : 'milestone';
                        }
                      }
                    }

                    if (isset($customMilestones[$p]) && is_array($customMilestones[$p])) {
                      $labels = array_merge($labels, $customMilestones[$p]);
                    }

                    $hasLabels = (count($labels) > 0);
                    $rowBg = $isCurrent ? '#fff3f9' : '#ffffff';
                  ?>
                  <tr>
                    <!-- Value column -->
                    <td width="64" valign="middle" style="width:64px;padding:8px 6px 8px 6px;background:<?php echo $rowBg; ?>;border-left:<?php echo $isCurrent ? '4px solid #ff86b8' : '4px solid #ffffff'; ?>;" bgcolor="<?php echo $rowBg; ?>">
                      <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:16px;color:#b34a7f;<?php echo $isCurrent ? 'font-weight:bold;color:#ff2b83;' : ''; ?>">
                        <?php echo $p; ?> pts
                      </div>
                    </td>

                    <!-- Line + dot/emoji column (continuous pink line as background) -->
                    <td width="24" valign="middle" align="center" style="width:24px;background:#ffd9ea;<?php if (!$email_mode) { echo 'background-image: linear-gradient(180deg, rgba(255,255,255,0.25), rgba(255,255,255,0) 50%, rgba(255,255,255,0.25)); background-size: 100% 28px; animation: shimmerY 10s linear infinite;'; } ?>" bgcolor="#ffd9ea">
                      <?php if ($iconAtPoint): ?>
                        <span style="display:inline-block;font-size:16px;line-height:16px;margin:6px 0;<?php if (!$email_mode) { echo 'animation:bounceSoft 2s ease-in-out infinite;'; } ?>"><?php echo htmlspecialchars($iconAtPoint, ENT_QUOTES, 'UTF-8'); ?></span>
                      <?php else: ?>
                        <span style="display:inline-block;width:12px;height:12px;background:#ff4da6;border-radius:999px;border:2px solid #ffffff;box-shadow:0 0 0 2px #ff97c2;margin:6px 0;<?php echo ($isCurrent && !$email_mode) ? 'animation:pulseGlow 1.8s ease-in-out infinite;box-shadow:0 0 0 2px #ff97c2, 0 0 10px #ff86b8;' : ''; ?>"></span>
                      <?php endif; ?>
                      <?php if ($just_increased && $p === $prevPoint): ?>
                        <span style="display:inline-block;font-size:14px;line-height:14px;color:#ff2b83;margin:0;<?php if (!$email_mode) { echo 'animation:floatUp 1.6s ease-out 1 both;'; } ?>">💗</span>
                      <?php endif; ?>
                    </td>

                    <!-- Labels column (stacked pills) -->
                    <td valign="middle" style="padding:8px 8px 8px 8px;background:<?php echo $rowBg; ?>;" bgcolor="<?php echo $rowBg; ?>">
                      <?php if ($isCurrent): ?>
                        <span class="js-ripple" style="display:inline-block;background:#ffe3f0;border:1px solid #ffb6d0;color:#ff2b83;border-radius:999px;padding:4px 10px;font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:14px;margin-right:0;margin-bottom:6px;<?php if (!$email_mode) { echo 'animation:popIn 500ms ease-out both;'; } ?>">You are here ✨</span>
                      <?php endif; ?>

                      <?php if ($hasLabels): ?>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                          <?php foreach ($labels as $label): ?>
                            <tr>
                              <td style="padding:0 0 6px 0;">
                                <span class="js-ripple" style="display:block;background:#ffe3f0;border:1px solid #ffb6d0;color:#ff2b83;border-radius:12px;padding:6px 10px;font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:14px;word-break:break-word;<?php if (!$email_mode) { echo 'animation:twinkle 3s ease-in-out infinite;box-shadow:0 1px 0 rgba(255,255,255,0.7) inset;'; } ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </table>
                      <?php endif; ?>

                      <?php if (!$isCurrent && !$hasLabels): ?>
                        <span style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:16px;color:#cc6a9a;">💗</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endfor; ?>
                </table>
                </div>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:0 20px 12px 20px;">
                <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:16px;color:#b34a7f;">
                  Reaching for the stars looks good on you ⭐💗
                </div>
              </td>
            </tr>
            <tr>
              <td align="left" style="padding:0 20px 20px 20px;">
                <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:14px;line-height:18px;color:#ff2b83;font-weight:bold;margin-top:4px;margin-bottom:8px;">
                  Kiss prizes
                </div>
                <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:16px;color:#b34a7f;margin-bottom:8px;">
                  You have <strong style="color:#ff2b83;"><?php echo (int)$kisses; ?></strong> kisses.
                  <?php if ($nextPrizeName !== null): ?>
                    <span class="nextBubble" style="margin-left:6px;"><?php echo htmlspecialchars($nextPrizeEmoji . ' ' . $nextPrizeName, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span style="margin-left:6px;">— <?php echo (int)$kissesToNextPrize; ?> to go</span>
                    <?php if ($kissProgressPercent !== null): ?>
                      <div class="miniBar" style="margin-top:6px;max-width:240px;">
                        <div class="miniBarFill" style="width: <?php echo max(0, min(100, (int)$kissProgressPercent)); ?>%;"></div>
                      </div>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="nextBubble" style="margin-left:6px;">All prizes are affordable 🎉</span>
                  <?php endif; ?>
                </div>
                <div class="prizeScroller" style="padding-top:2px;">
                  <?php foreach ($kissPrizes as $kp): ?>
                    <?php
                      $pName = isset($kp['name']) ? $kp['name'] : 'prize';
                      $pCost = isset($kp['cost']) ? (int)$kp['cost'] : 0;
                      $pEmoji = isset($kp['emoji']) ? $kp['emoji'] : '🎁';
                      $pAffordable = (int)$kisses >= $pCost;
                      $pPct = $pCost > 0 ? (int)round(((int)$kisses / $pCost) * 100) : 100;
                      if ($pPct < 0) { $pPct = 0; }
                      if ($pPct > 100) { $pPct = 100; }
                      $pRemaining = max(0, $pCost - (int)$kisses);
                    ?>
                    <div class="prizeCard" style="min-width:140px;">
                      <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                        <span style="font-size:24px;line-height:1;"><?php echo htmlspecialchars($pEmoji, ENT_QUOTES, 'UTF-8'); ?></span>
                        <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:13px;line-height:16px;color:#ff2b83;font-weight:bold;"><?php echo htmlspecialchars($pName, ENT_QUOTES, 'UTF-8'); ?></div>
                      </div>
                      <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:16px;color:#b34a7f;margin-bottom:6px;">Cost: <?php echo (int)$pCost; ?> kisses</div>
                      <div class="miniBar" style="max-width:180px;">
                        <div class="miniBarFill" style="width: <?php echo $pPct; ?>%;"></div>
                      </div>
                      <?php if ($pAffordable): ?>
                        <div class="nextBubble" style="margin-top:6px;background:#ffffff;border-color:#a6f0d3;color:#2b8a3e;">Available now</div>
                      <?php else: ?>
                        <div class="nextBubble" style="margin-top:6px;"><?php echo (int)$pRemaining; ?> to go</div>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </td>
            </tr>
          </table>
          <?php if (!$email_mode): ?></div><?php endif; ?>
        </td>
      </tr>
    </table>
    <?php if (!$email_mode): ?>
    <script>
      (function(){
        const cardWrap = document.getElementById('cardWrap');
        if (cardWrap) {
          const bounds = () => cardWrap.getBoundingClientRect();
          cardWrap.addEventListener('mousemove', (e) => {
            const b = bounds();
            const cx = b.left + b.width/2; const cy = b.top + b.height/2;
            const dx = (e.clientX - cx) / (b.width/2);
            const dy = (e.clientY - cy) / (b.height/2);
            const rotY = dx * 8; const rotX = -dy * 8;
            cardWrap.style.transform = 'rotateX(' + rotX + 'deg) rotateY(' + rotY + 'deg)';
          });
          cardWrap.addEventListener('mouseleave', () => {
            cardWrap.style.transform = 'rotateX(0deg) rotateY(0deg)';
          });
        }

        // Compliment ticker
        const compliments = [
          'You glow different today ✨', 'Every point is a kiss closer 💋', 'Sparkle champion ⭐',
          'Cutest streak keeper 🔥', 'Sweetness overload 🍯', 'Iconic. Always. 💖',
          'Keep shining bright 🌟', 'Style points unlocked 💎', 'Grace in motion 💃'
        ];
        const tickerTrack = document.getElementById('tickerTrack');
        if (tickerTrack) {
          const build = () => {
            // 3) Harden ticker: duplicate content just enough to cover width, not excessively
            const base = compliments.slice();
            const duplicates = Math.max(2, Math.ceil((window.innerWidth || 320) / 240));
            let seq = [];
            for (let i = 0; i < duplicates + 1; i++) { seq = seq.concat(base); }
            tickerTrack.innerHTML = seq.map((t) => '<span class="tickerItem">' + t + '</span>').join('');
          };
          build();
          window.addEventListener('resize', () => { build(); }, { passive: true });
        }

        // Floating emojis
        const floaters = document.getElementById('floaters');
        const emojis = ['💖','✨','💋','🌸','🎀'];
        let floaterCount = 0;
        function spawnFloater(){
          if (!floaters) return;
          floaterCount = (floaterCount + 1) % 80;
          const el = document.createElement('span');
          el.className = 'floater';
          el.textContent = emojis[Math.floor(Math.random()*emojis.length)];
          const left = Math.random()*100;
          const size = 14 + Math.random()*18;
          const dur = 8 + Math.random()*10;
          const driftX = (Math.random()*120 - 60) + 'px';
          el.style.left = left + 'vw';
          el.style.fontSize = size + 'px';
          el.style.setProperty('--dur', dur + 's');
          el.style.setProperty('--driftX', driftX);
          floaters.appendChild(el);
          setTimeout(() => { el.remove(); }, dur*1000);
        }
        setInterval(spawnFloater, 800);

        // Cursor heart trail
        const trail = document.getElementById('cursor-trail');
        let lastT = 0;
        window.addEventListener('pointermove', (e) => {
          const now = performance.now();
          if (now - lastT < 24) return; // throttle
          lastT = now;
          if (!trail) return;
          const h = document.createElement('span');
          h.className = 'trail-heart';
          h.textContent = '💗';
          h.style.left = e.clientX + 'px';
          h.style.top = e.clientY + 'px';
          trail.appendChild(h);
          setTimeout(() => h.remove(), 1500);
        });

        // Ripple + haptics
        const rippleTargets = Array.prototype.slice.call(document.querySelectorAll('.js-ripple'));
        rippleTargets.forEach((el) => {
          el.addEventListener('click', (e) => {
            try { if (navigator.vibrate) navigator.vibrate(8); } catch (err) {}
            el.classList.remove('is-rippling');
            void el.offsetWidth; // restart animation
            el.classList.add('is-rippling');
            setTimeout(() => el.classList.remove('is-rippling'), 500);
          }, { passive: true });
        });

        // Prize center scaling removed with prize section

        // Emoji confetti on increase
        const JUST_INCREASED = <?php echo $just_increased ? 'true' : 'false'; ?>;
        if (JUST_INCREASED) {
          const burst = (x, y) => {
            const parts = 24;
            for (let i = 0; i < parts; i++) {
              const s = document.createElement('span');
              s.className = 'trail-heart';
              s.textContent = emojis[(i % emojis.length)];
              const angle = (Math.PI * 2) * (i / parts);
              const radius = 4 + Math.random()*64;
              const tx = x + Math.cos(angle)*radius;
              const ty = y + Math.sin(angle)*radius;
              s.style.left = tx + 'px';
              s.style.top = ty + 'px';
              s.style.fontSize = (12 + Math.random()*10) + 'px';
              document.body.appendChild(s);
              setTimeout(() => s.remove(), 1200 + Math.random()*600);
            }
          };
          const rect = (cardWrap ? cardWrap.getBoundingClientRect() : { left: window.innerWidth/2, top: window.innerHeight/2, width: 0, height: 0 });
          burst(rect.left + rect.width/2, rect.top + 40);
        }
        // 4) JS overflow guard: clamp any element wider than viewport
        function clampOverflows(){
          try {
            const viewport = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
            const nodes = document.querySelectorAll('body *');
            nodes.forEach((el) => {
              const style = window.getComputedStyle(el);
              if (style.position === 'fixed') return; // fixed may be full-width background
              const rect = el.getBoundingClientRect();
              if (rect.width > viewport + 1) {
                el.style.maxWidth = '100%';
                el.style.overflowX = 'hidden';
              }
            });
          } catch (e) {}
        }
        clampOverflows();
        window.addEventListener('resize', clampOverflows, { passive: true });

      })();
    </script>
    <?php endif; ?>
  </body>
  </html>
