import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';

export default function AdminDashboard() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate('/admin/login', { replace: true });
  };

  return (
    <div className="dashboard-page admin-dashboard-page">
      <nav className="dashboard-nav admin-nav">
        <div className="nav-brand">
          <svg viewBox="0 0 32 32" fill="none">
            <rect width="32" height="32" rx="8" fill="#1E40AF"/>
            <path d="M16 8V24M8 16H24" stroke="white" strokeWidth="2.5" strokeLinecap="round"/>
          </svg>
          <span>Admin Portal</span>
        </div>
        <div className="nav-right">
          <span className="nav-user-name">🔐 {user?.full_name}</span>
          <span className="role-chip role-chip-admin">Admin</span>
          <button className="btn-logout" onClick={handleLogout}>
            <svg viewBox="0 0 20 20" fill="currentColor" style={{ width: 14, height: 14 }}>
              <path fillRule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h8a1 1 0 001-1v-2a1 1 0 10-2 0v1H4V5h6v1a1 1 0 102 0V4a1 1 0 00-1-1H3zm13.707 5.293a1 1 0 010 1.414L14.414 12H10a1 1 0 110-2h4.414l-1.707-1.707a1 1 0 011.414-1.414l3 3z" clipRule="evenodd"/>
            </svg>
            Logout
          </button>
        </div>
      </nav>

      <main className="dashboard-main">
        <div className="dashboard-welcome">
          <h1>Admin Dashboard</h1>
          <p>System Administrator control panel. Manage clinics, users, and system settings.</p>
        </div>
        <div className="dashboard-cards">
          {[
            { icon: '🏥', title: 'Clinic Approvals', desc: 'Review and verify new clinic registrations', soon: true },
            { icon: '👥', title: 'User Management', desc: 'Manage patients, staff, and admin accounts', soon: false },
            { icon: '📊', title: 'System Analytics', desc: 'View platform usage and metrics', soon: true },
            { icon: '⚙️', title: 'System Settings', desc: 'Configure platform behavior', soon: true },
          ].map((card) => (
            <div key={card.title} className="dashboard-card">
              <span className="card-icon">{card.icon}</span>
              <h3>{card.title}</h3>
              <p>{card.desc}</p>
              {card.soon && <span className="coming-soon-tag">Coming Soon</span>}
            </div>
          ))}
        </div>
      </main>
    </div>
  );
}
