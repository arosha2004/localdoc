import { useState, useMemo, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';
import MapModal from '../components/MapModal';
import { getCenters } from '../api/centersData';

// ── Star Rating Helper ─────────────────────────────────────────────────────────
function StarRating({ rating }) {
  const full = Math.floor(rating);
  const half = rating % 1 >= 0.5;
  return (
    <span className="star-rating" aria-label={`${rating} stars`}>
      {[...Array(5)].map((_, i) => (
        <svg key={i} viewBox="0 0 20 20" width="14" height="14"
          fill={i < full ? '#f59e0b' : (i === full && half ? 'url(#half)' : '#d1d5db')}>
          <defs>
            <linearGradient id="half">
              <stop offset="50%" stopColor="#f59e0b" />
              <stop offset="50%" stopColor="#d1d5db" />
            </linearGradient>
          </defs>
          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
        </svg>
      ))}
      <span className="rating-number">{rating.toFixed(1)}</span>
    </span>
  );
}

// ── Medical Center Card ────────────────────────────────────────────────────────
function MedicalCenterCard({ center, onViewMap }) {
  const tagClass = center.tag === 'govt' ? 'mc-tag-govt' : 'mc-tag-private';
  const tagLabel = center.tag === 'govt' ? 'Government' : 'Private';

  return (
    <div className={`mc-card ${!center.available ? 'mc-card-unavailable' : ''}`}>
      <div className="mc-card-header">
        <div className="mc-icon-wrap">
          <svg viewBox="0 0 32 32" fill="none" width="32" height="32">
            <rect width="32" height="32" rx="8" fill={center.tag === 'govt' ? '#dbeafe' : '#ede9fe'} />
            <path d="M16 7v18M7 16h18" stroke={center.tag === 'govt' ? '#2563eb' : '#7c3aed'}
              strokeWidth="2.5" strokeLinecap="round" />
          </svg>
        </div>
        <div className="mc-header-info">
          <h3 className="mc-name">{center.name}</h3>
          <p className="mc-type">{center.type}</p>
        </div>
        <span className={`mc-tag ${tagClass}`}>{tagLabel}</span>
      </div>

      <div className="mc-meta">
        <span className="mc-meta-item">
          <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13">
            <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
          </svg>
          {center.area}
        </span>
        <span className="mc-meta-item">
          <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13">
            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clipRule="evenodd" />
          </svg>
          {center.hours}
        </span>
        <span className="mc-meta-item">
          <svg viewBox="0 0 20 20" fill="currentColor" width="13" height="13">
            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
          </svg>
          {center.distance} away
        </span>
      </div>

      <p className="mc-address">
        <svg viewBox="0 0 20 20" fill="currentColor" width="12" height="12">
          <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9z" clipRule="evenodd" />
        </svg>
        {center.address}
      </p>

      {/* Doctor Availability Badge - Prominent */}
      <div className={center.doctor_available ? 'mc-doctor-badge doctor-available-badge' : 'mc-doctor-badge doctor-not-available-badge'}>
        <span className="doctor-status-dot"></span>
        Doctor {center.doctor_available ? 'is Currently Available (OPD)' : 'NOT Available (OPD)'}
      </div>

      <div className="mc-services">
        {center.services.slice(0, 4).map(s => (
          <span key={s} className="mc-service-chip">{s}</span>
        ))}
        {center.services.length > 4 && (
          <span className="mc-service-chip mc-service-more">+{center.services.length - 4} more</span>
        )}
      </div>

      <div className="mc-footer">
        <div className="mc-footer-left">
          <StarRating rating={center.rating} />
          <span className={`mc-status ${center.available ? 'mc-status-open' : 'mc-status-closed'}`}>
            <span className="mc-status-dot"></span>
            {center.available ? 'Open' : 'Closed'}
          </span>
        </div>
        <div className="mc-footer-right">
          <button className="mc-btn-map" onClick={() => onViewMap(center)}>
            <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14">
              <path fillRule="evenodd" d="M12 1.586l-4 4v12.828l4-4V1.586zM3.707 3.293A1 1 0 002 4v10a1 1 0 00.293.707L6 18.414V5.586L3.707 3.293zM17.707 5.293L14 1.586v12.828l2.293 2.293A1 1 0 0018 16V6a1 1 0 00-.293-.707z" clipRule="evenodd"/>
            </svg>
            Map
          </button>
          <a href={`tel:${center.phone}`} className="mc-btn-call">
            <svg viewBox="0 0 20 20" fill="currentColor" width="14" height="14">
              <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
            </svg>
            Call
          </a>
          <button className="mc-btn-book">Book Visit</button>
        </div>
      </div>
    </div>
  );
}

// ── Main Dashboard Component ───────────────────────────────────────────────────
export default function PatientDashboard() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [centers, setCenters] = useState([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterType, setFilterType] = useState('all');
  const [filterAvail, setFilterAvail] = useState('all');
  const [activeTab, setActiveTab] = useState('centers');
  const [selectedCenter, setSelectedCenter] = useState(null);

  useEffect(() => {
    // Load centers from shared local storage
    setCenters(getCenters());
    
    // Check for updates periodically (simulating real-time sync)
    const interval = setInterval(() => {
      setCenters(getCenters());
    }, 5000); // Check every 5 seconds
    
    return () => clearInterval(interval);
  }, []);

  const handleLogout = () => {
    logout();
    navigate('/login', { replace: true });
  };

  const filtered = useMemo(() => {
    return centers.filter(c => {
      const q = searchQuery.toLowerCase();
      const matchesSearch =
        !q ||
        c.name.toLowerCase().includes(q) ||
        c.area.toLowerCase().includes(q) ||
        c.type.toLowerCase().includes(q) ||
        c.services.some(s => s.toLowerCase().includes(q));
      const matchesType =
        filterType === 'all' ||
        (filterType === 'govt' && c.tag === 'govt') ||
        (filterType === 'private' && c.tag === 'private');
      const matchesAvail =
        filterAvail === 'all' ||
        (filterAvail === 'open' && c.available) ||
        (filterAvail === 'closed' && !c.available);
      return matchesSearch && matchesType && matchesAvail;
    });
  }, [centers, searchQuery, filterType, filterAvail]);

  return (
    <div className="dashboard-page">
      {/* ── Map Modal ── */}
      {selectedCenter && (
        <MapModal
          center={selectedCenter}
          onClose={() => setSelectedCenter(null)}
        />
      )}

      {/* ── Navbar ── */}
      <nav className="dashboard-nav">
        <div className="nav-brand">
          <svg viewBox="0 0 32 32" fill="none">
            <rect width="32" height="32" rx="8" fill="#2563EB" />
            <path d="M16 8V24M8 16H24" stroke="white" strokeWidth="2.5" strokeLinecap="round" />
          </svg>
          <span>LocalDoc Connect</span>
        </div>
        <div className="nav-right">
          <span className="nav-user-name">👋 {user?.full_name}</span>
          <span className="role-chip role-chip-patient">Patient</span>
          <button className="btn-logout" onClick={handleLogout}>
            <svg viewBox="0 0 20 20" fill="currentColor" style={{ width: 14, height: 14 }}>
              <path fillRule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h8a1 1 0 001-1v-2a1 1 0 10-2 0v1H4V5h6v1a1 1 0 102 0V4a1 1 0 00-1-1H3zm13.707 5.293a1 1 0 010 1.414L14.414 12H10a1 1 0 110-2h4.414l-1.707-1.707a1 1 0 011.414-1.414l3 3z" clipRule="evenodd" />
            </svg>
            Logout
          </button>
        </div>
      </nav>

      {/* ── Tab Bar ── */}
      <div className="patient-tabs">
        <button
          className={`patient-tab ${activeTab === 'home' ? 'patient-tab-active' : ''}`}
          onClick={() => setActiveTab('home')}
        >
          🏠 Home
        </button>
        <button
          className={`patient-tab ${activeTab === 'centers' ? 'patient-tab-active' : ''}`}
          onClick={() => setActiveTab('centers')}
        >
          🏥 Medical Centers
        </button>
      </div>

      <main className="dashboard-main">
        {/* ── HOME TAB ── */}
        {activeTab === 'home' && (
          <>
            <div className="dashboard-welcome">
              <h1>Welcome, {user?.full_name?.split(' ')[0]}!</h1>
              <p>You are signed in as a <strong>Patient</strong>. Use the tabs above to explore medical centers in Kandy.</p>
            </div>
            <div className="dashboard-cards">
              {[
                { icon: '🏥', title: 'Find Clinics', desc: 'Discover verified OPD centers near you in Kandy', action: () => setActiveTab('centers') },
                { icon: '📅', title: 'My Appointments', desc: 'View and manage your bookings', soon: true },
                { icon: '💬', title: 'Online Consult', desc: 'Request a basic online consultation', soon: true },
                { icon: '👤', title: 'My Profile', desc: 'Update your account information', soon: true },
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
                  {card.action && (
                    <span className="coming-soon-tag" style={{ background: 'var(--blue-600)', color: 'white' }}>
                      Explore →
                    </span>
                  )}
                </div>
              ))}
            </div>
          </>
        )}

        {/* ── MEDICAL CENTERS TAB ── */}
        {activeTab === 'centers' && (
          <div className="mc-section">
            {/* Section Header */}
            <div className="mc-section-header">
              <div>
                <h1 className="mc-section-title">
                  <span className="mc-kandy-badge">📍 Kandy</span>
                  Medical Centers
                </h1>
                <p className="mc-section-sub">
                  Showing real-time doctor availability for OPD visits
                </p>
              </div>
              <div className="mc-stats-row">
                <div className="mc-stat">
                  <span className="mc-stat-num">{centers.filter(c => c.doctor_available).length}</span>
                  <span className="mc-stat-label">Doc Available</span>
                </div>
              </div>
            </div>

            {/* Search & Filters */}
            <div className="mc-search-bar">
              <div className="mc-search-input-wrap">
                <svg className="mc-search-icon" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clipRule="evenodd" />
                </svg>
                <input
                  type="text"
                  placeholder="Search clinics…"
                  className="mc-search-input"
                  value={searchQuery}
                  onChange={e => setSearchQuery(e.target.value)}
                />
                {searchQuery && (
                  <button className="mc-search-clear" onClick={() => setSearchQuery('')}>×</button>
                )}
              </div>
              <div className="mc-filters">
                <div className="mc-filter-group">
                  <label>Type</label>
                  <select value={filterType} onChange={e => setFilterType(e.target.value)} className="mc-select">
                    <option value="all">All Types</option>
                    <option value="govt">Government</option>
                    <option value="private">Private</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Cards Grid */}
            {filtered.length > 0 ? (
              <div className="mc-grid">
                {filtered.map(center => (
                  <MedicalCenterCard
                    key={center.id}
                    center={center}
                    onViewMap={setSelectedCenter}
                  />
                ))}
              </div>
            ) : (
              <div className="mc-empty">
                <span className="mc-empty-icon">🔍</span>
                <h3>No centers found</h3>
                <button className="mc-btn-reset" onClick={() => { setSearchQuery(''); setFilterType('all'); }}>
                  Reset Search
                </button>
              </div>
            )}
          </div>
        )}
      </main>
    </div>
  );
}
