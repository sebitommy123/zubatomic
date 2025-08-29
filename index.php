<?php
  // Change these values; the UI will adjust automatically
  $score = 6; //example
  $just_increased = false; // set true to show a celebratory score-up animation

  // Email-friendly mode: disables animations and adds legacy attrs like bgcolor
  $email_mode = false;
  
  // Auto-enable email-friendly mode when a "book" is set
  $book_param = null;
  if (isset($_GET['book'])) { $book_param = $_GET['book']; }
  elseif (isset($_POST['book'])) { $book_param = $_POST['book']; }
  $book_is_set = !is_null($book_param) && trim((string)$book_param) !== '';
  if ($book_is_set) { $email_mode = true; }

  // Current streak (hardcoded): number of days without any beauty point loss
  // This is display-only; no logic is implemented here.
  $streakDays = 3; // example

  // Second metric: kisses (hardcoded, manually incremented when hitting kiss milestones)
  $kisses = 1; // example
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
      
      :root { --pink-1:#ffecf7; --pink-2:#ffd9ea; --pink-3:#ffc5dd; --pink-4:#ff97c2; --pink-5:#ff4da6; --magenta:#ff2b83; }
      html, body { height: 100%; }
      .bg-animated { position: fixed; inset: 0; background: radial-gradient(1200px 800px at 10% 10%, rgba(255,77,166,0.12), transparent 60%), radial-gradient(900px 700px at 90% 30%, rgba(255,151,194,0.18), transparent 60%), radial-gradient(1000px 800px at 30% 90%, rgba(255,197,221,0.16), transparent 60%); pointer-events:none; z-index:0; }
      .bg-floaters { position: fixed; inset: 0; overflow: hidden; pointer-events: none; z-index: 1; }
      .floater { position: absolute; font-size: 18px; opacity: 0.8; will-change: transform, opacity; animation: floaterDrift linear infinite, floaterBob ease-in-out infinite; filter: drop-shadow(0 4px 8px rgba(255,43,131,0.15)); }
      @keyframes floaterDrift { 0% { transform: translateY(110vh) translateX(0) rotate(0deg); opacity: 0; } 10% { opacity: 0.6; } 90% { opacity: 0.9; } 100% { transform: translateY(-20vh) translateX(0) rotate(360deg); opacity: 0; } }
      @keyframes floaterBob { 0%,100% { transform: translateX(-8px); } 50% { transform: translateX(8px); } }
      
      .cardShell { position: relative; z-index: 2; display: inline-block; border-radius: 20px; padding: 6px; background: linear-gradient(135deg, rgba(255,77,166,0.45), rgba(255,227,240,0.7)); box-shadow: 0 12px 30px rgba(255,43,131,0.18), 0 6px 16px rgba(179,74,127,0.12); transition: transform 300ms ease, box-shadow 300ms ease; }
      .cardShell:hover { transform: translateY(-2px) scale(1.01); box-shadow: 0 18px 40px rgba(255,43,131,0.24), 0 8px 22px rgba(179,74,127,0.16); }
      .cardShell::before { content:""; position:absolute; inset: 0; padding: 2px; border-radius: 22px; background: conic-gradient(from 0deg, #fff3f9, #ffd9ea, #ff97c2, #ff4da6, #ffd9ea, #fff3f9); -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0); -webkit-mask-composite: xor; mask-composite: exclude; animation: spinBorder 6s linear infinite; pointer-events:none; }
      @keyframes spinBorder { to { transform: rotate(1turn); } }
      
      .shineText { background: linear-gradient(90deg, #ff2b83, #ff97c2, #ff2b83); -webkit-background-clip: text; background-clip: text; color: transparent; animation: shimmer 3s ease-in-out infinite; }
      @keyframes shimmer { 0% { filter: drop-shadow(0 0 0 rgba(255,43,131,0)); } 50% { filter: drop-shadow(0 0 10px rgba(255,43,131,0.35)); } 100% { filter: drop-shadow(0 0 0 rgba(255,43,131,0)); } }
      
      tr[data-current="1"] td { position: relative; }
      tr[data-current="1"] td:first-child::after { content:""; position:absolute; right:-3px; top:50%; transform: translateY(-50%); width:6px; height:24px; border-radius:4px; background: linear-gradient(180deg, #ff97c2, #ff4da6); box-shadow: 0 0 10px rgba(255,77,166,0.35); }
      
      .prizeCard { transition: transform 200ms ease, box-shadow 200ms ease; box-shadow: 0 8px 18px rgba(255,43,131,0.12); }
      .prizeCard:hover { transform: translateY(-3px) rotate3d(1, -1, 0, 6deg) scale(1.04); box-shadow: 0 14px 28px rgba(255,43,131,0.22); }
      .prizeCard::after { content:""; position:absolute; inset:0; pointer-events:none; background: radial-gradient(600px 200px at var(--mx,50%) -10%, rgba(255,255,255,0.65), transparent 60%); opacity: 0; transition: opacity 200ms ease; border-radius:12px; }
      .prizeCard:hover::after { opacity: 1; }
      
      .cursorSparkle { position: fixed; width:8px; height:8px; border-radius:50%; background: radial-gradient(circle at 30% 30%, #fff, #ff97c2 45%, rgba(255,43,131,0.0) 70%); pointer-events: none; will-change: transform, opacity; animation: sparkleFade 900ms ease-out forwards; z-index: 3; filter: drop-shadow(0 0 6px rgba(255,43,131,0.45)); }
      @keyframes sparkleFade { 0% { transform: translate(-50%, -50%) scale(1); opacity: 1; } 100% { transform: translate(-50%, -80%) scale(0.4); opacity: 0; } }
      
      .confetti { position: fixed; width:8px; height:14px; background: var(--c, #ff2b83); top:-10px; left:0; border-radius:2px; transform: translateZ(0); will-change: transform, opacity; animation: confettiFall var(--d, 2.8s) cubic-bezier(.2,.7,.2,1) forwards; z-index: 3; }
      @keyframes confettiFall { 0% { transform: translateY(-10px) translateX(0) rotate(0deg); opacity: 1; } 100% { transform: translateY(110vh) translateX(var(--x, 0)) rotate(720deg); opacity: 0; } }
    </style>
    <?php endif; ?>
    <?php if (!$email_mode): ?>
    <div class="bg-animated" aria-hidden="true"></div>
    <div class="bg-floaters" aria-hidden="true" id="bg-floaters"></div>
    <?php endif; ?>
    <!-- Outer wrapper table (email-safe) -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffecf7;" bgcolor="#ffecf7">
      <tr>
        <td align="center" style="padding:20px 12px;">
          <!-- Card container -->
          <?php if (!$email_mode): ?><div class="cardShell"><?php endif; ?>
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
                  <span class="shineText">Your Sweet Progress 💖</span>
                </div>
                <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:13px;line-height:18px;color:#b34a7f;margin-top:6px;">
                  Every step is a sparkle closer ✨
                </div>
              </td>
            </tr>
            <tr>
              <td style="padding:8px 12px 18px 12px;">
                <!-- Progress list table: three columns (value | line+dot | labels) -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;border-spacing:0;">
                  <?php for ($p = $maxPoint; $p >= $minPoint; $p--) :
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
                  <tr<?php if ($isCurrent) { echo ' data-current="1"'; } ?> data-point="<?php echo $p; ?>">
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
                        <span style="display:inline-block;width:12px;height:12px;background:#ff4da6;border-radius:999px;border:2px solid #ffffff;box-shadow:0 0 0 2px #ff97c2;margin:6px 0;<?php echo ($isCurrent && !$email_mode) ? 'animation:pulseGlow 1.8s ease-in-out infinite;' : ''; ?>"></span>
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
                                <span style="display:block;background:#ffe3f0;border:1px solid #ffb6d0;color:#ff2b83;border-radius:12px;padding:6px 10px;font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:14px;word-break:break-word;<?php if (!$email_mode) { echo 'animation:twinkle 3s ease-in-out infinite;'; } ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
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
                      <div style="white-space:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;">
                        <?php foreach ($kissPrizes as $prize): ?>
                          <span class="prizeCard" style="display:inline-block;position:relative;vertical-align:top;margin-right:8px;background:#ffe3f0;border:1px solid #ffb6d0;border-radius:12px;padding:8px;">
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
        const justIncreased = <?php echo $just_increased ? 'true' : 'false'; ?>;
        const floatersRoot = document.getElementById('bg-floaters');
        if (floatersRoot) {
          const emojis = ['💗','✨','💖','🌸','💎','🫶','🎀','🩷'];
          const makeFloater = function(delay){
            const el = document.createElement('span');
            el.className = 'floater';
            el.textContent = emojis[Math.floor(Math.random()*emojis.length)];
            const left = Math.random() * 100;
            const duration = 7 + Math.random() * 10;
            const bobOffset = Math.random() * 2;
            el.style.left = left + 'vw';
            el.style.animationDuration = duration + 's, ' + (3 + bobOffset) + 's';
            el.style.animationDelay = (delay || Math.random()*-duration) + 's, 0s';
            floatersRoot.appendChild(el);
            setTimeout(()=>{ if (el.parentNode) el.parentNode.removeChild(el); }, (duration+1)*1000);
          };
          for (let i=0;i<18;i++) makeFloater(-Math.random()*12);
          setInterval(()=> makeFloater(), 900);
        }
        
        // Cursor sparkles
        window.addEventListener('mousemove', function(ev){
          for (let i=0;i<2;i++) {
            const s = document.createElement('span');
            s.className = 'cursorSparkle';
            const ox = (Math.random()-0.5)*16;
            const oy = (Math.random()-0.5)*16;
            s.style.left = (ev.clientX + ox) + 'px';
            s.style.top = (ev.clientY + oy) + 'px';
            document.body.appendChild(s);
            setTimeout(()=>{ if (s.parentNode) s.parentNode.removeChild(s); }, 900);
          }
        }, { passive: true });
        
        // Card parallax tilt
        const shell = document.querySelector('.cardShell');
        if (shell) {
          shell.addEventListener('mousemove', function(e){
            const r = shell.getBoundingClientRect();
            const cx = (e.clientX - r.left) / r.width - 0.5;
            const cy = (e.clientY - r.top) / r.height - 0.5;
            const rx = (cy * -10).toFixed(2);
            const ry = (cx * 10).toFixed(2);
            shell.style.transform = 'perspective(800px) rotateX(' + rx + 'deg) rotateY(' + ry + 'deg) translateY(-2px)';
          });
          shell.addEventListener('mouseleave', function(){
            shell.style.transform = '';
          });
        }
        
        // Prize hover shine follows mouse
        const prizeCards = document.querySelectorAll('.prizeCard');
        prizeCards.forEach(function(card){
          card.addEventListener('mousemove', function(e){
            const r = card.getBoundingClientRect();
            const mx = ((e.clientX - r.left) / r.width) * 100;
            card.style.setProperty('--mx', mx + '%');
          });
        });
        
        // Confetti burst helper
        function burstConfetti(count){
          const colors = ['#ff2b83','#ff97c2','#ffc5dd','#ffd9ea','#fff3f9'];
          for (let i=0;i<count;i++){
            const c = document.createElement('div');
            c.className = 'confetti';
            c.style.left = (Math.random()*100) + 'vw';
            c.style.background = colors[(Math.random()*colors.length)|0];
            c.style.setProperty('--x', (Math.random()*200 - 100) + 'px');
            c.style.setProperty('--d', (2 + Math.random()*2.2) + 's');
            document.body.appendChild(c);
            setTimeout(()=>{ if (c.parentNode) c.parentNode.removeChild(c); }, 4500);
          }
        }
        if (justIncreased) {
          burstConfetti(80);
        } else {
          // Periodic celebration to keep motion
          setInterval(()=> burstConfetti(12), 5500);
        }
      })();
    </script>
    <?php endif; ?>
  </body>
  </html>
