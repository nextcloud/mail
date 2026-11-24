### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###
require "date";
require "relational";
require "vacation";
### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###

require "fileinto";

if address "From" "marketing@company.org" {
    fileinto "INBOX.marketing";
}

### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###
# DATA: {"version":1,"enabled":true,"start":"2022-09-02T00:00:00+01:00","end":"2022-09-08T23:59:00+01:00","subject":"On vacation","message":"<p style=\"margin:0;\">I'm on vacation.<\/p>","messageHtml":true}
if allof(currentdate :value "ge" "iso8601" "2022-09-01T23:00:00Z", currentdate :value "le" "iso8601" "2022-09-08T22:59:00Z") {
	if not anyof(exists "List-Id", exists "List-Unsubscribe", address :all :matches ["From", "Sender"] ["noreply@*", "no-reply@*"]) {
		vacation :days 4 :subject "On vacation" :addresses ["Test Test <test@test.org>", "Test Alias <alias@test.org>"] :mime "Content-Type: multipart/alternative; boundary=\"=_Aw-rwhnlp1s29dSaJfG9biE\"
MIME-Version: 1.0

This message is in MIME format.

--=_Aw-rwhnlp1s29dSaJfG9biE
Content-Type: text/plain; charset=utf-8

I'm on vacation.
--=_Aw-rwhnlp1s29dSaJfG9biE
Content-Type: text/html; charset=utf-8

<!DOCTYPE html>
<html><meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\"><body>
<p style=\"margin:0;\">I'm on vacation.</p>
</body></html>

--=_Aw-rwhnlp1s29dSaJfG9biE--
";
	}
}
### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###