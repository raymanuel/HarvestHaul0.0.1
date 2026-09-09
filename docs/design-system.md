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

Ink Navy + Gold palette is fixed - do not rebrand.

### Brand tokens (`resources/css/app.css` @theme)

| Token | Hex | Use |
|-------|-----|-----|
| `--color-brand` | #16283C | Primary Ink Navy: buttons, links, accents |
| `--color-brand-dark` | #0E1620 | Deep navy: hover states, footer, drawers |
| `--color-brand-green` | #16A34A | Logo accent leaf (white logo chip only) |
| `--color-gold` / gold-500 | #BFA05A | Gold accent: active nav, welcome pops, premium emphasis |
| `--color-gold-light` | #D7BC7A | Gold on dark surfaces (text/borders, never a fill) |
| `--color-gold-600` / gold-700 | #7C6527 / #8A7030 | Gold deep accent (readable on light surfaces) |

Deprecated aliases: `accent-*` and `harvest-*` still exist and are byte-identical to
`gold`/`gold-light`/`gold-600`/`gold-700`. Use `gold` when a file is touched; do not
blanket-replace.

### Surfaces

| Context | Light | Dark |
|---------|-------|------|
| Page canvas | `--color-surface` #F5F6F2 (cream) | `--color-surface-dark` #0E1620 |
| Cards | `--color-surface-card` #FFFFFF (warm cream via `.app-shell`) | `--color-surface-card-dark` #14202D (ink navy) |
| Chrome (sidebar/topbar) | #16283C / #0E1620 | same |

Neutrals for borders on standalone pages: `#e5e7eb` (never the old cream `#e2e0dc`).

### Rules

- Gold (#BFA05A / #D7BC7A) is **accent only** — never a field, button fill, or bubble
  background. Ink Navy (#16283C) fills primary buttons; gold marks active/emphasis.
- Warnings and pending states are always amber (`warning-bg`/`warning-border`/`warning-text`,
  dark variants `-dark`). Never green.
- No gradient text (`bg-clip-text`), no gradient CTA buttons, no decorative eyebrow labels
  (small uppercase tracked kickers above headings are deleted on sight).
- No emojis anywhere in UI copy.
- Map/data-viz colors stay inside palette: polylines/markers use navy/gold ramp; the solid
  gold bubble is banned. No violet/emerald one-offs (blue = info, rose = cancelled, purple =
  booked/assigned semantics only).

## Components

### `<x-button>`

Replaces all duplicated CTA class strings. Props:

- `variant`: `primary` (Ink Navy solid), `secondary` (slate outline fill),
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
