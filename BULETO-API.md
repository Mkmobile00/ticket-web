# Buleto Mobile API (v1)

REST/JSON API for the Flutter app. Token auth via **Laravel Sanctum**.

- **Base URL (local):** `http://127.0.0.1:8000/api/v1`
  - From an Android emulator use `http://10.0.2.2:8000/api/v1` (10.0.2.2 = host machine).
  - From a real device on the same Wi‑Fi use your PC's LAN IP, e.g. `http://192.168.x.x:8000/api/v1`.
- **Format:** always send header `Accept: application/json`.
- **Auth:** after register/login you get a `token`. Send it on protected calls:
  `Authorization: Bearer {token}`
- **Currency:** amounts are numbers (e.g. `450.00`); display with `Rs `.

---

## Conventions

- **Validation errors** → HTTP `422`:
  ```json
  { "message": "The email field is required.",
    "errors": { "email": ["The email field is required."] } }
  ```
- **Unauthenticated** → `401`. **Forbidden** → `403`. **Not found** → `404`.
- **Paginated** lists return Laravel's shape: `{ data:[...], current_page, last_page, per_page, total, ... }`.

---

## 1. Auth

### POST `/register`
Body: `name`, `email`, `password`, `password_confirmation`, `phone?`
```json
// 201
{ "user": {"id":12,"name":"Asha","email":"asha@x.com","phone":null,"role":"customer","is_admin":false},
  "token": "13|abcdef..." }
```

### POST `/login`
Body: `email`, `password`
```json
// 200
{ "user": {...}, "token": "14|xyz..." }   // 401 {"message":"Invalid credentials."}
```

### GET `/me`  *(auth)*
```json
{ "user": {"id":12,"name":"Asha","email":"asha@x.com","phone":null,"role":"customer","is_admin":false} }
```

### POST `/logout`  *(auth)* — revokes the current token → `{ "message": "Logged out." }`

---

## 2. Catalog (public)

### GET `/cities`
```json
{ "data": [ {"id":3,"name":"Bengaluru","slug":"bengaluru"}, ... ] }
```

### GET `/movies`
Query: `search?`, `genre?` (slug/id), `city?` (id), `page?`. Paginated.
```json
{ "data": [ {
    "id":1,"title":"Jawan","slug":"jawan",
    "poster_image":"http://.../posters/jawan.jpg",
    "duration_minutes":169,"release_date":"2023-09-07","user_rating":4.5,"status":"now_showing",
    "genres":["Action","Thriller"],"languages":["Hindi"],"formats":["2D","3D","IMAX 2D"]
  } ],
  "current_page":1,"last_page":1,"per_page":12,"total":12 }
```

### GET `/movies/{slug}`
Full detail — adds `synopsis`, `banner_image`, `trailer_url`, `cast[]`.

### GET `/movies/{slug}/showtimes`
Query: `city?` (id), `date?` (YYYY-MM-DD). Grouped by cinema:
```json
{ "data": [ {
    "cinema": {"id":7,"name":"PVR: Forum Mall","city":"Bengaluru"},
    "showtimes": [ {"id":15,"screen":"IMAX","date":"2026-05-29","time":"19:45",
                    "language":"Hindi","format":"IMAX 2D","available_seats":362} ]
  } ] }
```

### GET `/events`  · GET `/events/{slug}`
List (paginated) + detail. Detail includes `tiers` (price sections).

### GET `/sports`  · GET `/sports/{slug}`
Same shape; sports have `matchup` ("Team A vs Team B") and `venue`.

---

## 3. Seat map

### GET `/seats/{type}/{id}`
`type` = `showtime` | `event` | `sport`. Works logged-out (then no `mine`).
```json
{ "context":"showtime:15",
  "tiers":[ {"id":1,"name":"IMAX","price":450,"rows":["A","B",...]},
            {"id":2,"name":"IMAX Prime","price":600,"rows":["H",...]} ],
  "rows":[ {"row":"A","tier":"IMAX","seats":[
            {"id":"A-1","status":"available","tier":"IMAX","price":450},
            {"id":"A-2","status":"booked","tier":"IMAX","price":450} ]} ],
  "lock_ttl":300 }
```
**status** ∈ `available` · `booked` · `locked` (held by someone else) · `mine` (held by you).
Poll this every ~10s to keep the map fresh.

---

## 4. Booking  *(auth)*

### POST `/bookings`  — reserve seats (5-min hold)
Body: `type` (showtime|event|sport), `id`, `seats[]` (e.g. `["A-1","A-2"]`, max 10)
```json
// 201
{ "message":"Seats held for 5 minutes. Pay to confirm.",
  "booking": {"id":9,"status":"pending","subject":"Jawan","showtime_id":15,
              "total_amount":1200,"qr_code":null,
              "seats":[{"seat":"N1","tier":"IMAX Prime","price":600},
                       {"seat":"N2","tier":"IMAX Prime","price":600}]},
  "payment_methods":["card","esewa","khalti"] }
```
Conflicts → `422` `{ "errors": {"seats":["Seat N-1 was just taken..."]} }`.

