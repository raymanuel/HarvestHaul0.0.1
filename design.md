# HarvestHaul Design System & Style Guide

This document outlines the visual identity, typography, color palette, design tokens, layout architecture, and component standards utilized across the HarvestHaul B2B agriculture logistics system.

---

## 1. Visual Theme & Philosophy

HarvestHaul utilizes a **hybrid premium design theme** tailored to modern agricultural logistics:
- **Clean Light Aesthetics (Welcome/Auth Pages)**: High-contrast, clean canvas using organic cream-white backgrounds, delicate green/emerald accents, ambient color glows, and technical grid overlays to evoke a sense of professional coordination and organic growth.
- **Translucent Glassmorphism**: Translucent floating cards and navigation bars (`backdrop-blur-xl`) with fine borders (`border-emerald-500/10`) to establish depth, structure, and readability.
- **Responsive Screen Framing**: Outer container layouts utilize smooth rounded corners (`rounded-[2.5rem]`) and thin outlines to frame application views like immersive dashboard screens.
- **Adaptive Dark Mode Support**: Core portal views feature responsive light/dark mode triggers, utilizing slate-900 panels and dark indigo accents when switched.

---

## 2. Color Palette & Typography

### Primary Palette
| Color | Tailwind Class / Value | Usage |
| :--- | :--- | :--- |
| **Canvas Background** | `#FAFBF9` | Primary light mode canvas |
| **Deep Forest Text** | `#1B1B18` | Primary high-contrast body copy |
| **Emerald Green** | `from-emerald-600 to-teal-500` | Brand gradients, buttons, active nodes |
| **Teal Accent** | `#14B8A6` / `text-teal-400` | Action states, success badges, drop-off pins |
| **Amber Warning** | `#F59E0B` | Warning cards, pending pickups, load indicators |
| **Slate Base** | `text-slate-600` / `#0F172A` (dark) | Secondary body copy and dark-mode panels |

### Ambient Background Elements
- **Agricultural Grid**: `bg-grid-pattern` (40px x 40px lines in `rgba(16, 185, 129, 0.04)`) depicting row crop lines.
- **Emerald Ambient Glow**: `ambient-glow-1` (radial-gradient of emerald/teal at `0.08` opacity).
- **Amber Accent Glow**: `ambient-glow-2` (radial-gradient of amber/gold at `0.04` opacity).

### Typography
- **Headings & Accents**: `Outfit` (sans-serif) — Clean, geometric, and modern. Used for large titles, section headers, and important labels.
- **Body & Controls**: `Plus Jakarta Sans` (sans-serif) — High legibility at small scale, wide character spacing, used for tables, inputs, and descriptions.

---

## 3. View Architectures & Layouts

### Welcome Landing (`welcome.blade.php`)
- **Header**: Glassmorphic sticky header (`backdrop-blur-xl bg-white/80`) with fine emerald borders and rounded navigations.
- **Hero Area**: Grid layout featuring a B2B crop routing pitch and a floating stats monitor mockup card on the right.
- **Live Operation Monitor**:
  - Interactive SVG map depicting the cargo run corridor (Tupi → Polomolok → GenSan Terminal).
  - Contains an `animateMotion` truck dispatch indicator (`🚛`) traveling along quadratic bezier curves.
  - Interactive tab switches between Map visualization and consolidated Proposal inbox.
- **Feature Grid**: Three-column responsive grid with translucent glassmorphism cards and smooth scale transitions (`group-hover:scale-110`).

### Authentication & Gateway (`auth/login.blade.php`, `auth/register-select.blade.php`)
- **Unified Guest Layout (`components/guest-layout.blade.php`)**:
  - Uses a premium background gradient (`#f1f5f9` to `#eff6ff`) with a wide centered glass card (`max-w-800px`, `backdrop-blur-12px`).
- **Showcase Columns**:
  - Dual-column layouts separating a visual network marketing card (left) and the functional authentication form (right).
  - Features translucent form inputs with green focus indicators (`focus:border-emerald-600 focus:ring-emerald-500/10`).

### Workspace Portals (`components/layout.blade.php`)
- **Collapsible Sidebar Nav**: Collapses to a compact icon strip. Hovering over collapsed elements displays floating labels via CSS tooltips.
- **Universal Top Bar**: Tracks notifications, displays active role scopes (Admin, Farmer, Logistics Partner, Driver), and controls the light/dark mode theme toggler.
- **Responsive Map Overlays**: Uses styled Leaflet layers for geofencing, harvest coordinate selects, and live telemetry tracking.
