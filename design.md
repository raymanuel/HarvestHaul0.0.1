---
name: HarvestHaul
description: B2B agriculture logistics platform — dependable crop routing, hauling, and field intel.
colors:
  brand: "#16283C"
  brand-dark: "#0E1620"
  gold: "#BFA05A"
  gold-light: "#D7BC7A"
  gold-600: "#7C6527"
  gold-700: "#8A7030"
  surface: "#F5F6F2"
  surface-dark: "#0E1620"
  surface-card: "#FFFFFF"
  surface-card-dark: "#14202D"
  text: "#17202B"
  text-muted: "#5A6573"
  text-dark: "#E9EEF4"
  text-dark-muted: "#94A3B4"
  soil: "#435060"
  soil-light: "#7C8A99"
  success: "#16a34a"
  warning: "#a16207"
  error: "#b91c1c"
  info: "#1d4ed8"
typography:
  display:
    fontFamily: "Schibsted Grotesk, DM Sans, ui-sans-serif, system-ui, sans-serif"
    fontWeight: 700
    letterSpacing: "-0.01em"
  body:
    fontFamily: "DM Sans, ui-sans-serif, system-ui, sans-serif"
    fontWeight: 500
  mono:
    fontFamily: "JetBrains Mono, ui-monospace, monospace"
rounded:
  sm: "8px"
  md: "12px"
  lg: "16px"
  xl: "24px"
spacing:
  sm: "8px"
  md: "16px"
  lg: "24px"
  xl: "40px"
components:
  button-primary:
    backgroundColor: "{colors.brand}"
    textColor: "#FFFFFF"
    rounded: "{rounded.sm}"
    padding: "10px 20px"
  button-primary-hover:
    backgroundColor: "{colors.brand-dark}"
  button-gold:
    backgroundColor: "{colors.gold}"
    textColor: "{colors.text}"
    rounded: "{rounded.sm}"
  link-gold:
    textColor: "{colors.gold-600}"
  card:
    backgroundColor: "{colors.surface-card}"
    rounded: "{rounded.xl}"
    padding: "24px"
  stat-card:
    backgroundColor: "{colors.surface-card}"
    rounded: "{rounded.lg}"
    padding: "20px"
  status-banner-warning:
    backgroundColor: "{colors.warning}"
    rounded: "{rounded.lg}"
  chart-card:
    backgroundColor: "{colors.surface-card}"
    rounded: "{rounded.xl}"
---

# HarvestHaul Design System & Style Guide

This document outlines the visual identity, typography, color palette, design tokens, layout architecture, and component standards utilized across the HarvestHaul B2B agriculture logistics system.

---

## Overview

**Creative North Star: "Dependable Field Intelligence"**

HarvestHaul uses a **premium enterprise theme** built on an **Ink Navy + Gold** palette tailored to dependable agricultural logistics:

- **Ink Navy Primary**: Deep navy (`#16283C`) anchors the system — CTAs, sidebar, active states, and the brand mark. It reads professional, trustworthy, and grounded.
- **Gold Accent**: Warm gold (`#BFA05A`) marks premium emphasis — active navigation, welcome highlights, and key emphasis. Gold is an accent, never a flood.
- **Cream Surfaces**: Light-mode canvas (`#F5F6F2`) and white cards keep content crisp and readable.
- **Inky Dark Mode**: Dark-mode uses deep ink surfaces (`#0E1620` canvas, `#14202D` cards) with light gold text accents (`#D7BC7A`) for legibility.
- **Translucent Glassmorphism**: Floating cards, nav bars, and overlays use `backdrop-blur-xl` with fine borders for layered depth.
- **Adaptive Dark Mode**: Full light/dark theming via a topbar toggle, applied with `.dark` class overrides driven by themed CSS variables.

**Key Characteristics:**
- Dependable and grounded — ink navy primary reads trustworthy and professional.
- Premium but restrained — gold is an accent, never a flood.
- Readable in both light and dark across a field-use scene.
- Text-only navigation and plain spoken copy — no decorative icons or emoji.

---

## Colors

An **Ink Navy + Gold** palette — a cold, grounded navy against a warm gold accent, on warm cream and inky dark canvases.

### Primary

