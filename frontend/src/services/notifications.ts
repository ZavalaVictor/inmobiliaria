import { apiRequest } from './http.ts'
import type { UnreadNotificationsResponse } from '../types/notifications.ts'

export async function getUnreadNotificationCount(): Promise<number> {
  const response = await apiRequest<UnreadNotificationsResponse>('/v1/notificaciones/no-leidas/count')
  return response.data.count
}
