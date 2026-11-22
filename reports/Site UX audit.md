# Snow Goose Kennels: site and admin audit

Audit date: 2026-11-22. Branch audited: `akc-forms` (all features to date), against the demo database.

## How this was done

- Every admin page and every public page was loaded in a real browser at **desktop (1280 px)** and **phone (390 px)** widths: 29 pages × 2 = 58 loads.
- Each load was checked with **axe-core** (WCAG 2.0 A/AA plus best practices), for horizontal overflow, console errors, tap-target size, heading structure, page height and load time.
- Screenshots of the key screens were reviewed by eye, including the dark mode and the three Lab colour themes.
- The server response headers, cookies, error pages, session settings and built asset sizes were checked.
- The code was read where a finding needed an explanation.

Nothing was changed. Everything below is a recommendation.

## The short version

**What is working well.** The look is warm and consistent, the type is good, nothing scrolls sideways on any page at phone width, pages load in about a second, and there are no application errors. The help system exists and the vocabulary is plain. The public site is fast, private data is not leaking, and uploaded papers are only reachable by a logged-in owner.

**What holds back ease of use, in order of importance:**

1. The two big edit pages (litter and dog) are **very long**, with no way to jump around and no single Save. The litter page is 10,386 px tall on a desktop and **18,487 px on a phone**.
2. On a phone, **navigation is hidden behind a tiny icon** in the top corner. There is no bottom bar.
3. The **help panels misbehave**: they pop open on the first visit to *every different dog or litter*, and on edit pages they are titled "Adding a dog".
4. Many **form controls have no accessible name** (49 critical axe hits across the admin). Screen readers and voice control cannot tell the boxes apart.
5. The login session is **2 hours**, "Remember me" starts off, and there are **no security headers** and **no branded error pages**.
6. The **dashboard** is getting noisy, and its summary cards are not clickable.

## Findings

Severity: **High** = fix soon; **Medium** = worth doing next; **Low** = polish.

### A. Navigation and structure

| # | Finding | Evidence | Recommendation | Sev. |
|---|---|---|---|---|
| A1 | **Phone navigation is hidden.** The menu is behind a small icon top-left, which is the hardest place to reach with a thumb. Her main device is a phone. | Phone screenshots of every admin page | Add a bottom tab bar on phones: Home, Litters, Dogs, Waitlist, More. "More" opens the rest. Keep the sidebar for desktop. | High |
| A2 | **Edit pages are walls of cards.** The litter page has about 14 sections; the dog page about 12. | Litter 10,386 px desktop / 18,487 px phone; dog 5,981 / 8,674; photo library 11,968 px on a phone | Put a short "On this page" jump bar at the top (Basics · Family · Papers · Health · Photos…) that sticks while scrolling. Collapse rarely used sections by default (papers, heat cycles, achievements). On phones, show one section at a time. | High |
| A3 | **No global Save.** The page has one big form plus many small forms (each puppy, each health record). It is not obvious what is saved. | Litter and dog pages | When the main form has changes, show a sticky "Save changes / Discard" bar at the bottom of the screen. Keep the small forms but label them clearly. | High |
| A4 | **Destructive buttons sit at the top**, in red, beside the page title, so they are easy to hit by accident on a phone. | "Archive dog", "Archive litter" | Move to a quiet "Archive this dog" section at the bottom of the page. | Medium |
| A5 | **Sidebar organisation.** Eleven items in one flat list under a meaningless "Platform" label, in an order that grew by accident. | Sidebar | Group them: *Every day* (Dashboard, Waitlist, Contacts) · *Dogs & litters* (Dogs, Litters, Pairing planner) · *Website* (Photos, Happy families, Website text) · *Records* (Records). Replace the "Platform" label. | Medium |
| A6 | **Breadcrumbs stop at the section.** A dog's page says "Dogs", not "Dogs › Bella". | Header bar | Add the dog, litter or contact name as the last crumb. | Low |
| A7 | **No search.** Finding a puppy, a buyer or a dog means browsing three lists. | n/a | A single "Find…" box at the top (and on phones) that searches dogs, litters, puppies, contacts and the waitlist. | Medium |
| A8 | **No "needs you" signal in the menu.** New website applications only show on the dashboard. | n/a | Small count badges on Waitlist (new applications) and Dashboard (items needing attention). | Medium |
| A9 | **Dashboard stat cards are not links.** "13 Dogs", "6 on the waitlist" look tappable and are not. | Dashboard | Make them links to the matching page. | Low |

