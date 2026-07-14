# Login/Register Slide Animation — Approach Options

## Option 1: All Forms Embedded, JS-Swapped Panels

**How it works:**
- All 3 registration forms (farmer, logistics, buyer) + login form + role cards exist in `login.blade.php`
- Each form is wrapped in a `<div id="form-farmer" style="display:none">` (hidden)
- Role cards are visible by default in the slide-in panel
- User clicks a role card → JS hides the card list, shows the corresponding form
- "Back" button returns to role cards
- Leaflet map: init on first-show via `MutationObserver` or a `mapRef.invalidateSize()` call after the form becomes visible

**What stays separate:**
- Legal modal content (already AJAX-loaded today via `openLegalModal()`)

**Pros:**
- Zero page navigations, buttery smooth slide animation
- No new routes or controllers needed
- Fastest perceived performance

**Cons:**
- `login.blade.php` becomes ~800–1000 lines (big file)
- Farmer map needs lazy init handling (doable, ~10 lines of JS)

**Effort:** ~1–2 hours (mostly copy-paste + JS panel swapping logic)

---

## Option 2: AJAX-Load Forms Into Slide Panel

**How it works:**
- Role cards stay in `login.blade.php`
- Clicking a role card does `fetch('/register/role/farmer?partial=1').then(html => { panel.innerHTML = html })`
- New backend endpoint (or query param) returns the form HTML without layout/x-register-layout wrapper
- Leaflet map loads after AJAX completes

**Pros:**
- Each form stays in its own file (clean separation)
- `login.blade.php` stays small
- Still feels seamless (no page reload)

**Cons:**
- Needs a new backend route (or modification to existing `register.role` route)
- AJAX loading means a brief spinner/loading state
- Leaflet init timing is slightly trickier (wait for HTML injection + container visible)

**Effort:** ~2–3 hours (route + controller changes + JS)

---

## Option 3: Simplified Inline Registration Form

**How it works:**
- Instead of 3 role-specific forms, the slide panel shows a single generic form:
  - Name, email, phone, password, role (dropdown: Farmer / Logistics / Buyer)
- No Leaflet map, no cooperative selection
- After registration, user is prompted to complete their profile (farm location, coop, etc.)

**Pros:**
- Simplest implementation (one form, no map complexity)
- `login.blade.php` stays manageable
- Slide animation works perfectly with no delays

**Cons:**
- Less polished registration experience
- Farmers must set farm location post-registration (extra step)
- Profile completion flow needs building

**Effort:** ~1 hour (simple form + profile completion redirect)

---

## Option 4: Keep Current Implementation (Role Cards → Separate Page)

**How it works:**
- What's already deployed: slide-in panel shows role cards
- Clicking a card navigates to `register/role/{role}` (separate page)
- Breaks the slide animation illusion

**Pros:**
- Already built, zero additional work
- Each registration form is clean and isolated

**Cons:**
- Navigation breaks the seamless sliding experience
- User leaves the beautiful animated page for a basic form page

**Effort:** 0 (already done)
