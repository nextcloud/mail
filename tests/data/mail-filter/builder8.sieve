### Nextcloud Mail: Filters ### DON'T EDIT ###
require ["imap4flags"];
### Nextcloud Mail: Filters ### DON'T EDIT ###
# Hello, this is a test
### Nextcloud Mail: Filters ### DON'T EDIT ###
# FILTER: [{"name":"Test 1","enable":true,"operator":"allof","tests":[{"operator":"is","values":["alice@example.org","bob@example.org"],"field":"from"}],"actions":[{"type":"addflag","flag":"\\Seen"}],"priority":10}]
# Test 1
if address :is :all "From" ["alice@example.org", "bob@example.org"] {
	addflag "\\Seen";
}
### Nextcloud Mail: Filters ### DON'T EDIT ###
