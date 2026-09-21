### Nextcloud Mail: Filters ### DON'T EDIT ###
require ["imap4flags", "copy"];
### Nextcloud Mail: Filters ### DON'T EDIT ###
# Hello, this is a test
### Nextcloud Mail: Filters ### DON'T EDIT ###
# FILTER: [{"name":"Test 13","enable":true,"operator":"allof","tests":[{"operator":"is","values":["bob@example.org"],"field":"to"}],"actions":[{"type":"addsystemflag","flag":"\\Seen"},{"type":"redirect","recipient":"alice@example.org"}],"priority":10}]
# Test 13
if address :is :all "To" ["bob@example.org"] {
	addflag "\\Seen";
	redirect :copy "alice@example.org";
}
### Nextcloud Mail: Filters ### DON'T EDIT ###
