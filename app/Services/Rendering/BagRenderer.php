<?php

namespace App\Services\Rendering;

use App\Models\Colourway;
use App\Models\Product;
use Illuminate\Support\HtmlString;

/**
 * Draws the product illustrations in SVG, in code.
 *
 * Every item in the catalogue needs a picture in every colour it is stocked
 * in, which is hundreds of photographs nobody has taken yet. Drawing them from
 * the shape and the colourway means the catalogue is complete on day one and
 * stays consistent. When real photography arrives, replace the call site with
 * an <img> — nothing else in the application depends on this.
 *
 * The palette comes from the Colourway record, so adding a colour in the back
 * office is all it takes for it to appear on the storefront.
 */
class BagRenderer
{
    private int $sequence = 0;

    public const SHAPES = [
        'tote', 'boxy', 'jute', 'backpack', 'pouch',
        'wine', 'laundry', 'zip', 'nonwoven', 'shoe',
    ];

    /** Fallback when an item has no colourway on file. */
    private const FALLBACK = [
        'name' => 'Natural',
        'body' => '#E7DCC6',
        'dark' => '#D3C4A6',
        'light' => '#F2EBDB',
        'cord' => '#C9B893',
    ];

    /* -------------------------------------------------------------- Public */

    public function render(string $shape, ?Colourway $colourway = null, array $options = []): HtmlString
    {
        $palette = $this->palette($colourway);
        $id = $this->nextId();
        $shape = in_array($shape, self::SHAPES, true) ? $shape : 'tote';
        $textured = ($options['textured'] ?? false) || $shape === 'jute';

        $label = $options['alt'] ?? sprintf('%s bag', $palette['name']);

        $svg = sprintf(
            '<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="%s" focusable="false">%s%s</svg>',
            e($label),
            $this->defs($id, $palette, $textured),
            $this->shape($shape, $id, $palette, $textured)
        );

        return new HtmlString($svg);
    }

    public function forProduct(Product $product, ?Colourway $colourway = null, array $options = []): HtmlString
    {
        $colourway ??= $product->relationLoaded('colourways')
            ? $product->colourways->first()
            : $product->colourways()->first();

        return $this->render($product->shape, $colourway, $options + [
            'alt' => sprintf('%s — %s', $product->name, $colourway?->name ?? 'as shown'),
        ]);
    }

    public function figure(string $shape, ?Colourway $colourway = null, array $options = []): HtmlString
    {
        return new HtmlString(sprintf(
            '<div class="bag-img %s">%s</div>',
            e($options['class'] ?? ''),
            $this->render($shape, $colourway, $options)
        ));
    }

    /* ------------------------------------------------------------ Internals */

    private function palette(?Colourway $colourway): array
    {
        if (! $colourway) {
            return self::FALLBACK;
        }

        return [
            'name' => $colourway->name,
            'body' => $colourway->hex_body,
            'dark' => $colourway->hex_dark,
            'light' => $colourway->hex_light,
            'cord' => $colourway->hex_cord,
        ];
    }

    /** Gradient ids must be unique within a document, or bags share a fill. */
    private function nextId(): string
    {
        return 'b'.(++$this->sequence).substr(md5((string) mt_rand()), 0, 6);
    }

    private function defs(string $id, array $p, bool $textured): string
    {
        $texture = $textured
            ? sprintf(
                '<pattern id="%1$st" width="7" height="7" patternUnits="userSpaceOnUse">'.
                '<rect width="7" height="7" fill="%2$s"/>'.
                '<path d="M0 3.5h7M3.5 0v7" stroke="%3$s" stroke-width="1.1" opacity=".5"/>'.
                '</pattern>',
                $id, $p['body'], $p['dark']
            )
            : '';

        return sprintf(
            '<defs>'.
            '<linearGradient id="%1$sg" x1="0" y1="0" x2="1" y2="1">'.
            '<stop offset="0%%" stop-color="%2$s"/>'.
            '<stop offset="55%%" stop-color="%3$s"/>'.
            '<stop offset="100%%" stop-color="%4$s"/>'.
            '</linearGradient>'.
            '<linearGradient id="%1$ss" x1="0" y1="0" x2="0" y2="1">'.
            '<stop offset="0%%" stop-color="#000" stop-opacity=".16"/>'.
            '<stop offset="100%%" stop-color="#000" stop-opacity="0"/>'.
            '</linearGradient>%5$s</defs>',
            $id, $p['light'], $p['body'], $p['dark'], $texture
        );
    }

