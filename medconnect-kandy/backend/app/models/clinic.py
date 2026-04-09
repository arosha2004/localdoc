from sqlalchemy import Column, Integer, String, Boolean, Float, JSON
from app.database import Base

class MedicalCenter(Base):
    __tablename__ = "medical_centers"

    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(255), nullable=False)
    type = Column(String(100), nullable=False)
    area = Column(String(100), nullable=False)
    address = Column(String(255), nullable=False)
    phone = Column(String(50), nullable=False)
    hours = Column(String(100), nullable=False)
    services = Column(JSON, nullable=False)  # Store list as JSON
    rating = Column(Float, nullable=False)
    distance = Column(String(50), nullable=False)
    available = Column(Boolean, default=True)
    tag = Column(String(50), nullable=False)
    lat = Column(Float, nullable=False)
    lng = Column(Float, nullable=False)
    doctor_available = Column(Boolean, default=False)