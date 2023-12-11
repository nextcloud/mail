### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###
require "date";
require "relational";
require "vacation";
require "variables";
### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###

require "fileinto";

if address "From" "marketing@company.org" {
    fileinto "INBOX.marketing";
}

### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###
# DATA: {"version":1,"enabled":true,"start":"2022-09-02T00:00:00+01:00","end":"2022-09-08T23:59:00+01:00","subject":"Re: ${subject}","message":"I'm on vacation."}
set "subject" "";
if header :matches "subject" "*" {
	set "subject" "${1}";
}
if allof(currentdate :value "ge" "iso8601" "2022-09-01T23:00:00Z", currentdate :value "le" "iso8601" "2022-09-08T22:59:00Z") {
	vacation :days 4 :subject "Re: ${subject}" :addresses ["Test Test <test@test.org>", "Test Alias <alias@test.org>"] "I'm on vacation.";
}
### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###