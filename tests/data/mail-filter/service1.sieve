### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###
require "date";
require "relational";
require "vacation";
### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###

require "fileinto";

if allof(
    address "From" "noreply@help.nextcloud.org",
    header :contains "Subject" "[Nextcloud community]"
){
  fileinto "Community";
}

### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###
# DATA: {"version":1,"enabled":true,"start":"2024-10-08T22:00:00+00:00","subject":"Thanks for your message!","message":"I'm not here, please try again later.\u00a0"}
if currentdate :value "ge" "iso8601" "2024-10-08T22:00:00Z" {
	vacation :days 4 :subject "Thanks for your message!" :addresses ["alice@mail.internal"] "I'm not here, please try again later. ";
}
### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###
