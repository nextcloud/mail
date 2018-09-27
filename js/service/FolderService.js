import HttpClient from 'nextcloud-axios'

export function fetchAll(accountId) {
  const url = OC.generateUrl('/apps/mail/api/accounts/{accountId}/folders', {
    accountId
  })

  // FIXME: this return format is weird and should be avoided
  // TODO: respect `resp.data.delimiter` value
  return HttpClient.get(url).then(resp => resp.data.folders)
}
