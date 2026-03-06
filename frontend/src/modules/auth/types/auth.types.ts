export interface UserDTO {
  id: string
  email: string
  firstName: string
  lastName: string
  status: string
  roles: string[]
  avatarUrl?: string
  totpEnabled: boolean
}

export interface LoginRequest {
  email: string
  password: string
}

export interface RegisterRequest {
  email: string
  password: string
  firstName: string
  lastName: string
}

export interface AuthTokensResponse {
  access_token: string
  refresh_token: string
  token_type: string
  expires_in: number
}

export interface TwoFactorChallengeResponse {
  partial_token: string
  two_factor_required: boolean
}

export interface TwoFactorSetupResponse {
  qr_code_svg: string
  secret: string
  backup_codes: string[]
}

export type LoginResponse = AuthTokensResponse | TwoFactorChallengeResponse

export function isTwoFactorChallenge(
  response: LoginResponse,
): response is TwoFactorChallengeResponse {
  return 'two_factor_required' in response && response.two_factor_required === true
}
