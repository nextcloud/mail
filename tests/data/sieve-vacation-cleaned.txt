
require "fileinto";

if address "From" "marketing@company.org" {
    fileinto "INBOX.marketing";
}
