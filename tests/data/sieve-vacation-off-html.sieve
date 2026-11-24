
require "fileinto";

if address "From" "marketing@company.org" {
    fileinto "INBOX.marketing";
}

### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###
# DATA: {"version":1,"enabled":false,"subject":"On vacation","message":"<p style=\"margin:0;\">I'm on vacation.<\/p>","messageHtml":true}
### Nextcloud Mail: Vacation Responder ### DON'T EDIT ###