<?php
  // Change this score value; the UI will adjust automatically
  $score = 7; // example
  $just_increased = false; // set to true to show a celebratory score-up animation

  // Ensure non-negative, since points start at 0
  if (!is_int($score)) { $score = (int)$score; }
  if ($score < 0) { $score = 0; }

  $minPoint = $score;
  $maxPoint = $score + 20;
  $prevPoint = max(0, $score - 1);

  // Simple helpers
  function hasKiss($p) {
    return ($p > 0) && ($p % 4 === 0);
  }
  function hasTrip($p) {
    return ($p > 0) && ($p % 10 === 0);
  }
  function hasExperience($p) {
    return ($p > 0) && ($p % 17 === 0);
  }
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sweet Progress</title>
  </head>
  <body style="margin:0;padding:0;background:#ffecf7;">
    <style>
      /* Email clients support varies; these animations gracefully fail where unsupported */
      @keyframes popIn { 0% { transform: scale(0.7); opacity: 0.2; } 60% { transform: scale(1.06); opacity: 1; } 100% { transform: scale(1); } }
      @keyframes bounceSoft { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-2px); } }
      @keyframes pulseGlow { 0% { transform: scale(0.92); } 50% { transform: scale(1.06); } 100% { transform: scale(0.92); } }
      @keyframes twinkle { 0%,100% { opacity: 1; } 50% { opacity: 0.75; } }
      @keyframes floatUp { 0% { transform: translateY(8px); opacity: 0.0; } 50% { transform: translateY(-2px); opacity: 1; } 100% { transform: translateY(-10px); opacity: 0; } }
    </style>
    <!-- Outer wrapper table (email-safe) -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffecf7;">
      <tr>
        <td align="center" style="padding:20px 12px;">
          <!-- Card container -->
          <table role="presentation" width="360" cellpadding="0" cellspacing="0" border="0" style="width:360px;max-width:94%;background:#ffffff;border-radius:16px;border:2px solid #ffc5dd;">
            <tr>
              <td align="center" style="padding:20px 20px 8px 20px;">
                <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:22px;line-height:28px;color:#ff2b83;font-weight:bold;animation:popIn 600ms ease-out both;">
                  Your Sweet Progress 💖
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
                    $kiss = hasKiss($p);
                    $trip = hasTrip($p);
                    $exp = hasExperience($p);
                    $hasLabels = ($trip || $exp);
                    $rowBg = $isCurrent ? '#fff3f9' : '#ffffff';
                  ?>
                  <tr>
                    <!-- Value column -->
                    <td width="64" valign="middle" style="width:64px;padding:8px 6px 8px 6px;background:<?php echo $rowBg; ?>;border-left:<?php echo $isCurrent ? '4px solid #ff86b8' : '4px solid #ffffff'; ?>;">
                      <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:16px;color:#b34a7f;<?php echo $isCurrent ? 'font-weight:bold;color:#ff2b83;' : ''; ?>">
                        <?php echo $p; ?> pts
                      </div>
                    </td>

                    <!-- Line + dot column (continuous pink line as background) -->
                    <td width="24" valign="middle" align="center" style="width:24px;background:#ffd9ea;">
                      <?php if ($kiss): ?>
                        <span style="display:inline-block;font-size:16px;line-height:16px;margin:6px 0;animation:bounceSoft 2s ease-in-out infinite;">💋</span>
                      <?php else: ?>
                        <span style="display:inline-block;width:12px;height:12px;background:#ff4da6;border-radius:999px;border:2px solid #ffffff;box-shadow:0 0 0 2px #ff97c2;margin:6px 0;<?php echo $isCurrent ? 'animation:pulseGlow 1.8s ease-in-out infinite;' : ''; ?>"></span>
                      <?php endif; ?>
                      <?php if ($just_increased && $p === $prevPoint): ?>
                        <span style="display:inline-block;font-size:14px;line-height:14px;color:#ff2b83;margin:0;animation:floatUp 1.6s ease-out 1 both;">💗</span>
                      <?php endif; ?>
                    </td>

                    <!-- Labels column -->
                    <td valign="middle" style="padding:8px 8px 8px 8px;background:<?php echo $rowBg; ?>;">
                      <?php if ($isCurrent): ?>
                        <span style="display:inline-block;background:#ffe3f0;border:1px solid #ffb6d0;color:#ff2b83;border-radius:999px;padding:4px 10px;font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:14px;margin-right:6px;animation:popIn 500ms ease-out both;">You are here ✨</span>
                      <?php endif; ?>

                      <?php if ($trip): ?>
                        <span style="display:inline-block;background:#ffe3f0;border:1px solid #ffb6d0;color:#ff2b83;border-radius:999px;padding:4px 10px;font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:14px;margin-right:6px;animation:twinkle 3s ease-in-out infinite;">trip</span>
                      <?php endif; ?>
                      <?php if ($exp): ?>
                        <span style="display:inline-block;background:#ffe3f0;border:1px solid #ffb6d0;color:#ff2b83;border-radius:999px;padding:4px 10px;font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:14px;margin-right:6px;animation:twinkle 3s ease-in-out infinite;">experience</span>
                      <?php endif; ?>

                      <?php if (!$isCurrent && !$hasLabels): ?>
                        <span style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:16px;color:#cc6a9a;">Keep going, cutie! 💗</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endfor; ?>
                </table>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:0 20px 20px 20px;">
                <div style="font-family:Arial, 'Helvetica Neue', Helvetica, sans-serif;font-size:12px;line-height:16px;color:#b34a7f;">
                  Reaching for the stars looks good on you ⭐💗
                </div>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
  </html>