    private function shadow(): string
    {
        return '<ellipse cx="100" cy="186" rx="62" ry="7" fill="#14201A" opacity=".09"/>';
    }

    /**
     * The print area, drawn as a dashed outline rather than a logo. These are
     * blank goods: what the buyer needs to see is where their artwork goes.
     */
    private function imprint(array $p, float $x, float $y, float $scale): string
    {
        $colour = $this->isDark($p) ? 'rgba(255,255,255,.30)' : 'rgba(20,32,26,.17)';

        return sprintf(
            '<g transform="translate(%s,%s) scale(%s)">'.
            '<rect x="-27" y="-19" width="54" height="38" rx="3" fill="none" stroke="%s" '.
            'stroke-width="1.7" stroke-dasharray="6 5" stroke-linecap="round"/></g>',
            $x, $y, $scale, $colour
        );
    }

    private function isDark(array $p): bool
    {
        [$r, $g, $b] = sscanf($p['body'], '#%02x%02x%02x');

        return (($r * 299) + ($g * 587) + ($b * 114)) / 1000 < 140;
    }

    private function shape(string $shape, string $id, array $p, bool $textured): string
    {
        $fill = $textured ? "url(#{$id}t)" : "url(#{$id}g)";
        $sheen = "url(#{$id}s)";

        return match ($shape) {
            'boxy' => $this->boxy($p, $fill, $sheen),
            'jute' => $this->jute($p, $id, $sheen),
            'backpack' => $this->backpack($p, $fill, $sheen),
            'pouch' => $this->pouch($p, $fill, $sheen),
            'wine' => $this->wine($p, $fill, $sheen),
            'laundry' => $this->laundry($p, $fill, $sheen),
            'zip' => $this->zip($p, $fill, $sheen),
            'nonwoven' => $this->nonwoven($p, $id, $sheen),
            'shoe' => $this->shoe($p, $fill, $sheen),
            default => $this->tote($p, $fill, $sheen),
        };
    }

    /* --------------------------------------------------------------- Shapes */

    /** Classic shopping tote, long shoulder handles. */
    private function tote(array $p, string $fill, string $sheen): string
    {
        $body = 'M47 62 L153 62 L160 174 Q160.5 180 154 180 L46 180 Q39.5 180 40 174 Z';
        $handle = 'M76 66 C76 30 124 30 124 66';

        return $this->shadow()
            ."<path d=\"{$handle}\" fill=\"none\" stroke=\"{$p['dark']}\" stroke-width=\"7\" stroke-linecap=\"round\" opacity=\".65\" transform=\"translate(6,-5)\"/>"
            ."<path d=\"{$body}\" fill=\"{$fill}\"/>"
            ."<path d=\"M47 62 L153 62 L153.8 77 L46.2 77 Z\" fill=\"{$p['dark']}\" opacity=\".55\"/>"
            ."<path d=\"{$body}\" fill=\"{$sheen}\"/>"
            ."<path d=\"M62 77 L59 180 M138 77 L141 180\" stroke=\"{$p['dark']}\" stroke-width=\"1.4\" opacity=\".35\" fill=\"none\"/>"
            .$this->imprint($p, 100, 118, 1.25)
            ."<path d=\"{$handle}\" fill=\"none\" stroke=\"{$p['light']}\" stroke-width=\"7.5\" stroke-linecap=\"round\"/>"
            ."<path d=\"{$handle}\" fill=\"none\" stroke=\"rgba(0,0,0,.12)\" stroke-width=\"2\" stroke-linecap=\"round\" transform=\"translate(0,2)\"/>";
    }

