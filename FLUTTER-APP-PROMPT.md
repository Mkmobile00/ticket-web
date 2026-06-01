# Buleto — Flutter App Build Prompt

Paste this whole file into an AI coding assistant (Cursor, Claude, Copilot Chat, etc.) to scaffold and build the Flutter mobile app for the **Buleto** ticket-booking platform. The backend (Laravel + Sanctum REST API) is already built and live; this app only consumes it.

---

## 0) MASTER CONTEXT (paste first)

```
You are building "Buleto", a Flutter mobile app for booking tickets to MOVIES,
EVENTS and SPORTS (BookMyShow-style, India market, currency = Rs / INR).

The backend already exists: a Laravel 13 REST API secured with Laravel Sanctum
(Bearer tokens). I will give you the full API contract. Do NOT build a backend —
only the Flutter client that calls these endpoints.

Requirements for all code:
- Flutter 3.x (stable), Dart 3, null-safety.
- State management: Riverpod (flutter_riverpod) — or Provider if simpler.
- Networking: dio with an auth interceptor that injects the Bearer token.
- Secure token storage: flutter_secure_storage.
- Routing: go_router.
- JSON: model classes with fromJson/toJson (no codegen required, but json_serializable is fine).
- Clean folder structure (feature-first). Handle loading / empty / error states everywhere.
- Currency is displayed as "Rs {amount}". Amounts come as numbers.
- Dark cinematic theme (deep navy background, red→orange accent #FF5046 → #FF8A3D).
- Mobile-first, responsive, smooth animations.

Build incrementally, one feature at a time, and show me the files for each step.
```

---

## 1) API CONNECTION

- **Base URL:** `http://10.0.2.2:8000/api/v1` (Android emulator → host machine)
  - iOS simulator: `http://127.0.0.1:8000/api/v1`
  - Real device (same Wi‑Fi): `http://<PC-LAN-IP>:8000/api/v1`
  - Production: `https://<your-domain>/api/v1`
- **Headers on every request:** `Accept: application/json`
- **Auth:** after `register`/`login`/`auth/google` you receive `{ user, token }`.
  Store `token` securely and send `Authorization: Bearer {token}` on protected calls.
- **Errors:** `401` unauthenticated, `403` forbidden, `404` not found,
  `422` validation `{ "message", "errors": { field: [..] } }`, `429` rate-limited.
- **Pagination:** list endpoints return `{ data:[...], current_page, last_page, per_page, total }`.

> The complete request/response reference is in **`BULETO-API.md`** (same repo). Use it for exact field names.

### Endpoint map (45 endpoints)
```
AUTH        POST   /register {name,email,password,password_confirmation,phone?}
            POST   /login {email,password}
            POST   /auth/google {id_token}
            POST   /password/forgot {email}
            POST   /password/reset {email,code,password,password_confirmation}
            GET    /me                                    (auth)
            POST   /logout                                (auth)
            POST   /email/verify/send                     (auth)
            POST   /email/verify {code}                   (auth)

CATALOG     GET    /cities
            GET    /genres | /languages | /formats
            GET    /movies?search=&genre=&city=&page=
            GET    /movies/{slug}
            GET    /movies/{slug}/showtimes?city=&date=
            GET    /events | /events/{slug}
            GET    /sports | /sports/{slug}
            GET    /seats/{type}/{id}     type=showtime|event|sport

CONTENT     GET    /home   (banners + now_showing + events + sports + cities)
            GET    /search?q=
            GET    /popcorn
            GET    /faqs | /partners | /blog | /blog/{slug}
            POST   /promo/validate {code,amount}
            POST   /contact {name,email,subject?,message}
            POST   /newsletter {email}

PROFILE     GET    /profile                               (auth)
            PUT    /profile {name,email,phone?}           (auth)
            PUT    /profile/password {current_password,password,password_confirmation}  (auth)
            POST   /device-token {token,platform?}        (auth, FCM)
            DELETE /device-token {token}                  (auth)

BOOKING     GET    /bookings                              (auth)
            POST   /bookings {type,id,seats:[]}           (auth)  reserve 5-min hold
            GET    /bookings/{id}                         (auth)
            POST   /bookings/{id}/addons {items:[{popcorn_item_id,quantity}]}  (auth)
            POST   /bookings/{id}/apply-promo {code}      (auth)
            POST   /bookings/{id}/pay {method}            (auth)  method=card|esewa|khalti
            POST   /bookings/{id}/verify-payment {pidx?}  (auth)
            POST   /bookings/{id}/cancel                  (auth)
            DELETE /bookings/{id}/release                 (auth)
```

