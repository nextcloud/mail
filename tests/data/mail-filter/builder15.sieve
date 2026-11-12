# Hello, this is a test
### Nextcloud Mail: Filters ### DON'T EDIT ###
# FILTER: [{"name":"Test 15","enable":true,"operator":"anyof","tests":[{"operator":"is","values":[],"field":"subject"},{"operator":"contains","values":["alice@example.org"],"field":"from"}],"actions":[{"type":"keep"}],"priority":10}]
# Test 15
if address :contains :all "From" ["alice@example.org"] {
	keep;
}
### Nextcloud Mail: Filters ### DON'T EDIT ###
