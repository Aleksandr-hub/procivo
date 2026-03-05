export interface ImpersonationResponse {
  access_token: string
  impersonated_user: {
    id: string
    email: string
    firstName: string
    lastName: string
  }
  expires_in: number
}

export async function startImpersonation(
  userId: string,
  reason: string,
): Promise<ImpersonationResponse> {
  const { default: httpClient } = await import('@/shared/api/http-client')
  const response = await httpClient.post<ImpersonationResponse>(
    `/admin/impersonate/${userId}`,
    { reason },
  )
  return response.data
}

export async function endImpersonation(): Promise<void> {
  const { default: httpClient } = await import('@/shared/api/http-client')
  await httpClient.post('/admin/impersonate/end')
}
