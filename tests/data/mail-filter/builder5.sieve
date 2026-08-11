### Nextcloud Mail: Filters ### DON'T EDIT ###
require ["imap4flags"];
### Nextcloud Mail: Filters ### DON'T EDIT ###
# Hello, this is a test
### Nextcloud Mail: Filters ### DON'T EDIT ###
# FILTER: [{"actions":[{"flag":"Report","type":"addflag"},{"flag":"To read","type":"addflag"}],"enable":true,"name":"Test 5","operator":"allof","priority":10,"tests":[{"field":"subject","operator":"matches","values":["work*report"]}]}]
# Test 5
if header :matches "Subject" ["work*report"] {
	addflag "$report";
	addflag "$to_read";
}
### Nextcloud Mail: Filters ### DON'T EDIT ###
