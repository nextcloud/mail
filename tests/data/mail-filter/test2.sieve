### Nextcloud Mail: Filters ### DON'T EDIT ###
require ["fileinto"];
### Nextcloud Mail: Filters ### DON'T EDIT ###

# Hello, this is a test

### Nextcloud Mail: Filters ### DON'T EDIT ###
# DATA: [{"name":"Test 2000","enable":true,"operator":"anyof","tests":[{"operator":"contains","values":["Project-A","Project-B"],"field":"subject"},{"operator":"is","values":["john@example.org"],"field":"from"}],"actions":[{"type":"fileinto","flag":"","mailbox":"Test Data"}],"priority":"20"}]
# Filter: Test 2000
if anyof (header :contains "Subject" ["Project-A", "Project-B"], address :is :all "From" ["john@example.org"]) {
fileinto "Test Data";
}

### Nextcloud Mail: Filters ### DON'T EDIT ###
