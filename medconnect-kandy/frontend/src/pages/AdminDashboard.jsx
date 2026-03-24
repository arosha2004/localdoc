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
          <button className="btn-logout" onClick={handleLogout}>Logout</button>
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
