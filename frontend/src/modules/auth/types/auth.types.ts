export interface UserDTO {
  id: string
  email: string
  firstName: string
  lastName: string
  status: string
  roles: string[]
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
