import HttpClient from 'nextcloud-axios'

function fixAccountId(original) {
  return {
    id: original.accountId,
    ...original
  }
}

export function fetchAll() {
  const url = OC.generateUrl('/apps/mail/api/accounts')

  return HttpClient.get(url).then(resp => resp.data.map(fixAccountId))
}

export function fetch(id) {
  const url = OC.generateUrl('/apps/mail/api/accounts/{id}', {
    id
  })

  return HttpClient.get(url).then(resp => fixAccountId(resp.data))
}
