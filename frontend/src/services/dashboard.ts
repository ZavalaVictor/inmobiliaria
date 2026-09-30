import { apiRequest } from './http.ts'
import type { DashboardPeriodKey, DashboardResponse } from '../types/dashboard.ts'

export async function getDashboard(periodo: DashboardPeriodKey = 'mes'): Promise<DashboardResponse['data']> {
  const response = await apiRequest<DashboardResponse>(`/v1/dashboard?periodo=${periodo}`)
  return response.data
}
