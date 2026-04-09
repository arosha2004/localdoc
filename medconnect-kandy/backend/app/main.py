from fastapi import FastAPI, Depends
from fastapi.middleware.cors import CORSMiddleware
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import text
from app.database import engine, Base, get_db


app = FastAPI(
    title="LocalDoc Connect API",
    version="1.0.0",
    description="Location-Based Medical Center Discovery & Appointment System - Kandy, Sri Lanka"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"], # Allow all origins for local network/mobile testing
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

from app.routers import auth, clinics

# Register routers
app.include_router(auth.router)
app.include_router(clinics.router)

@app.on_event("startup")
async def startup():
    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.create_all)

@app.get("/health")
async def health():
    return {"status": "ok", "project": "LocalDoc Connect - Kandy"}

@app.get("/health/db")
async def health_db(db: AsyncSession = Depends(get_db)):
    try:
        await db.execute(text("SELECT 1"))
        return {"database": "connected"}
    except Exception as e:
        return {"database": "error", "detail": str(e)}