### B. Help, guidance and wording

| # | Finding | Evidence | Recommendation | Sev. |
|---|---|---|---|---|
| B1 | **Bug: help panels open for every dog and litter.** The "seen it" memory is keyed by the page *title*, which on an edit page is the dog's or litter's name. Each different dog opens a fresh panel. | `PageHelp` is given the title as its id | Key it by page type (e.g. "dog-edit"), not by name. | High |
| B2 | **Edit pages say "Adding a dog"** even when editing an existing dog (the help title is fixed). | Dog page screenshot | Use "About this page" or switch the title between add and edit. | High |
| B3 | **Help panels are long and open by default**, pushing the form below the fold (the litter panel is 7 bullets; on a phone it fills the first screen). | Phone screenshot of a litter | Start collapsed everywhere except one short first-run welcome on the dashboard. Keep each panel to 3 bullets and link to the full Help page for more. | High |
| B4 | **The Help page has about 20 how-tos and 40 glossary terms and no way to search or jump.** | Help page: 4,481 px desktop, 6,459 px phone | Add a search box, a contents list, and deep links from page help ("See: Fill in the AKC litter application"). | Medium |
| B5 | **No "what next?" prompts.** After creating a litter, nothing says "now add the puppies". | n/a | A short next-step line at the top of a new record. | Medium |
| B6 | **Wording is mostly good,** but a few terms are insider shorthand: "pick order", "Needs attention" items such as "Heartworm preventive due for Maple". | Dashboard | Keep the tooltips; show a one-line explanation under the first use of each term. | Low |
| B7 | **Success and error messages disappear fast** (toasts). A phone user who looks away misses them. | n/a | Keep errors on screen until dismissed; keep the red box on the field (already done). | Low |

### C. Forms and data entry

| # | Finding | Evidence | Recommendation | Sev. |
|---|---|---|---|---|
| C1 | **Form controls without accessible names.** Dropdowns and some inputs are not tied to their labels. | axe: `select-name` (critical) ×40 on the litter page, ×6 on the dog page; `label` (critical) ×20 and ×5 | Fix once in the shared `Field`/`NativeSelect` components so every form benefits. | High |
| C2 | **The pedigree chart is marked up as a table without rows.** | axe: `aria-required-parent` ×15, `aria-required-children` on the dog page and the public dog page | Mark it up as a list, or give it real rows. | High |
| C3 | **Many small tap targets.** 129 controls under 36 px on the litter page (desktop view), 89 on a phone; 67 on the Records page. | Tap-target scan | Give icon buttons and inline links a 44 px hit area on phones. | Medium |
| C4 | **Money fields are number inputs** with spinner arrows, which is awkward on phones and easy to scroll-change. | Litter, waitlist, puppy forms | Use plain text fields with a numeric keypad and a "$" prefix. | Low |
| C5 | **Deleting uses the browser's grey confirm box.** | `ConfirmDelete` | A branded dialog that names what will happen, with the safer button on the left. | Low |
| C6 | **No undo.** | n/a | After a remove or archive, show "Undo" in the message for 10 seconds. | Low |

### D. Visual design

