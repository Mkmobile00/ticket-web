# Google Sign‑In Setup (Boleto — Web + App)

The code is fully built. To make Google login/signup actually work you must create
the Google credentials in **Firebase / Google Cloud** (only you can — it's tied to
your Google account). Follow these steps once.

**Your Android app details (needed below):**
- Package name: `com.buleto.buleto_app`
- Debug SHA‑1: `47:F1:24:D2:94:A6:A3:F2:47:95:1C:57:09:5D:34:E6:F0:88:A5:80`
- Debug SHA‑256: `FA:B7:79:3E:91:36:13:59:FD:E7:62:10:D4:C7:12:6B:59:AA:EB:E0:26:37:FA:CF:C7:FD:6F:72:5D:A6:27:25`

---

## 1. Create / open a Firebase project
1. Go to https://console.firebase.google.com
2. Click **Add project** (or open your existing project), follow the wizard.
   (Analytics is optional — you can skip it.)

## 2. Enable Google sign‑in
1. In the left menu: **Build → Authentication → Get started**.
2. Open the **Sign‑in method** tab.
3. Click **Google → Enable**.
4. Set a **support email** (your email) → **Save**.

## 3. Register the Android app + add SHA‑1
1. Click the **gear icon → Project settings**.
2. Under **Your apps**, click the **Android** icon (or "Add app" → Android).
3. **Android package name:** `com.buleto.buleto_app`
4. **Debug signing certificate SHA‑1:** paste
   `47:F1:24:D2:94:A6:A3:F2:47:95:1C:57:09:5D:34:E6:F0:88:A5:80`
   (If the app is already registered: Project settings → your Android app →
   **Add fingerprint** → paste the SHA‑1.)
5. Click **Register app**.
6. **Download the new `google-services.json`** → send it to me. It now contains the
   `oauth_client` entries that make Android Google sign‑in work.

> When you ship a release build, also add the **release** keystore's SHA‑1 the same way.

## 4. Get the Web Client ID
The Web client ID is what both the website and the API use to verify the token.
1. **Authentication → Sign‑in method → Google → Web SDK configuration** — copy the
   **Web client ID**, OR
2. Google Cloud Console → **APIs & Services → Credentials** → under **OAuth 2.0
   Client IDs** copy **"Web client (auto created by Google Service)"**.

It looks like: `1234567890-abcdefg.apps.googleusercontent.com`
→ **Send me this Web Client ID.**

## 5. Allow the website's domain (for the web button)
1. Google Cloud Console → **APIs & Services → Credentials** → open the **Web client**.
2. Under **Authorized JavaScript origins**, add:
   - `http://127.0.0.1:8000`
   - `http://192.168.1.78:8000`
   - your real domain later (e.g. `https://yourdomain.com`)
3. **Save** (changes can take a few minutes to apply).

## 6. Turn it on (I do this once you send the two items)
**Web:** paste the Web Client ID in **Admin → Settings → Google Client Id** → Save.
The Google button on `/sign-in` becomes the live Google flow (signup + login).

**App:** I drop in the new `google-services.json` and rebuild with:
```
flutter run --dart-define=GOOGLE_SERVER_CLIENT_ID=<web-client-id> \
            --dart-define=API_BASE_URL=http://192.168.1.78:8000/api/v1
```
The Google button on the app login screen then works.

---

## What to send me
1. The **Web Client ID** (`…apps.googleusercontent.com`)
2. The **new `google-services.json`** (after adding the SHA‑1)

That's it — I'll wire both and verify Google signup/login end‑to‑end on web and app.

## How it behaves (already coded)
- First-time Google user → **account created automatically** (signup) and marked verified.
- Returning Google user → **logged in**.
- Works on **web** (`/design-api/google`) and **app/API** (`/api/v1/auth/google`).
- The Client ID is **admin-managed** (Settings) — no code change to update it.
