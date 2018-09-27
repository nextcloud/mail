import HttpClient from 'nextcloud-axios'

export function fetchEnvelopes(accountId, folderId, cursor) {
  const url = OC.generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages', {
    accountId,
    folderId,
  })
  const params = {}

  if (cursor) {
    params.cursor = cursor
  }

  return HttpClient.get(url, {
    params: params
  }).then(resp => resp.data)
}

export function syncEnvelopes(accountId, folderId, syncToken, uids) {
  const url = OC.generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/sync', {
    accountId,
    folderId,
  })

  return HttpClient.get(url, {
    params: {
      syncToken,
      uids,
    }
  }).then(resp => resp.data)
}

export function setEnvelopeFlag(accountId, folderId, id, flag, value) {
  const url = OC.generateUrl('apps/mail/api/accounts/{accountId}/folders/{folderId}/messages/{id}/flags', {
    accountId,
    folderId,
    id
  })

  const flags = {}
  flags[flag] = value

  return HttpClient.put(url, {
    flags: flags
  }).then(resp => { flag: value })
}

export function fetchMessage(accountId, folderId, id) {
  const url = OC.generateUrl('/apps/mail/api/accounts/{accountId}/folders/{folderId}/messages/{id}', {
    accountId,
    folderId,
    id
  })

  return HttpClient.get(url).then(resp => resp.data)
}
