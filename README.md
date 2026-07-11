# Food Calorie Calculator

Comprehensive UK food calorie and nutrition calculator WordPress plugin. Ships with **5,200+ real-sourced foods** covering 190+ countries, FSA traffic lights, SVG macro rings, Omega-3/caffeine/micronutrient tracking, meal builder with templates, BMR/TDEE calculator, promotion suite, analytics dashboard, and CSV/XLSX import-export.

**Plugin URI:** https://foodcaloriecalculator.co.uk
**Version:** 16.3.1
**Requires:** WordPress 6.0+, PHP 8.1+
**Text Domain:** `food-calorie-calculator`

---

## Key Features

### Food Database
- **5,200+ foods** from 190+ countries with verified USDA FDC and UK composition data
- Per-100g macros: energy (kcal/kJ), protein, carbs, sugars, fat, saturates, fibre, salt
- Omega-3 breakdown (ALA, EPA, DHA), caffeine, iron, calcium, vitamin C
- Serving sizes with portion calculator
- 8 allergen tags + 6 dietary tags per food
- Sponsor/affiliate integration per food

### Calculator Frontend
- Voice search (SVG microphone icon)
- FSA traffic light system (UK food labelling)
- Reference Intake (% RI) bars with colour coding
- SVG macro rings (Protein/Carbs/Fat) — prints natively in PDF
- Macro progress bars + 4-stat summary grid
- Health highlights (High Protein, Low Sugar, etc.)
- Allergen & dietary badges
- Omega-3 & caffeine cards
- Micronutrient cards (Iron, Calcium, Vitamin C)
- Compare foods side-by-side
- Dark mode support
- PWA Install as App (Add to Home Screen)
- Curated trending dropdown on search focus

### Meal Builder
- Add foods to meals with editable quantities
- Meal categories: Breakfast, Lunch, Dinner, Snack (auto-detected by time)
- Save/load meal templates (localStorage)
- Meal totals with full breakdown table
- Serves divider (per-person calculation)
- Daily goal progress bar
- Print Meal, Copy Meal, Share Meal, Save as Template
- Micronutrients in meal totals

### Print / PDF
- Branded header with food name + kcal summary + date
- Clean layout: SVG rings, progress bars, nutrition table all print natively
- Gradient header + teal CTA footer banner
- Nutritional disclaimer
- No browser header (margin-top: 0), footer preserved
- Dynamic filename: "Food Name – FCC.pdf" or "Meal Plan (N items) – FCC.pdf"
- Clickable URLs in PDF

### Promotion Suite (📌 Pinned Tab)
- **Pinned Search Results** — force foods to positions 1st/2nd/3rd for specific keywords
  - Promotional badges (Best Seller, Sponsored, New, Editor Pick, etc.)
  - Toggle ON/OFF, Duplicate, Collapse per rule
- **Curated Trending (Dropdown)** — hand-picked foods shown on search focus
- **Promotional Banners** — custom message + CTA button when a food is selected

### Analytics Dashboard
- 5 tabs: Overview, Search, Monetization, Content, Audience
- Per-card date filters (7d/30d/90d/All + custom date range picker)
- Search volume chart, top 10 foods, success rate trend
- Category breakdown (doughnut), peak search days, hourly distribution
- Zero-result queries table with "+ Add" quick action
- Trending searches with growth %
- Supplement CTR, sponsor clicks, white label ARR
- Database completeness rings (serving sizes %, micronutrients %, allergens %)
- Content gaps with priority scoring
- Subscriber growth chart + email opt-in cards with avatars
- CSV export for all data sections

### Admin Features
- Full settings panel: General, Features, Appearance, Labels, Pinned, Advanced
- 20+ feature toggles in grouped cards
- Food edit page with SVG icons per section
- Bulk food import/export (CSV/XLSX)
- Food request system (missed searches + user requests)
- Email Hub for newsletter subscribers
- Content Planner with AI-scored priorities
- Sponsored Foods management
- Affiliate Links management
- Ad Networks integration
- Supplement Lead Gen
- White Label licensing
- Per-card analytics date filtering

---

## Installation

1. Upload the `food-calorie-calculator` folder to `/wp-content/plugins/`.
2. Activate the plugin in **Plugins → Installed Plugins**.
3. On activation, the plugin creates database tables and seeds 5,200+ foods automatically.
4. Add the calculator to any page using the shortcode `[food_calorie_calculator]` or the **Food Calorie Calculator** Gutenberg block.

---

