import type { ApiResponse } from './api.ts'

export interface UnreadNotifications {
  count: number
}

export type UnreadNotificationsResponse = ApiResponse<UnreadNotifications>