    /** Boxy gusseted canvas tote, short handles. */
    private function boxy(array $p, string $fill, string $sheen): string
    {
        $body = 'M44 64 L156 64 L156 172 Q156 178 150 178 L50 178 Q44 178 44 172 Z';
        $handle = 'M78 68 C78 42 122 42 122 68';

        return $this->shadow()
            ."<path d=\"{$handle}\" fill=\"none\" stroke=\"{$p['dark']}\" stroke-width=\"6.5\" stroke-linecap=\"round\" opacity=\".6\" transform=\"translate(5,-4)\"/>"
            ."<path d=\"{$body}\" fill=\"{$fill}\"/>"
            ."<path d=\"M44 64 L156 64 L156 78 L44 78 Z\" fill=\"{$p['dark']}\" opacity=\".5\"/>"
            ."<path d=\"M156 64 L172 76 L172 172 Q172 178 166 178 L150 178 Q156 178 156 172 Z\" fill=\"{$p['dark']}\" opacity=\".85\"/>"
            ."<path d=\"{$body}\" fill=\"{$sheen}\"/>"
            .$this->imprint($p, 100, 120, 1.2)
            ."<path d=\"{$handle}\" fill=\"none\" stroke=\"{$p['light']}\" stroke-width=\"7\" stroke-linecap=\"round\"/>";
    }

    /** Jute / burlap square bag with a contrast print panel. */
    private function jute(array $p, string $id, string $sheen): string
    {
        $body = 'M46 66 L154 66 L154 174 Q154 179 149 179 L51 179 Q46 179 46 174 Z';
        $handle = 'M74 70 C74 40 126 40 126 70';

        return $this->shadow()
            ."<path d=\"{$handle}\" fill=\"none\" stroke=\"{$p['dark']}\" stroke-width=\"7\" stroke-linecap=\"round\" opacity=\".6\" transform=\"translate(6,-4)\"/>"
            ."<path d=\"{$body}\" fill=\"url(#{$id}t)\"/>"
            ."<path d=\"M46 66 L154 66 L154 80 L46 80 Z\" fill=\"{$p['dark']}\" opacity=\".6\"/>"
            .'<rect x="66" y="96" width="68" height="58" rx="2" fill="#F4EFE3" opacity=".88"/>'
            .$this->imprint(['body' => '#F4EFE3'], 100, 116, 1.1)
            ."<path d=\"{$body}\" fill=\"{$sheen}\"/>"
            ."<path d=\"{$handle}\" fill=\"none\" stroke=\"{$p['light']}\" stroke-width=\"7.5\" stroke-linecap=\"round\"/>";
    }

    /** Drawstring backpack (cinch pack). */
    private function backpack(array $p, string $fill, string $sheen): string
    {
        $body = 'M58 62 Q58 52 70 50 L130 50 Q142 52 142 62 L152 158 Q154 178 134 178 L66 178 Q46 178 48 158 Z';

        return $this->shadow()
            ."<path d=\"M56 56 C40 118 52 172 56 176 M144 56 C160 118 148 172 144 176\" fill=\"none\" stroke=\"{$p['cord']}\" stroke-width=\"3.2\" stroke-linecap=\"round\"/>"
            ."<path d=\"{$body}\" fill=\"{$fill}\"/>"
            ."<path d=\"M58 62 Q58 52 70 50 L130 50 Q142 52 142 62 L143 72 L57 72 Z\" fill=\"{$p['dark']}\" opacity=\".55\"/>"
            ."<path d=\"M66 52 q8 8 0 16 M82 50 q8 9 0 18 M100 50 q8 9 0 18 M118 50 q8 9 0 18 M134 52 q6 8 0 16\" stroke=\"{$p['dark']}\" stroke-width=\"1.6\" fill=\"none\" opacity=\".5\"/>"
            ."<path d=\"{$body}\" fill=\"{$sheen}\"/>"
            .$this->imprint($p, 100, 122, 1.2)
            ."<circle cx=\"58\" cy=\"172\" r=\"7\" fill=\"{$p['dark']}\"/><circle cx=\"142\" cy=\"172\" r=\"7\" fill=\"{$p['dark']}\"/>";
    }

