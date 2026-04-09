from pydantic import BaseModel
from typing import List, Optional

class Coordinates(BaseModel):
    lat: float
    lng: float

class MedicalCenterBase(BaseModel):
    name: str
    type: str
    area: str
    address: str
    phone: str
    hours: str
    services: List[str]
    rating: float
    distance: str
    available: bool
    tag: str
    lat: float
    lng: float
    doctor_available: bool

class MedicalCenterCreate(MedicalCenterBase):
    pass

class MedicalCenterUpdate(BaseModel):
    doctor_available: Optional[bool] = None

class MedicalCenterResponse(MedicalCenterBase):
    id: int
    coords: Optional[Coordinates] = None

    class Config:
        from_attributes = True

    # Pydantic v2 validator to build coords from lat/lng
    def __init__(self, **data):
        super().__init__(**data)
        if hasattr(self, "lat") and hasattr(self, "lng"):
            self.coords = Coordinates(lat=self.lat, lng=self.lng)
