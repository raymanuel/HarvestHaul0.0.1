# HarvestHaul Design System

Single source of truth for visual design. Last verified 2026-08-25.

## Typography

Self-hosted via `public/fonts/fonts.css` (variable woff2, latin + latin-ext):

| Role | Family | Weights | Token |
|------|--------|---------|-------|
| Display / headings / buttons | Schibsted Grotesk | 400 900 (variable) | `--font-display`, `.heading-font`, `.font-display` |
| Body | DM Sans | 300 800 (variable) | `--font-sans`, default |
| Numerals / prices / code | JetBrains Mono | 500 700 (variable) | `--font-mono` |

Space Grotesk and Instrument Serif were removed 2026-08-25; no view may reference them.
Standalone pages that do not extend a Blade layout (welcome, auth/verified, legal) must link
`/fonts/fonts.css` directly and never load external font CDNs.

## Color

Teal Tide palette is fixed - do not rebrand.

### Brand tokens (`resources/css/app.css` @theme)

| Token | Hex | Use |
|-------|-----|-----|
| `--color-brand` / brand-600 | #0F766E | Primary teal: buttons, links, accents |
| `--color-brand-light` | #14B8A6 | Teal on dark surfaces |
| `--color-brand-700` | #0D9488 | Hover teal, icon fills, map lines |
| `--color-brand-dark` | #0B4F49 | Deep teal: dark buttons, footer, banners |
| `--color-harvest` / harvest-600 | #F26B5E | Coral: buyer-side CTAs, welcome accents |
| `--color-harvest-dark` | #E14B3D | Coral hover; Express Haul Intent buttons |
| `--color-harvest-700` | #C23A2E | Coral deep accent (eyebrow-free labels, icons) |

### Surfaces

| Context | Light | Dark |
|---------|-------|------|
| Page canvas | #FAFAFA | slate-950 family |
| Cards | white | slate-800/80 |
| Chrome (sidebar/topbar) | #101A2B / #0B1220 | same |

Neutrals for borders on standalone pages: `#e5e7eb` (never the old cream `#e2e0dc`).

### Rules

- Warnings and pending states are always amber (`amber-50/200` bg, `amber-700` text). Never green.
- No gradient text (`bg-clip-text`), no gradient CTA buttons, no decorative eyebrow labels
  (small uppercase tracked kickers above headings are deleted on sight).
- No emojis anywhere in UI copy.
- Map/data-viz colors stay inside palette: polylines/markers use teal ramp (#0D9488/#14B8A6);
  Express-intent actions use coral (#E14B3D). No violet/blue/emerald one-offs.

## Components

### `<x-button>`

Replaces all duplicated CTA class strings. Props:

- `variant`: `primary` (teal solid), `harvest` (coral solid), `secondary` (slate outline fill),
  `ghost` (text-only), `danger` (red)
- `size`: `sm` / `md` / `lg`
- `full`: adds `w-full`
- `tag="a"` renders an anchor (pass `href`); otherwise a submit button
- Extra classes merge through (e.g. `active:scale-[0.98]`, `rounded-2xl`)

Variant classes are literal strings in `resources/views/components/button.blade.php`
so the Tailwind v4 scanner compiles them.

### Other shared components

`stat-card` (accent variants with literal match arms), `badge`, `market-prices-card`,
`section-label`, `notification-dropdown`. Market price cards render their own heading;
do not add a duplicate `<x-section-label>` above them.

## Accessibility floor

- Text contrast >= 4.5:1. Light-mode muted text is `slate-500 dark:text-slate-400`
  (v4 `slate-400` alone computes to rgb(144,161,185) = 2.9:1 and fails).
- Warning text uses `amber-700` minimum in light mode.
- Inputs keep `:focus-visible` outlines; password fields use
  `autocomplete="current-password"` on login.
- Icon-only controls carry `aria-label`.
- Sidebar collapse snaps instantly (no width/padding transitions - they caused reflow jank).

## Motion

FAQ/disclosure elements animate `grid-template-rows: 0fr -> 1fr` (no max-height clipping).
`prefers-reduced-motion` collapses all animation globally.