## Shortcode

```
[food_calorie_calculator]
```

No attributes required. All behaviour is controlled from **Food Calculator → Settings** in the WordPress admin.

---

## Admin Menus

| Menu | Description |
|------|-------------|
| Dashboard | Stats, shortcode copy button, quick links |
| Foods | Add / edit / delete individual foods |
| Categories | Manage food categories |
| Import / Export | Bulk import from CSV or XLSX; export full database |
| Settings | General, Features, Appearance, Labels, Pinned, Advanced |
| Food Requests | Missed searches + user food requests |
| Sponsored | Manage sponsored food placements |
| Analytics | Business intelligence dashboard with 5 tabs |
| Email Hub | Newsletter subscriber management |
| Content Planner | AI-prioritised content gaps |
| White Label | Licensing for third-party sites |
| Affiliate Links | Manage affiliate retailer links |
| Ad Networks | Ad slot integration |
| Supplement Lead Gen | Supplement recommendation engine |

---

## Import / Export Format

The CSV and XLSX import/export support all nutrient columns including micronutrients and allergen/dietary tags.

| Column | Required | Nullable | Type | Notes |
|--------|----------|----------|------|-------|
| `name` | ✓ | — | string | Full food name |
| `slug` | — | — | string | Auto-generated from name if omitted |
| `category` | — | — | string | Created automatically if it doesn't exist |
| `energy_kcal` | ✓ | — | float | Per 100 g/ml |
| `energy_kj` | ✓ | — | float | Per 100 g/ml |
| `fat_g` | ✓ | — | float | Per 100 g/ml |
| `of_which_saturates_g` | ✓ | — | float | Per 100 g/ml |
| `carbohydrate_g` | ✓ | — | float | Per 100 g/ml |
| `of_which_sugars_g` | ✓ | — | float | Per 100 g/ml |
| `fibre_g` | ✓ | — | float | Per 100 g/ml |
| `protein_g` | ✓ | — | float | Per 100 g/ml |
| `salt_g` | ✓ | — | float | Per 100 g/ml |
| `omega3_total_mg` | — | ✓ | float | Leave empty if no verified source |
| `omega3_ala_mg` | — | ✓ | float | Leave empty if no verified source |
| `omega3_epa_mg` | — | ✓ | float | Leave empty if no verified source |
| `omega3_dha_mg` | — | ✓ | float | Leave empty if no verified source |
| `caffeine_mg` | — | ✓ | float | Leave empty if no verified source |
| `iron_mg` | — | ✓ | float | Micronutrient — verified USDA FDC only |
| `calcium_mg` | — | ✓ | float | Micronutrient — verified USDA FDC only |
| `vitamin_c_mg` | — | ✓ | float | Micronutrient — verified USDA FDC only |
| `serving_sizes` | — | — | JSON | Array of serving size objects |
| `source_notes` | — | — | string | Citation / source reference |
| Allergen columns | — | — | 0/1 | `contains_gluten`, `contains_dairy`, `contains_nuts`, `contains_eggs`, `contains_soy`, `contains_fish`, `contains_shellfish`, `contains_celery` |
| Dietary columns | — | — | 0/1 | `is_vegetarian`, `is_vegan`, `is_halal`, `is_kosher`, `is_keto_friendly`, `is_gluten_free` |

**Upsert logic:** If a row's `slug` matches an existing food, that food is updated. Otherwise a new food is inserted.

---

## Developer Notes

**Database tables:**

- `{prefix}fcc_foods` — all foods with per-100g nutrition data (DB_VERSION 1.5)
- `{prefix}fcc_categories` — food categories
- `{prefix}fcc_food_requests` — user food requests + email opt-ins
- `{prefix}fcc_search_log` — search analytics time-series

**REST API endpoints** (`fcc/v1`):

- `GET /foods/search?q=&limit=&category=` — autocomplete search (with pinned rules)
- `GET /foods/{id}` — single food detail
- `POST /foods/{id}/hit` — increment search count
- `POST /foods/{id}/sponsor-click` — record sponsor click
- `GET /categories` — all categories

**CSS custom properties** (override in your theme):

```css
:root {
  --fcc-primary: #075B5E;
  --fcc-accent:  #FF3F33;
  --fcc-bg:      #FFE6E1;
  --fcc-radius:  8px;
}
```

Or set colours via **Settings → Appearance** in the admin.

---

## Licence

GPL-2.0-or-later — https://www.gnu.org/licenses/gpl-2.0.html
