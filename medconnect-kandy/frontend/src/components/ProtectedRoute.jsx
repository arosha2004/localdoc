import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

/**
 * Wraps a route to require authentication.
 * Optionally restricts to specific roles.
 *
 * Waits for the startup token-validation check (authLoading) to complete
 * before deciding to redirect, so a valid stored token is never incorrectly
 * treated as "not authenticated" on first render.
 */
export default function ProtectedRoute({ children, roles }) {
  const { user, isAuthenticated, authLoading } = useAuth();
  const location = useLocation();

  // Still validating the stored token against the server — hold on
  if (authLoading) {
    return (
      <div style={{
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        height: '100vh',
        background: '#0f172a',
        color: '#94a3b8',
        fontFamily: 'Inter, sans-serif',
        fontSize: '1rem',
        gap: '0.75rem'
      }}>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
          style={{ animation: 'spin 1s linear infinite' }}>
          <style>{`@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }`}</style>
          <circle cx="12" cy="12" r="10" stroke="#334155" strokeWidth="3"/>
          <path d="M12 2a10 10 0 0 1 10 10" stroke="#3b82f6" strokeWidth="3" strokeLinecap="round"/>
        </svg>
        Verifying session…
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  if (roles && !roles.includes(user?.role)) {
    return <Navigate to="/unauthorized" replace />;
  }

  return children;
}
