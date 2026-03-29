import { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';
import { getCenters, saveCenters } from '../api/centersData';

export default function AdminDashboard() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [centers, setCenters] = useState([]);
  const [activeView, setActiveView] = useState('overview'); // 'overview' | 'management'

  useEffect(() => {
    setCenters(getCenters());
  }, []);

  const handleLogout = () => {
    logout();
    navigate('/admin/login', { replace: true });
  };

  const toggleDoctorAvailability = (id) => {
    const updated = centers.map(c => 
      c.id === id ? { ...c, doctor_available: !c.doctor_available } : c
    );
    setCenters(updated);
    saveCenters(updated);
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

      {/* Admin Tabs */}
      <div className="patient-tabs">
        <button 
          className={`patient-tab ${activeView === 'overview' ? 'patient-tab-active' : ''}`}
          onClick={() => setActiveView('overview')}
        >
          📊 Stats & Overview
        </button>
        <button 
          className={`patient-tab ${activeView === 'management' ? 'patient-tab-active' : ''}`}
          onClick={() => setActiveView('management')}
        >
          🏥 Clinic Management
        </button>
      </div>

      <main className="dashboard-main">
        {activeView === 'overview' ? (
          <>
            <div className="dashboard-welcome">
              <h1>Admin Dashboard</h1>
              <p>System Administrator control panel. Manage clinics and doctor availability live.</p>
            </div>
            <div className="dashboard-cards">
              {[
                { icon: '🏥', title: 'Clinic Management', desc: 'Manage clinics and doctor availability live', action: () => setActiveView('management') },
                { icon: '👥', title: 'User Management', desc: 'Manage patients and staff accounts', soon: true },
                { icon: '📈', title: 'Usage Metrics', desc: 'View platform traffic and search data', soon: true },
                { icon: '⚙️', title: 'Settings', desc: 'Configure system-wide parameters', soon: true },
              ].map((card) => (
                <div 
                  key={card.title} 
                  className="dashboard-card" 
                  onClick={card.action} 
                  style={card.action ? { cursor: 'pointer' } : {}}
                >
                  <span className="card-icon">{card.icon}</span>
                  <h3>{card.title}</h3>
                  <p>{card.desc}</p>
                  {card.soon && <span className="coming-soon-tag">Coming Soon</span>}
                  {card.action && <span className="coming-soon-tag" style={{ background: 'var(--blue-600)', color: 'white' }}>Open →</span>}
                </div>
              ))}
            </div>
          </>
        ) : (
          <div className="mc-section">
            <div className="mc-section-header">
              <div>
                <h1 className="mc-section-title">Clinic Management</h1>
                <p className="mc-section-sub">Update medical center status and doctor availability in real-time</p>
              </div>
            </div>

            <div className="admin-mc-list">
              {centers.map(center => (
                <div key={center.id} className="admin-mc-row">
                  <div className="admin-mc-info">
                    <h4>{center.name}</h4>
                    <p>{center.area} · {center.type}</p>
                    <div className={center.doctor_available ? 'mc-doctor-badge doctor-available-badge' : 'mc-doctor-badge doctor-not-available-badge'}>
                      <span className="doctor-status-dot"></span>
                      Doctor is {center.doctor_available ? 'Available' : 'NOT Available'}
                    </div>
                  </div>
                  <div className="admin-mc-controls">
                    <div className="toggle-container" onClick={() => toggleDoctorAvailability(center.id)}>
                      <span className="toggle-label">Doctor Present?</span>
                      <label className="switch">
                        <input 
                          type="checkbox" 
                          checked={center.doctor_available} 
                          onChange={() => {}} // Handled by container click
                        />
                        <span className="switch-slider"></span>
                      </label>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            <div style={{ marginTop: '2rem', textAlign: 'center' }}>
              <button className="mc-btn-reset" onClick={() => setActiveView('overview')}>
                Done
              </button>
            </div>
          </div>
        )}
      </main>
    </div>
  );
}
