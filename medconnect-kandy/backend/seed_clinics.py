import asyncio
from app.database import engine, Base, AsyncSessionLocal
from app.models.clinic import MedicalCenter

INITIAL_CENTERS = [
  {
    "id": 2,
    "name": 'Nalanda Medical Centre',
    "type": 'Private Clinic',
    "area": 'Peradeniya',
    "address": 'No. 42 Peradeniya Road, Kandy',
    "phone": '+94 81 238 7890',
    "hours": '8:00 AM – 8:00 PM',
    "services": ['OPD', 'Gynaecology', 'Dental', 'Laboratory'],
    "rating": 4.7,
    "distance": '2.3 km',
    "available": True,
    "tag": 'private',
    "lat": 7.2683,
    "lng": 80.5966,
    "doctor_available": False,
  },
  {
    "id": 3,
    "name": 'Hemas Hospital Kandy',
    "type": 'Private Hospital',
    "area": 'Katugastota',
    "address": 'No. 289 Katugastota Road, Kandy',
    "phone": '+94 81 205 5150',
    "hours": '24 Hours',
    "services": ['Emergency', 'OPD', 'Radiology', 'Orthopaedics', 'ICU'],
    "rating": 4.8,
    "distance": '3.1 km',
    "available": True,
    "tag": 'private',
    "lat": 7.3116,
    "lng": 80.6278,
    "doctor_available": True,
  },
  {
    "id": 4,
    "name": 'Durdans Kandy Medical Centre',
    "type": 'Private Clinic',
    "area": 'Kandy City',
    "address": 'No. 100 Yatinuwara Veediya, Kandy',
    "phone": '+94 81 220 0050',
    "hours": '7:00 AM – 10:00 PM',
    "services": ['OPD', 'Cardiology', 'Neurology', 'Laboratory', 'Pharmacy'],
    "rating": 4.6,
    "distance": '1.2 km',
    "available": False,
    "tag": 'private',
    "lat": 7.2974,
    "lng": 80.6358,
    "doctor_available": False,
  },
  {
    "id": 5,
    "name": 'Suwasetha Medical Centre',
    "type": 'Private Clinic',
    "area": 'Ampitiya',
    "address": 'No. 15 Ampitiya Road, Kandy',
    "phone": '+94 81 222 6688',
    "hours": '8:30 AM – 6:00 PM',
    "services": ['OPD', 'Paediatrics', 'Dental', 'Physiotherapy'],
    "rating": 4.3,
    "distance": '4.0 km',
    "available": True,
    "tag": 'private',
    "lat": 7.2756,
    "lng": 80.6512,
    "doctor_available": True,
  },
  {
    "id": 7,
    "name": 'Asiri Kandy Medical Hub',
    "type": 'Private Hospital',
    "area": 'Dharmaraja Junction',
    "address": 'No. 67 D.S. Senanayake Veediya, Kandy',
    "phone": '+94 81 222 4545',
    "hours": '24 Hours',
    "services": ['Emergency', 'OPD', 'Dermatology', 'ENT', 'Oncology'],
    "rating": 4.9,
    "distance": '0.5 km',
    "available": True,
    "tag": 'private',
    "lat": 7.2958,
    "lng": 80.6368,
    "doctor_available": True,
  },
  {
    "id": 8,
    "name": 'Kandy Lifecare Clinic',
    "type": 'Private Clinic',
    "area": 'Getambe',
    "address": 'No. 22 Getambe Road, Kandy',
    "phone": '+94 81 238 9922',
    "hours": '9:00 AM – 7:00 PM',
    "services": ['OPD', 'Gynaecology', 'Laboratory', 'Pharmacy'],
    "rating": 4.4,
    "distance": '3.7 km',
    "available": False,
    "tag": 'private',
    "lat": 7.2823,
    "lng": 80.6189,
    "doctor_available": False,
  },
  {
    "id": 9,
    "name": 'Udawatte Medical Centre',
    "type": 'Private Clinic',
    "area": 'Udawatte',
    "address": 'No. 8 Udawatte Lane, Kandy',
    "phone": '+94 81 222 1133',
    "hours": '8:00 AM – 9:00 PM',
    "services": ['OPD', 'Dental', 'Orthopaedics', 'Physiotherapy'],
    "rating": 4.5,
    "distance": '2.0 km',
    "available": True,
    "tag": 'private',
    "lat": 7.2945,
    "lng": 80.6400,
    "doctor_available": True,
  },
  {
    "id": 11,
    "name": 'Mahiyawa Medical Centre',
    "type": 'Private Clinic',
    "area": 'Mahiyawa',
    "address": 'No. 36 Mahiyawa Road, Kandy',
    "phone": '+94 81 220 7755',
    "hours": '9:00 AM – 6:30 PM',
    "services": ['OPD', 'Laboratory', 'Eye Care', 'Pharmacy'],
    "rating": 4.1,
    "distance": '6.2 km',
    "available": True,
    "tag": 'private',
    "lat": 7.2640,
    "lng": 80.6550,
    "doctor_available": False,
  }
]

async def seed():
    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.create_all)

    async with AsyncSessionLocal() as db:
        for center_data in INITIAL_CENTERS:
            # Check if it exists
            # We can just ignore the id and let it be auto-generated or use explicit ID
            center_id = center_data["id"]
            existing = await db.get(MedicalCenter, center_id)
            if existing:
                print(f"Medical center {center_data['name']} already exists.")
                continue

            center = MedicalCenter(
                id=center_id,
                name=center_data["name"],
                type=center_data["type"],
                area=center_data["area"],
                address=center_data["address"],
                phone=center_data["phone"],
                hours=center_data["hours"],
                services=center_data["services"],
                rating=center_data["rating"],
                distance=center_data["distance"],
                available=center_data["available"],
                tag=center_data["tag"],
                lat=center_data["lat"],
                lng=center_data["lng"],
                doctor_available=center_data["doctor_available"]
            )
            db.add(center)
        
        await db.commit()
        print("Successfully seeded medical centers!")

if __name__ == "__main__":
    asyncio.run(seed())
