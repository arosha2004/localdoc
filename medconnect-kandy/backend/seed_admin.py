"""
Seed script — creates the first System Administrator account.
Run once after the database tables have been created:

  cd backend
  .\venv\Scripts\python seed_admin.py
"""
import asyncio
from sqlalchemy import select
from app.database import engine, Base, AsyncSessionLocal
from app.models.user import User, UserRole
from app.core.security import get_password_hash

ADMIN_EMAIL    = "admin@localdoc.lk"
ADMIN_PASSWORD = "LocalDocAdmin2024!"  # Change before production!
ADMIN_NAME     = "System Administrator"


async def seed():
    # Create tables if not already present
    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.create_all)

    async with AsyncSessionLocal() as db:
        result = await db.execute(select(User).where(User.email == ADMIN_EMAIL))
        existing = result.scalar_one_or_none()

        if existing:
            print(f"✅  Admin already exists: {ADMIN_EMAIL}")
            return

        admin = User(
            full_name=ADMIN_NAME,
            email=ADMIN_EMAIL,
            hashed_password=get_password_hash(ADMIN_PASSWORD),
            role=UserRole.admin,
            is_active=True,
            is_verified=True,
        )
        db.add(admin)
        await db.commit()
        print(f"✅  Admin created successfully!")
        print(f"    Email:    {ADMIN_EMAIL}")
        print(f"    Password: {ADMIN_PASSWORD}")
        print(f"    ⚠️  Change the password immediately in production!")


if __name__ == "__main__":
    asyncio.run(seed())
