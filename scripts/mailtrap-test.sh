#!/usr/bin/env bash
# Usage: MAILTRAP_USER=452a33d67df1e7 MAILTRAP_PASS=e33e03202d27f5 ./scripts/mailtrap-test.sh

echo 'Sending test email to Mailtrap...'
curl \
  --ssl-reqd \
  --url 'smtp://sandbox.smtp.mailtrap.io:2525' \
  --user "${MAILTRAP_USER}:${MAILTRAP_PASS}" \
  --mail-from from@example.com \
  --mail-rcpt to@example.com \
  --upload-file - <<'MAIL'
From: Dev Tester <from@example.com>
To: Mailtrap Inbox <to@example.com>
Subject: MyShop Mailtrap test
Content-Type: multipart/alternative; boundary="boundary-string"

--boundary-string
Content-Type: text/plain; charset="utf-8"

Hello from MyShop dev! If you see this, SMTP works.

--boundary-string
Content-Type: text/html; charset="utf-8"

<!doctype html>
<html>
  <body style="font-family: sans-serif;">
    <div style="max-width: 600px; margin: auto;">
      <h1>MyShop Mailtrap test</h1>
      <p>This is an HTML part. SMTP is configured correctly.</p>
    </div>
  </body>
</html>

--boundary-string--
MAIL