- **Ink Navy** (`#16283C`): The primary brand anchor — CTAs, sidebar, active states, the brand mark. Hover deepens to **Deepest Ink** (`#0E1620`).
- **Warm Gold** (`#BFA05A`): The premium accent — active navigation, welcome highlights, key emphasis. Never a flood; reserved for emphasis.
- **Light Gold** (`#D7BC7A`): Gold text on dark surfaces, legible against ink.

### Secondary

- **Dark Gold** (`#7C6527`): Gold text on light surfaces — the AA-passing dark-gold alternative to Warm Gold.

### Neutral

- **Cream Canvas** (`#F5F6F2`): Light-mode page background.
- **White Card** (`#FFFFFF`): Light-mode card / surface container.
- **Ink Canvas** (`#0E1620`): Dark-mode page background.
- **Ink Card** (`#14202D`): Dark-mode card / surface container.
- **Ink Text** (`#17202B`): Primary body text in light mode.
- **Muted Text** (`#5A6573`): Secondary text in light mode.
- **Paper Text** (`#E9EEF4`): Primary body text in dark mode.
- **Muted Paper** (`#94A3B4`): Secondary text in dark mode.
- **Soil** (`#435060`): Neutral slate-blue for supporting UI.
- **Soil Light** (`#7C8A99`): Light neutral for subtle surfaces.

### Semantics

- **Success** (green, `#16a34a`): confirmed, approved, delivered, in-transit positive states.
- **Warning** (amber `#a16207`): pending, under negotiation, needs attention.
- **Error** (red, `#b91c1c`): rejected, failed, deleted, destructive actions.
- **Info** (blue, `#1d4ed8`): informational context, system notices.

### Named Rules

**The No-Green Warning Rule.** Warnings consistently use **amber** — never green — and the `warning-*` token family. Green belongs to success states only.

**The Gold-Is-Accent Rule.** Gold marks emphasis only; it is never the field of a screen.

> **Note on aliases**: `accent`, `harvest`, and `brand-light` are deprecated aliases of the gold family, retained so existing templates keep compiling. `gold` is the canonical name for new work.

---

## Typography

| Role | Font | Usage |
| :--- | :--- | :--- |
| Display / Headings | `Schibsted Grotesk` | Large titles, section headers, important labels |
| Body / Controls | `DM Sans` | Tables, inputs, descriptions, UI text |
| Mono / Data | `JetBrains Mono` | Codes, coordinates, measurements |

Fonts are self-hosted (`public/fonts/`). Heading utility class `.heading-font` applies `Schibsted Grotesk` at weight 700 with tight tracking.

### Hierarchy

- **Display / Headline** (Schibsted Grotesk, weight 700, `-0.01em` tracking): large titles and section headers via `.heading-font`.
- **Title / Label** (DM Sans, weight 500–700, uppercase with `tracking-widest` for small labels): card titles, badge labels, table headers.
- **Body** (DM Sans, weight 500): all descriptive and control text.
- **Data / Measurement** (JetBrains Mono): coordinates, codes, measurements.

---

## Layout

### View Architectures

- **Welcome Landing** (`welcome.blade.php`)
    - **Header**: Sticky header on the light cream canvas with navy/gold brand mark.
    - **Hero Area**: B2B crop-routing pitch with a floating live-op monitor mockup.
    - **Role Showcase**: Tabbed panels (Farmer, Logistics Partner, Driver, Buyer) on navy/cream panels.
    - **Sections**: About, Services, FAQ, CTA, footer — all on the cream canvas with navy accents. No emojis; plain text and colored accent bars for visual distinction.

- **Authentication & Gateway** (`auth/*.blade.php`, `components/guest-layout.blade.php`)
    - **Unified Guest Layout**: Deep navy gradient backdrop with a centered glass card.
    - **Showcase Columns**: Dual-column layout separating a visual network card (left) and auth form (right).
    - **Focus Indicators**: Navy focus rings (`focus:ring-brand/20`) on form inputs.

- **Workspace Portals** (`components/layout.blade.php`, `components/sidebar.blade.php`)
    - **Collapsible Sidebar Nav**: Text-only nav items (no icons) collapsing to a compact badge strip; hover reveals labels via CSS tooltips.
    - **Universal Top Bar**: Notifications, role scopes (Admin, Farmer, Logistics Partner, Driver), light/dark toggle.
    - **App Shell Surfaces**: `.app-shell` cards override Tailwind `white`/`slate-800` to the token-based cream/ink surfaces for both themes.

- **Geospatial / Maps**
    - Leaflet overlays styled with the brand palette for geofencing, harvest coordinate selects, and live telemetry tracking.

