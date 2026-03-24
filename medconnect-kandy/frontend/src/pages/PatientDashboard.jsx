import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';

export default function PatientDashboard() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate('/login', { replace: true });
  };

  return (
    <div className="dashboard-page">
      <nav className="dashboard-nav">
        <div className="nav-brand">
          <svg viewBox="0 0 32 32" fill="none">
            <rect width="32" height="32" rx="8" fill="#2563EB"/>
            <path d="M16 8V24M8 16H24" stroke="white" strokeWidth="2.5" strokeLinecap="round"/>
          </svg>
          <span>LocalDoc Connect</span>
        </div>
        <div className="nav-right">
          <span className="nav-user-name">👋 {user?.full_name}</span>
          <span className="role-chip role-chip-patient">Patient</span>
          <button className="btn-logout" onClick={handleLogout}>Logout</button>
        </div>
      </nav>

      <main className="dashboard-main">
        <div className="dashboard-welcome">
          <h1>Welcome, {user?.full_name?.split(' ')[0]}!</h1>
          <p>You are now signed in as a <strong>Patient</strong>. The clinic search and booking features are coming soon.</p>
        </div>
        <div className="dashboard-cards">
          {[
            { icon: '🏥', title: 'Find Clinics', desc: 'Discover verified OPD centers near you', soon: true },
            { icon: '📅', title: 'My Appointments', desc: 'View and manage your bookings', soon: true },
            { icon: '💬', title: 'Online Consult', desc: 'Request a basic online consultation', soon: true },
            { icon: '👤', title: 'My Profile', desc: 'Update your account information', soon: true },
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
