workflow "Auto-approve Dependabot PRs" {
  resolves = ["Auto Approve"]
  on = "push"
}

action "Auto Approve" {
  uses = "hmarr/auto-approve-action@v1.0.0"
  secrets = ["GITHUB_TOKEN"]
}
