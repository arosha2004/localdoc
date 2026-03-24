import { Link } from 'react-router-dom';

export default function UnauthorizedPage() {
  return (
    <div className="error-page">
      <div className="error-content">
        <div className="error-icon">🔒</div>
        <h1>403 — Access Denied</h1>
        <p>You do not have permission to view this page.</p>
        <Link to="/login" className="btn-primary-auth" style={{ display: 'inline-block', textDecoration: 'none', marginTop: '1.5rem' }}>
          Return to Login
        </Link>
      </div>
    </div>
  );
}
