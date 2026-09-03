# Hello, this is a test
### Nextcloud Mail: Filters ### DON'T EDIT ###
# FILTER: [{"name":"Test 10","enable":true,"operator":"allof","tests":[{"operator":"is","values":["bob@example.org"],"field":"to"}],"actions":[{"type":"forward","recipient":""},{"type":"stop"}],"priority":10}]
# Test 10
if address :is :all "To" ["bob@example.org"] {
	stop;
}
### Nextcloud Mail: Filters ### DON'T EDIT ###