### POST `/bookings/{id}/pay`  — pay & confirm
Body: `method` = `card` | `esewa` | `khalti`
- **card** → confirms instantly (test gateway):
  ```json
  { "message":"Payment successful. Booking confirmed.",
    "booking": {"id":9,"status":"confirmed","qr_code":"BULETO|9|5358...",
                "qr_image":"https://api.qrserver.com/...","seats":[...]} }
  ```
- **esewa / khalti** → returns a redirect to open in a WebView:
  ```json
  { "requires_redirect":true, "gateway":"esewa",
    "redirect":"https://...", "form": {"action":"https://rc-epay.esewa.com.np/...","fields":{...}} }
  ```
  After the gateway returns to the callback, poll `GET /bookings/{id}` until `status=confirmed`.

### GET `/bookings`  — my bookings (paginated)
### GET `/bookings/{id}`  — one booking (with seats + `qr_image`)
### POST `/bookings/{id}/cancel`  — cancel & free seats → `{ "message":"Booking cancelled and seats released." }`

---

## 5. Profile  *(auth)*

- **GET `/profile`** → `{ data: {id,name,email,phone,role} }`
- **PUT `/profile`** body `name, email, phone?` → `{ message, data }`
- **PUT `/profile/password`** body `current_password, password, password_confirmation` → `{ message }` (422 if current password wrong)

---

## 6. Content & misc (public)

- **GET `/home`** — one call for the home screen:
  ```json
  { "banners":[{"title","image","link"}],
    "now_showing":[movieCard...], "events":[...], "sports":[...],
    "cities":[{"id","name","slug"}] }
  ```
- **GET `/search?q=`** (min 2 chars) → `{ movies:[...], events:[...], sports:[...] }`
- **GET `/popcorn`** → `{ data:[{id,name,description,price,image}] }`
- **GET `/faqs?page=`** → `{ data:[{question,answer,page_type}] }`
- **GET `/partners`** → `{ data:[{name,logo,url}] }`
- **GET `/blog`** (paginated) · **GET `/blog/{slug}`** → article detail
- **POST `/promo/validate`** body `code, amount` →
  ```json
  // 200 valid
  { "valid":true,"code":"SAVE10","discount":100,"final_amount":900,"message":"Promo applied." }
  // 422 invalid → { "valid":false, "message":"Invalid or expired promo code." }
  ```
- **POST `/contact`** body `name,email,subject?,message` → `201 { message }`
- **POST `/newsletter`** body `email` → `201 { message }`

---

## 7. Extra auth (password reset / Google / email verify)

- **POST `/password/forgot`** body `email` → 200 (always; emails a 6-digit code, valid 30 min)
- **POST `/password/reset`** body `email, code, password, password_confirmation` → 200 / 422 (bad code)
- **POST `/auth/google`** body `id_token` (from the `google_sign_in` plugin) → `{ user, token }` (401 if invalid)
- **POST `/email/verify/send`** *(auth)* → emails a 6-digit code
- **POST `/email/verify`** *(auth)* body `code` → marks the email verified

## 8. Filter options (public)
- **GET `/genres`** · **GET `/languages`** · **GET `/formats`** → `{ data:[{id,name,...}] }` (for filter dropdowns)

## 9. Booking modifiers *(auth, pending bookings only)*
- **POST `/bookings/{id}/addons`** body `items:[{popcorn_item_id, quantity}]` → replaces add-ons, re-totals
- **POST `/bookings/{id}/apply-promo`** body `code` → attaches promo, re-totals (`total = seats + add-ons − discount`)
- **POST `/bookings/{id}/verify-payment`** body `pidx?` → finalize an eSewa/Khalti booking after the WebView returns (402 if not yet paid)
- **DELETE `/bookings/{id}/release`** → instantly drop a pending hold & free its seats

> Order on the booking screen: reserve → (optional) addons + apply-promo → pay. For eSewa/Khalti, after the WebView returns call `verify-payment`, then poll `GET /bookings/{id}`.

## 10. Push notifications *(auth)*
- **POST `/device-token`** body `token, platform?(android|ios)` → register FCM/APNs token
- **DELETE `/device-token`** body `token` → unregister

---

## 11. Rate limits
- `/login`, `/password/*`: 5 / 10 min per IP
- `/bookings` (reserve): 10 / min per user
- `/bookings/{id}/pay`, `/verify-payment`: 5 / min per user
Exceeding → `429` with `Retry-After`.

---

## Typical Flutter flow
1. `POST /login` → store `token` (secure storage).
2. `GET /cities`, `GET /movies` → home/listing.
3. `GET /movies/{slug}/showtimes?city=` → pick a showtime.
4. `GET /seats/showtime/{id}` → render seat map (poll every 10s).
5. `POST /bookings` `{type:"showtime", id, seats:[...]}` → hold seats, start a 5-min timer.
6. `POST /bookings/{id}/pay` `{method:"card"}` → confirmed; show `qr_image`.
7. `GET /bookings` → "My Tickets".

Emails (welcome, login alert, booking confirmation + QR, cancellation) are sent server-side automatically.

## Dart example
```dart
final res = await http.post(
  Uri.parse('$base/login'),
  headers: {'Accept': 'application/json'},
  body: {'email': email, 'password': password},
);
final token = jsonDecode(res.body)['token'];
// authed call:
await http.get(Uri.parse('$base/bookings'),
  headers: {'Accept':'application/json','Authorization':'Bearer $token'});
```
