# SommAI

> An AI-powered wine recommendation widget for e-commerce. Embed intelligent wine discovery on your website in minutes.

**SommAI** is a production-ready SaaS that helps wine retailers and online shops guide customers to the perfect bottle. It uses Claude AI to analyze customer preferences and match them against your product catalog in real-time.

![version](https://img.shields.io/badge/version-1.0.3-blue)
![license](https://img.shields.io/badge/license-proprietary-red)
![stack](https://img.shields.io/badge/built%20with-TypeScript%2C%20Preact%2C%20PHP-brightgreen)

## Features

- 🧠 **AI-Powered Recommendations** — Claude Haiku analyzes natural language queries ("something for a winter night" → curated selection)
- 🔄 **Real-Time Streaming** — SSE streaming UI shows recommendations as Claude thinks
- 🏷️ **Smart Filtering** — Type + price range filters without consuming AI tokens
- 📦 **Catalog Agnostic** — Auto-detects wine types from Google Shopping feeds, Commerce7 XML, or custom JSON
- 🌍 **Multi-Language** — English & Spanish support (extensible)
- 🛒 **Commerce7 Ready** — Native "Add to Cart" button if C7 tenant is configured
- 📱 **Fully Responsive** — Mobile-optimized UI, compact card grid
- 🎨 **Customizable Branding** — Accent color, title, suggestions, widget locale

## How It Works

```
Customer → [Widget Search] → Cloudflare Worker → Claude API → Real-Time Recommendations
                                   ↓
                          Parse Your Product Feed
                          Extract: type, price, vintage, country, grape
```

1. Customer enters a query or selects filters in the embedded widget
2. Query goes to your Cloudflare Worker (no cold starts, global edge network)
3. Worker fetches your product feed (XML/JSON), cached at CDN
4. Request streams to Claude Haiku for semantic analysis
5. Recommendations appear card-by-card as tokens arrive
6. Customer clicks "Add to Cart" or "View Wine" (external link)

## Installation

### For Wine Retailers (WordPress)

1. **Download** `sommai-plugin-v1.0.4.zip` (20 KB)
2. **Upload** via WordPress Admin → Plugins → Upload Plugin
3. **Activate** SommAI from Plugins page
4. **Configure** Settings → SommAI:
   - Paste your **License Key** (request at sommai.com)
   - Enter your **Product Feed URL** (Google Shopping XML or Commerce7 feed)
   - *(Optional)* Set Commerce7 Tenant ID for Add to Cart
   - *(Optional)* Customize widget title, color, suggestions
5. **Embed** on any page/post:
   ```
   [sommai title="Find Your Wine"]
   ```

### For Developers

```bash
# Clone the monorepo
git clone https://github.com/yourusername/sommai.git
cd sommai

# Worker (backend)
cd worker
npm install
npm run dev                    # local dev @ localhost:8787
npx wrangler deploy           # deploy to Cloudflare

# Widget (frontend)
cd ../widget
npm install
npm run dev                    # local dev @ localhost:5174
npm run build                  # output: dist/sommai.min.js

# Plugin (WordPress integration)
# No build needed — it bundles the widget JS automatically
```

## Configuration

### Environment Variables (Worker)

Set via `npx wrangler secret put`:

```bash
ANTHROPIC_API_KEY=sk-ant-...     # Claude API key
```

In `worker/wrangler.toml`:

```toml
vars = { CLAUDE_MODEL = "claude-haiku-4-5-20251001" }
```

### WordPress Plugin Settings

| Field | Required | Description |
|-------|----------|-------------|
| License Key | ✅ | SommAI tenant ID (validates requests) |
| Product Feed URL | | Auto-detected via BetterSEO (when installed) |
| Language | | Spanish (es) or English (en). Default: es |
| Widget Title | | Heading text above search. Leave blank to hide. |
| Accent Color | | Primary UI color for buttons. Default: burgundy (#6b2737) |
| Commerce7 Tenant ID | | For "Add to Cart" — leave blank to disable |
| Search Suggestions | | Curated suggestion chips (one per line). Defaults provided. |
| Worker URL | (dev only) | Override Cloudflare Worker endpoint |
| Custom JS URL | (dev only) | Use external CDN instead of bundled JS |

### Shortcode Examples

```html
<!-- Default configuration -->
[sommai]

<!-- Override title only -->
[sommai title="Descubrí tu vino perfecto"]

<!-- Override language and color -->
[sommai locale="en" accent="#8B1A1A"]

<!-- Custom cart integration per widget -->
[sommai cart_provider="commerce7" cart_c7tenant="bodega-name"]

<!-- Override all settings -->
[sommai
    title="Find Your Perfect Wine"
    locale="en"
    accent="#8B1A1A"
    cart_provider="commerce7"
    cart_c7tenant="my-winery"]
```

## Product Feed Format

SommAI supports:

### 1. Google Shopping XML (`g:` namespace)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
  <channel>
    <item>
      <g:id>SKU123</g:id>
      <g:title>Malbec Premium 2020</g:title>
      <g:price>450 ARS</g:price>
      <g:product_type>Red Wine | Malbec</g:product_type>
      <g:description>Smooth malbec from Mendoza region, 2020 vintage</g:description>
      <g:custom_label_0>Malbec</g:custom_label_0>
      <g:custom_label_1>2020</g:custom_label_1>
      <g:custom_label_2>Mendoza</g:custom_label_2>
      <g:image_link>https://example.com/malbec.jpg</g:image_link>
      <g:link>https://example.com/products/malbec-2020</g:link>
    </item>
  </channel>
</rss>
```

### 2. Commerce7 XML (automatically parsed)

### 3. JSON Feed

```json
[
  {
    "id": "SKU123",
    "name": "Malbec Premium 2020",
    "price": 450,
    "currency": "ARS",
    "description": "Smooth malbec from Mendoza, 2020 vintage",
    "type": "red",
    "image_url": "https://example.com/malbec.jpg",
    "url": "https://example.com/products/malbec-2020"
  }
]
```

**Type Inference:** Wine type (red, white, sparkling, rosé, fortified) is detected from:
- `<g:product_type>` or `type` field (strict match)
- Product name (regex: "tinto", "blanco", "espumante", "rosado", etc.)
- Description fallback

Only exact type matches are used for filtering — prevents false positives.

## API Reference

### Worker Endpoints

All endpoints require header: `X-SommAI-Key: <license-key>`

#### `POST /recommend` — AI Recommendations (Streaming)

**Headers:**
```
X-SommAI-Key: <license-key>
Content-Type: application/json
```

**Request Body:**
```json
{
  "query": "red wine for a cold winter night",
  "filters": {
    "type": "red",
    "budget_min": 200,
    "budget_max": 1000
  },
  "max_results": 5,
  "locale": "es"
}
```

**Response (Server-Sent Events):**
```
data: {"type":"token","data":" A"}
data: {"type":"token","data":" bold"}
...
data: {"type":"result","data":[{"product":{...},"score":95,"reason":"Perfect for winter nights..."}]}
data: {"type":"done"}
```

---

#### `POST /filter` — Static Filtering (No Claude Tokens)

Same request as `/recommend`, but **returns results instantly without consuming AI tokens**. Useful for filter-only queries.

**Response (JSON):**
```json
{
  "results": [
    {
      "product": {
        "id": "SKU123",
        "name": "Malbec Premium 2020",
        "type": "red",
        "price": 450,
        "currency": "ARS",
        "vintage": 2020,
        "country": "Argentina",
        "region": "Mendoza",
        "grape": "Malbec",
        "image_url": "https://...",
        "url": "https://..."
      },
      "score": 100,
      "reason": ""
    }
  ]
}
```

---

#### `POST /catalog/meta` — Catalog Metadata

Fetch real product types and price range from a feed (called by widget on mount).

**Request:**
```json
{}
```

**Response:**
```json
{
  "types": ["red", "white", "sparkling"],
  "count": 243,
  "price_min": 150,
  "price_max": 5000
}
```

---

#### `GET /health` — Health Check

Returns `200` if Worker can reach Claude API.

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     WordPress Site (User's Domain)              │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ [sommai] Shortcode                                       │   │
│  │  ↓                                                        │   │
│  │ <div data-sommai                                         │   │
│  │       data-worker="https://sommai-worker..."           │   │
│  │       data-license="bsomm_live_abc123"                  │   │
│  │       data-locale="es">                                │   │
│  │                                                           │   │
│  │  [Preact Widget Runtime - 37 KB + Styles]              │   │
│  │  ├─ Search Bar (textarea with clear button)             │   │
│  │  ├─ Smart Filters (type + budget range)                │   │
│  │  ├─ Suggestion Chips (customizable)                     │   │
│  │  └─ Results Grid (Wine Cards with C7 integration)       │   │
│  └──────────────────────────────────────────────────────────┘   │
│                          ↕ SSE/fetch                            │
└─────────────────────────────────────────────────────────────────┘
                            ↕
        ┌─────────────────────────────────────┐
        │   Cloudflare Worker (Global Edge)   │
        │ ┌───────────────────────────────┐   │
        │ │ License Key Validation        │   │
        │ │ Product Feed Parsing & Cache  │   │
        │ │ Static Filtering (no tokens)  │   │
        │ │ Claude Streaming Handler      │   │
        │ │ Metadata Extraction           │   │
        │ └───────────────────────────────┘   │
        └──────────────┬──────────────────────┘
                       ├─ REST → Product Feed (cached @ CDN)
                       └─ SSE ↔ Claude API (streaming)
```

## Bundle Sizes

| Component | Size (uncompressed) | Size (gzip) |
|-----------|-------------------|------------|
| Widget JS | 37.48 kB | 12.84 kB |
| Plugin ZIP | 20 kB | — |
| Worker (deployed) | 158 kB | — |

## Performance Notes

- **Widget Load:** Single IIFE bundle, no external dependencies
- **Network:** Worker at edge reduces latency (Cloudflare global CDN)
- **Caching:** Product feeds cached for 1 hour at Worker level
- **Streaming:** SSE enables partial rendering while Claude processes
- **Type Filtering:** Instant (no AI tokens consumed)

## Troubleshooting

### Widget doesn't appear
- Check license key is valid (starts with `bsomm_live_`)
- Verify shortcode syntax: `[sommai]` with no extra spaces
- Ensure Worker URL is reachable (test in browser)

### No wines found
- Confirm product feed URL is valid (must return XML/JSON)
- Check wine type inference — feed must have explicit type or detectable name
- For CSV/custom formats, convert to Google Shopping XML first

### "Add to Cart" button missing
- C7 Tenant ID not configured, OR
- Product marked as out-of-stock (`in_stock: false`), OR
- C7 session cookie not present (user must visit C7 domain first)

### Filter returns all products
- Ensure product feed has explicit `product_type` or type-containing names
- Check filter logic uses strict type matching (not `includes()`)
- Rebuild widget if updating from older version

## Licensing

SommAI is **proprietary software**. Use requires a paid license.

- **Individual Retailer:** Starting €49/month
- **Multi-Location:** Custom pricing
- **Open-Source Partnerships:** Available for qualified integrators

Request a license: https://sommai.com

See [LICENSE](./LICENSE) file for full terms.

## Support

- 📧 **Email:** support@sommai.com
- 🐛 **Security Issues:** security@sommai.com
- 📚 **Documentation:** https://sommai.com/docs
- 💬 **Community:** Discord (linked from main site)

## Roadmap

- [ ] Gutenberg block builder (Wordpress 6.4+)
- [ ] Scheduled feed caching + validation
- [ ] A/B testing for suggestions and colors
- [ ] Analytics dashboard (search trends, conversion tracking)
- [ ] Additional languages (French, Italian, Portuguese)
- [ ] Shopify app
- [ ] WooCommerce native integration

## Contributing

SommAI is closed-source. We welcome partnership inquiries for integrations and white-label deployments.

Contact: partners@sommai.com

---

## Project Structure

```
sommai/
├── worker/                 # Cloudflare Worker backend (TypeScript)
│   ├── src/
│   │   ├── index.ts       # Request router
│   │   ├── recommend.ts   # Claude streaming handler
│   │   ├── filter.ts      # Static filtering (no tokens)
│   │   ├── catalog.ts     # XML/JSON feed parser
│   │   ├── catalog-meta.ts# Metadata extraction
│   │   ├── prompt.ts      # Claude system/user prompts
│   │   └── types.ts       # TypeScript interfaces
│   ├── wrangler.toml      # Cloudflare Worker config
│   └── package.json
│
├── widget/                # Preact frontend (TypeScript)
│   ├── src/
│   │   ├── App.tsx        # Main component
│   │   ├── components/
│   │   │   ├── SearchBar.tsx
│   │   │   ├── FilterBar.tsx
│   │   │   ├── WineCard.tsx
│   │   │   └── Suggestions.tsx
│   │   ├── hooks/
│   │   │   ├── useRecommend.ts  # Claude search + filtering
│   │   │   └── useCatalogMeta.ts# Fetch types from catalog
│   │   ├── styles.ts      # All CSS (300+ lines)
│   │   ├── types.ts       # TypeScript interfaces
│   │   └── main.tsx       # IIFE bundle entry
│   ├── vite.config.ts
│   ├── tsconfig.json
│   └── package.json
│
├── plugin/                # WordPress plugin (PHP 7.4+)
│   ├── sommai.php         # Plugin header + bootstrap
│   ├── includes/
│   │   ├── class-admin.php       # Settings page UI
│   │   ├── class-shortcode.php   # [sommai] shortcode
│   │   └── class-enqueue.php     # JS/CSS loading
│   ├── assets/
│   │   ├── admin.js       # Settings page interactivity
│   │   └── styles.css     # Admin styles (minimal)
│   └── readme.txt
│
├── landing/               # Marketing site (future)
│
└── README.md             # This file
```

---

## Author

**Mariano de Iriondo** ([Gorilion](https://gorilion.com))
mariano@gorilion.com

SommAI is a proprietary SaaS product. All rights reserved © 2026.

---

**Made with ❤️ for wine lovers & shop owners**

v1.0.3 · TypeScript · Preact · PHP · Cloudflare Workers · Claude API