### Rhythm & Density

- **Dashboard grid**: stat cards at `md:grid-cols-3`; weather + market intel at `lg:grid-cols-3` (weather 1 col, market 2 col).
- **Section rhythm**: consistent `mb-8`/`mb-10` gutters; uniform `gap-6` within grids.

---

## Elevation & Depth

Surfaces are **flat at rest with gentle lift on interaction** — depth is conveyed through shadow response, not persistent layers.

- Cards rest flat with a fine 1px border and a soft `0 1px 2px rgba(0,0,0,0.04)` shadow.
- Stat/grid cards lift on hover (`hover:-translate-y-1 hover:shadow-xl`).
- Glass surfaces (`glass-card`) use `backdrop-blur-xl` with fine borders for layered depth over maps and hero visuals.

### Named Rules

**The Flat-By-Default Rule.** Surfaces are flat at rest; shadows appear only as a response to hover, elevation, or focus.

---

## Shapes

The form language is **softly rounded, friendly, and consistent across surface sizes**:

- **Cards**: gently curved edges — `rounded-2xl` (12px) for stat cards, `rounded-3xl` (24px) for large panels.
- **Small badges / chips**: `rounded` (8px) and `rounded-md`.
- **Buttons**: `rounded-xl` (12px).
- **Borders**: fine 1px slate borders, lightening to translucent white in dark mode.

---

## Components

### Buttons

- **Shape:** rounded (`rounded-xl`, 12px radius).
- **Primary:** ink navy fill, white text (`bg-brand hover:bg-brand-dark text-white`), comfortable padding.
- **Gold emphasis (light):** `text-gold-600 bg-gold/10 border-gold/20`.
- **Gold emphasis (dark):** `text-gold-light bg-gold/10 border-gold/20`.
- **Hover / Focus:** darken fill on primary; navy/gold focus rings (`focus:ring-brand/20`).

### Chips / Badges

- **Style:** `bg-warning-bg text-warning-text border-warning-border` for warnings (dark: `*-dark` variants); gold-tinted badge for premium emphasis.
- **State:** selected / unselected via color-tinted backgrounds with fine borders.

### Cards / Containers

- **Corner Style:** rounded `rounded-2xl` (12px) to `rounded-3xl` (24px) depending on card size.
- **Background:** `bg-surface-card` (dark: `bg-surface-card-dark`).
- **Shadow Strategy:** flat at rest, lift on hover (reference Elevation & Depth).
- **Border:** fine 1px slate border, translucent white in dark mode.
- **Internal Padding:** `p-5` to `p-6` (20–24px).

### Stat Card

- **Region-labeled** with `role="region"` and `aria-label`; uppercase tracking-widest title at 10px; large heading-font value with a smaller unit; hover lift + arrow link that nudges right.

### Status Banner

- **Warning style:** amber surface (`bg-warning-bg border-warning-border text-warning-text`), rounded `rounded-2xl`, uniform `mb-8` rhythm — used for unverified accounts and missing-location notices. Never green.

### Inputs / Fields

- **Style:** fine slate-drawn stroke, rounded (refer theme), muted background at rest.
- **Focus:** navy focus ring (`focus:ring-brand/20`).
- **Error / Disabled:** error tokens (`error-*`) for invalid state.

### Navigation

- **Sidebar:** always dark ink navy; text-only items (no icons); collapsed state collapses to first-letter badges. Active item uses a gold-tinted background (`nav-active-bg`) with gold text.
- **Top bar:** role scope, notifications, and light/dark toggle as ghost functional icons on the ink background.

### Welcome Bar

- Managing greeting header: displays a time-aware greeting (morning / afternoon / evening) with the user's name, a weekday + date line, an optional subtitle, and an optional slot for company meta (logistics).

---

## Do's and Don'ts

### Do:

- **Do** use ink navy for primary actions and the brand mark — it reads professional and grounded.
- **Do** use gold only as an accent for emphasis and active states (never as a field).
- **Do** keep warnings amber and success states green — never the reverse.
- **Do** keep navigation and copy text-first with no decorative icons or emoji.

### Don't:

- **Don't** use gold as a flood across a screen; its rarity is the point.
- **Don't** use green for pending/warning states.
- **Don't** rely on persistent drop shadows for depth — stay flat at rest.
- **Don't** introduce emoji into interface surfaces; use plain text or colored accent bars.
