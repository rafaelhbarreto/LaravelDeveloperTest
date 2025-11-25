# Prompt 4 – Frontend Implementation (Laravel 12 + Inertia.js + Vue 3 + TailwindCSS)

## Context

After implementing the API integration and data storage for Congress members, I need to build a web interface to visualize this data.  
The solution must use **Laravel 12 + Inertia.js + Vue 3 + TailwindCSS** to create a modern, responsive Single Page Application (SPA).

---

## Tech Stack

-   **Backend:** Laravel 12
-   **Frontend Framework:** Vue.js 3.5 (Composition API with `<script setup>`)
-   **CSS Framework:** TailwindCSS 4.0
-   **Build Tool:** Vite 7.0
-   **HTTP Client:** Axios

---

## Required Features

---

### 1. Members Listing (Index)

**Route:** `GET /congress/members`

**Requirements:**

-   ✅ Responsive table displaying member data
-   ✅ Server-side pagination (25 per page)
-   ✅ Filters:
    -   Search by name (300ms debounced)
    -   Filter by party (dropdown)
    -   Filter by state (dropdown)
-   ✅ Column sorting:
    -   Name
    -   Party
    -   State
    -   District
    -   Updated date
-   ✅ Colored badges for party:
    -   Blue = Democrat
    -   Red = Republican
-   ✅ Link to member details page
-   ✅ Empty state when there is no data

---

### 2. Member Details (Show)

**Route:** `GET /congress/members/{bioguideId}`

**Requirements:**

-   ✅ Member photo (fallback to generic avatar if missing)
-   ✅ Full information:
    -   Full name
    -   Party
    -   State
    -   District (when applicable)
    -   Updated date
-   ✅ List of terms:
    -   Chamber (House / Senate)
    -   Period (Start year → End year or `"Present"`)
    -   "Current" badge for active term
-   ✅ “Back to Members List” button
-   ✅ “View in Congress.gov API” link
-   ✅ Fully responsive layout

---

## File Structure

### Backend

```txt
app/
├── Http/
│   ├── Controllers/
│   │   └── CongressMemberController.php
│   ├── Middleware/
│   │   └── HandleInertiaRequests.php
│   └── Resources/
│       ├── MemberResource.php
│       └── MemberTermResource.php

routes/
└── web.php

bootstrap/
└── app.php  // register Inertia middleware
```

---

### Frontend

```txt
resources/
├── js/
│   ├── Components/
│   │   ├── Cards/
│   │   │   ├── MemberCard.vue
│   │   │   └── TermCard.vue
│   │   ├── Filters/
│   │   │   └── MemberFilters.vue
│   │   ├── DataTable.vue
│   │   └── Pagination.vue
│   ├── Layouts/
│   │   └── AppLayout.vue
│   ├── Pages/
│   │   └── Congress/
│   │       └── Members/
│   │           ├── Index.vue
│   │           └── Show.vue
│   ├── app.js
│   └── bootstrap.js
└── views/
    └── app.blade.php

vite.config.js
package.json
```

---

## Feature Checklist

-   [ ] Listing with pagination
-   [ ] Name filter (debounced)
-   [ ] Party filter
-   [ ] State filter
-   [ ] Column sorting
-   [ ] Colored badges for parties
-   [ ] Mobile responsiveness
-   [ ] Detail page
-   [ ] Member photo
-   [ ] Terms list
-   [ ] Functional links
-   [ ] Empty state messages
-   [ ] Loading states (Inertia progress bar)

---

## Final Notes

-   **TailwindCSS** enables consistent and fast styling
-   **Server-side pagination** avoids loading all records at once
-   **Debounce** reduces unnecessary network requests
-   **Responsiveness** ensures a good UX on all devices