    /** Small drawstring pouch. */
    private function pouch(array $p, string $fill, string $sheen): string
    {
        $body = 'M62 72 Q62 62 74 60 L126 60 Q138 62 138 72 L144 158 Q146 176 128 176 L72 176 Q54 176 56 158 Z';

        return $this->shadow()
            ."<path d=\"{$body}\" fill=\"{$fill}\"/>"
            ."<path d=\"M62 72 Q62 62 74 60 L126 60 Q138 62 138 72 L139 80 L61 80 Z\" fill=\"{$p['dark']}\" opacity=\".55\"/>"
            ."<path d=\"M70 62 q7 8 0 16 M86 60 q7 9 0 18 M100 60 q7 9 0 18 M114 60 q7 9 0 18 M130 62 q6 8 0 16\" stroke=\"{$p['dark']}\" stroke-width=\"1.5\" fill=\"none\" opacity=\".5\"/>"
            ."<path d=\"M62 66 C38 62 36 50 52 48 M138 66 C162 62 164 50 148 48\" fill=\"none\" stroke=\"{$p['cord']}\" stroke-width=\"3.4\" stroke-linecap=\"round\"/>"
            ."<path d=\"{$body}\" fill=\"{$sheen}\"/>"
            .$this->imprint($p, 100, 122, 1.1);
    }

    /** Wine / bottle bag — tall, narrow, divided. */
    private function wine(array $p, string $fill, string $sheen): string
    {
        $body = 'M66 48 L134 48 L138 174 Q138 179 133 179 L67 179 Q62 179 62 174 Z';
        $handle = 'M84 52 C84 30 116 30 116 52';

        return $this->shadow()
            ."<path d=\"{$handle}\" fill=\"none\" stroke=\"{$p['dark']}\" stroke-width=\"6\" stroke-linecap=\"round\" opacity=\".6\" transform=\"translate(4,-3)\"/>"
            ."<path d=\"{$body}\" fill=\"{$fill}\"/>"
            ."<path d=\"M66 48 L134 48 L134.6 62 L65.4 62 Z\" fill=\"{$p['dark']}\" opacity=\".55\"/>"
            ."<path d=\"M100 62 L100 179\" stroke=\"{$p['dark']}\" stroke-width=\"1.6\" opacity=\".4\"/>"
            ."<path d=\"{$body}\" fill=\"{$sheen}\"/>"
            .$this->imprint($p, 100, 118, .95)
            ."<path d=\"{$handle}\" fill=\"none\" stroke=\"{$p['light']}\" stroke-width=\"6.5\" stroke-linecap=\"round\"/>";
    }

    /** Laundry / large cinch sack. */
    private function laundry(array $p, string $fill, string $sheen): string
    {
        $body = 'M56 74 Q54 60 68 56 L132 56 Q146 60 144 74 L154 160 Q157 179 136 179 L64 179 Q43 179 46 160 Z';

        return $this->shadow()
            ."<path d=\"{$body}\" fill=\"{$fill}\"/>"
            ."<path d=\"M56 74 Q54 60 68 56 L132 56 Q146 60 144 74 L145 84 L55 84 Z\" fill=\"{$p['dark']}\" opacity=\".55\"/>"
            ."<path d=\"M64 58 q8 10 0 22 M82 56 q8 12 0 24 M100 56 q8 12 0 24 M118 56 q8 12 0 24 M136 58 q7 10 0 22\" stroke=\"{$p['dark']}\" stroke-width=\"1.6\" fill=\"none\" opacity=\".45\"/>"
            ."<path d=\"M56 68 C34 62 32 48 50 44 M144 68 C166 62 168 48 150 44\" fill=\"none\" stroke=\"{$p['cord']}\" stroke-width=\"3.6\" stroke-linecap=\"round\"/>"
            ."<path d=\"{$body}\" fill=\"{$sheen}\"/>"
            .$this->imprint($p, 100, 124, 1.3);
    }

