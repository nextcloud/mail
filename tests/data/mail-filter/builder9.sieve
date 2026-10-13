### Nextcloud Mail: Filters ### DON'T EDIT ###
require ["copy"];
### Nextcloud Mail: Filters ### DON'T EDIT ###
# Hello, this is a test
### Nextcloud Mail: Filters ### DON'T EDIT ###
# FILTER: [{"name":"Test 9","enable":true,"operator":"allof","tests":[{"operator":"is","values":["bob@example.org"],"field":"to"}],"actions":[{"type":"redirect","recipient":"alice@example.org"}],"priority":10}]
# Test 9
if address :is :all "To" ["bob@example.org"] {
	redirect :copy "alice@example.org";
}
### Nextcloud Mail: Filters ### DON'T EDIT ###