### Seat map response (key for the seat screen)
```json
GET /seats/showtime/15
{
  "context": "showtime:15",
  "tiers": [ {"id":1,"name":"IMAX","price":450,"rows":["A","B",...]} ],
  "rows": [ {"row":"A","tier":"IMAX","seats":[
      {"id":"A-1","status":"available","tier":"IMAX","price":450},
      {"id":"A-2","status":"booked","tier":"IMAX","price":450}
  ]} ],
  "lock_ttl": 300
}
```
`status` ∈ `available` (free) · `booked` (sold, red) · `locked` (someone else, yellow) · `mine` (you, green). **Poll this endpoint every 10s** while the seat screen is open.

### Booking object
```json
{ "id":9,"status":"pending|confirmed|cancelled","subject":"Jawan","showtime_id":15,
  "total_amount":1200,"payment_method":"card","qr_code":"BULETO|9|...",
  "qr_image":"https://api.qrserver.com/...&data=BULETO|9|...",
  "booked_at":"2026-05-29T...","seats":[{"seat":"N1","tier":"IMAX Prime","price":600}] }
```

---

## 2) PACKAGES (pubspec)
```yaml
dependencies:
  flutter_riverpod: ^2.5.0
  dio: ^5.4.0
  go_router: ^14.0.0
  flutter_secure_storage: ^9.0.0
  cached_network_image: ^3.3.0
  intl: ^0.19.0
  qr_flutter: ^4.1.0          # render QR locally (or just show qr_image URL)
  webview_flutter: ^4.7.0     # eSewa/Khalti payment pages
  google_sign_in: ^6.2.0      # Google login -> id_token
  firebase_core: ^2.30.0      # push (optional)
  firebase_messaging: ^14.9.0 # push (optional)
  shimmer: ^3.0.0             # loading skeletons
```

---

## 3) FOLDER STRUCTURE
```
lib/
  main.dart
  app.dart                      // MaterialApp.router, theme
  core/
    api/dio_client.dart         // Dio + auth interceptor + base URL
    api/api_endpoints.dart
    theme/app_theme.dart        // dark theme, colors, Rs formatter
    router/app_router.dart      // go_router
    storage/token_store.dart    // secure storage
  models/                       // user, movie, showtime, seat, booking, event, sport, ...
  features/
    auth/        (login, register, forgot_password, splash)
    home/        (home feed, city selector)
    movies/      (list, detail, showtimes)
    events/      (list, detail)
    sports/      (list, detail)
    seats/       (seat_map screen + seat widget + polling)
    checkout/    (summary, addons, promo, payment, webview)
    bookings/    (my bookings, ticket/QR)
    account/     (profile, change password, settings)
    common/      (widgets: loaders, error views, buttons)
  providers/                    // riverpod providers (auth, catalog, booking...)
```

---

## 4) SCREENS & NAVIGATION

