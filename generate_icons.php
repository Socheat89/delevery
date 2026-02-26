<?php
// Quick SVG-based icon generator for PWA assets
// Generates PNG icons at 192 and 512px

function generateSVGIcon($size)
{
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#6366f1"/>
      <stop offset="100%" style="stop-color:#22d3ee"/>
    </linearGradient>
  </defs>
  <rect width="{$size}" height="{$size}" rx="64" fill="url(#bg)"/>
  <text x="50%" y="58%" font-size="60%" text-anchor="middle" dominant-baseline="middle" font-family="Arial">🚚</text>
</svg>
SVG;
    return $svg;
}

if (!is_dir(__DIR__ . '/assets'))
    mkdir(__DIR__ . '/assets', 0755, true);

file_put_contents(__DIR__ . '/assets/icon-192.svg', generateSVGIcon(192));
file_put_contents(__DIR__ . '/assets/icon-512.svg', generateSVGIcon(512));

echo "Icons generated!";
