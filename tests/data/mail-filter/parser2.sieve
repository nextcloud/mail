### Nextcloud Mail: Filters ### DON'T EDIT ###
require ["fileinto"];
### Nextcloud Mail: Filters ### DON'T EDIT ###
# Hello, this is a test
### Nextcloud Mail: Filters ### DON'T EDIT ###
# FILTER: [{"name":"Test 2","enable":true,"operator":"anyof","tests":[{"operator":"contains","values":["Project-A","Project-B"],"field":"subject"},{"operator":"is","values":["john@example.org"],"field":"from"}],"actions":[{"type":"fileinto","flag":"","mailbox":"Test Data"}],"priority":20}]
# Test 2
if anyof (header :contains "Subject" ["Project-A", "Project-B"], address :is :all "From" ["john@example.org"]) {
fileinto "Test Data";
}
### Nextcloud Mail: Filters ### DON'T EDIT ###
