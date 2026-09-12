/* ==========================================================================
   TBM — Bag illustration engine
   Generates clean, consistent SVG product illustrations in code.
   Swap for real photography later: replace bagSVG() output with an <img>.
   ========================================================================== */
(function (w) {
  'use strict';

  var uid = 0;
  function nid() { return 'b' + (++uid) + (Math.random() * 1e6 | 0); }

  /* ---- Colorways ------------------------------------------------------- */
  var COLORS = {
    natural:   { name: 'Natural',        body: '#E7DCC6', dark: '#D3C4A6', light: '#F2EBDB', cord: '#C9B893' },
    bone:      { name: 'Bone',           body: '#EFEAE0', dark: '#DCD5C7', light: '#F8F5EF', cord: '#CFC7B6' },
    black:     { name: 'Black',          body: '#2A2B28', dark: '#1B1C1A', light: '#3D3E3A', cord: '#4A4B46' },
    navy:      { name: 'Navy',           body: '#22344F', dark: '#182639', light: '#31476A', cord: '#3D5578' },
    forest:    { name: 'Forest',         body: '#2C4A38', dark: '#1F3528', light: '#3B6249', cord: '#487058' },
    olive:     { name: 'Olive',          body: '#6B6B45', dark: '#545437', light: '#83835A', cord: '#8D8D63' },
    sage:      { name: 'Sage',           body: '#A8B5A0', dark: '#8E9C87', light: '#C0CBB9', cord: '#94A28C' },
    kraft:     { name: 'Kraft',          body: '#C89A63', dark: '#AE8350', light: '#DCB484', cord: '#B68A57' },
    rust:      { name: 'Rust',           body: '#A95C3C', dark: '#8C4A2F', light: '#C2765450'.slice(0,7), cord: '#B96B4A' },
    burgundy:  { name: 'Burgundy',       body: '#6E2A38', dark: '#551F2B', light: '#8A3A4A', cord: '#7E3442' },
    royal:     { name: 'Royal Blue',     body: '#2B4FA2', dark: '#1F3C80', light: '#3D63BC', cord: '#3A5CB4' },
    red:       { name: 'Red',            body: '#B5352E', dark: '#932722', light: '#CB4B43', cord: '#C33E36' },
    grey:      { name: 'Heather Grey',   body: '#B4B2AC', dark: '#9C9A94', light: '#CAC8C2', cord: '#A5A39D' },
    denim:     { name: 'Washed Denim',   body: '#6E88A8', dark: '#59718E', light: '#8AA2BE', cord: '#63799A' },
    jute:      { name: 'Raw Jute',       body: '#CBA96F', dark: '#B18F57', light: '#DDC08B', cord: '#AD8C56' },
    white:     { name: 'White',          body: '#F7F6F2', dark: '#E3E1DA', light: '#FFFFFF', cord: '#DAD8D0' },
    blush:     { name: 'Blush',          body: '#E0BFB4', dark: '#C8A398', light: '#EFD5CC', cord: '#CFAA9F' },
    teal:      { name: 'Teal',           body: '#2E6B6B', dark: '#21504F', light: '#3F8785', cord: '#387C7B' }
  };
  COLORS.rust.light = '#C27654';

  function c(key) { return COLORS[key] || COLORS.natural; }

  /* ---- Shared pieces --------------------------------------------------- */
  function defs(id, p, textured) {
    var tex = '';
    if (textured) {
      tex =
        '<pattern id="' + id + 't" width="7" height="7" patternUnits="userSpaceOnUse">' +
          '<rect width="7" height="7" fill="' + p.body + '"/>' +
          '<path d="M0 3.5h7M3.5 0v7" stroke="' + p.dark + '" stroke-width="1.1" opacity=".5"/>' +
        '</pattern>';
    }
    return '<defs>' +
      '<linearGradient id="' + id + 'g" x1="0" y1="0" x2="1" y2="1">' +
        '<stop offset="0%" stop-color="' + p.light + '"/>' +
        '<stop offset="55%" stop-color="' + p.body + '"/>' +
        '<stop offset="100%" stop-color="' + p.dark + '"/>' +
      '</linearGradient>' +
      '<linearGradient id="' + id + 's" x1="0" y1="0" x2="0" y2="1">' +
        '<stop offset="0%" stop-color="#000" stop-opacity=".16"/>' +
        '<stop offset="100%" stop-color="#000" stop-opacity="0"/>' +
      '</linearGradient>' +
      tex +
    '</defs>';
  }

  function shadow() {
    return '<ellipse cx="100" cy="186" rx="62" ry="7" fill="#14201A" opacity=".09"/>';
  }

  /* Ghosted imprint area on the bag face — these are blank goods, so the
     face shows the printable zone rather than a logo. */
  function imprint(p, x, y, scale, dark) {
    var col = dark ? 'rgba(255,255,255,.30)' : 'rgba(20,32,26,.17)';
    return '<g transform="translate(' + x + ',' + y + ') scale(' + scale + ')">' +
      '<rect x="-27" y="-19" width="54" height="38" rx="3" fill="none" stroke="' + col + '" ' +
        'stroke-width="1.7" stroke-dasharray="6 5" stroke-linecap="round"/>' +
    '</g>';
  }

  function isDark(p) {
    var h = p.body.replace('#', '');
    var r = parseInt(h.substr(0, 2), 16), g = parseInt(h.substr(2, 2), 16), b = parseInt(h.substr(4, 2), 16);
    return (r * 299 + g * 587 + b * 114) / 1000 < 140;
  }

  /* ---- Shapes ---------------------------------------------------------- */
  var SHAPES = {

    /* Classic shopping tote with long shoulder handles */
    tote: function (id, p, tex) {
      var f = tex ? 'url(#' + id + 't)' : 'url(#' + id + 'g)';
      return shadow() +
        // back handle
        '<path d="M76 66 C76 30 124 30 124 66" fill="none" stroke="' + p.dark + '" stroke-width="7" stroke-linecap="round" opacity=".65" transform="translate(6,-5)"/>' +
        // body
        '<path d="M47 62 L153 62 L160 174 Q160.5 180 154 180 L46 180 Q39.5 180 40 174 Z" fill="' + f + '"/>' +
        '<path d="M47 62 L153 62 L153.8 77 L46.2 77 Z" fill="' + p.dark + '" opacity=".55"/>' +
        '<path d="M47 62 L153 62 L160 174 Q160.5 180 154 180 L46 180 Q39.5 180 40 174 Z" fill="url(#' + id + 's)"/>' +
        // side seams
        '<path d="M62 77 L59 180 M138 77 L141 180" stroke="' + p.dark + '" stroke-width="1.4" opacity=".35" fill="none"/>' +
        imprint(p, 100, 118, 1.25, isDark(p)) +
        // front handle
        '<path d="M76 66 C76 30 124 30 124 66" fill="none" stroke="' + p.light + '" stroke-width="7.5" stroke-linecap="round"/>' +
        '<path d="M76 66 C76 30 124 30 124 66" fill="none" stroke="rgba(0,0,0,.12)" stroke-width="2" stroke-linecap="round" transform="translate(0,2)"/>';
    },

    /* Boxy gusseted canvas tote, short handles */
    boxy: function (id, p, tex) {
      var f = tex ? 'url(#' + id + 't)' : 'url(#' + id + 'g)';
      return shadow() +
        '<path d="M78 68 C78 42 122 42 122 68" fill="none" stroke="' + p.dark + '" stroke-width="6.5" stroke-linecap="round" opacity=".6" transform="translate(5,-4)"/>' +
        '<path d="M44 64 L156 64 L156 172 Q156 178 150 178 L50 178 Q44 178 44 172 Z" fill="' + f + '"/>' +
        '<path d="M44 64 L156 64 L156 78 L44 78 Z" fill="' + p.dark + '" opacity=".5"/>' +
        '<path d="M156 64 L172 76 L172 172 Q172 178 166 178 L150 178 Q156 178 156 172 Z" fill="' + p.dark + '" opacity=".85"/>' +
        '<path d="M44 64 L156 64 L156 172 Q156 178 150 178 L50 178 Q44 178 44 172 Z" fill="url(#' + id + 's)"/>' +
        imprint(p, 100, 120, 1.2, isDark(p)) +
        '<path d="M78 68 C78 42 122 42 122 68" fill="none" stroke="' + p.light + '" stroke-width="7" stroke-linecap="round"/>';
    },

    /* Jute / burlap square bag with contrast panel */
    jute: function (id, p) {
      return shadow() +
        '<path d="M74 70 C74 40 126 40 126 70" fill="none" stroke="' + p.dark + '" stroke-width="7" stroke-linecap="round" opacity=".6" transform="translate(6,-4)"/>' +
        '<path d="M46 66 L154 66 L154 174 Q154 179 149 179 L51 179 Q46 179 46 174 Z" fill="url(#' + id + 't)"/>' +
        '<path d="M46 66 L154 66 L154 80 L46 80 Z" fill="' + p.dark + '" opacity=".6"/>' +
        '<rect x="66" y="96" width="68" height="58" rx="2" fill="#F4EFE3" opacity=".88"/>' +
        imprint(p, 100, 116, 1.1, false) +
        '<path d="M46 66 L154 66 L154 174 Q154 179 149 179 L51 179 Q46 179 46 174 Z" fill="url(#' + id + 's)"/>' +
        '<path d="M74 70 C74 40 126 40 126 70" fill="none" stroke="' + p.light + '" stroke-width="7.5" stroke-linecap="round"/>';
    },

    /* Drawstring backpack (cinch pack) */
    backpack: function (id, p, tex) {
      var f = tex ? 'url(#' + id + 't)' : 'url(#' + id + 'g)';
      return shadow() +
        '<path d="M56 56 C40 118 52 172 56 176 M144 56 C160 118 148 172 144 176" fill="none" stroke="' + p.cord + '" stroke-width="3.2" stroke-linecap="round"/>' +
        '<path d="M58 62 Q58 52 70 50 L130 50 Q142 52 142 62 L152 158 Q154 178 134 178 L66 178 Q46 178 48 158 Z" fill="' + f + '"/>' +
        '<path d="M58 62 Q58 52 70 50 L130 50 Q142 52 142 62 L143 72 L57 72 Z" fill="' + p.dark + '" opacity=".55"/>' +
        '<path d="M66 52 q8 8 0 16 M82 50 q8 9 0 18 M100 50 q8 9 0 18 M118 50 q8 9 0 18 M134 52 q6 8 0 16" stroke="' + p.dark + '" stroke-width="1.6" fill="none" opacity=".5"/>' +
        '<path d="M58 62 Q58 52 70 50 L130 50 Q142 52 142 62 L152 158 Q154 178 134 178 L66 178 Q46 178 48 158 Z" fill="url(#' + id + 's)"/>' +
        imprint(p, 100, 122, 1.2, isDark(p)) +
        '<circle cx="58" cy="172" r="7" fill="' + p.dark + '"/><circle cx="142" cy="172" r="7" fill="' + p.dark + '"/>';
    },

    /* Small drawstring pouch */
    pouch: function (id, p, tex) {
      var f = tex ? 'url(#' + id + 't)' : 'url(#' + id + 'g)';
      return shadow() +
        '<path d="M62 72 Q62 62 74 60 L126 60 Q138 62 138 72 L144 158 Q146 176 128 176 L72 176 Q54 176 56 158 Z" fill="' + f + '"/>' +
        '<path d="M62 72 Q62 62 74 60 L126 60 Q138 62 138 72 L139 80 L61 80 Z" fill="' + p.dark + '" opacity=".55"/>' +
        '<path d="M70 62 q7 8 0 16 M86 60 q7 9 0 18 M100 60 q7 9 0 18 M114 60 q7 9 0 18 M130 62 q6 8 0 16" stroke="' + p.dark + '" stroke-width="1.5" fill="none" opacity=".5"/>' +
        '<path d="M62 66 C38 62 36 50 52 48 M138 66 C162 62 164 50 148 48" fill="none" stroke="' + p.cord + '" stroke-width="3.4" stroke-linecap="round"/>' +
        '<path d="M62 72 Q62 62 74 60 L126 60 Q138 62 138 72 L144 158 Q146 176 128 176 L72 176 Q54 176 56 158 Z" fill="url(#' + id + 's)"/>' +
        imprint(p, 100, 122, 1.1, isDark(p));
    },

    /* Wine / bottle bag — tall, narrow, with divider */
    wine: function (id, p, tex) {
      var f = tex ? 'url(#' + id + 't)' : 'url(#' + id + 'g)';
      return shadow() +
        '<path d="M84 52 C84 30 116 30 116 52" fill="none" stroke="' + p.dark + '" stroke-width="6" stroke-linecap="round" opacity=".6" transform="translate(4,-3)"/>' +
        '<path d="M66 48 L134 48 L138 174 Q138 179 133 179 L67 179 Q62 179 62 174 Z" fill="' + f + '"/>' +
        '<path d="M66 48 L134 48 L134.6 62 L65.4 62 Z" fill="' + p.dark + '" opacity=".55"/>' +
        '<path d="M100 62 L100 179" stroke="' + p.dark + '" stroke-width="1.6" opacity=".4"/>' +
        '<path d="M66 48 L134 48 L138 174 Q138 179 133 179 L67 179 Q62 179 62 174 Z" fill="url(#' + id + 's)"/>' +
        imprint(p, 100, 118, .95, isDark(p)) +
        '<path d="M84 52 C84 30 116 30 116 52" fill="none" stroke="' + p.light + '" stroke-width="6.5" stroke-linecap="round"/>';
    },

    /* Laundry / large cinch sack */
    laundry: function (id, p, tex) {
      var f = tex ? 'url(#' + id + 't)' : 'url(#' + id + 'g)';
      return shadow() +
        '<path d="M56 74 Q54 60 68 56 L132 56 Q146 60 144 74 L154 160 Q157 179 136 179 L64 179 Q43 179 46 160 Z" fill="' + f + '"/>' +
        '<path d="M56 74 Q54 60 68 56 L132 56 Q146 60 144 74 L145 84 L55 84 Z" fill="' + p.dark + '" opacity=".55"/>' +
        '<path d="M64 58 q8 10 0 22 M82 56 q8 12 0 24 M100 56 q8 12 0 24 M118 56 q8 12 0 24 M136 58 q7 10 0 22" stroke="' + p.dark + '" stroke-width="1.6" fill="none" opacity=".45"/>' +
        '<path d="M56 68 C34 62 32 48 50 44 M144 68 C166 62 168 48 150 44" fill="none" stroke="' + p.cord + '" stroke-width="3.6" stroke-linecap="round"/>' +
        '<path d="M56 74 Q54 60 68 56 L132 56 Q146 60 144 74 L154 160 Q157 179 136 179 L64 179 Q43 179 46 160 Z" fill="url(#' + id + 's)"/>' +
        imprint(p, 100, 124, 1.3, isDark(p));
    },

    /* Zippered flat pouch / cosmetic bag */
    zip: function (id, p, tex) {
      var f = tex ? 'url(#' + id + 't)' : 'url(#' + id + 'g)';
      return shadow() +
        '<rect x="38" y="72" width="124" height="94" rx="11" fill="' + f + '"/>' +
        '<rect x="38" y="72" width="124" height="16" rx="8" fill="' + p.dark + '" opacity=".6"/>' +
        '<path d="M44 80 H156" stroke="' + p.light + '" stroke-width="2.6" stroke-linecap="round" opacity=".85"/>' +
        '<path d="M46 80 h110" stroke="' + p.dark + '" stroke-width="5" stroke-linecap="round" stroke-dasharray="1.6 3.4" opacity=".55"/>' +
        '<rect x="150" y="74" width="13" height="13" rx="3.5" fill="' + p.light + '"/>' +
        '<path d="M156 87 v13 a5 5 0 0 0 5 5 h4" stroke="' + p.dark + '" stroke-width="2.6" fill="none" stroke-linecap="round"/>' +
        '<rect x="38" y="72" width="124" height="94" rx="11" fill="url(#' + id + 's)"/>' +
        imprint(p, 100, 122, 1.15, isDark(p));
    },

    /* Non-woven grocery tote — short handles, square base */
    nonwoven: function (id, p) {
      return shadow() +
        '<path d="M80 66 C80 46 120 46 120 66" fill="none" stroke="' + p.dark + '" stroke-width="6" stroke-linecap="round" opacity=".6" transform="translate(5,-3)"/>' +
        '<path d="M50 62 L150 62 L150 176 L50 176 Z" fill="url(#' + id + 'g)"/>' +
        '<path d="M50 62 L150 62 L150 74 L50 74 Z" fill="' + p.dark + '" opacity=".5"/>' +
        '<path d="M150 62 L166 72 L166 176 L150 176 Z" fill="' + p.dark + '" opacity=".85"/>' +
        '<path d="M50 176 L150 176 L166 176" stroke="' + p.dark + '" stroke-width="2" opacity=".5" fill="none"/>' +
        '<path d="M50 62 L150 62 L150 176 L50 176 Z" fill="url(#' + id + 's)"/>' +
        imprint(p, 100, 120, 1.2, isDark(p)) +
        '<path d="M80 66 C80 46 120 46 120 66" fill="none" stroke="' + p.light + '" stroke-width="6.5" stroke-linecap="round"/>';
    },

    /* Shoe bag — medium drawstring, landscape */
    shoe: function (id, p, tex) {
      var f = tex ? 'url(#' + id + 't)' : 'url(#' + id + 'g)';
      return shadow() +
        '<path d="M46 82 Q44 70 58 67 L142 67 Q156 70 154 82 L158 156 Q160 174 142 174 L58 174 Q40 174 42 156 Z" fill="' + f + '"/>' +
        '<path d="M46 82 Q44 70 58 67 L142 67 Q156 70 154 82 L155 92 L45 92 Z" fill="' + p.dark + '" opacity=".55"/>' +
        '<path d="M60 68 q7 12 0 24 M80 67 q7 13 0 25 M100 67 q7 13 0 25 M120 67 q7 13 0 25 M140 68 q6 12 0 24" stroke="' + p.dark + '" stroke-width="1.5" fill="none" opacity=".45"/>' +
        '<path d="M46 76 C28 70 28 58 44 56 M154 76 C172 70 172 58 156 56" fill="none" stroke="' + p.cord + '" stroke-width="3.4" stroke-linecap="round"/>' +
        '<path d="M46 82 Q44 70 58 67 L142 67 Q156 70 154 82 L158 156 Q160 174 142 174 L58 174 Q40 174 42 156 Z" fill="url(#' + id + 's)"/>' +
        imprint(p, 100, 126, 1.15, isDark(p));
    }
  };

  /* ---- Public API ------------------------------------------------------ */
  function bagSVG(shape, colorKey, opts) {
    opts = opts || {};
    var p = c(colorKey);
    var id = nid();
    var fn = SHAPES[shape] || SHAPES.tote;
    var textured = opts.textured || shape === 'jute';
    return '<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="' +
      (opts.alt || (p.name + ' bag')) + '">' +
      defs(id, p, textured) + fn(id, p, textured) + '</svg>';
  }

  function bagFigure(shape, colorKey, opts) {
    opts = opts || {};
    return '<div class="bag-img ' + (opts.cls || '') + '">' + bagSVG(shape, colorKey, opts) + '</div>';
  }

  w.TBMBags = { svg: bagSVG, figure: bagFigure, colors: COLORS, color: c, shapes: Object.keys(SHAPES) };
})(window);
