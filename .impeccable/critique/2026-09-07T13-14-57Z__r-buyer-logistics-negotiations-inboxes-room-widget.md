---
target: in-app messaging placement (farmer/buyer/logistics negotiations inboxes + room + widget)
total_score: 16
max_score: 40
na_heuristics: 
p0_count: 2
p1_count: 3
timestamp: 2026-09-07T13-14-57Z
slug: r-buyer-logistics-negotiations-inboxes-room-widget
---
Method: dual-agent (A: ses_f8403bb10ffeTFkJ41ysmsPkLx · B: ses_f8403ad31ffe585IUUjpWLODyt) — browser injection skipped (no browser tool; detector CLI ran).

# In-App Messaging Placement — Critique

Design Health Score: 16/40 (Poor).

| # | Heuristic | Score | Key Issue |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 2 | Widget unread badge good; no last-synced status; inbox tables stale until refresh |
| 2 | Match Real World | 1 | "Enter Room", "B2B crop price discussions", "Secure Direct Message Tunnel", "Thread #ID" jargon |
| 3 | User Control and Freedom | 2 | Chat room hides widget (layout:690); no thread switching without full page round-trip |
| 4 | Consistency | 1 | 8 names for same feature; crop inbox=table, logistics=cards; two different chat rooms |
| 5 | Error Prevention | 2 | Agree-terms confirm good; no draft/char-limit protection; silent widget failures |
| 6 | Recognition > Recall | 2 | Widget good; inbox tables force Lot# recall, no preview |
| 7 | Flexibility & Efficiency | 1 | No shortcuts, no widget search, no bulk/filter |
| 8 | Aesthetic & Minimalist | 2 | Clean styling; room page crams chat+terms+product+counterparty+map |
| 9 | Error Recovery | 2 | Widget .catch() silently shows empty state on network failure |
| 10 | Help & Documentation | 1 | No tooltips/onboarding/help |

Design Specificity: skin is HarvestHaul, bones are generic chat-widget boilerplate. Detector=0 findings (ruleset gap). Manual review found broken-class bug + 16 deprecated aliases + 7 gold-fills + 1 dark-on-dark.

Priority issues:
- P0: widget only shows crop negotiations (not haul intents); exluded from chat rooms entirely (layout:690). Merge feed + persist in rooms or add thread drawer.
- P0: crop and haul are two separate messaging systems for one transaction (finalize panel even shows hauling rate). Unify Deal Room or cross-link.
- P1: chat room is a deal workspace w/ chat attached (1048-line page); terms form + map + popovers; high-stakes Propose button styled weaker than Send.
- P1: feature named 8 different ways across surfaces + "Room #" vs "Thread #".
- P1: inboxes are 6-col data tables not conversation lists; widget:196 invalid class hover:bg-[#0E1620]/10/50 breaks light hover.
- P2: gold as solid fill (widget:34, buyer:28, room:21/162/361/478); deprecated harvest aliases ~16x.
- P3: text-[9px]/[8px] micro-type 14x; widget error-swallow fake empty state; mobile popup ~full-width no backdrop.

Personas: Alex no search/thread-switch/shortcuts; Jordan icon-only FAB no onboarding/tooltip, badge in peripheral vision; Sam missing dialog role, aria-label on badge, input labels, aria-expanded, aria-live.

Minor: polling in background tabs; dead grid-cols-3; "Secure Direct Message Tunnel" overclaim; escapeHtml x3; Leaflet link/script in body; popup close→navigate flicker.

Questions:
1. If messaging closes deals, why buried 2 clicks for farmers/flat for buyers/absent for coop-logistics, but always a FAB?
2. Why two systems for one transaction (finalize shows hauling rate)?
3. Widget hides itself in chat rooms yet is the only crop-thread entry for coop logistics — what is the attention model?