    /** Zippered flat pouch / cosmetic bag. */
    private function zip(array $p, string $fill, string $sheen): string
    {
        return $this->shadow()
            ."<rect x=\"38\" y=\"72\" width=\"124\" height=\"94\" rx=\"11\" fill=\"{$fill}\"/>"
            ."<rect x=\"38\" y=\"72\" width=\"124\" height=\"16\" rx=\"8\" fill=\"{$p['dark']}\" opacity=\".6\"/>"
            ."<path d=\"M44 80 H156\" stroke=\"{$p['light']}\" stroke-width=\"2.6\" stroke-linecap=\"round\" opacity=\".85\"/>"
            ."<path d=\"M46 80 h110\" stroke=\"{$p['dark']}\" stroke-width=\"5\" stroke-linecap=\"round\" stroke-dasharray=\"1.6 3.4\" opacity=\".55\"/>"
            ."<rect x=\"150\" y=\"74\" width=\"13\" height=\"13\" rx=\"3.5\" fill=\"{$p['light']}\"/>"
            ."<path d=\"M156 87 v13 a5 5 0 0 0 5 5 h4\" stroke=\"{$p['dark']}\" stroke-width=\"2.6\" fill=\"none\" stroke-linecap=\"round\"/>"
            ."<rect x=\"38\" y=\"72\" width=\"124\" height=\"94\" rx=\"11\" fill=\"{$sheen}\"/>"
            .$this->imprint($p, 100, 122, 1.15);
    }

    /** Non-woven grocery tote — short handles, square base. */
    private function nonwoven(array $p, string $id, string $sheen): string
    {
        $body = 'M50 62 L150 62 L150 176 L50 176 Z';
        $handle = 'M80 66 C80 46 120 46 120 66';

        return $this->shadow()
            ."<path d=\"{$handle}\" fill=\"none\" stroke=\"{$p['dark']}\" stroke-width=\"6\" stroke-linecap=\"round\" opacity=\".6\" transform=\"translate(5,-3)\"/>"
            ."<path d=\"{$body}\" fill=\"url(#{$id}g)\"/>"
            ."<path d=\"M50 62 L150 62 L150 74 L50 74 Z\" fill=\"{$p['dark']}\" opacity=\".5\"/>"
            ."<path d=\"M150 62 L166 72 L166 176 L150 176 Z\" fill=\"{$p['dark']}\" opacity=\".85\"/>"
            ."<path d=\"M50 176 L150 176 L166 176\" stroke=\"{$p['dark']}\" stroke-width=\"2\" opacity=\".5\" fill=\"none\"/>"
            ."<path d=\"{$body}\" fill=\"{$sheen}\"/>"
            .$this->imprint($p, 100, 120, 1.2)
            ."<path d=\"{$handle}\" fill=\"none\" stroke=\"{$p['light']}\" stroke-width=\"6.5\" stroke-linecap=\"round\"/>";
    }

    /** Shoe bag — medium drawstring, landscape. */
    private function shoe(array $p, string $fill, string $sheen): string
    {
        $body = 'M46 82 Q44 70 58 67 L142 67 Q156 70 154 82 L158 156 Q160 174 142 174 L58 174 Q40 174 42 156 Z';

        return $this->shadow()
            ."<path d=\"{$body}\" fill=\"{$fill}\"/>"
            ."<path d=\"M46 82 Q44 70 58 67 L142 67 Q156 70 154 82 L155 92 L45 92 Z\" fill=\"{$p['dark']}\" opacity=\".55\"/>"
            ."<path d=\"M60 68 q7 12 0 24 M80 67 q7 13 0 25 M100 67 q7 13 0 25 M120 67 q7 13 0 25 M140 68 q6 12 0 24\" stroke=\"{$p['dark']}\" stroke-width=\"1.5\" fill=\"none\" opacity=\".45\"/>"
            ."<path d=\"M46 76 C28 70 28 58 44 56 M154 76 C172 70 172 58 156 56\" fill=\"none\" stroke=\"{$p['cord']}\" stroke-width=\"3.4\" stroke-linecap=\"round\"/>"
            ."<path d=\"{$body}\" fill=\"{$sheen}\"/>"
            .$this->imprint($p, 100, 126, 1.15);
    }
}