| # | Finding | Evidence | Recommendation | Sev. |
|---|---|---|---|---|
| D1 | **The overall look is strong:** warm paper texture, serif headings, calm cards, good dark mode. All three Lab themes work on the public site and admin. | Screenshots | Keep. | n/a |
| D2 | **Red text fails contrast.** The red used for "Archive", "Delete" and warnings is #e7000b on a cream background: 4.19:1; the minimum is 4.5. | axe `color-contrast` | Darken the red one step. | Medium |
| D3 | **Small grey "(recommended)" chips and fine print are faint.** | axe: 3.28:1 on the health-test chips | Use full opacity for the text on tinted chips. | Medium |
| D4 | **Everything on a card has the same weight.** The most important action on a page (Save) looks like all the other buttons, and sits at the bottom. | Dog and litter pages | See A3: a sticky Save bar. | Medium |
| D5 | **The photo library is a single column of big cards on a phone**, so 48 photos is an 11,968 px scroll. | Phone screenshot | Two small tiles per row on phones; tap a tile to see its details. | Medium |
| D6 | **The dashboard's "Needs attention" is noisy.** The same recurring item (heartworm medicine) appears once per dog. | Dashboard | Group recurring adult-dog items ("Heartworm due for 5 dogs"), put overdue first, and let her dismiss or snooze an item. | Medium |
| D7 | **The "AKC checklist" and "Readiness" lists are plain.** | Litter and Records pages | A coloured progress bar ("5 of 8 done") would make them quicker to read. | Low |

### E. Public site

| # | Finding | Evidence | Recommendation | Sev. |
|---|---|---|---|---|
| E1 | **Photo thumbnails are buttons with no name.** | axe `button-name` ×7 on the gallery, ×2 on dog pages | Give each an accessible name ("Show photo 3 of 8"). | High |
| E2 | **Definition lists are malformed** on the home page and the litter cards. | axe `definition-list` ×2 | Group each term with its value properly. | Medium |
| E3 | **The litter photo strip scrolls sideways but cannot be reached by keyboard** (phone view). | axe `scrollable-region-focusable` | Make the strip focusable. | Medium |
| E4 | **Gallery images repeat their caption as the alt text** next to the same caption. | axe `image-redundant-alt` ×13 | Use an empty alt when the caption is shown beside it. | Low |
| E5 | **The 404 page is the unstyled Laravel default.** | `/nope` | A branded "We can't find that page" page with a link home, plus matching 403, 419, 500 and maintenance pages. | Medium |
| E6 | **Share previews and search engines only see the generic tags,** because page titles and descriptions are set by JavaScript. | Earlier audit | Server-side rendering is the proper fix, if search traffic matters. | Low |

### F. Security, sessions and reliability

| # | Finding | Evidence | Recommendation | Sev. |
|---|---|---|---|---|
| F1 | **No security headers.** No X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy or HSTS. | `curl -I` on the home page | Add one small middleware (and HSTS once the site is on https). A Content-Security-Policy can follow. | High |
| F2 | **The login lasts 2 hours and "Remember me" is off.** Someone who goes away mid-form loses their work and has to log in again. | `SESSION_LIFETIME=120`; login screenshot | Make the session about 8 hours, tick "Remember me" by default, and warn before it expires. | High |
| F3 | **Cookies are fine** (HttpOnly, SameSite=Lax); on the live server add `SESSION_SECURE_COOKIE=true`. | Response headers | Add to the Forge environment. | Medium |
| F4 | **The login screen is generic** ("Log in to your account"). | Screenshot | Show the kennel name and logo, and say what to do if the password is forgotten (reset needs real mail). | Low |
| F5 | **A console error appears on phone admin pages:** "Hydration completed but contains mismatches". Harmless to users, but it signals the sidebar renders differently on the server and the browser. | 12 phone pages | Fix the phone/desktop detection in the sidebar. | Low |

### G. Performance

- Load times were about **0.8 to 1.2 s** for every page on a local server. No page is slow.
- The built site is 1.9 MB. The main script is 238 KB, the shared bundle 247 KB and the stylesheet 122 KB. The 428 KB script is the PDF filler, which loads only when she taps the AKC form button.
- Nothing needs optimising now. If the photo library grows, add a small-tile view (D5) so a phone does not load 48 large thumbnails.

