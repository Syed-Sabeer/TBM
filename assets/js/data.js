/* ==========================================================================
   TBM — Catalog data (front-end prototype)
   NOTE ON SKUs:
     parentSku  = internal / mill SKU. Never rendered on the storefront.
     sku        = customer-facing item number. Used everywhere the buyer looks
                  (listing, PDP, cart, packing slip, invoice).
   NOTE ON PRICING:
     Storefront never ships a hard price. price = base x qtyBreakFactor x tierFactor,
     and tierFactor comes from the logged-in account. Logged out => no price at all.
   ========================================================================== */
(function (w) {
  'use strict';

  /* ---- Price tiers assigned per customer account ------------------------ */
  var TIERS = {
    A: { code: 'A', name: 'Tier A — Distributor', factor: 0.86 },
    B: { code: 'B', name: 'Tier B — Preferred',   factor: 0.94 },
    C: { code: 'C', name: 'Tier C — Standard',    factor: 1.00 }
  };

  /* ---- Quantity breaks (shared across catalog) -------------------------- */
  var BREAKS = [50, 100, 250, 500, 1000, 2500];
  var BREAK_FACTOR = [1.00, 0.93, 0.87, 0.82, 0.77, 0.72];

  /* ---- Warehouses -------------------------------------------------------- */
  var WAREHOUSES = [
    { code: 'LAX', name: 'Los Angeles, CA', region: 'West Coast', lead: '1–2 business days' },
    { code: 'EWR', name: 'Edison, NJ',      region: 'East Coast', lead: '1–2 business days' },
    { code: 'DFW', name: 'Dallas, TX',      region: 'Central',    lead: '2–3 business days' }
  ];

  /* ---- Categories -------------------------------------------------------- */
  var CATEGORIES = [
    { slug: 'cotton-totes',      name: 'Cotton Totes',       group: 'Totes',        shape: 'tote',      blurb: 'Everyday 5–12 oz cotton canvas.' },
    { slug: 'canvas-totes',      name: 'Canvas Totes',       group: 'Totes',        shape: 'boxy',      blurb: 'Heavyweight, gusseted, built to carry.' },
    { slug: 'organic-totes',     name: 'Organic Totes',      group: 'Totes',        shape: 'tote',      blurb: 'GOTS-certified organic cotton.' },
    { slug: 'recycled-totes',    name: 'Recycled Totes',     group: 'Totes',        shape: 'boxy',      blurb: 'Post-consumer recycled cotton + rPET.' },
    { slug: 'jute-totes',        name: 'Jute & Burlap',      group: 'Totes',        shape: 'jute',      blurb: 'Natural jute with laminated interior.' },
    { slug: 'denim-totes',       name: 'Denim Totes',        group: 'Totes',        shape: 'tote',      blurb: 'Washed denim, recycled content.' },
    { slug: 'non-woven-totes',   name: 'Non-Woven Totes',    group: 'Totes',        shape: 'nonwoven',  blurb: 'Budget grocery and event bags.' },
    { slug: 'sublimation-totes', name: 'Sublimation Totes',  group: 'Totes',        shape: 'boxy',      blurb: 'Full-bleed polyester, edge to edge.' },
    { slug: 'drawstring-backpacks', name: 'Drawstring Backpacks', group: 'Drawstring', shape: 'backpack', blurb: 'Cinch packs for events and schools.' },
    { slug: 'drawstring-pouches',   name: 'Drawstring Pouches',   group: 'Drawstring', shape: 'pouch',    blurb: 'Gift, jewellery and product pouches.' },
    { slug: 'wine-bags',         name: 'Wine & Bottle Bags', group: 'Specialty',    shape: 'wine',      blurb: '1, 2 and 6-bottle carriers.' },
    { slug: 'shoe-bags',         name: 'Shoe Bags',          group: 'Specialty',    shape: 'shoe',      blurb: 'Dust bags for footwear and retail.' },
    { slug: 'laundry-bags',      name: 'Laundry Bags',       group: 'Specialty',    shape: 'laundry',   blurb: 'Hotel, dorm and commercial sizes.' },
    { slug: 'cosmetic-bags',     name: 'Cosmetic Bags',      group: 'Specialty',    shape: 'zip',       blurb: 'Zippered pouches, lined or unlined.' }
  ];

  /* ---- Products ---------------------------------------------------------- */
  /* base = Tier C price at the 50-pc break, in USD */
  var P = [
    ['DS4500','BPK18','All-Day Organic Cotton Tote','organic-totes','tote','Organic Cotton','8 oz',
      ['15"W x 16"H x 4"D'],['natural','bone','black','forest','sage','navy'],2.65,100,
      {LAX:18400,EWR:11250,DFW:4200},['bestseller','eco','gots']],

    ['DS4510','BPK22','Stow-N-Go Organic Cotton Tote','organic-totes','boxy','Organic Cotton','10 oz',
      ['16"W x 15"H x 6"D'],['natural','black','olive','burgundy'],3.40,100,
      {LAX:9600,EWR:7400,DFW:1800},['eco','gots']],

    ['DS4520','BPK09','Value Cotton Shopper','cotton-totes','tote','Cotton Canvas','5 oz',
      ['15"W x 16"H'],['natural','white','black','red','royal','navy','forest'],1.28,250,
      {LAX:42000,EWR:36500,DFW:12400},['bestseller','value']],

    ['DS4525','BPK11','Everyday Cotton Tote w/ Gusset','cotton-totes','boxy','Cotton Canvas','6 oz',
      ['15"W x 16"H x 3"D'],['natural','black','navy','grey','sage'],1.74,250,
      {LAX:26800,EWR:19200,DFW:6100},['bestseller']],

    ['DS4530','BPK14','Market Cotton Tote — Long Handle','cotton-totes','tote','Cotton Canvas','7 oz',
      ['16"W x 17"H x 5"D'],['natural','bone','black','olive','denim'],2.18,100,
      {LAX:15400,EWR:9800,DFW:3300},[]],

    ['DS4540','BPK31','Heavyweight Canvas Carryall','canvas-totes','boxy','Cotton Canvas','16 oz',
      ['18"W x 14"H x 6"D'],['natural','black','olive','navy','rust'],5.95,50,
      {LAX:6200,EWR:4100,DFW:900},['premium']],

    ['DS4545','BPK33','Boat & Tote Canvas Bag','canvas-totes','boxy','Cotton Canvas','24 oz',
      ['17"W x 15"H x 6"D','20"W x 17"H x 7"D'],['natural','navy','red','forest','black'],7.40,50,
      {LAX:3800,EWR:2950,DFW:640},['premium','bestseller']],

    ['DS4550','BPK37','Trendy All-Day Recycled Canvas Tote','recycled-totes','boxy','Recycled Cotton','10 oz',
      ['15"W x 16"H x 5"D'],['natural','grey','black','sage','denim'],3.95,100,
      {LAX:11200,EWR:8700,DFW:2600},['eco','bestseller','grs']],

    ['DS4555','BPK39','Casual Recycled Canvas Shopper','recycled-totes','tote','Recycled Cotton','8 oz',
      ['14"W x 15"H x 4"D'],['natural','bone','grey','navy'],3.10,100,
      {LAX:8400,EWR:6300,DFW:1500},['eco','grs']],

    ['DS4560','BPK44','Square Jute Burlap Bag','jute-totes','jute','Jute / Burlap','—',
      ['12"W x 12"H x 7"D','14"W x 14"H x 8"D'],['jute','natural','black','forest'],3.85,100,
      {LAX:7600,EWR:5100,DFW:1200},['eco','bestseller']],

    ['DS4565','BPK46','Laminated Jute Shopper','jute-totes','jute','Jute / Laminated','—',
      ['16"W x 13"H x 6"D'],['jute','burgundy','navy','olive'],4.60,100,
      {LAX:4900,EWR:3600,DFW:820},['eco']],

    ['DS4570','BPK52','Washed Denim Tote','denim-totes','tote','Recycled Denim','12 oz',
      ['15"W x 16"H x 4"D'],['denim','black','bone'],4.25,100,
      {LAX:5400,EWR:3900,DFW:1100},['eco','new']],

    ['DS4575','BPK54','Denim Weekend Carryall','denim-totes','boxy','Recycled Denim','14 oz',
      ['19"W x 14"H x 7"D'],['denim','black'],6.80,50,
      {LAX:2300,EWR:1700,DFW:410},['new','premium']],

    ['DS4580','BPK61','Non-Woven Grocery Tote','non-woven-totes','nonwoven','80 gsm Non-Woven','—',
      ['13"W x 15"H x 8"D'],['royal','red','forest','black','white','kraft'],0.74,500,
      {LAX:96000,EWR:74000,DFW:31000},['value','bestseller']],

    ['DS4585','BPK63','Non-Woven Convention Tote','non-woven-totes','nonwoven','100 gsm Non-Woven','—',
      ['15"W x 16"H x 4"D'],['royal','black','red','navy','white'],0.98,500,
      {LAX:61000,EWR:48500,DFW:19000},['value']],

    ['DS4590','BPK71','Full-Bleed Sublimation Tote','sublimation-totes','boxy','Polyester Canvas','—',
      ['15"W x 15"H x 4"D'],['white','bone'],4.15,50,
      {LAX:5200,EWR:3400,DFW:760},['new']],

    ['DS4595','BPK73','DTF-Ready Poly Shopper','sublimation-totes','tote','Poly / Cotton Blend','—',
      ['14"W x 16"H'],['white','bone','grey'],2.95,100,
      {LAX:7100,EWR:5600,DFW:1400},['new']],

    ['DS4600','BPK80','Organic Cotton Drawstring Backpack','drawstring-backpacks','backpack','Organic Cotton','6 oz',
      ['14"W x 17"H'],['natural','black','navy','forest','burgundy'],2.85,100,
      {LAX:14200,EWR:10800,DFW:3400},['bestseller','eco','gots']],

    ['DS4605','BPK82','Value Cotton Cinch Pack','drawstring-backpacks','backpack','Cotton Canvas','5 oz',
      ['13"W x 16"H'],['natural','white','black','red','royal'],1.95,250,
      {LAX:28500,EWR:21400,DFW:7800},['value']],

    ['DS4610','BPK84','Poly Sport Cinch Pack','drawstring-backpacks','backpack','210D Polyester','—',
      ['14"W x 18"H'],['black','royal','red','forest','navy'],1.42,500,
      {LAX:44000,EWR:33500,DFW:11200},['value']],

    ['DS4620','BPK90','Cotton Drawstring Pouch','drawstring-pouches','pouch','Cotton Muslin','4 oz',
      ['4"W x 6"H','6"W x 8"H','8"W x 10"H','12"W x 16"H'],['natural','white','black','sage','blush'],0.52,500,
      {LAX:138000,EWR:96000,DFW:42000},['bestseller','value']],

    ['DS4625','BPK92','Organic Muslin Gift Pouch','drawstring-pouches','pouch','Organic Muslin','4 oz',
      ['5"W x 7"H','8"W x 10"H'],['natural','bone','sage'],0.78,500,
      {LAX:64000,EWR:49000,DFW:16500},['eco','gots']],

    ['DS4630','BPK96','Velvet-Touch Jewellery Pouch','drawstring-pouches','pouch','Poly Velvet','—',
      ['3"W x 4"H','4"W x 6"H'],['burgundy','forest','black','navy','blush'],0.94,500,
      {LAX:38000,EWR:27500,DFW:9400},[]],

    ['DS4640','BPK101','Single-Bottle Cotton Wine Bag','wine-bags','wine','Cotton Canvas','8 oz',
      ['6"W x 14"H'],['natural','burgundy','black','forest'],1.86,250,
      {LAX:19600,EWR:14200,DFW:4800},['bestseller']],

    ['DS4645','BPK103','Two-Bottle Jute Wine Carrier','wine-bags','wine','Jute / Burlap','—',
      ['8"W x 14"H x 4"D'],['jute','burgundy','forest'],3.25,100,
      {LAX:6800,EWR:4900,DFW:1300},['eco']],

    ['DS4650','BPK110','Cotton Shoe Dust Bag','shoe-bags','shoe','Cotton Flannel','5 oz',
      ['13"W x 16"H','15"W x 18"H'],['natural','white','black','grey'],0.88,500,
      {LAX:72000,EWR:53000,DFW:18600},['bestseller','value']],

    ['DS4655','BPK112','Non-Woven Shoe Bag','shoe-bags','shoe','Non-Woven','—',
      ['14"W x 17"H'],['white','black','royal'],0.46,1000,
      {LAX:124000,EWR:88000,DFW:35000},['value']],

    ['DS4660','BPK120','Heavy Cotton Laundry Sack','laundry-bags','laundry','Cotton Canvas','10 oz',
      ['22"W x 28"H','24"W x 36"H'],['natural','white','navy','black'],5.40,50,
      {LAX:8600,EWR:6200,DFW:1700},['premium']],

    ['DS4665','BPK122','Dorm Mesh-Bottom Laundry Bag','laundry-bags','laundry','Poly / Mesh','—',
      ['22"W x 30"H'],['navy','black','royal','grey'],3.60,100,
      {LAX:12400,EWR:9100,DFW:2900},[]],

    ['DS4670','BPK130','Canvas Zip Cosmetic Pouch','cosmetic-bags','zip','Cotton Canvas','10 oz',
      ['8"W x 5"H','10"W x 7"H'],['natural','black','sage','blush','navy'],2.45,250,
      {LAX:17800,EWR:12900,DFW:4100},['bestseller']],

    ['DS4675','BPK132','Lined Cotton Travel Pouch','cosmetic-bags','zip','Cotton / PVC-free Lining','12 oz',
      ['10"W x 6"H x 3"D'],['natural','bone','black','olive'],3.55,100,
      {LAX:6900,EWR:4800,DFW:1400},['eco','new']],

    ['DS4680','BPK136','Recycled Canvas Dopp Kit','cosmetic-bags','zip','Recycled Cotton','12 oz',
      ['10"W x 6"H x 4"D'],['grey','black','olive','denim'],4.80,100,
      {LAX:4200,EWR:3100,DFW:780},['eco','new','grs']]
  ];

  var FLAG_LABEL = {
    bestseller: { text: 'Bestseller', cls: 'badge-new' },
    eco:        { text: 'Eco',        cls: 'badge-eco' },
    gots:       { text: 'GOTS',       cls: 'badge' },
    grs:        { text: 'GRS',        cls: 'badge' },
    value:      { text: 'Value',      cls: 'badge' },
    premium:    { text: 'Premium',    cls: 'badge' },
    new:        { text: 'New',        cls: 'badge-warn' }
  };

  var COPY = {
    tote: 'A clean, roomy shopper that prints beautifully and holds its shape through daily use. Reinforced stress points at the handle join, double-stitched side seams and a flat base that sits square on a counter.',
    boxy: 'A structured, gusseted body that stands on its own — the shape retailers and grocers ask for. Bar-tacked handles, boxed corners and a wide, unbroken imprint panel front and back.',
    jute: 'Natural jute fibre woven tight and finished with a laminated interior so the bag wipes clean. Contrast cotton panel on the face gives you a crisp, high-contrast print surface.',
    backpack: 'A cinch pack that survives an event day. Reinforced grommet corners, thick braided cord and a gathered top that pulls closed fast and stays closed.',
    pouch: 'Soft, breathable and sized for product, gifting and e-commerce inserts. Double-drawn cord, clean hem, no loose threads.',
    wine: 'Built around the bottle — reinforced base, snug body and a short handle that carries upright. Divider keeps glass off glass in multi-bottle sizes.',
    laundry: 'Oversized and honest. Heavy body fabric, wide cinch opening and a cord thick enough to hang on a hook without cutting in.',
    zip: 'A flat, tidy pouch with a smooth metal zip and a pull tab that survives real use. Optional PVC-free lining for wet goods.',
    nonwoven: 'The workhorse for events, grocery pickups and giveaways. Ultrasonically welded seams, square base insert and a low landed cost per unit.',
    shoe: 'Breathable dust protection for footwear, accessories and retail packouts. Single or double drawn cord depending on size.'
  };

  var PRODUCTS = P.map(function (r, i) {
    var flags = r[12] || [];
    return {
      id: r[0].toLowerCase(),
      sku: r[0],
      parentSku: r[1],          /* never render this on the storefront */
      name: r[2],
      cat: r[3],
      shape: r[4],
      material: r[5],
      weight: r[6],
      sizes: r[7],
      colors: r[8],
      base: r[9],
      moq: r[10],
      stock: r[11],
      flags: flags,
      isNew: flags.indexOf('new') > -1,
      rating: (4.3 + ((i * 7) % 7) / 10).toFixed(1),
      reviews: 12 + ((i * 37) % 140),
      desc: COPY[r[4]] || COPY.tote
    };
  });

  function byId(id) {
    id = (id || '').toLowerCase();
    return PRODUCTS.filter(function (p) { return p.id === id || p.sku.toLowerCase() === id; })[0] || null;
  }
  function byCat(slug) { return PRODUCTS.filter(function (p) { return p.cat === slug; }); }
  function catBySlug(slug) { return CATEGORIES.filter(function (c) { return c.slug === slug; })[0] || null; }

  /* price for a product at a given qty for a given tier */
  function breakIndex(qty) {
    var idx = 0;
    for (var i = 0; i < BREAKS.length; i++) if (qty >= BREAKS[i]) idx = i;
    return idx;
  }
  function priceAt(product, qty, tierCode) {
    var t = TIERS[tierCode] || TIERS.C;
    var f = BREAK_FACTOR[breakIndex(qty)];
    return Math.round(product.base * f * t.factor * 100) / 100;
  }
  function tierRow(product, tierCode) {
    var t = TIERS[tierCode] || TIERS.C;
    return BREAK_FACTOR.map(function (f) {
      return Math.round(product.base * f * t.factor * 100) / 100;
    });
  }
  function totalStock(p) {
    return Object.keys(p.stock).reduce(function (s, k) { return s + p.stock[k]; }, 0);
  }

  w.TBMData = {
    TIERS: TIERS, BREAKS: BREAKS, BREAK_FACTOR: BREAK_FACTOR,
    WAREHOUSES: WAREHOUSES, CATEGORIES: CATEGORIES, PRODUCTS: PRODUCTS, FLAG_LABEL: FLAG_LABEL,
    byId: byId, byCat: byCat, catBySlug: catBySlug,
    priceAt: priceAt, tierRow: tierRow, breakIndex: breakIndex, totalStock: totalStock
  };
})(window);
