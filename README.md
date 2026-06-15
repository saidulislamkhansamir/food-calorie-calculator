# Food Calorie Calculator

Interactive UK food calorie and nutrition calculator WordPress plugin. Ships with 110+ real-sourced foods, FSA traffic lights, macro chart, Omega-3/caffeine tracking, meal builder, BMR/TDEE, and CSV/XLSX import-export.

**Plugin URI:** https://foodcaloriecalculator.co.uk  
**Requires:** WordPress 6.0+, PHP 8.1+  
**Text Domain:** `food-calorie-calculator`

---

## Installation

1. Upload the `food-calorie-calculator` folder to `/wp-content/plugins/`.
2. Activate the plugin in **Plugins → Installed Plugins**.
3. On activation, the plugin creates two database tables and seeds 110+ UK foods automatically.
4. Add the calculator to any page using the shortcode `[food_calorie_calculator]` or the **Food Calorie Calculator** Gutenberg block.

---

## Shortcode

```
[food_calorie_calculator]
```

No attributes required. All behaviour is controlled from **Food Calorie Calculator → Settings** in the WordPress admin.

---

## Admin Menus

| Menu | Description |
|------|-------------|
| Dashboard | Stats, shortcode copy button, quick links |
| Foods | Add / edit / delete individual foods |
| Categories | Manage food categories |
| Import / Export | Bulk import from CSV or XLSX; export full database |
| Settings | RI values, FSA thresholds, feature toggles, appearance, labels |

---

## Import / Export Format

The CSV and XLSX import/export use the same 18-column format. The first row must be the header row with the exact column names below.

| Column | Required | Nullable | Type | Notes |
|--------|----------|----------|------|-------|
| `name` | ✓ | — | string | Full food name |
| `slug` | — | — | string | Auto-generated from name if omitted. Used as upsert key. |
| `category` | — | — | string | Category name. Created automatically if it doesn't exist. |
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
| `source_notes` | — | — | string | Citation / source reference |

**Important:** Empty cells for nullable columns (omega3_*, caffeine_mg) are stored as `NULL` — never as zero. The frontend automatically hides Omega-3 and caffeine sections when values are NULL.

**Upsert logic:** If a row's `slug` matches an existing food, that food is updated. If no slug is provided and the name is new, a new food is inserted.

---

## Omega-3 Data Sources

Omega-3 values are populated **only** from verified published sources. Foods without published data have `NULL` values and do not display Omega-3 information to users.

| Food | Source | ALA (mg) | EPA (mg) | DHA (mg) | Total (mg) |
|------|--------|----------|----------|----------|------------|
| Atlantic Salmon (farmed) | USDA FDC #175168 | — | 690 | 1457 | 2147 |
| Mackerel | USDA FDC #175139 | — | 898 | 1401 | 2299 |
| Sardines (canned in oil) | USDA FDC #175139 | — | 473 | 509 | 982 |
| Tuna (fresh) | USDA FDC #175159 | — | 363 | 1001 | 1364 |
| Kipper | McCance & Widdowson 8th ed. | — | 600 | 900 | 1500 |
| Herring | McCance & Widdowson 8th ed. | — | 600 | 900 | 1500 |
| Rainbow Trout | USDA FDC #175154 | — | 247 | 661 | 908 |
| Walnuts | USDA FDC #170187 | 9079 | — | — | 9079 |
| Flaxseed (ground) | USDA FDC #169414 | 22813 | — | — | 22813 |
| Chia Seeds | USDA FDC #170554 | 17552 | — | — | 17552 |
| Rapeseed Oil | USDA FDC #172337 | 9137 | — | — | 9137 |
| Avocado | USDA FDC #171705 | 111 | — | — | 111 |

## Caffeine Data Sources

| Food | Source | Caffeine (mg/100g or 100ml) |
|------|--------|-----------------------------|
| Filter Coffee | EFSA 2015 caffeine review | 40 |
| Espresso | EFSA 2015 caffeine review | 212 |
| Instant Coffee | McCance & Widdowson 8th ed. | 60 |
| Black Tea | McCance & Widdowson 8th ed. | 20 |
| Green Tea | EFSA 2015 caffeine review | 12 |
| Cola | EFSA 2015 caffeine review | 10 |
| Energy Drink (Red Bull) | Product label (per 100ml) | 32 |
| Dark Chocolate 70% | USDA FDC #170273 | 80 |
| Milk Chocolate | USDA FDC #169655 | 20 |

---

## Developer Notes

**Database tables:**

- `{prefix}fcc_foods` — all foods with per-100g nutrition data
- `{prefix}fcc_categories` — food categories

**REST API endpoints** (`fcc/v1`):

- `GET /foods/search?q=&limit=&category=` — autocomplete search
- `GET /foods/{id}` — single food detail
- `GET /categories` — all categories

**CSS custom properties** (override in your theme):

```css
:root {
  --fcc-primary: #005EB8;
  --fcc-accent:  #009639;
  --fcc-bg:      #f9fafb;
  --fcc-radius:  8px;
}
```

Or set colours via **Settings → Appearance** in the admin.

---

## Licence

GPL-2.0-or-later — https://www.gnu.org/licenses/gpl-2.0.html