1. **Splash** → check secure-stored token → `/home` (logged in) or `/login`.
2. **Auth:** Login, Register, Forgot Password (email → OTP code → new password), Google sign-in button.
3. **Home:** city selector (persist chosen city), banner carousel, "Now Showing" grid, Events row, Sports row, search bar. Data from `GET /home`.
4. **Search:** live results from `GET /search?q=` (debounced).
5. **Movie list:** filters (city/genre/language from `/genres` `/languages`), pagination.
6. **Movie detail:** poster, rating, genres/languages/formats, synopsis, cast, "Book Tickets".
7. **Showtimes:** `GET /movies/{slug}/showtimes?city=` grouped by cinema; pick a showtime.
8. **Events / Sports:** list + detail (detail has `tiers` and, for events, `speakers`); "Book Tickets".
9. **Seat map (the core screen):**
   - `GET /seats/{type}/{id}`; render rows of seats colored by status; a "screen/stage" graphic on top; tier price legend.
   - Tap to select (max 10). Show running total in Rs.
   - Poll status every 10s; never override the user's own selection.
   - "Proceed" → `POST /bookings {type,id,seats}` → start a **5-minute countdown**.
10. **Checkout:** booking summary + seats; optional **Add-ons** (`GET /popcorn` → `POST /bookings/{id}/addons`); **Promo** (`POST /bookings/{id}/apply-promo`); the booking total auto-updates; 5-min hold timer (on expiry → `DELETE /bookings/{id}/release` and go back).
11. **Payment:**
    - `POST /bookings/{id}/pay {method}`.
    - `method=card` → response is confirmed → go to Ticket.
    - `method=esewa|khalti` → response has `redirect`/`form` → open in **WebView**; on return call `POST /bookings/{id}/verify-payment`; then poll `GET /bookings/{id}` until `confirmed`.
12. **Ticket:** show `qr_image` (or render `qr_code` with qr_flutter), movie/cinema/seats/total, "Add to wallet"/share.
13. **My Bookings:** `GET /bookings`; open ticket; cancel (`POST /bookings/{id}/cancel`).
14. **Account:** profile (`GET/PUT /profile`), change password, email verification (send/verify), logout. Register FCM token via `POST /device-token` after login.

---

## 5) KEY LOGIC TO IMPLEMENT

- **Auth interceptor:** attach Bearer token; on `401` clear token + route to `/login`.
- **Seat hold timer:** when a pending booking is created, run a 300s countdown; on expiry call release and return to the seat map.
- **Optimistic seat selection** with server reconciliation via the 10s poll.
- **Promo:** before applying to a booking you can preview with `POST /promo/validate {code, amount}`; to actually apply use `POST /bookings/{id}/apply-promo`.
- **Google sign-in:** `google_sign_in` → `googleAuth.idToken` → `POST /auth/google {id_token}` → store returned token.
- **Push:** init firebase_messaging, get FCM token, `POST /device-token`. Handle foreground/background messages.
- **Rs formatting:** `String rs(num v) => 'Rs ${NumberFormat("#,##0.00","en_IN").format(v)}';`

---

## 6) THEME
- Background `#0D0F12` / surfaces `#141A2B`; text light grey `#CFD4DB`.
- Accent gradient `#FF5046 → #FF8A3D` (buttons, highlights, selected seat).
- Seat colors: available `#3A4658`, mine/selected `#2F9E6F`, locked `#E7B400`, booked `#C0392B`.
- Rounded cards (radius 12–14), soft shadows, poster aspect ratio 2:3.

---

## 7) BUILD ORDER (give me these as separate steps)

1. Project scaffold: theme, dio client + interceptor, secure token store, go_router, splash.
2. Auth: login, register, forgot-password, google sign-in, `/me`, logout.
3. Home feed (`/home`) + city selector + search.
4. Movies: list (filters/pagination) + detail + showtimes.
5. Events & Sports: list + detail.
6. **Seat map** screen (the most important): rendering, selection, 10s polling, reserve.
7. Checkout: summary, add-ons, promo, 5-min timer.
8. Payment: card (instant) + eSewa/Khalti WebView + verify-payment.
9. Ticket (QR) + My Bookings (list/cancel).
10. Account: profile, change password, email verify, FCM device token.

For each step output the full Dart files and tell me where they go.

---

## 8) TEST CREDENTIALS (dev)
- Customer: `user@buleto.test` / `password`
- Make sure the Laravel server is running (`php artisan serve`) and MySQL is up before testing the app.
- Quick API check from the app machine: `GET http://10.0.2.2:8000/api/v1/home`.
```
