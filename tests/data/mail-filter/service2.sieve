### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###
require "date";
require "relational";
require "vacation";
### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###
### Nextcloud Mail: Filters ### DON'T EDIT ###
require ["imap4flags"];
### Nextcloud Mail: Filters ### DON'T EDIT ###

require "fileinto";

if allof(
    address "From" "noreply@help.nextcloud.org",
    header :contains "Subject" "[Nextcloud community]"
){
  fileinto "Community";
}

### Nextcloud Mail: Filters ### DON'T EDIT ###
# FILTER: [{"name":"Add flag for emails with subject Hello","enable":true,"operator":"allof","tests":[{"operator":"contains","values":["Hello"],"field":"subject"}],"actions":[{"type":"addflag","flag":"Test 123"}],"priority":10},{"name":"Add flag for emails with subject World","enable":true,"operator":"allof","tests":[{"operator":"contains","values":["World"],"field":"subject"}],"actions":[{"type":"addflag","flag":"Test 456"}],"priority":20}]
# Add flag for emails with subject Hello
if header :contains "Subject" ["Hello"] {
	addflag "$test_123";
}
# Add flag for emails with subject World
if header :contains "Subject" ["World"] {
	addflag "$test_456";
}
### Nextcloud Mail: Filters ### DON'T EDIT ###
### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###
# DATA: {"version":1,"enabled":true,"start":"2024-10-08T22:00:00+00:00","subject":"Thanks for your message!","message":"I'm not here, please try again later.\u00a0"}
if currentdate :value "ge" "iso8601" "2024-10-08T22:00:00Z" {
	vacation :days 4 :subject "Thanks for your message!" :addresses ["alice@mail.internal"] "I'm not here, please try again later. ";
}
### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###
