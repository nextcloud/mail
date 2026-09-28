### Nextcloud Mail: Filters ### DON'T EDIT ###
require ["copy", "fileinto"];
### Nextcloud Mail: Filters ### DON'T EDIT ###
# Hello, this is a test
### Nextcloud Mail: Filters ### DON'T EDIT ###
# FILTER: [{"name":"Test 11","enable":true,"operator":"allof","tests":[{"operator":"is","values":["bob@example.org"],"field":"to"}],"actions":[{"type":"redirect","recipient":"alice@example.org"},{"type":"fileinto","mailbox":"Trash"}],"priority":10}]
# Test 11
if address :is :all "To" ["bob@example.org"] {
	redirect :copy "alice@example.org";
	fileinto "Trash";
}
### Nextcloud Mail: Filters ### DON'T EDIT ###
