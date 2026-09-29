export function MailIcon(): React.JSX.Element {
  return <svg aria-hidden="true" className="size-5" fill="none" viewBox="0 0 24 24"><path d="m3 6 9 7 9-7M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" /></svg>
}

export function LockIcon(): React.JSX.Element {
  return <svg aria-hidden="true" className="size-5" fill="none" viewBox="0 0 24 24"><rect height="10" rx="1.5" stroke="currentColor" strokeWidth="1.8" width="14" x="5" y="10" /><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2" stroke="currentColor" strokeLinecap="round" strokeWidth="1.8" /></svg>
}

export function EyeIcon({ hidden }: { hidden: boolean }): React.JSX.Element {
  return <svg aria-hidden="true" className="size-5" fill="none" viewBox="0 0 24 24"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" />{hidden ? <path d="m4 4 16 16" stroke="currentColor" strokeLinecap="round" strokeWidth="1.8" /> : <circle cx="12" cy="12" r="2.5" stroke="currentColor" strokeWidth="1.8" />}</svg>
}

export function EnvelopeCheckIcon(): React.JSX.Element {
  return <svg aria-hidden="true" className="size-7" fill="none" viewBox="0 0 32 32"><rect height="18" rx="2" stroke="currentColor" strokeWidth="1.8" width="24" x="3" y="6" /><path d="m4 8 11 8 11-8M20 23l2 2 4-4" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" /></svg>
}

export function CheckCircleIcon(): React.JSX.Element {
  return <svg aria-hidden="true" className="size-8" fill="none" viewBox="0 0 40 40"><circle cx="20" cy="20" fill="currentColor" r="18" /><path d="m11 20 6 6 12-13" stroke="white" strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" /></svg>
}
