### Nextcloud Mail: Filters ### DON'T EDIT ###
require ["fileinto", "imap4flags"];
### Nextcloud Mail: Filters ### DON'T EDIT ###

# Hello, this is a test

### Nextcloud Mail: Filters ### DON'T EDIT ###
# DATA: [{"name":"Test A","enable":true,"operator":"anyof","tests":[{"operator":"contains","values":["Project-A","Project-B"],"field":"subject"},{"operator":"is","values":["john@example.org"],"field":"from"}],"actions":[{"type":"fileinto","flag":"","mailbox":"Test Data"},{"type":"stop"}],"priority":"20"},{"name":"Test B","enable":true,"operator":"allof","tests":[{"operator":"contains","values":["@example.org"],"field":"to"}],"actions":[{"type":"addflag","flag":"Test A"}],"priority":30}]
# Filter: Test A
if anyof (header :contains "Subject" ["Project-A", "Project-B"], address :is :all "From" ["john@example.org"]) {
fileinto "Test Data";
stop;
}

# Filter: Test B
if address :contains :all "To" ["@example.org"] {
addflag "$test_a";
}

### Nextcloud Mail: Filters ### DON'T EDIT ###
