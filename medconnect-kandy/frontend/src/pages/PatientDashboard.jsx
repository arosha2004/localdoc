import { useState, useMemo, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';
import MapModal from '../components/MapModal';
import { getCenters } from '../api/centersData';

function StarRating({ rating }) {
  const full = Math.floor(rating);
  const half = rating % 1 >= 0.5;
  return (
    <div className="flex items-center gap-1">
      {[...Array(5)].map((_, i) => (
        <svg key={i} viewBox="0 0 20 20" className={`w-4 h-4 ${i < full ? 'text-amber-400' : (i === full && half ? 'text-amber-400' : 'text-slate-200')}`} fill="currentColor">
          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
        </svg>
      ))}
      <span className="text-xs font-bold text-slate-700 ml-1">{rating.toFixed(1)}</span>
    </div>
  );
}

function MedicalCenterCard({ center, onViewMap }) {
  const isGovt = center.tag === 'govt';

  return (
    <div className={`relative bg-white/70 backdrop-blur-xl rounded-[24px] p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white/80 hover:shadow-[0_20px_40px_rgb(59,130,246,0.1)] hover:border-blue-100 hover:-translate-y-1 transition-all duration-300 flex flex-col gap-5 ${!center.available ? 'opacity-75 grayscale-[0.2]' : ''}`}>

      {/* Decorative Top Gradient Line */}
      <div className="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 opacity-0 transition-opacity duration-300 hover:opacity-100 rounded-t-[24px]"></div>

      {/* Header */}
      <div className="flex items-start gap-4">
        <div className={`w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 shadow-inner ${isGovt ? 'bg-gradient-to-br from-blue-50 to-blue-100 text-blue-600' : 'bg-gradient-to-br from-purple-50 to-purple-100 text-purple-600'}`}>
          <svg className="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
            <path d="M12 5v14M5 12h14" />
          </svg>
        </div>
        <div className="flex-1 pt-0.5">
          <div className="flex items-start justify-between">
            <h3 className="text-[17px] font-extrabold text-slate-900 leading-tight mb-1">{center.name}</h3>
            <span className={`text-[9px] font-black uppercase tracking-wider px-2 py-1 rounded-lg shrink-0 ml-2 ${isGovt ? 'bg-blue-100/50 text-blue-700 border border-blue-200/50' : 'bg-purple-100/50 text-purple-700 border border-purple-200/50'}`}>
              {isGovt ? 'Govt' : 'Private'}
            </span>
          </div>
          <p className="text-[13px] text-slate-500 font-semibold">{center.type}</p>
        </div>
      </div>

      {/* Meta Infos */}
      <div className="flex flex-wrap gap-2 mt-1">
        <div className="flex items-center gap-1.5 text-[11px] font-bold text-slate-600 bg-white shadow-sm border border-slate-100 px-3 py-1.5 rounded-lg">
          <svg className="w-3.5 h-3.5 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
          </svg>
          {center.area}
        </div>
        <div className="flex items-center gap-1.5 text-[11px] font-bold text-slate-600 bg-white shadow-sm border border-slate-100 px-3 py-1.5 rounded-lg">
          <svg className="w-3.5 h-3.5 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clipRule="evenodd" />
          </svg>
          {center.hours}
        </div>
        <div className="flex items-center gap-1.5 text-[11px] font-bold text-blue-700 bg-blue-50/50 shadow-sm border border-blue-100/50 px-3 py-1.5 rounded-lg">
          <svg className="w-3.5 h-3.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z" />
          </svg>
          {center.distance} away
        </div>
      </div>

      <p className="flex items-start gap-2 text-[12px] text-slate-500 leading-relaxed font-medium">
        <svg className="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        {center.address}
      </p>

      {/* Doctor Availability Pill */}
      <div className={`flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-[12px] font-bold shadow-inner ${center.doctor_available ? 'bg-gradient-to-r from-emerald-50 to-teal-50 text-emerald-800 border border-emerald-100' : 'bg-gradient-to-r from-rose-50 to-red-50 text-rose-800 border border-rose-100'}`}>
        <span className="relative flex h-2.5 w-2.5">
          {center.doctor_available && <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>}
          <span className={`relative inline-flex rounded-full h-2.5 w-2.5 ${center.doctor_available ? 'bg-emerald-500' : 'bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.6)]'}`}></span>
        </span>
        Doctor {center.doctor_available ? 'is Currently Available (OPD)' : 'NOT Available (OPD)'}
      </div>

      {/* Services Tags */}
      <div className="flex flex-wrap gap-2">
        {center.services.slice(0, 4).map(s => (
          <span key={s} className="px-2.5 py-1 text-[11px] font-bold flex items-center justify-center tracking-wide text-blue-600 bg-white shadow-sm border border-blue-100 rounded-lg">
            {s}
          </span>
        ))}
        {center.services.length > 4 && (
          <span className="px-2.5 py-1 text-[11px] font-bold flex items-center justify-center text-slate-500 bg-slate-50 border border-slate-100 rounded-lg">
            +{center.services.length - 4} more
          </span>
        )}
      </div>

      {/* Footer Area */}
      <div className="mt-auto pt-5 border-t border-slate-100/60 flex items-center justify-between">
        <div className="flex flex-col gap-1">
          <StarRating rating={center.rating} />
          <div className={`flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider ${center.available ? 'text-emerald-500' : 'text-slate-400'}`}>
            <div className={`w-1.5 h-1.5 rounded-full ${center.available ? 'bg-emerald-500 shadow-[0_0_5px_rgba(16,185,129,0.8)]' : 'bg-slate-400'}`}></div>
            {center.available ? 'Open' : 'Closed'}
          </div>
        </div>

        <div className="flex items-center gap-2">
          <button onClick={() => onViewMap(center)} className="w-10 h-10 flex items-center justify-center rounded-xl bg-white shadow-sm text-blue-600 hover:bg-blue-50 hover:border-blue-200 transition-all border border-slate-200" title="View on Map">
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0121 18.382V7.618a1 1 0 01-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
          </button>
          <a href={`tel:${center.phone}`} className="w-10 h-10 flex items-center justify-center rounded-xl bg-white shadow-sm text-slate-600 hover:bg-slate-50 hover:border-slate-300 transition-all border border-slate-200" title="Call Clinic">
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
          </a>
          <button className="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-[13px] font-bold rounded-xl shadow-[0_8px_15px_rgba(59,130,246,0.3)] hover:shadow-[0_12px_20px_rgba(59,130,246,0.4)] hover:-translate-y-0.5 transition-all outline-none">
            Book Visit
          </button>
        </div>
      </div>
    </div>
  );
}

// ── Main Dashboard Component ───
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
    setCenters(getCenters());
    const interval = setInterval(() => {
      setCenters(getCenters());
    }, 5000);
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
    <div className="min-h-screen relative font-sans bg-cover bg-center bg-fixed" style={{ backgroundImage: "url('/dashboard_bg.png')" }}>
      {/* Content Legibility Overlay */}
      <div className="fixed inset-0 bg-white/60 backdrop-blur-[4px] pointer-events-none z-0"></div>

      {/* Premium Background Elements */}
      <div className="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-b from-blue-100/60 to-transparent pointer-events-none z-0"></div>
      <div className="absolute -top-[200px] -right-[200px] w-[600px] h-[600px] bg-blue-500/15 rounded-full blur-[80px] pointer-events-none z-0 fixed"></div>
      <div className="absolute top-[100px] -left-[100px] w-[400px] h-[400px] bg-indigo-500/15 rounded-full blur-[60px] pointer-events-none z-0 fixed"></div>

      {selectedCenter && (
        <MapModal
          center={selectedCenter}
          onClose={() => setSelectedCenter(null)}
        />
      )}

      {/* Navbar - Premium Glassmorphism */}
      <nav className="sticky top-0 z-50 bg-white/70 backdrop-blur-xl border-b border-white shadow-[0_4px_30px_rgba(0,0,0,0.03)] h-20 px-6 md:px-10 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-[12px] bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
            <svg viewBox="0 0 32 32" fill="none" className="w-6 h-6">
              <path d="M16 8V24M8 16H24" stroke="white" strokeWidth="3" strokeLinecap="round" />
            </svg>
          </div>
          <span className="text-xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600 tracking-tight">
            LocalDoc Connect
          </span>
        </div>
        <div className="flex items-center gap-4">
          <div className="hidden sm:flex items-center gap-2 mr-2">
            <span className="text-sm text-slate-500 font-medium">👋 {user?.full_name}</span>
            <span className="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-[10px] font-extrabold uppercase tracking-wide border border-blue-100 shadow-sm">Patient</span>
          </div>
          <button onClick={handleLogout} className="flex items-center gap-2 px-4 py-2 bg-white border border-rose-100 text-rose-600 font-bold text-sm rounded-xl hover:bg-rose-50 hover:border-rose-200 shadow-sm transition-all transform hover:-translate-y-0.5">
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
            Logout
          </button>
        </div>
      </nav>

      {/* Tab Nav */}
      <div className="flex justify-center mt-6 z-10 relative">
        <div className="bg-white/60 backdrop-blur-md p-1.5 rounded-2xl shadow-sm border border-white/80 inline-flex">
          <button
            onClick={() => setActiveTab('home')}
            className={`flex items-center gap-2 px-6 py-2.5 rounded-xl font-bold text-sm transition-all duration-300 ${activeTab === 'home' ? 'bg-white text-blue-700 shadow-sm border border-slate-100' : 'text-slate-500 hover:text-slate-800'}`}
          >
            <span className="text-lg">🏠</span> Home
          </button>
          <button
            onClick={() => setActiveTab('centers')}
            className={`flex items-center gap-2 px-6 py-2.5 rounded-xl font-bold text-sm transition-all duration-300 ${activeTab === 'centers' ? 'bg-white text-blue-700 shadow-sm border border-slate-100' : 'text-slate-500 hover:text-slate-800'}`}
          >
            <span className="text-lg">🏥</span> Medical Centers
          </button>
          <button
            onClick={() => setActiveTab('profile')}
            className={`flex items-center gap-2 px-6 py-2.5 rounded-xl font-bold text-sm transition-all duration-300 ${activeTab === 'profile' ? 'bg-white text-amber-600 shadow-sm border border-slate-100' : 'text-slate-500 hover:text-slate-800'}`}
          >
            <span className="text-lg">👤</span> Profile
          </button>
        </div>
      </div>

      <main className="max-w-[1280px] mx-auto px-6 py-10 relative z-10">

        {/* ── HOME TAB ── */}
        {activeTab === 'home' && (
          <div className="animate-fade-in-up">
            <div className="mb-12 text-center max-w-2xl mx-auto">
              <h1 className="text-4xl md:text-5xl font-black text-slate-900 tracking-tight mb-4">Hello, {user?.full_name?.split(' ')[0]}!</h1>
              <p className="text-lg text-slate-500 font-medium">Use the dashboard to find top-rated medical centers in Kandy and book appointments effortlessly.</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              {[
                { icon: '🏥', title: 'Find Clinics', desc: 'Discover verified OPD centers near you in Kandy', action: () => setActiveTab('centers'), color: 'from-blue-500 to-indigo-500' },
                { icon: '📅', title: 'My Appointments', desc: 'View and manage your upcoming bookings', soon: true, color: 'from-emerald-400 to-teal-500' },
                { icon: '💬', title: 'Online Consult', desc: 'Request a basic online consultation', soon: true, color: 'from-purple-500 to-fuchsia-500' },
                { icon: '👤', title: 'My Profile', desc: 'Update your account details and health info', action: () => setActiveTab('profile'), color: 'from-amber-400 to-orange-500' },
              ].map((card) => (
                <div key={card.title} onClick={card.action} className={`group bg-white/80 backdrop-blur-xl border border-white rounded-[24px] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all duration-300 ${card.action ? 'cursor-pointer hover:-translate-y-2 hover:shadow-[0_20px_40px_rgb(59,130,246,0.1)]' : 'opacity-80'}`}>
                  <div className={`w-16 h-16 rounded-[18px] bg-gradient-to-br ${card.color} flex items-center justify-center text-3xl mb-6 shadow-lg transform group-hover:scale-110 transition-transform`}>
                    {card.icon}
                  </div>
                  <h3 className="text-xl font-extrabold text-slate-900 mb-2">{card.title}</h3>
                  <p className="text-sm font-medium text-slate-500 leading-relaxed mb-6">{card.desc}</p>

                  {card.soon ? (
                    <span className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-500 rounded-lg text-xs font-bold uppercase tracking-wider">
                      Coming Soon
                    </span>
                  ) : (
                    <span className="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-50 text-blue-700 rounded-xl text-[13px] font-bold decoration-transparent group-hover:bg-blue-600 group-hover:text-white transition-colors">
                      Explore Centers &rarr;
                    </span>
                  )}
                </div>
              ))}
            </div>
          </div>
        )}

        {/* ── MEDICAL CENTERS TAB ── */}
        {activeTab === 'centers' && (
          <div className="animate-fade-in-up">

            {/* Elegant Header */}
            <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
              <div>
                <div className="inline-flex items-center gap-2 px-3 py-1 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full shadow-md shadow-blue-500/20 mb-4">
                  <span className="text-lg">📍</span>
                  <span className="text-xs font-extrabold text-white uppercase tracking-wider">Kandy</span>
                </div>
                <h1 className="text-4xl font-black text-slate-900 tracking-tight leading-tight mb-2">Medical Centers</h1>
                <p className="text-base text-slate-500 font-medium">Showing live doctor availability for OPD visits</p>
              </div>

              <div className="flex items-center bg-white/80 backdrop-blur-md rounded-2xl p-4 shadow-sm border border-white">
                <div className="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mr-4">
                  <span className="text-2xl font-black text-blue-600">{centers.filter(c => c.doctor_available).length}</span>
                </div>
                <div>
                  <div className="text-[10px] font-black uppercase text-slate-400 tracking-widest">Available</div>
                  <div className="text-sm font-bold text-slate-700">Doctors Now</div>
                </div>
              </div>
            </div>

            {/* Stunning Search Bar */}
            <div className="bg-white/90 backdrop-blur-2xl rounded-[24px] p-2 flex flex-col md:flex-row items-center gap-2 shadow-[0_8px_30px_rgb(0,0,0,0.06)] border border-white relative z-20 mb-10">
              <div className="flex-1 flex items-center w-full relative px-4 py-2">
                <svg className="w-6 h-6 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                  type="text"
                  placeholder="Search clinics by name, area, or service..."
                  className="w-full pl-4 pr-10 py-3 bg-transparent border-none outline-none text-slate-800 text-lg font-medium placeholder-slate-400 focus:ring-0"
                  value={searchQuery}
                  onChange={e => setSearchQuery(e.target.value)}
                />
                {searchQuery && (
                  <button className="absolute right-4 w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-800 transition-colors" onClick={() => setSearchQuery('')}>
                    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M6 18L18 6M6 6l12 12" /></svg>
                  </button>
                )}
              </div>

              <div className="w-full md:w-[1px] h-[1px] md:h-12 bg-slate-200/80 mx-2"></div>

              <div className="w-full md:w-auto flex items-center px-4 py-2">
                <div className="flex flex-col w-full md:w-40">
                  <label className="text-[10px] font-bold text-slate-400 tracking-wider uppercase mb-1">Clinic Type</label>
                  <select
                    value={filterType}
                    onChange={e => setFilterType(e.target.value)}
                    className="w-full bg-transparent border-none outline-none text-slate-800 text-base font-bold cursor-pointer focus:ring-0 appearance-none"
                    style={{ backgroundImage: `url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e")`, backgroundPosition: 'right 0 center', backgroundRepeat: 'no-repeat', backgroundSize: '1.5em 1.5em' }}
                  >
                    <option value="all">All Types</option>
                    <option value="govt">Government Hubs</option>
                    <option value="private">Private Clinics</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Grid */}
            {filtered.length > 0 ? (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                {filtered.map(center => (
                  <MedicalCenterCard
                    key={center.id}
                    center={center}
                    onViewMap={setSelectedCenter}
                  />
                ))}
              </div>
            ) : (
              <div className="text-center py-20 bg-white/50 backdrop-blur-md rounded-[32px] border border-white shadow-sm">
                <div className="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-6">
                  <span className="text-4xl">🔍</span>
                </div>
                <h3 className="text-2xl font-extrabold text-slate-800 mb-2">No centers found</h3>
                <p className="text-slate-500 font-medium mb-8">Try adjusting your search query or filters to find what you're looking for.</p>
                <button className="px-8 py-3 bg-slate-900 text-white font-bold rounded-xl hover:bg-black transition-colors shadow-lg" onClick={() => { setSearchQuery(''); setFilterType('all'); }}>
                  Clear all filters
                </button>
              </div>
            )}
          </div>
        )}

        {/* ── PROFILE TAB ── */}
        {activeTab === 'profile' && (
          <div className="animate-fade-in-up max-w-4xl mx-auto">
            <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
              <div>
                <div className="inline-flex items-center gap-2 px-3 py-1 bg-gradient-to-r from-amber-400 to-orange-500 rounded-full shadow-md shadow-orange-500/20 mb-4">
                  <span className="text-lg">👤</span>
                  <span className="text-xs font-extrabold text-white uppercase tracking-wider">Your Profile</span>
                </div>
                <h1 className="text-4xl font-black text-slate-900 tracking-tight leading-tight mb-2">Account Settings</h1>
                <p className="text-base text-slate-500 font-medium">Manage your personal information and health details</p>
              </div>
            </div>

            <div className="bg-white/80 backdrop-blur-2xl border border-white rounded-[32px] p-8 md:p-12 shadow-[0_20px_60px_rgb(0,0,0,0.05)] relative overflow-hidden">
              {/* Decorative elements */}
              <div className="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-amber-200/40 to-orange-300/40 rounded-full blur-[60px] pointer-events-none"></div>
              
              <div className="flex flex-col md:flex-row gap-12 relative z-10">
                {/* Avatar Section */}
                <div className="flex flex-col items-center shrink-0 w-full md:w-auto">
                  <div className="w-32 h-32 rounded-[32px] bg-gradient-to-br from-amber-100 to-orange-100 p-1 shadow-lg relative group cursor-pointer mb-6 transform transition-all hover:scale-105">
                    <div className="w-full h-full bg-white rounded-[28px] border-4 border-white flex items-center justify-center overflow-hidden relative">
                      <span className="text-5xl">👤</span>
                      <div className="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                      </div>
                    </div>
                  </div>
                  <h3 className="text-xl font-extrabold text-slate-800 text-center">{user?.full_name}</h3>
                  <p className="text-sm font-medium text-slate-500 mb-4 text-center">{user?.email}</p>
                  <span className="px-3 py-1 bg-amber-50 text-amber-700 rounded-lg text-xs font-bold border border-amber-100 shadow-sm">Patient Account</span>
                </div>

                {/* Form Section */}
                <div className="flex-1 space-y-6">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-2">
                      <label className="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Full Name</label>
                      <input type="text" defaultValue={user?.full_name} className="w-full px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:bg-white transition-all shadow-sm" />
                    </div>
                    <div className="space-y-2">
                      <label className="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Phone Number</label>
                      <input type="tel" defaultValue={user?.phone || ''} placeholder="+94 7X XXX XXXX" className="w-full px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:bg-white transition-all shadow-sm" />
                    </div>
                    <div className="space-y-2">
                      <label className="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Date of Birth</label>
                      <input type="date" className="w-full px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:bg-white transition-all shadow-sm" />
                    </div>
                    <div className="space-y-2">
                      <label className="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Blood Group</label>
                      <div className="relative">
                        <select className="w-full px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:bg-white transition-all appearance-none cursor-pointer shadow-sm">
                          <option value="">Select Blood Group</option>
                          <option value="A+">A+</option>
                          <option value="A-">A-</option>
                          <option value="B+">B+</option>
                          <option value="B-">B-</option>
                          <option value="O+">O+</option>
                          <option value="O-">O-</option>
                          <option value="AB+">AB+</option>
                          <option value="AB-">AB-</option>
                        </select>
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                          <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div className="space-y-2">
                    <label className="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Allergies / Special Notes</label>
                    <textarea rows="3" placeholder="List any allergies, medications, or specific health conditions..." className="w-full px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-2xl text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:bg-white transition-all resize-none shadow-sm"></textarea>
                  </div>

                  <div className="pt-6 border-t border-slate-100/60 flex justify-end gap-3">
                    <button className="px-6 py-3 rounded-xl font-bold text-slate-600 hover:bg-slate-100 transition-colors">Cancel</button>
                    <button className="px-8 py-3 rounded-xl font-bold text-white bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 shadow-[0_8px_20px_rgba(245,158,11,0.3)] hover:shadow-[0_12px_25px_rgba(245,158,11,0.4)] hover:-translate-y-0.5 transition-all outline-none">
                      Save Changes
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </main>

      {/* Global CSS for Animations */}
      <style>{`
        @keyframes fadeInUp {
          from { opacity: 0; transform: translateY(15px); }
          to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
          animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
      `}</style>
    </div>
  );
}