## Page by page (owner's view)

- **Login.** Clean. Needs the kennel name, "Remember me" on, and a longer session (F2, F4).
- **Dashboard.** Good start, but it is becoming a long list. Priorities: group the noise (D6), link the cards (A9), add a quick-action row (Add photos, Weigh puppies, Add a dog), and show the first-run welcome only once.
- **Dogs list.** Clear grouping (ours / other kennels / archived). Add a search box and a sort option when there are more than ten dogs.
- **Dog page.** Too long (A2). The first screen is a large help panel, then the form. Recommended order: Basics → Family tree → Health → Papers → Titles → AKC → Archive.
- **Litters list.** Clear. Add each litter's most urgent next step ("Send AKC application by Apr 24") on its card.
- **Litter page.** The longest page in the system (10k–18k px). It needs the jump bar, collapsible puppies (a compact one-line puppy row that expands), and the sticky Save bar.
- **Waitlist.** Easy to follow. Add a "reply" shortcut (call / email) next to each person, and count new applications in the menu.
- **Contacts.** Good. Add a one-tap "Call" on phone lists.
- **Photos.** Powerful but long on a phone (D5). The album chips are a good idea; on a phone make them a horizontal scroller.
- **Records.** The readiness list is useful. Move the spreadsheet downloads under a "More" heading, and put the "Print" buttons first.
- **Pairing planner.** Works well and reads clearly. Add a short example result the first time.
- **Website text.** Straightforward. Add a live preview link ("See how this looks") next to each section.
- **Help.** Complete but long. Search and a contents list would fix most of it (B4).

## Suggested order of work

**Round 1: quick wins (about a day).**
1. Fix the help-panel bug and make panels start collapsed (B1, B2, B3).
2. Label every control properly in the shared components, and fix the pedigree markup (C1, C2).
3. Security headers, longer session, "Remember me" on (F1, F2).
4. Darken the red, strengthen the faint chips (D2, D3).
5. Name the photo buttons on the public site, fix the definition lists and the scrolling strip (E1–E3).
6. Branded error pages (E5).
7. Move "Archive" to the bottom of the page (A4). Make the dashboard cards links (A9).

**Round 2: ease of use (a few days).**
1. Phone bottom navigation (A1).
2. "On this page" jump bar and collapsible sections on the dog and litter pages (A2).
3. Sticky Save bar (A3).
4. Group the sidebar, add count badges, add the kennel name to the login (A5, A8, F4).
5. Group and snooze the dashboard attention items (D6).
6. Compact two-up photo grid on phones (D5).

**Round 3: nice to have.**
Global search (A7), searchable Help with deep links (B4), in-app confirm dialogs and undo (C5, C6), money fields (C4), tap-target sizes (C3), server-side rendering for search previews (E6).

## Appendix: automated results

Violations of the "region" best-practice rule (content outside landmarks) appear on every admin page and are omitted here. Tap-target counts are controls under 36 px.

