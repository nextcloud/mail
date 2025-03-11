### Nextcloud Mail: Filters ### DON'T EDIT ###
require ["fileinto", "imap4flags"];
### Nextcloud Mail: Filters ### DON'T EDIT ###
# Hello, this is a test
### Nextcloud Mail: Filters ### DON'T EDIT ###
# FILTER: [{"name":"Test 3.1","enable":true,"operator":"anyof","tests":[{"operator":"contains","values":["Project-A","Project-B"],"field":"subject"},{"operator":"is","values":["john@example.org"],"field":"from"}],"actions":[{"type":"fileinto","mailbox":"Test Data"},{"type":"stop"}],"priority":20},{"name":"Test 3.2","enable":true,"operator":"allof","tests":[{"operator":"contains","values":["@example.org"],"field":"to"}],"actions":[{"type":"addflag","flag":"Test A"}],"priority":30}]
# Test 3.1
if anyof (header :contains "Subject" ["Project-A", "Project-B"], address :is :all "From" ["john@example.org"]) {
	fileinto "Test Data";
	stop;
}
# Test 3.2
if address :contains :all "To" ["@example.org"] {
	addflag "$test_a";
}
### Nextcloud Mail: Filters ### DON'T EDIT ###
