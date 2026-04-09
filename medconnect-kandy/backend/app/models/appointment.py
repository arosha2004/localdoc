from sqlalchemy import Column, String, DateTime, Integer, Enum, ForeignKey, Boolean
from sqlalchemy.dialects.postgresql import UUID
from app.database import Base
import uuid, enum

class BookingType(str, enum.Enum):
    slot = "slot"
    token = "token"

class BookingStatus(str, enum.Enum):
    pending = "pending"
    confirmed = "confirmed"
    served = "served"
    cancelled = "cancelled"
    no_show = "no_show"

class Session(Base):
    __tablename__ = "sessions"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    clinic_id = Column(Integer, ForeignKey("medical_centers.id"))
    doctor_name = Column(String)
    date = Column(DateTime)
    capacity = Column(Integer)
    booking_type = Column(Enum(BookingType))
    is_active = Column(Boolean, default=True)
    delay_minutes = Column(Integer, default=0)

class Booking(Base):
    __tablename__ = "bookings"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    session_id = Column(UUID(as_uuid=True), ForeignKey("sessions.id"))
    patient_id = Column(Integer, ForeignKey("users.id"))
    token_number = Column(Integer, nullable=True)
    status = Column(Enum(BookingStatus), default=BookingStatus.pending)