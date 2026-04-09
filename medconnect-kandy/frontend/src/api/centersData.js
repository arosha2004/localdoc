const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

// Helper to get centers
export const getCenters = async () => {
  try {
    const response = await fetch(`${API_URL}/clinics/`);
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const data = await response.json();
    return data;
  } catch (error) {
    console.error("Could not fetch clinics from API:", error);
    // fallback or empty array
    return [];
  }
};

// Helper to save centers - in this case just a PUT request for doctor availability
export const updateCenterDoctorAvailability = async (id, doctor_available) => {
  try {
    const response = await fetch(`${API_URL}/clinics/${id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ doctor_available }),
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const data = await response.json();
    return data;
  } catch (error) {
    console.error("Could not update clinic:", error);
    throw error;
  }
};
