
require "fileinto";

if address "From" "marketing@company.org" {
    fileinto "INBOX.marketing";
}

### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###
# DATA: {"version":1,"enabled":false,"subject":"On vacation","message":"I'm on vacation.","messageHtml":false}
### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###