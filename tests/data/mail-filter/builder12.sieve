### Nextcloud Mail: Filters ### DON'T EDIT ###
require ["copy"];
### Nextcloud Mail: Filters ### DON'T EDIT ###
# Hello, this is a test
### Nextcloud Mail: Filters ### DON'T EDIT ###
# FILTER: [{"name":"Test 12","enable":true,"operator":"allof","tests":[{"operator":"is","values":["bob@example.org"],"field":"to"}],"actions":[{"type":"redirect","recipient":"alice@example.org"},{"type":"stop"}],"priority":10}]
# Test 12
if address :is :all "To" ["bob@example.org"] {
	redirect :copy "alice@example.org";
	stop;
}
### Nextcloud Mail: Filters ### DON'T EDIT ###
