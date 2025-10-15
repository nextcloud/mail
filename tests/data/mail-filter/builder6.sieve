### Nextcloud Mail: Filters ### DON'T EDIT ###
require ["fileinto", "imap4flags"];
### Nextcloud Mail: Filters ### DON'T EDIT ###
# Hello, this is a test
### Nextcloud Mail: Filters ### DON'T EDIT ###
# FILTER: [{"actions":[{"mailbox":"Test Data","type":"fileinto"},{"flag":"Projects\\Reporting","type":"addflag"}],"enable":true,"name":"Test 6","operator":"anyof","priority":10,"tests":[{"field":"subject","operator":"is","values":["\"Project-A\"","Project\\A"]},{"field":"subject","operator":"is","values":["\"Project-B\"","Project\\B"]}]}]
# Test 6
if anyof (header :is "Subject" ["\"Project-A\"", "Project\\A"], header :is "Subject" ["\"Project-B\"", "Project\\B"]) {
	fileinto "Test Data";
	addflag "$projects\\reporting";
}
### Nextcloud Mail: Filters ### DON'T EDIT ###
