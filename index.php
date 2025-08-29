<?php
  // Change these values; the UI will adjust automatically
  $score = 6; //example
  $just_increased = false; // set true to show a celebratory score-up animation

  // Email-friendly mode: disables animations and adds legacy attrs like bgcolor
  $email_mode = false;

  // Current streak (hardcoded): number of days without any beauty point loss
  // This is display-only; no logic is implemented here.
  $streakDays = 3; // example

  // Second metric: kisses (hardcoded, manually incremented when hitting kiss milestones)
  $kisses = 10; // example
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
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sweet Progress</title>
  </head>
  <body style="margin:0;padding:0;background:#ffecf7;" bgcolor="#ffecf7">
    <?php if (!$email_mode): ?>
    <style>
      @keyframes popIn { 0% { transform: scale(0.7); opacity: 0.2; } 60% { transform: scale(1.06); opacity: 1; } 100% { transform: scale(1); } }
      @keyframes bounceSoft { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-2px); } }
      @keyframes pulseGlow { 0% { transform: scale(0.92); } 50% { transform: scale(1.06); } 100% { transform: scale(0.92); } }
      @keyframes twinkle { 0%,100% { opacity: 1; } 50% { opacity: 0.75; } }
      @keyframes floatUp { 0% { transform: translateY(8px); opacity: 0.0; } 50% { transform: translateY(-2px); opacity: 1; } 100% { transform: translateY(-10px); opacity: 0; } }

      /* Extra web-only visuals */
      :root { --pink:#ff2b83; --pink-200:#ff86b8; --pink-100:#ffc5dd; --rose-50:#fff3f9; }
      @keyframes shimmerX { 0% { background-position: 0% 50%; } 100% { background-position: 200% 50%; } }
      @keyframes bob { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
      @keyframes drift { 0% { transform: translateY(0) translateX(0) rotate(0deg); opacity: 0; } 10% { opacity: 1; } 100% { transform: translateY(-120vh) translateX(var(--driftX, 0)) rotate(360deg); opacity: 0; } }
      .bg-gradient { position:fixed; inset:0; z-index:0; background: radial-gradient(1200px 800px at 10% 110%, rgba(255, 197, 221, 0.55), transparent 50%), radial-gradient(1000px 700px at 90% -10%, rgba(255, 134, 184, 0.45), transparent 40%), linear-gradient(135deg, #ffe6f2, #ffecf7 40%, #ffe3f0 70%); filter: saturate(105%); animation: shimmerX 12s linear infinite; background-size: 200% 200%; }
      .titleShimmer { background: linear-gradient(90deg, #ff2b83, #ff86b8, #ff2b83); background-size: 200% 100%; -webkit-background-clip: text; background-clip: text; color: transparent; animation: shimmerX 5s ease-in-out infinite; text-shadow: 0 2px 18px rgba(255, 43, 131, 0.25); }
      #cardWrap { display:inline-block; will-change: transform, filter; transform-style: preserve-3d; transition: transform 300ms ease, filter 300ms ease; filter: drop-shadow(0 14px 28px rgba(255, 43, 131, 0.24)) drop-shadow(0 4px 10px rgba(0,0,0,0.08)); }
      #cardWrap:hover { filter: drop-shadow(0 18px 36px rgba(255, 43, 131, 0.30)) drop-shadow(0 6px 14px rgba(0,0,0,0.10)); }
      #floaters { position:fixed; inset:0; pointer-events:none; z-index:1; overflow:hidden; }
      .floater { position:absolute; bottom:-40px; font-size:20px; will-change: transform, opacity; animation: drift var(--dur, 8s) linear forwards; filter: drop-shadow(0 2px 8px rgba(255, 43, 131, 0.25)); }
      #cursor-trail { position:fixed; inset:0; pointer-events:none; z-index:3; }
      .trail-heart { position:absolute; font-size:12px; transform: translate(-50%, -50%); animation: floatUp 1400ms ease-out forwards; filter: drop-shadow(0 2px 6px rgba(255, 43, 131, 0.35)); }
      .ticker { position:relative; overflow:hidden; border-radius:12px; border:1px solid var(--pink-100); background:#fffafc; }
      .tickerTrack { display:inline-block; white-space:nowrap; padding:8px 0; animation: marquee 24s linear infinite; }
      .tickerItem { display:inline-block; margin:0 14px; font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif; font-size:12px; line-height:16px; color:#b34a7f; }
      @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
      .prizeScroller { white-space:nowrap; overflow-x:auto; -webkit-overflow-scrolling:touch; scroll-snap-type:x mandatory; padding-bottom:2px; }
      .prizeCard { display:inline-block; vertical-align:top; margin-right:8px; background:#ffe3f0; border:1px solid #ffb6d0; border-radius:12px; padding:8px; scroll-snap-align:center; transition: transform 300ms ease, box-shadow 300ms ease; animation: bob 4s ease-in-out infinite; box-shadow: 0 1px 0 rgba(255,255,255,0.6) inset, 0 8px 16px rgba(255, 43, 131, 0.10); }
      .prizeCard:hover { transform: translateY(-6px) rotate(-2deg) scale(1.03); box-shadow: 0 1px 0 rgba(255,255,255,0.7) inset, 0 14px 28px rgba(255,43,131,0.18); }
      .prizeImg { display:block; border-radius:8px; background:#ffffff; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
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
          <?php if (!$email_mode): ?><div id="cardWrap"><?php endif; ?>
          <table role="presentation" width="360" cellpadding="0" cellspacing="0" border="0" style="width:360px;max-width:94%;background:#ffffff;border-radius:16px;border:2px solid #ffc5dd;" bgcolor="#ffffff">
            <tr>
              <td align="center" style="padding:14px 20px 0 20px;">
                <?php if ((int)$streakDays > 0): ?>
                  <span style="display:inline-block;background:#ffe3f0;border:1px solid #ffb6d0;color:#ff2b83;border-radius:999px;padding:6px 12px;font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:14px;<?php if (!$email_mode) { echo 'animation:popIn 500ms ease-out both;'; } ?>">
                    <span role="img" aria-label="fire" title="fire" style="margin-right:6px;">🔥</span>
                    Current streak: <strong style="color:#ff2b83;">&<?php echo (int)$streakDays; ?></strong> day<?php echo ((int)$streakDays === 1 ? '' : 's'); ?>
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
              <td style="padding:0 16px 8px 16px;">
                <div class="ticker" style="border:1px solid #ffc5dd;">
                  <div class="tickerTrack" id="tickerTrack"></div>
                </div>
              </td>
            </tr>
            <?php endif; ?>
            <tr>
              <td style="padding:8px 12px 18px 12px;">
                <!-- Prominent current score & progress bar -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:8px;">
                  <tr>
                    <td align="left" style="padding:0 4px 8px 4px;">
                      <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:14px;line-height:18px;color:#ff2b83;font-weight:bold;<?php if (!$email_mode) { echo 'animation:popIn 500ms ease-out both;'; } ?>">
                        Current score: <span style="color:#ff2b83;">&<?php echo (int)$score; ?></span> pts
                      </div>
                      <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:16px;color:#b34a7f;">
                        You’ve come so far. Keep going!
                      </div>
                      <div style="height:10px;background:#ffe3f0;border:1px solid #ffb6d0;border-radius:999px;margin-top:6px;overflow:hidden;">
                        <div style="height:100%;width:<?php echo max(0,min(100,$horizonPercent)); ?>%;background:#ff86b8;border-right:1px solid #ff2b83;<?php if (!$email_mode) { echo 'animation:popIn 500ms ease-out both;'; } ?>"></div>
                      </div>
                    </td>
                  </tr>
                </table>
                <!-- Progress list table: three columns (value | line+dot | labels) -->
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
                    <td width="24" valign="middle" align="center" style="width:24px;background:#ffd9ea;" bgcolor="#ffd9ea">
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
                        <span style="display:inline-block;background:#ffe3f0;border:1px solid #ffb6d0;color:#ff2b83;border-radius:999px;padding:4px 10px;font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:14px;margin-right:0;margin-bottom:6px;<?php if (!$email_mode) { echo 'animation:popIn 500ms ease-out both;'; } ?>">You are here ✨</span>
                      <?php endif; ?>

                      <?php if ($hasLabels): ?>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                          <?php foreach ($labels as $label): ?>
                            <tr>
                              <td style="padding:0 0 6px 0;">
                                <span style="display:block;background:#ffe3f0;border:1px solid #ffb6d0;color:#ff2b83;border-radius:12px;padding:6px 10px;font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:14px;word-break:break-word;<?php if (!$email_mode) { echo 'animation:twinkle 3s ease-in-out infinite;box-shadow:0 1px 0 rgba(255,255,255,0.7) inset;'; } ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
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
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:0 20px 12px 20px;">
                <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:16px;color:#b34a7f;">
                  Reaching for the stars looks good on you ⭐💗
                </div>
              </td>
            </tr>
            <!-- Footer-like kisses and prizes section -->
            <tr>
              <td align="left" style="padding:12px 12px 14px 12px;border-top:1px solid #ffc5dd;background:#fffafc;" bgcolor="#fffafc">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                  <tr>
                    <td style="padding:0 0 8px 0;">
                      <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:16px;color:#b34a7f;">
                        <span style="color:#ff2b83;font-weight:bold;">Kisses:</span>
                        <span style="color:#b34a7f;"><?php echo $kisses; ?></span>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <?php if ($email_mode): ?>
                      <!-- Email-friendly horizontal list via table cells -->
                      <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                          <?php foreach ($kissPrizes as $prize): ?>
                            <td valign="top" style="padding-right:8px;">
                              <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="background:#ffe3f0;border:1px solid #ffb6d0;border-radius:12px;">
                                <tr>
                                  <td align="center" style="padding:8px;">
                                    <?php
                                      $hasImg = isset($prize['img']) && trim((string)$prize['img']) !== '';
                                      $hasEmoji = isset($prize['emoji']) && trim((string)$prize['emoji']) !== '';
                                      $alt = htmlspecialchars(isset($prize['name']) ? $prize['name'] : 'prize', ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <?php if ($hasImg): ?>
                                      <img src="<?php echo htmlspecialchars($prize['img'], ENT_QUOTES, 'UTF-8'); ?>" width="56" height="56" alt="<?php echo $alt; ?>" style="display:block;border:0;outline:none;text-decoration:none;border-radius:8px;background:#ffffff;" />
                                    <?php elseif ($hasEmoji): ?>
                                      <span role="img" aria-label="<?php echo $alt; ?>" title="<?php echo $alt; ?>" style="display:block;width:56px;height:56px;line-height:56px;font-size:32px;text-align:center;border-radius:8px;background:#ffffff;"><?php echo htmlspecialchars($prize['emoji'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php else: ?>
                                      <span role="img" aria-label="<?php echo $alt; ?>" title="<?php echo $alt; ?>" style="display:block;width:56px;height:56px;line-height:56px;font-size:28px;text-align:center;border-radius:8px;background:#ffffff;">🎁</span>
                                    <?php endif; ?>
                                  </td>
                                </tr>
                                <tr>
                                  <td align="center" style="padding:0 8px 8px 8px;">
                                    <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:14px;color:#b34a7f;white-space:nowrap;margin-bottom:2px;">
                                      <?php echo $alt; ?>
                                    </div>
                                    <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:14px;color:#ff2b83;white-space:nowrap;">
                                      <?php echo (int)$prize['cost']; ?> kisses
                                    </div>
                                  </td>
                                </tr>
                              </table>
                            </td>
                          <?php endforeach; ?>
                        </tr>
                      </table>
                      <?php else: ?>
                      <!-- Web preview: allow horizontal scroll if needed -->
                      <div class="prizeScroller" style="white-space:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;">
                        <?php foreach ($kissPrizes as $prize): ?>
                          <span class="prizeCard" style="display:inline-block;vertical-align:top;margin-right:8px;background:#ffe3f0;border:1px solid #ffb6d0;border-radius:12px;padding:8px;">
                            <?php
                              $hasImg = isset($prize['img']) && trim((string)$prize['img']) !== '';
                              $hasEmoji = isset($prize['emoji']) && trim((string)$prize['emoji']) !== '';
                              $alt = htmlspecialchars(isset($prize['name']) ? $prize['name'] : 'prize', ENT_QUOTES, 'UTF-8');
                            ?>
                            <?php if ($hasImg): ?>
                              <img class="prizeImg" src="<?php echo htmlspecialchars($prize['img'], ENT_QUOTES, 'UTF-8'); ?>" width="56" height="56" alt="<?php echo $alt; ?>" style="display:block;border:0;outline:none;text-decoration:none;border-radius:8px;background:#ffffff;" />
                            <?php elseif ($hasEmoji): ?>
                              <span role="img" aria-label="<?php echo $alt; ?>" title="<?php echo $alt; ?>" style="display:block;width:56px;height:56px;line-height:56px;font-size:32px;text-align:center;border-radius:8px;background:#ffffff;"><?php echo htmlspecialchars($prize['emoji'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php else: ?>
                              <span role="img" aria-label="<?php echo $alt; ?>" title="<?php echo $alt; ?>" style="display:block;width:56px;height:56px;line-height:56px;font-size:28px;text-align:center;border-radius:8px;background:#ffffff;">🎁</span>
                            <?php endif; ?>
                            <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:14px;color:#b34a7f;margin-top:6px;text-align:center;white-space:nowrap;">
                              <?php echo $alt; ?>
                            </div>
                            <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:14px;color:#ff2b83;margin-top:2px;text-align:center;white-space:nowrap;">
                              <?php echo (int)$prize['cost']; ?> kisses
                            </div>
                          </span>
                        <?php endforeach; ?>
                      </div>
                      <?php endif; ?>
                    </td>
                  </tr>
                </table>
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
            const seq = compliments.concat(compliments).concat(compliments);
            tickerTrack.innerHTML = seq.map((t) => '<span class="tickerItem">' + t + '</span>').join('');
          };
          build();
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
      })();
    </script>
    <?php endif; ?>
  </body>
  </html>
