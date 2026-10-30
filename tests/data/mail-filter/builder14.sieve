### Nextcloud Mail: Filters ### DON'T EDIT ###
require ["imap4flags"];
### Nextcloud Mail: Filters ### DON'T EDIT ###
# Hello, this is a test
### Nextcloud Mail: Filters ### DON'T EDIT ###
# FILTER: [{"name":"Test 14","enable":true,"operator":"true","tests":[],"actions":[{"type":"addflag","flag":"Incoming"}],"priority":10}]
# Test 14
if true {
	addflag "$incoming";
}
### Nextcloud Mail: Filters ### DON'T EDIT ###