| Screen | Area | Page | Height | Small targets | axe violations (other than "region") |
|---|---|---|---|---|---|
| Desktop | admin | dash | 1,362 px | 14 | — |
| Desktop | admin | dogs | 1,252 px | 15 | — |
| Desktop | admin | dog | 5,981 px | 46 | aria-required-children ×1, aria-required-parent ×15, color-contrast ×14, label ×5, select-name ×6 |
| Desktop | admin | dog-new | 2,034 px | 24 | select-name ×6 |
| Desktop | admin | litters | 962 px | 15 | — |
| Desktop | admin | litter | 10,386 px | 129 | color-contrast ×1, label ×20, select-name ×40 |
| Desktop | admin | litter-new | 2,082 px | 27 | select-name ×4 |
| Desktop | admin | akc-app | 1,770 px | 30 | — |
| Desktop | admin | waitlist | 1,539 px | 15 | — |
| Desktop | admin | waitlist-new | 1,278 px | 20 | select-name ×5 |
| Desktop | admin | waitlist-edit | 1,346 px | 20 | color-contrast ×1, select-name ×6 |
| Desktop | admin | contacts | 1,620 px | 15 | select-name ×1 |
| Desktop | admin | contact | 1,956 px | 23 | color-contrast ×1, select-name ×1 |
| Desktop | admin | planner | 1,310 px | 17 | select-name ×2 |
| Desktop | admin | photos | 4,367 px | 49 | select-name ×2 |
| Desktop | admin | records | 2,753 px | 67 | — |
| Desktop | admin | testimonials | 1,384 px | 19 | label ×1 |
| Desktop | admin | site | 2,894 px | 19 | — |
| Desktop | admin | help | 4,481 px | 34 | — |
| Desktop | admin | settings | 900 px | 13 | color-contrast ×2 |
| Desktop | public | home | 5,183 px | 19 | definition-list ×2 |
| Desktop | public | litters | 4,649 px | 19 | definition-list ×2 |
| Desktop | public | dogs | 1,969 px | 7 | — |
| Desktop | public | dogshow | 1,634 px | 11 | aria-required-children ×1, aria-required-parent ×15, button-name ×2 |
| Desktop | public | gallery | 1,934 px | 7 | button-name ×7, image-redundant-alt ×13 |
| Desktop | public | how | 1,544 px | 10 | — |
| Desktop | public | families | 972 px | 8 | — |
| Desktop | public | apply | 1,616 px | 8 | select-name ×3 |
| Desktop | public | contact | 900 px | 10 | — |
| Phone | admin | dash | 2,592 px | 2 | — |
| Phone | admin | dogs | 2,562 px | 3 | — |
| Phone | admin | dog | 8,674 px | 34 | aria-required-children ×1, aria-required-parent ×15, color-contrast ×4, label ×5, select-name ×6 |
| Phone | admin | dog-new | 3,024 px | 12 | select-name ×6 |
| Phone | admin | litters | 1,656 px | 3 | — |
| Phone | admin | litter | 18,487 px | 89 | color-contrast ×1, label ×20, select-name ×40 |
| Phone | admin | litter-new | 3,644 px | 15 | select-name ×4 |
| Phone | admin | akc-app | 2,286 px | 18 | — |
| Phone | admin | waitlist | 1,855 px | 3 | — |
| Phone | admin | waitlist-new | 1,646 px | 8 | select-name ×5 |
| Phone | admin | waitlist-edit | 1,714 px | 8 | color-contrast ×1, select-name ×6 |
| Phone | admin | contacts | 2,252 px | 3 | select-name ×1 |
| Phone | admin | contact | 2,408 px | 11 | color-contrast ×1, select-name ×1 |
| Phone | admin | planner | 1,838 px | 5 | select-name ×2 |
| Phone | admin | photos | 11,968 px | 37 | select-name ×2 |
| Phone | admin | records | 4,029 px | 53 | — |
| Phone | admin | testimonials | 1,740 px | 7 | label ×1 |
| Phone | admin | site | 3,832 px | 7 | — |
| Phone | admin | help | 6,459 px | 18 | — |
| Phone | admin | settings | 907 px | 1 | color-contrast ×2 |
| Phone | public | home | 7,323 px | 19 | definition-list ×2, scrollable-region-focusable ×1 |
| Phone | public | litters | 6,014 px | 19 | definition-list ×2, scrollable-region-focusable ×1 |
| Phone | public | dogs | 2,233 px | 7 | — |
| Phone | public | dogshow | 2,171 px | 11 | aria-required-children ×1, aria-required-parent ×15, button-name ×2 |
| Phone | public | gallery | 2,465 px | 7 | button-name ×7, image-redundant-alt ×13 |
| Phone | public | how | 1,810 px | 10 | — |
| Phone | public | families | 1,481 px | 8 | — |
| Phone | public | apply | 2,140 px | 8 | select-name ×3 |
| Phone | public | contact | 878 px | 10 | — |
