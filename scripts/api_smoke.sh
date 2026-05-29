#!/usr/bin/env bash
# Full API smoke test — hits every /api/v1 endpoint and reports PASS/FAIL.
B="http://127.0.0.1:8000/api/v1"
PASS=0; FAIL=0
jqget(){ python -c "import sys,json;d=json.load(sys.stdin);print(eval(\"d$1\"))" 2>/dev/null; }

check(){ # name method path expected_code [data...] [authheader]
  local name="$1" method="$2" path="$3" exp="$4"; shift 4
  local args=(); local auth=""
  for a in "$@"; do
    if [[ "$a" == AUTH:* ]]; then auth="${a#AUTH:}"; else args+=("$a"); fi
  done
  local hdr=(-H "Accept: application/json")
  [ -n "$auth" ] && hdr+=(-H "Authorization: Bearer $auth")
  local code
  if [ "$method" = "GET" ]; then
    code=$(curl -s -o /tmp/api_out -w "%{http_code}" "${hdr[@]}" "$B$path")
  else
    code=$(curl -s -o /tmp/api_out -w "%{http_code}" -X "$method" "${hdr[@]}" "${args[@]}" "$B$path")
  fi
  if [ "$code" = "$exp" ]; then printf "  PASS  %-6s %-34s -> %s\n" "$method" "$path" "$code"; PASS=$((PASS+1));
  else printf "  FAIL  %-6s %-34s -> %s (want %s) %s\n" "$method" "$path" "$code" "$exp" "$(head -c 120 /tmp/api_out)"; FAIL=$((FAIL+1)); fi
}

echo "== AUTH =="
RND=$RANDOM$RANDOM
check "register" POST /register 201 -d "name=Flutter Tester" -d "email=ft$RND@test.com" -d "password=secret123" -d "password_confirmation=secret123" -d "phone=9800000000"
TOKEN=$(curl -s -H "Accept: application/json" -d "email=user@buleto.test" -d "password=password" "$B/login" | jqget "['token']")
check "login" POST /login 200 -d "email=user@buleto.test" -d "password=password"
check "login-bad" POST /login 401 -d "email=user@buleto.test" -d "password=wrong"
check "me" GET /me 200 "AUTH:$TOKEN"
check "me-noauth" GET /me 401

echo "== CATALOG (public) =="
check "cities" GET /cities 200
check "movies" GET /movies 200
check "movie" GET /movies/jawan 200
check "showtimes" GET /movies/jawan/showtimes 200
check "events" GET /events 200
check "event" GET /events/digital-marketing-conference-2020 200
check "sports" GET /sports 200
check "sport" GET /sports/world-cup-final 200
check "seats-showtime" GET /seats/showtime/1 200
check "seats-event" GET /seats/event/1 200
check "seats-sport" GET /seats/sport/1 200

echo "== CONTENT (public) =="
check "home" GET /home 200
check "search" GET "/search?q=jaw" 200
check "popcorn" GET /popcorn 200
check "faqs" GET /faqs 200
check "partners" GET /partners 200
check "blog" GET /blog 200
check "blog-show" GET /blog/the-future-of-movie-theaters 200
check "promo-ok" POST /promo/validate 200 -d "code=SAVE10" -d "amount=1000"
check "promo-bad" POST /promo/validate 422 -d "code=NOPE" -d "amount=1000"
check "contact" POST /contact 201 -d "name=Tester" -d "email=t@t.com" -d "subject=Hi" -d "message=Hello there"
check "newsletter" POST /newsletter 201 -d "email=news$RND@test.com"

echo "== PROFILE (auth) =="
check "profile" GET /profile 200 "AUTH:$TOKEN"
check "profile-update" PUT /profile 200 -d "name=Customer User Updated" -d "email=user@buleto.test" -d "phone=9811111111" "AUTH:$TOKEN"
check "password-wrong" PUT /profile/password 422 -d "current_password=wrong" -d "password=newsecret1" -d "password_confirmation=newsecret1" "AUTH:$TOKEN"

echo "== BOOKING (auth) =="
# reserve 2 seats on showtime 1 using high rows to avoid clashes
SEAT1="M-$((RANDOM%20+1))"; SEAT2="N-$((RANDOM%20+1))"
RESP=$(curl -s -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -d "type=showtime" -d "id=1" --data-urlencode "seats[]=$SEAT1" --data-urlencode "seats[]=$SEAT2" "$B/bookings")
BID=$(echo "$RESP" | jqget "['booking']['id']")
if [ -n "$BID" ]; then printf "  PASS  %-6s %-34s -> reserved booking %s (%s,%s)\n" "POST" "/bookings" "$BID" "$SEAT1" "$SEAT2"; PASS=$((PASS+1)); else printf "  FAIL  POST /bookings -> %s\n" "$(echo "$RESP"|head -c 160)"; FAIL=$((FAIL+1)); fi
check "pay" POST "/bookings/$BID/pay" 200 -d "method=card" "AUTH:$TOKEN"
check "bookings-list" GET /bookings 200 "AUTH:$TOKEN"
check "booking-show" GET "/bookings/$BID" 200 "AUTH:$TOKEN"

echo "== BOOKING MODS (pending: addons / promo / verify / release) =="
SEAT3="L-$((RANDOM%20+1))"
RESP2=$(curl -s -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -d "type=showtime" -d "id=1" --data-urlencode "seats[]=$SEAT3" "$B/bookings")
B2=$(echo "$RESP2" | jqget "['booking']['id']")
if [ -n "$B2" ]; then printf "  PASS  POST   /bookings (mods)                  -> %s (%s)\n" "$B2" "$SEAT3"; PASS=$((PASS+1)); else printf "  FAIL  POST /bookings (mods) -> %s\n" "$(echo "$RESP2"|head -c 140)"; FAIL=$((FAIL+1)); fi
check "addons" POST "/bookings/$B2/addons" 200 --data-urlencode "items[0][popcorn_item_id]=1" --data-urlencode "items[0][quantity]=2" "AUTH:$TOKEN"
check "apply-promo" POST "/bookings/$B2/apply-promo" 200 -d "code=SAVE10" "AUTH:$TOKEN"
check "verify-pending" POST "/bookings/$B2/verify-payment" 402 "AUTH:$TOKEN"
check "release" DELETE "/bookings/$B2/release" 200 "AUTH:$TOKEN"

echo "== FILTER LISTS + SPEAKERS =="
check "genres" GET /genres 200
check "languages" GET /languages 200
check "formats" GET /formats 200
check "event-detail" GET /events/digital-marketing-conference-2020 200

echo "== PASSWORD RESET / GOOGLE / EMAIL VERIFY =="
check "forgot" POST /password/forgot 200 -d "email=user@buleto.test"
check "reset-bad" POST /password/reset 422 -d "email=user@buleto.test" -d "code=000000" -d "password=newsecret1" -d "password_confirmation=newsecret1"
check "google-bad" POST /auth/google 401 -d "id_token=invalidtoken"
check "verify-send" POST /email/verify/send 200 "AUTH:$TOKEN"
check "verify-bad" POST /email/verify 422 -d "code=000000" "AUTH:$TOKEN"

echo "== DEVICE TOKEN (push) =="
check "device-add" POST /device-token 200 -d "token=fcm-$RND" -d "platform=android" "AUTH:$TOKEN"
check "device-del" DELETE /device-token 200 -d "token=fcm-$RND" "AUTH:$TOKEN"

check "logout" POST /logout 200 "AUTH:$TOKEN"

echo ""
echo "================  PASS: $PASS   FAIL: $FAIL  ================"
