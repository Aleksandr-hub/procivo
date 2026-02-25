export interface ApiError {
  error?: string
  message?: string
  violations?: Record<string, string[]>
}

export interface AuthTokens {
  access_token: string
  refresh_token: string
  token_type: string
  expires_in: number
}

export interface IdResponse {
  id: string
}

export interface MessageResponse {
  message: string
}
