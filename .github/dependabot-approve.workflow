workflow "Approve Dependabot PRs" {
  resolves = ["hmarr/auto-approve-action@v1.0.0"]
  on = "push"
}

action "Filters for GitHub Actions" {
  uses = "actions/bin/filter@3c0b4f0e63ea54ea5df2914b4fabf383368cd0da"
  args = "actor dependabot-preview"
}

action "hmarr/auto-approve-action@v1.0.0" {
  uses = "hmarr/auto-approve-action@v1.0.0"
  needs = ["Filters for GitHub Actions"]
  secrets = ["GITHUB_TOKEN"]
}
