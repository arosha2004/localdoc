from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.future import select
from typing import List

from app.database import get_db
from app.models.clinic import MedicalCenter
from app.schemas.clinic import MedicalCenterResponse, MedicalCenterUpdate

router = APIRouter(prefix="/clinics", tags=["Clinics"])

@router.get("/", response_model=List[MedicalCenterResponse])
async def get_clinics(db: AsyncSession = Depends(get_db)):
    result = await db.execute(select(MedicalCenter).order_by(MedicalCenter.id))
    clinics = result.scalars().all()
    
    # We populate the virtual 'coords' field using schema logic
    response = []
    for c in clinics:
        # Pydantic v2 validation might require a dict or we rely on the schema
        c_dict = {
            "id": c.id,
            "name": c.name,
            "type": c.type,
            "area": c.area,
            "address": c.address,
            "phone": c.phone,
            "hours": c.hours,
            "services": c.services,
            "rating": c.rating,
            "distance": c.distance,
            "available": c.available,
            "tag": c.tag,
            "lat": c.lat,
            "lng": c.lng,
            "doctor_available": c.doctor_available,
            "coords": {"lat": c.lat, "lng": c.lng}
        }
        response.append(c_dict)
        
    return response

@router.put("/{clinic_id}", response_model=MedicalCenterResponse)
async def update_clinic(clinic_id: int, clinic_in: MedicalCenterUpdate, db: AsyncSession = Depends(get_db)):
    result = await db.execute(select(MedicalCenter).where(MedicalCenter.id == clinic_id))
    clinic = result.scalar_one_or_none()
    if not clinic:
        raise HTTPException(status_code=404, detail="Clinic not found")
        
    if clinic_in.doctor_available is not None:
        clinic.doctor_available = clinic_in.doctor_available
        
    await db.commit()
    await db.refresh(clinic)
    
    # Need to return dict matching schema with coords
    return {
        "id": clinic.id,
        "name": clinic.name,
        "type": clinic.type,
        "area": clinic.area,
        "address": clinic.address,
        "phone": clinic.phone,
        "hours": clinic.hours,
        "services": clinic.services,
        "rating": clinic.rating,
        "distance": clinic.distance,
        "available": clinic.available,
        "tag": clinic.tag,
        "lat": clinic.lat,
        "lng": clinic.lng,
        "doctor_available": clinic.doctor_available,
        "coords": {"lat": clinic.lat, "lng": clinic.lng}
    }
