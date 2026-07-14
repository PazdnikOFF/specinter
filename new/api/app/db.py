from psycopg_pool import AsyncConnectionPool
from psycopg.rows import dict_row
from .config import PG_DSN

pool = AsyncConnectionPool(PG_DSN, min_size=1, max_size=10, open=False,
                          kwargs={"row_factory": dict_row})


async def fetch(sql: str, params: tuple = ()):
    async with pool.connection() as conn:
        cur = await conn.execute(sql, params)
        return await cur.fetchall()


async def fetchone(sql: str, params: tuple = ()):
    async with pool.connection() as conn:
        cur = await conn.execute(sql, params)
        return await cur.fetchone()
