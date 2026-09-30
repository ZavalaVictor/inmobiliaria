export function DashboardIcon({ className }: { className?: string }): React.JSX.Element {
  return <svg aria-hidden="true" className={className} fill="none" viewBox="0 0 24 24"><rect height="7" rx="1.5" stroke="currentColor" strokeWidth="1.8" width="7" x="3" y="3" /><rect height="7" rx="1.5" stroke="currentColor" strokeWidth="1.8" width="7" x="14" y="3" /><rect height="7" rx="1.5" stroke="currentColor" strokeWidth="1.8" width="7" x="3" y="14" /><rect height="7" rx="1.5" stroke="currentColor" strokeWidth="1.8" width="7" x="14" y="14" /></svg>
}

export function WorkspaceIcon({ className }: { className?: string }): React.JSX.Element {
  return <svg aria-hidden="true" className={className} fill="none" viewBox="0 0 24 24"><path d="M4 19.5V6.8A1.8 1.8 0 0 1 5.8 5h12.4A1.8 1.8 0 0 1 20 6.8v12.7M3 19.5h18M8 9h8M8 13h5" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" /></svg>
}
